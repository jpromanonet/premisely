<?php

declare(strict_types=1);

namespace Premisely\Modules\Moves\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Moves\Services\MoveService;
use Premisely\Shared\PropertyContext;
use Throwable;

final class MoveController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $moves = Connection::fetchAll(
            'SELECT m.*, dp.name AS destination_name
             FROM moves m
             LEFT JOIN properties dp ON dp.id = m.destination_property_id
             WHERE m.property_id = :p AND m.archived_at IS NULL
             ORDER BY m.created_at DESC',
            ['p' => $pid]
        );
        $this->view('moves/index', [
            'title' => 'Mudanzas',
            'property' => PropertyContext::property(),
            'moves' => $moves,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves');
        }
        $this->view('moves/create', [
            'title' => 'Nueva mudanza',
            'property' => PropertyContext::property(),
            'destinations' => $this->otherProperties(),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves');
        }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            flash('error', 'El nombre es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves/create');
        }
        $move = (new MoveService())->create(PropertyContext::propertyId(), [
            'name' => $name,
            'destination_property_id' => $request->input('destination_property_id'),
            'planned_at' => $request->input('planned_at'),
            'notes' => $request->input('notes'),
        ]);
        flash('success', 'Mudanza creada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves/' . $move['public_id']);
    }

    public function show(Request $request, array $params): never
    {
        $move = $this->findMove($params['move'] ?? '');
        $pid = PropertyContext::propertyId();
        $items = Connection::fetchAll(
            'SELECT mi.*, i.name AS item_name, i.public_id AS item_public_id, b.code AS box_code, s.name AS space_name
             FROM move_items mi
             INNER JOIN inventory_items i ON i.id = mi.inventory_item_id
             LEFT JOIN storage_boxes b ON b.id = mi.box_id
             LEFT JOIN spaces s ON s.id = mi.target_space_id
             WHERE mi.move_id = :m ORDER BY mi.id',
            ['m' => $move['id']]
        );
        $inventory = Connection::fetchAll(
            'SELECT id, public_id, name FROM inventory_items
             WHERE property_id = :p AND archived_at IS NULL
               AND id NOT IN (SELECT inventory_item_id FROM move_items WHERE move_id = :m)
             ORDER BY name LIMIT 200',
            ['p' => $pid, 'm' => $move['id']]
        );
        $this->view('moves/show', [
            'title' => $move['name'],
            'property' => PropertyContext::property(),
            'move' => $move,
            'items' => $items,
            'inventory' => $inventory,
            'boxes' => Connection::fetchAll(
                'SELECT id, code, name FROM storage_boxes WHERE property_id = :p AND archived_at IS NULL ORDER BY code',
                ['p' => $pid]
            ),
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
                ['p' => $pid]
            ),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function addItem(Request $request, array $params): never
    {
        $move = $this->findMove($params['move'] ?? '');
        if (!PropertyContext::canEdit() || ($move['status'] ?? '') === 'completed') {
            flash('error', 'No se puede modificar.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves/' . $move['public_id']);
        }
        try {
            (new MoveService())->addItem(
                PropertyContext::propertyId(),
                (int) $move['id'],
                (int) $request->input('inventory_item_id'),
                (string) $request->input('disposition', 'transfer'),
                $request->input('box_id') !== '' && $request->input('box_id') !== null ? (int) $request->input('box_id') : null,
                $request->input('target_space_id') !== '' && $request->input('target_space_id') !== null ? (int) $request->input('target_space_id') : null,
                $request->input('notes') !== null ? (string) $request->input('notes') : null
            );
            flash('success', 'Objeto agregado a la mudanza.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves/' . $move['public_id']);
    }

    public function apply(Request $request, array $params): never
    {
        $move = $this->findMove($params['move'] ?? '');
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves/' . $move['public_id']);
        }
        try {
            $n = (new MoveService())->apply(PropertyContext::propertyId(), $move);
            flash('success', "Mudanza aplicada ({$n} objetos).");
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves/' . $move['public_id']);
    }

    /** @return array<string, mixed> */
    private function findMove(string $key): array
    {
        $move = Connection::fetch(
            'SELECT * FROM moves WHERE property_id = :p AND (public_id = :k OR id = :id) AND archived_at IS NULL LIMIT 1',
            [
                'p' => PropertyContext::propertyId(),
                'k' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        if (!$move) {
            flash('error', 'Mudanza no encontrada.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/moves');
        }
        return $move;
    }

    /** @return list<array<string, mixed>> */
    private function otherProperties(): array
    {
        return Connection::fetchAll(
            'SELECT p.id, p.name FROM properties p
             INNER JOIN property_members pm ON pm.property_id = p.id
             WHERE pm.user_id = :uid AND pm.status = \'active\' AND p.archived_at IS NULL AND p.id != :cur
             ORDER BY p.name',
            ['uid' => Auth::id(), 'cur' => PropertyContext::propertyId()]
        );
    }
}
