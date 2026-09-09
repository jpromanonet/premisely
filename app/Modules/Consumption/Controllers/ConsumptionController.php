<?php

declare(strict_types=1);

namespace Premisely\Modules\Consumption\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class ConsumptionController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $days = (int) $request->query('days', '30');
        if (!in_array($days, [30, 90], true)) {
            $days = 30;
        }

        $rows = Connection::fetchAll(
            "SELECT s.id, s.public_id, s.name, s.unit, s.quantity, s.minimum_quantity,
                    COALESCE(SUM(CASE WHEN m.type = 'consume' THEN m.quantity ELSE 0 END), 0) AS consumed,
                    COUNT(CASE WHEN m.type = 'consume' THEN 1 END) AS events
             FROM stock_items s
             LEFT JOIN stock_movements m ON m.stock_item_id = s.id
               AND m.property_id = s.property_id
               AND m.created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
             WHERE s.property_id = :p AND s.archived_at IS NULL
             GROUP BY s.id, s.public_id, s.name, s.unit, s.quantity, s.minimum_quantity
             HAVING consumed > 0 OR s.quantity <= s.minimum_quantity
             ORDER BY consumed DESC
             LIMIT 100",
            ['p' => $pid]
        );

        foreach ($rows as &$row) {
            $consumed = (float) $row['consumed'];
            $daily = $days > 0 ? $consumed / $days : 0;
            $row['daily_avg'] = round($daily, 3);
            $qty = (float) $row['quantity'];
            $min = (float) $row['minimum_quantity'];
            $row['days_to_min'] = $daily > 0 ? (int) floor(max(0, $qty - $min) / $daily) : null;
        }
        unset($row);

        $this->view('consumption/index', [
            'title' => 'Análisis de consumo',
            'property' => PropertyContext::property(),
            'rows' => $rows,
            'days' => $days,
        ]);
    }
}
