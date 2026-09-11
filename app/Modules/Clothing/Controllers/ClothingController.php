<?php

declare(strict_types=1);

namespace Premisely\Modules\Clothing\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;

final class ClothingController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $items = Connection::fetchAll(
            'SELECT c.*, m.display_name AS owner_name, s.name AS space_name
             FROM clothing_items c
             LEFT JOIN property_members m ON m.id = c.owner_member_id
             LEFT JOIN spaces s ON s.id = c.space_id
             WHERE c.property_id = :p AND c.archived_at IS NULL
             ORDER BY c.name',
            ['p' => $pid]
        );
        $this->view('clothing/index', [
            'title' => 'Ropa',
            'property' => PropertyContext::property(),
            'items' => $items,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/clothing');
        }
        $pid = PropertyContext::propertyId();
        $this->view('clothing/create', [
            'title' => 'Nueva prenda',
            'property' => PropertyContext::property(),
            'members' => Connection::fetchAll(
                'SELECT id, display_name FROM property_members WHERE property_id = :p AND status = \'active\' ORDER BY display_name',
                ['p' => $pid]
            ),
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
                ['p' => $pid]
            ),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/clothing');
        }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            flash('error', 'El nombre es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/clothing/create');
        }
        Connection::query(
            'INSERT INTO clothing_items
             (public_id, property_id, owner_member_id, space_id, name, category, season, size_label, `condition`, status, notes, created_at)
             VALUES (:pid, :prop, :owner, :space, :name, :cat, :season, :size, :cond, :status, :notes, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'owner' => $request->input('owner_member_id') ?: null,
                'space' => $request->input('space_id') ?: null,
                'name' => $name,
                'cat' => $request->input('category', 'prenda'),
                'season' => $request->input('season'),
                'size' => $request->input('size_label'),
                'cond' => $request->input('condition', 'bueno'),
                'status' => $request->input('status', 'disponible'),
                'notes' => $request->input('notes'),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'clothing_item', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Prenda registrada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/clothing');
    }

    public function archive(Request $request, array $params): never
    {
        Connection::query(
            'UPDATE clothing_items SET archived_at = NOW() WHERE public_id = :pid AND property_id = :prop',
            ['pid' => $params['item'] ?? '', 'prop' => PropertyContext::propertyId()]
        );
        flash('success', 'Prenda archivada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/clothing');
    }
}
