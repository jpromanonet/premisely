<?php

declare(strict_types=1);

namespace Premisely\Modules\Reports\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Stock\Services\StockService;
use Premisely\Shared\PropertyContext;

final class ReportController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $month = (string) ($request->query('month') ?: date('Y-m'));
        (new StockService())->syncAllWithShopping($pid);

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
                'SELECT COUNT(*) FROM stock_items
                 WHERE property_id = :p AND archived_at IS NULL
                   AND quantity <= 0',
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

        $trendRows = Connection::fetchAll(
            'SELECT DATE_FORMAT(spent_at, \'%Y-%m\') AS ym, COALESCE(SUM(amount),0) AS total
             FROM expenses
             WHERE property_id = :p AND archived_at IS NULL
               AND spent_at >= DATE_SUB(STR_TO_DATE(CONCAT(:m, \'-01\'), \'%Y-%m-%d\'), INTERVAL 5 MONTH)
               AND spent_at < DATE_ADD(STR_TO_DATE(CONCAT(:m2, \'-01\'), \'%Y-%m-%d\'), INTERVAL 1 MONTH)
             GROUP BY ym',
            ['p' => $pid, 'm' => $month, 'm2' => $month]
        );
        $byMonth = [];
        foreach ($trendRows as $row) {
            $byMonth[(string) $row['ym']] = (float) $row['total'];
        }
        $trend = [];
        $cursor = \DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01') ?: new \DateTimeImmutable('first day of this month');
        $cursor = $cursor->modify('-5 months');
        for ($i = 0; $i < 6; $i++) {
            $key = $cursor->format('Y-m');
            $trend[] = ['month' => $key, 'total' => $byMonth[$key] ?? 0.0];
            $cursor = $cursor->modify('+1 month');
        }

        $this->view('reports/index', [
            'title' => 'Reportes',
            'property' => PropertyContext::property(),
            'month' => $month,
            'inventory' => $inventory,
            'stock' => $stock,
            'expenses' => $expenses,
            'trend' => $trend,
            'tasksDone' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM tasks
                 WHERE property_id = :p AND archived_at IS NULL AND status = \'done\'
                   AND completed_at IS NOT NULL AND DATE_FORMAT(completed_at, \'%Y-%m\') = :m',
                ['p' => $pid, 'm' => $month]
            ),
            'habitsDone' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM routine_executions e
                 INNER JOIN routines r ON r.id = e.routine_id
                 WHERE e.property_id = :p AND r.archived_at IS NULL
                   AND DATE_FORMAT(e.executed_at, \'%Y-%m\') = :m',
                ['p' => $pid, 'm' => $month]
            ),
            'shopping' => (int) Connection::fetchColumn(
                'SELECT COUNT(*) FROM shopping_list_items si
                 INNER JOIN shopping_lists sl ON sl.id = si.shopping_list_id
                 WHERE sl.property_id = :p AND sl.status = \'active\' AND si.status = \'pending\'',
                ['p' => $pid]
            ),
        ]);
    }
}
