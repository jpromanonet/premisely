<?php

declare(strict_types=1);

namespace Premisely\Modules\Dashboard\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Routines\Controllers\RoutineController;
use Premisely\Modules\Stock\Services\StockService;
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
        $meals = Connection::fetchAll(
            'SELECT slot, COUNT(*) AS n FROM meal_entries
             WHERE property_id = :pid AND meal_date = :day
             GROUP BY slot',
            ['pid' => $pid, 'day' => $today]
        );
        $filled = [];
        foreach ($meals as $meal) {
            if ((int) $meal['n'] > 0) {
                $filled[] = (string) $meal['slot'];
            }
        }

        (new StockService())->syncAllWithShopping($pid);

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
            'monthExpenses' => (float) (Connection::fetchColumn(
                'SELECT COALESCE(SUM(amount),0) FROM expenses
                 WHERE property_id = :pid AND archived_at IS NULL
                   AND spent_at >= DATE_FORMAT(CURDATE(), \'%Y-%m-01\')',
                ['pid' => $pid]
            ) ?: 0),
            'expenseCategories' => Connection::fetchAll(
                'SELECT COALESCE(c.name, \'Sin categoría\') AS category_name, COALESCE(SUM(e.amount),0) AS total
                 FROM expenses e
                 LEFT JOIN expense_categories c ON c.id = e.category_id
                 WHERE e.property_id = :pid AND e.archived_at IS NULL
                   AND spent_at >= DATE_FORMAT(CURDATE(), \'%Y-%m-01\')
                 GROUP BY category_name ORDER BY total DESC LIMIT 6',
                ['pid' => $pid]
            ),
            'expenseTrend' => $this->expenseTrend($pid),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    /** @return list<array{month:string,total:float}> */
    private function expenseTrend(int $propertyId): array
    {
        $month = date('Y-m');
        $rows = Connection::fetchAll(
            'SELECT DATE_FORMAT(spent_at, \'%Y-%m\') AS ym, COALESCE(SUM(amount),0) AS total
             FROM expenses
             WHERE property_id = :pid AND archived_at IS NULL
               AND spent_at >= DATE_SUB(STR_TO_DATE(CONCAT(:m, \'-01\'), \'%Y-%m-%d\'), INTERVAL 5 MONTH)
               AND spent_at < DATE_ADD(STR_TO_DATE(CONCAT(:m2, \'-01\'), \'%Y-%m-%d\'), INTERVAL 1 MONTH)
             GROUP BY ym',
            ['pid' => $propertyId, 'm' => $month, 'm2' => $month]
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
