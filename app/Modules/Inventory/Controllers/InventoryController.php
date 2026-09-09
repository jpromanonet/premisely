<?php

declare(strict_types=1);

namespace Premisely\Modules\Inventory\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Inventory\Services\InventoryService;
use Premisely\Shared\PropertyContext;

final class InventoryController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $items = Connection::fetchAll(
            'SELECT i.*, s.name AS space_name, c.name AS category_name
             FROM inventory_items i
             LEFT JOIN spaces s ON s.id = i.space_id
             LEFT JOIN inventory_categories c ON c.id = i.category_id
             WHERE i.property_id = :p AND i.archived_at IS NULL
             ORDER BY i.name',
            ['p' => $pid]
        );
        $value = (float) Connection::fetchColumn(
            'SELECT COALESCE(SUM(estimated_value),0) FROM inventory_items WHERE property_id = :p AND archived_at IS NULL',
            ['p' => $pid]
        );
        $this->view('inventory/index', [
            'title' => 'Inventario',
            'heading' => 'Inventario',
            'property' => PropertyContext::property(),
            'items' => $items,
            'totalValue' => $value,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        $this->form(null);
    }

    public function store(Request $request, array $params): never
    {
        $validator = Validator::make($request->all(), ['name' => 'required|max:180']);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory/create');
        }
        $item = (new InventoryService())->create(PropertyContext::propertyId(), $request->all());
        flash('success', 'Objeto creado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory/' . $item['public_id']);
    }

    public function show(Request $request, array $params): never
    {
        $item = $this->find($params['item'] ?? '');
        $events = Connection::fetchAll(
            'SELECT * FROM inventory_item_events WHERE inventory_item_id = :id ORDER BY created_at DESC LIMIT 50',
            ['id' => $item['id']]
        );
        $warranties = [];
        try {
            $warranties = Connection::fetchAll(
                'SELECT * FROM inventory_item_warranties
                 WHERE inventory_item_id = :id AND archived_at IS NULL
                 ORDER BY ends_on IS NULL, ends_on DESC',
                ['id' => $item['id']]
            );
        } catch (\Throwable) {
            // V1.5 table may not exist yet during transition.
        }
        $this->view('inventory/show', [
            'title' => $item['name'],
            'heading' => $item['name'],
            'property' => PropertyContext::property(),
            'item' => $item,
            'events' => $events,
            'warranties' => $warranties,
            'spaces' => $this->spaces(),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function edit(Request $request, array $params): never
    {
        $this->form($this->find($params['item'] ?? ''));
    }

    public function update(Request $request, array $params): never
    {
        $item = $this->find($params['item'] ?? '');
        (new InventoryService())->update(PropertyContext::propertyId(), (int) $item['id'], $request->all());
        flash('success', 'Objeto actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory/' . $item['public_id']);
    }

    public function move(Request $request, array $params): never
    {
        $item = $this->find($params['item'] ?? '');
        $spaceId = $request->input('space_id') !== '' ? (int) $request->input('space_id') : null;
        (new InventoryService())->move(PropertyContext::propertyId(), (int) $item['id'], $spaceId);
        flash('success', 'Objeto movido.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory/' . $item['public_id']);
    }

    public function archive(Request $request, array $params): never
    {
        $item = $this->find($params['item'] ?? '');
        (new InventoryService())->archive(PropertyContext::propertyId(), (int) $item['id']);
        flash('success', 'Objeto archivado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory');
    }

    /** @param array<string, mixed>|null $item */
    private function form(?array $item): never
    {
        $this->view('inventory/form', [
            'title' => $item ? 'Editar objeto' : 'Nuevo objeto',
            'heading' => $item ? 'Editar objeto' : 'Nuevo objeto',
            'property' => PropertyContext::property(),
            'item' => $item,
            'spaces' => $this->spaces(),
            'members' => Connection::fetchAll(
                'SELECT * FROM property_members WHERE property_id = :p AND status = \'active\'',
                ['p' => PropertyContext::propertyId()]
            ),
            'categories' => Connection::fetchAll(
                'SELECT * FROM inventory_categories WHERE property_id IS NULL OR property_id = :p ORDER BY name',
                ['p' => PropertyContext::propertyId()]
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function find(string $key): array
    {
        $item = Connection::fetch(
            'SELECT * FROM inventory_items WHERE property_id = :p AND (public_id = :k OR id = :id) AND archived_at IS NULL LIMIT 1',
            ['p' => PropertyContext::propertyId(), 'k' => $key, 'id' => ctype_digit($key) ? (int) $key : 0]
        );
        if (!$item) {
            flash('error', 'Objeto no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory');
        }
        return $item;
    }

    /** @return list<array<string, mixed>> */
    private function spaces(): array
    {
        return Connection::fetchAll(
            'SELECT * FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
            ['p' => PropertyContext::propertyId()]
        );
    }
}
