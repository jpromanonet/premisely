<?php

declare(strict_types=1);

namespace Premisely\Modules\Dashboard\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Routines\Controllers\RoutineController;
use Premisely\Modules\Stock\Services\StockService;
use Premisely\Shared\ExpenseHelper;
use Premisely\Shared\PropertyContext;

final class DashboardController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $properties = Connection::fetchAll(
            'SELECT p.*, pm.role
             FROM properties p
             INNER JOIN property_members pm ON pm.property_id = p.id
             WHERE pm.user_id = :uid AND pm.status = \'active\' AND p.archived_at IS NULL
             ORDER BY p.name',
            ['uid' => Auth::id()]
        );

        $propertyIds = array_map(static fn ($p) => (int) $p['id'], $properties);
        $openTasks = 0;
        $lowStock = 0;
        $upcomingRoutines = 0;

        if ($propertyIds !== []) {
            $in = implode(',', $propertyIds);
            $openTasks = (int) Connection::fetchColumn(
                "SELECT COUNT(*) FROM tasks WHERE property_id IN ($in) AND archived_at IS NULL AND status IN ('pending','in_progress')"
            );
            $lowStock = (int) Connection::fetchColumn(
                "SELECT COUNT(*) FROM stock_items
                 WHERE property_id IN ($in) AND archived_at IS NULL AND quantity <= 0"
            );
            $upcomingRoutines = (int) Connection::fetchColumn(
                "SELECT COUNT(*) FROM routines
                 WHERE property_id IN ($in) AND archived_at IS NULL AND is_active = 1
                   AND next_due_at IS NOT NULL AND next_due_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)"
            );
        }

        $this->view('dashboard/index', [
            'title' => 'Panel',
            'properties' => $properties,
            'stats' => [
                'properties' => count($properties),
                'open_tasks' => $openTasks,
                'low_stock' => $lowStock,
                'upcoming_routines' => $upcomingRoutines,
            ],
            'user' => Auth::user(),
        ]);
    }

    public function property(Request $request, array $params): never
    {
        $property = PropertyContext::property();
        $pid = PropertyContext::propertyId();
        $today = date('Y-m-d');
        $board = RoutineController::forDay($pid, $today);

        $tasksToday = (int) Connection::fetchColumn(
            'SELECT COUNT(*) FROM tasks
             WHERE property_id = :pid AND archived_at IS NULL
               AND status IN (\'pending\', \'in_progress\')
               AND (due_date = :day OR due_date IS NULL)',
            ['pid' => $pid, 'day' => $today]
        );
        \Premisely\Modules\Repairs\Controllers\RepairController::archiveCopiesOfTasks($pid);
        $openRepairs = (int) Connection::fetchColumn(
            'SELECT COUNT(*) FROM repair_records
             WHERE property_id = :pid AND archived_at IS NULL
               AND status IN (\'pendiente\', \'en_curso\', \'open\', \'in_progress\')',
            ['pid' => $pid]
        );
        $weekday = (int) (new \DateTimeImmutable($today))->format('N');
        $meals = Connection::fetchAll(
            'SELECT slot, COUNT(*) AS n FROM weekly_menu_items
             WHERE property_id = :pid AND weekday = :weekday
             GROUP BY slot',
            ['pid' => $pid, 'weekday' => $weekday]
        );
        $filled = [];
        foreach ($meals as $meal) {
            if ((int) $meal['n'] > 0) {
                $filled[] = (string) $meal['slot'];
            }
        }

        (new StockService())->syncAllWithShopping($pid);

        $monthKey = date('Y-m');
        $monthStart = $monthKey . '-01';
        $monthEnd = (new \DateTimeImmutable($monthStart))->modify('last day of this month')->format('Y-m-d');
        $monthStartTs = $monthStart . ' 00:00:00';
        $monthEndTs = (new \DateTimeImmutable($monthEnd))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

        $monthSpend = Connection::fetchAll(
            'SELECT public_id, title, amount, currency, completed_at, week_start
             FROM week_spend_items
             WHERE property_id = :pid AND archived_at IS NULL
               AND week_start <= :month_end
               AND DATE_ADD(week_start, INTERVAL 6 DAY) >= :month_start
             ORDER BY week_start, id',
            ['pid' => $pid, 'month_start' => $monthStart, 'month_end' => $monthEnd]
        );
        $monthExpenses = 0.0;
        foreach ($monthSpend as $row) {
            $monthExpenses += (float) $row['amount'];
        }

        $this->view('dashboard/property', [
            'title' => 'Panel · ' . $property['name'],
            'property' => $property,
            'today' => $today,
            'habitsDone' => $board['done'],
            'habitsTotal' => $board['total'],
            'todo' => $tasksToday + $openRepairs,
            'mealsFilled' => $filled,
            'shopping' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM shopping_list_items si
                 INNER JOIN shopping_lists sl ON sl.id = si.shopping_list_id
                 WHERE sl.property_id = :pid AND sl.status = \'active\' AND si.status = \'pending\'',
                ['pid' => $pid]
            ),
            'lowStock' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM stock_items
                 WHERE property_id = :pid AND archived_at IS NULL AND quantity <= 0',
                ['pid' => $pid]
            ),
            'inventory' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM inventory_items
                 WHERE property_id = :pid AND archived_at IS NULL',
                ['pid' => $pid]
            ),
            'stockCount' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM stock_items WHERE property_id = :pid AND archived_at IS NULL',
                ['pid' => $pid]
            ),
            'monthExpenses' => $monthExpenses,
            'monthSpend' => $monthSpend,
            'weeksInMonth' => self::weeksInMonth($monthStart),
            'monthHabitsDone' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM routine_executions e
                 INNER JOIN routines r ON r.id = e.routine_id
                 WHERE e.property_id = :pid AND r.archived_at IS NULL
                   AND e.executed_at >= :a AND e.executed_at < :b',
                ['pid' => $pid, 'a' => $monthStartTs, 'b' => $monthEndTs]
            ),
            'monthTasksDone' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM tasks
                 WHERE property_id = :pid AND archived_at IS NULL AND status = \'done\'
                   AND completed_at IS NOT NULL
                   AND completed_at >= :a AND completed_at < :b
                   AND (kind IS NULL OR kind NOT IN (\'reparacion\', \'medico\'))',
                ['pid' => $pid, 'a' => $monthStartTs, 'b' => $monthEndTs]
            ),
            'monthMedicalDone' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM tasks
                 WHERE property_id = :pid AND archived_at IS NULL AND status = \'done\'
                   AND kind = \'medico\'
                   AND completed_at IS NOT NULL
                   AND completed_at >= :a AND completed_at < :b',
                ['pid' => $pid, 'a' => $monthStartTs, 'b' => $monthEndTs]
            ),
            'monthRepairsDone' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM tasks
                 WHERE property_id = :pid AND archived_at IS NULL AND status = \'done\'
                   AND kind = \'reparacion\'
                   AND completed_at IS NOT NULL
                   AND completed_at >= :a AND completed_at < :b',
                ['pid' => $pid, 'a' => $monthStartTs, 'b' => $monthEndTs]
            ) + (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM repair_records
                 WHERE property_id = :pid AND archived_at IS NULL
                   AND status IN (\'hecho\', \'done\')
                   AND closed_at IS NOT NULL
                   AND closed_at >= :a AND closed_at <= :b',
                ['pid' => $pid, 'a' => $monthStart, 'b' => $monthEnd]
            ),
            'expenseTrend' => $this->weekSpendTrend($pid),
            'fixedExpenses' => ExpenseHelper::activeFixed($pid),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    private static function weeksInMonth(string $monthStart): int
    {
        $first = new \DateTimeImmutable($monthStart);
        $last = $first->modify('last day of this month');
        $start = $first->modify('monday this week');
        $end = $last->modify('monday this week');
        return (int) (($end->getTimestamp() - $start->getTimestamp()) / 604800) + 1;
    }

    /** @return list<array{month:string,total:float}> */
    private function weekSpendTrend(int $propertyId): array
    {
        $month = date('Y-m');
        $from = (new \DateTimeImmutable($month . '-01'))->modify('-5 months')->format('Y-m-d');
        $to = (new \DateTimeImmutable($month . '-01'))->modify('first day of next month')->format('Y-m-d');
        $rows = Connection::fetchAll(
            'SELECT DATE_FORMAT(week_start, \'%Y-%m\') AS ym, COALESCE(SUM(amount),0) AS total
             FROM week_spend_items
             WHERE property_id = :pid AND archived_at IS NULL
               AND week_start >= :from AND week_start < :to
             GROUP BY ym',
            ['pid' => $propertyId, 'from' => $from, 'to' => $to]
        );
        $byMonth = [];
        foreach ($rows as $row) {
            $byMonth[(string) $row['ym']] = (float) $row['total'];
        }
        $cursor = new \DateTimeImmutable($month . '-01');
        $cursor = $cursor->modify('-5 months');
        $trend = [];
        for ($i = 0; $i < 6; $i++) {
            $key = $cursor->format('Y-m');
            $trend[] = ['month' => $key, 'total' => $byMonth[$key] ?? 0.0];
            $cursor = $cursor->modify('+1 month');
        }

        return $trend;
    }
}
