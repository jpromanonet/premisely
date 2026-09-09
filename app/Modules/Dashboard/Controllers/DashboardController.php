<?php

declare(strict_types=1);

namespace Premisely\Modules\Dashboard\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
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
                 WHERE property_id IN ($in) AND archived_at IS NULL AND quantity <= minimum_quantity"
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

        $stats = [
            'open_tasks' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM tasks WHERE property_id = :pid AND archived_at IS NULL AND status IN (\'pending\',\'in_progress\')',
                ['pid' => $pid]
            ),
            'low_stock' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM stock_items WHERE property_id = :pid AND archived_at IS NULL AND quantity <= minimum_quantity',
                ['pid' => $pid]
            ),
            'shopping_pending' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM shopping_list_items si
                 INNER JOIN shopping_lists sl ON sl.id = si.shopping_list_id
                 WHERE sl.property_id = :pid AND sl.status = \'active\' AND si.status = \'pending\'',
                ['pid' => $pid]
            ),
            'open_repairs' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM repair_records WHERE property_id = :pid AND archived_at IS NULL AND status != \'closed\'',
                ['pid' => $pid]
            ),
            'month_expenses' => (float) (Connection::fetchColumn(
                'SELECT COALESCE(SUM(amount),0) FROM expenses
                 WHERE property_id = :pid AND archived_at IS NULL
                   AND spent_at >= DATE_FORMAT(CURDATE(), \'%Y-%m-01\')',
                ['pid' => $pid]
            ) ?: 0),
        ];

        $dueSoon = Connection::fetchAll(
            'SELECT title, next_due_at, category FROM routines
             WHERE property_id = :pid AND archived_at IS NULL AND is_active = 1
               AND next_due_at IS NOT NULL AND next_due_at <= DATE_ADD(NOW(), INTERVAL 14 DAY)
             ORDER BY next_due_at LIMIT 8',
            ['pid' => $pid]
        );

        $tasks = Connection::fetchAll(
            'SELECT public_id, title, status, due_date, priority FROM tasks
             WHERE property_id = :pid AND archived_at IS NULL AND status IN (\'pending\',\'in_progress\')
             ORDER BY due_date IS NULL, due_date LIMIT 8',
            ['pid' => $pid]
        );

        $this->view('dashboard/property', [
            'title' => 'Panel · ' . $property['name'],
            'property' => $property,
            'stats' => $stats,
            'dueSoon' => $dueSoon,
            'tasks' => $tasks,
            'activity' => ActivityLogger::forProperty($pid, 12),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }
}
