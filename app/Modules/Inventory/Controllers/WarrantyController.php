<?php

declare(strict_types=1);

namespace Premisely\Modules\Inventory\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;

final class WarrantyController extends Controller
{
    public function store(Request $request, array $params): never
    {
        $item = Connection::fetch(
            'SELECT * FROM inventory_items WHERE property_id = :p AND public_id = :pid AND archived_at IS NULL LIMIT 1',
            ['p' => PropertyContext::propertyId(), 'pid' => $params['item'] ?? '']
        );
        if (!$item) {
            flash('error', 'Objeto no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory');
        }

        Connection::query(
            'INSERT INTO inventory_item_warranties
             (public_id, inventory_item_id, property_id, provider_name, manufacturer, starts_on, ends_on, conditions_text, notes, created_at)
             VALUES (:pid, :item, :prop, :provider, :mfr, :starts, :ends, :cond, :notes, NOW())',
            [
                'pid' => ulid(),
                'item' => $item['id'],
                'prop' => PropertyContext::propertyId(),
                'provider' => $request->input('provider_name'),
                'mfr' => $request->input('manufacturer'),
                'starts' => $request->input('starts_on') ?: null,
                'ends' => $request->input('ends_on') ?: null,
                'cond' => $request->input('conditions_text'),
                'notes' => $request->input('notes'),
            ]
        );

        if ($request->input('ends_on')) {
            Connection::query(
                'UPDATE inventory_items SET warranty_until = :ends, updated_at = NOW() WHERE id = :id',
                ['ends' => $request->input('ends_on'), 'id' => $item['id']]
            );
        }

        ActivityLogger::log(PropertyContext::propertyId(), 'inventory_item', (int) $item['id'], 'warranty.added');
        flash('success', 'Garantía registrada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory/' . $item['public_id']);
    }
}
