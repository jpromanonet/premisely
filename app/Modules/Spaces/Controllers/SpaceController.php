<?php

declare(strict_types=1);

namespace Premisely\Modules\Spaces\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;

final class SpaceController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $spaces = Connection::fetchAll(
            'SELECT s.*, p.name AS parent_name FROM spaces s
             LEFT JOIN spaces p ON p.id = s.parent_id
             WHERE s.property_id = :pid AND s.archived_at IS NULL
             ORDER BY s.name',
            ['pid' => $pid]
        );
        $this->view('spaces/index', [
            'title' => 'Espacios',
            'heading' => 'Espacios',
            'property' => PropertyContext::property(),
            'spaces' => $spaces,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/spaces');
        }
        $validator = Validator::make($request->all(), ['name' => 'required|max:120']);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/spaces');
        }
        Connection::query(
            'INSERT INTO spaces (public_id, property_id, parent_id, name, type, description, created_at)
             VALUES (:pid, :prop, :parent, :name, :type, :desc, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'parent' => $request->input('parent_id') ?: null,
                'name' => $request->input('name'),
                'type' => $request->input('type', 'otro'),
                'desc' => $request->input('description'),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'space', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Espacio creado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/spaces');
    }

    public function update(Request $request, array $params): never
    {
        $id = (int) ($params['space'] ?? 0);
        Connection::query(
            'UPDATE spaces SET name = :name, type = :type, parent_id = :parent, description = :desc, updated_at = NOW()
             WHERE id = :id AND property_id = :pid AND archived_at IS NULL',
            [
                'name' => $request->input('name'),
                'type' => $request->input('type', 'otro'),
                'parent' => $request->input('parent_id') ?: null,
                'desc' => $request->input('description'),
                'id' => $id,
                'pid' => PropertyContext::propertyId(),
            ]
        );
        flash('success', 'Espacio actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/spaces');
    }

    public function archive(Request $request, array $params): never
    {
        $id = (int) ($params['space'] ?? 0);
        Connection::query(
            'UPDATE spaces SET archived_at = NOW() WHERE id = :id AND property_id = :pid',
            ['id' => $id, 'pid' => PropertyContext::propertyId()]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'space', $id, 'archived');
        flash('success', 'Espacio archivado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/spaces');
    }
}
