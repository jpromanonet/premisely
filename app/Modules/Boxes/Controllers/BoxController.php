<?php

declare(strict_types=1);

namespace Premisely\Modules\Boxes\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;

final class BoxController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $boxes = Connection::fetchAll(
            'SELECT b.*, s.name AS space_name,
                    (SELECT COUNT(*) FROM storage_box_items i WHERE i.box_id = b.id) AS item_count
             FROM storage_boxes b
             LEFT JOIN spaces s ON s.id = b.space_id
             WHERE b.property_id = :p AND b.archived_at IS NULL
             ORDER BY b.code',
            ['p' => $pid]
        );
        $this->view('boxes/index', [
            'title' => 'Cajas',
            'property' => PropertyContext::property(),
            'boxes' => $boxes,
            'spaces' => Connection::fetchAll(
                'SELECT * FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
                ['p' => $pid]
            ),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function store(Request $request, array $params): never
    {
        $code = trim((string) $request->input('code', ''));
        if ($code === '') {
            $code = 'C' . random_int(10, 99);
        }
        Connection::query(
            'INSERT INTO storage_boxes (public_id, property_id, space_id, code, name, description, created_at)
             VALUES (:pid, :prop, :space, :code, :name, :desc, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'space' => $request->input('space_id') ?: null,
                'code' => $code,
                'name' => $request->input('name'),
                'desc' => $request->input('description'),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'storage_box', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Caja creada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/boxes');
    }

    public function show(Request $request, array $params): never
    {
        $box = $this->find((string) ($params['box'] ?? ''));
        $items = Connection::fetchAll(
            'SELECT * FROM storage_box_items WHERE box_id = :id ORDER BY name',
            ['id' => $box['id']]
        );
        $this->view('boxes/show', [
            'title' => $box['name'],
            'property' => PropertyContext::property(),
            'box' => $box,
            'items' => $items,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function addItem(Request $request, array $params): never
    {
        $box = $this->find((string) ($params['box'] ?? ''));
        Connection::query(
            'INSERT INTO storage_box_items (box_id, property_id, inventory_item_id, name, quantity, notes, created_at)
             VALUES (:box, :prop, :inv, :name, :qty, :notes, NOW())',
            [
                'box' => $box['id'],
                'prop' => PropertyContext::propertyId(),
                'inv' => $request->input('inventory_item_id') ?: null,
                'name' => $request->input('name'),
                'qty' => $request->input('quantity', 1),
                'notes' => $request->input('notes'),
            ]
        );
        flash('success', 'Contenido agregado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/boxes/' . $box['public_id']);
    }

    public function archive(Request $request, array $params): never
    {
        $box = $this->find((string) ($params['box'] ?? ''));
        Connection::query(
            'UPDATE storage_boxes SET archived_at = NOW() WHERE id = :id AND property_id = :p',
            ['id' => $box['id'], 'p' => PropertyContext::propertyId()]
        );
        flash('success', 'Caja archivada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/boxes');
    }

    /** @return array<string, mixed> */
    private function find(string $key): array
    {
        $box = Connection::fetch(
            'SELECT b.*, s.name AS space_name FROM storage_boxes b
             LEFT JOIN spaces s ON s.id = b.space_id
             WHERE b.property_id = :p AND (b.public_id = :k OR b.id = :id) AND b.archived_at IS NULL LIMIT 1',
            ['p' => PropertyContext::propertyId(), 'k' => $key, 'id' => ctype_digit($key) ? (int) $key : 0]
        );
        if (!$box) {
            flash('error', 'Caja no encontrada.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/boxes');
        }
        return $box;
    }
}
