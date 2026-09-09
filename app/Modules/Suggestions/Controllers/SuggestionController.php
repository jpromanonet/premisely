<?php

declare(strict_types=1);

namespace Premisely\Modules\Suggestions\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class SuggestionController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $pub = (string) PropertyContext::property()['public_id'];
        $suggestions = [];

        $low = Connection::fetchAll(
            'SELECT public_id, name, quantity, unit, minimum_quantity FROM stock_items
             WHERE property_id = :p AND archived_at IS NULL AND quantity <= minimum_quantity
             ORDER BY name LIMIT 20',
            ['p' => $pid]
        );
        foreach ($low as $item) {
            $suggestions[] = [
                'type' => 'stock_low',
                'title' => 'Reponer ' . $item['name'],
                'detail' => $item['quantity'] . ' ' . $item['unit'] . ' (mín. ' . $item['minimum_quantity'] . ')',
                'href' => url('/properties/' . $pub . '/shopping'),
            ];
        }

        $warranties = Connection::fetchAll(
            'SELECT i.public_id, i.name, w.ends_on FROM inventory_item_warranties w
             INNER JOIN inventory_items i ON i.id = w.inventory_item_id
             WHERE w.property_id = :p AND w.archived_at IS NULL AND w.ends_on IS NOT NULL
               AND w.ends_on BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY w.ends_on LIMIT 20',
            ['p' => $pid]
        );
        foreach ($warranties as $w) {
            $suggestions[] = [
                'type' => 'warranty',
                'title' => 'Garantía: ' . $w['name'],
                'detail' => 'Vence ' . $w['ends_on'],
                'href' => url('/properties/' . $pub . '/inventory/' . $w['public_id']),
            ];
        }

        $maint = Connection::fetchAll(
            'SELECT public_id, title, next_due_at FROM maintenance_plans
             WHERE property_id = :p AND archived_at IS NULL AND next_due_at IS NOT NULL
               AND next_due_at <= CURDATE()
             ORDER BY next_due_at LIMIT 20',
            ['p' => $pid]
        );
        foreach ($maint as $m) {
            $suggestions[] = [
                'type' => 'maintenance',
                'title' => 'Mantenimiento vencido: ' . $m['title'],
                'detail' => 'Previsto ' . $m['next_due_at'],
                'href' => url('/properties/' . $pub . '/maintenance'),
            ];
        }

        $routines = Connection::fetchAll(
            'SELECT public_id, title, next_due_at FROM routines
             WHERE property_id = :p AND archived_at IS NULL AND is_active = 1
               AND next_due_at IS NOT NULL AND next_due_at < NOW()
             ORDER BY next_due_at LIMIT 20',
            ['p' => $pid]
        );
        foreach ($routines as $r) {
            $suggestions[] = [
                'type' => 'routine',
                'title' => 'Rutina vencida: ' . $r['title'],
                'detail' => (string) $r['next_due_at'],
                'href' => url('/properties/' . $pub . '/routines'),
            ];
        }

        $this->view('suggestions/index', [
            'title' => 'Sugerencias',
            'property' => PropertyContext::property(),
            'suggestions' => $suggestions,
        ]);
    }
}
