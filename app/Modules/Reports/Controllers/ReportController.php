<?php

declare(strict_types=1);

namespace Premisely\Modules\Reports\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class ReportController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $month = (string) ($request->query('month') ?: date('Y-m'));

        $inventory = [
            'count' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM inventory_items WHERE property_id = :p AND archived_at IS NULL',
                ['p' => $pid]
            ),
            'value' => (float) Connection::fetchColumn(
                'SELECT COALESCE(SUM(estimated_value),0) FROM inventory_items WHERE property_id = :p AND archived_at IS NULL',
                ['p' => $pid]
            ),
            'by_status' => Connection::fetchAll(
                'SELECT status, COUNT(*) AS total FROM inventory_items
                 WHERE property_id = :p AND archived_at IS NULL GROUP BY status ORDER BY total DESC',
                ['p' => $pid]
            ),
        ];

        $stock = [
            'count' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM stock_items WHERE property_id = :p AND archived_at IS NULL',
                ['p' => $pid]
            ),
            'low' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM stock_items WHERE property_id = :p AND archived_at IS NULL AND quantity <= minimum_quantity',
                ['p' => $pid]
            ),
        ];

        $expenses = [
            'month_total' => (float) Connection::fetchColumn(
                'SELECT COALESCE(SUM(amount),0) FROM expenses
                 WHERE property_id = :p AND archived_at IS NULL AND DATE_FORMAT(spent_at, \'%Y-%m\') = :m',
                ['p' => $pid, 'm' => $month]
            ),
            'by_category' => Connection::fetchAll(
                'SELECT COALESCE(c.name, \'Sin categoría\') AS category_name, COALESCE(SUM(e.amount),0) AS total
                 FROM expenses e
                 LEFT JOIN expense_categories c ON c.id = e.category_id
                 WHERE e.property_id = :p AND e.archived_at IS NULL AND DATE_FORMAT(e.spent_at, \'%Y-%m\') = :m
                 GROUP BY category_name ORDER BY total DESC',
                ['p' => $pid, 'm' => $month]
            ),
        ];

        $ops = [
            'tasks_done' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM tasks WHERE property_id = :p AND status = \'completed\'
                   AND DATE_FORMAT(completed_at, \'%Y-%m\') = :m',
                ['p' => $pid, 'm' => $month]
            ),
            'routines_overdue' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM routines WHERE property_id = :p AND is_active = 1 AND archived_at IS NULL
                   AND next_due_at IS NOT NULL AND next_due_at < NOW()',
                ['p' => $pid]
            ),
            'maintenance_upcoming' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM maintenance_plans WHERE property_id = :p AND archived_at IS NULL
                   AND next_due_at IS NOT NULL AND next_due_at <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)',
                ['p' => $pid]
            ),
            'warranties_expiring' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM inventory_item_warranties WHERE property_id = :p AND archived_at IS NULL
                   AND ends_on IS NOT NULL AND ends_on BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)',
                ['p' => $pid]
            ),
        ];

        $this->view('reports/index', [
            'title' => 'Reportes',
            'property' => PropertyContext::property(),
            'month' => $month,
            'inventory' => $inventory,
            'stock' => $stock,
            'expenses' => $expenses,
            'ops' => $ops,
        ]);
    }
}
