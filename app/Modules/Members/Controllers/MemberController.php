<?php

declare(strict_types=1);

namespace Premisely\Modules\Members\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;

final class MemberController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $members = Connection::fetchAll(
            'SELECT * FROM property_members WHERE property_id = :p ORDER BY status ASC, display_name',
            ['p' => $pid]
        );
        $this->view('members/index', [
            'title' => 'Integrantes',
            'heading' => 'Integrantes',
            'property' => PropertyContext::property(),
            'members' => $members,
            'canManage' => PropertyContext::canManage(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
        }
        $this->view('members/create', [
            'title' => 'Agregar miembro',
            'property' => PropertyContext::property(),
            'canManage' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
        }
        $validator = Validator::make($request->all(), [
            'display_name' => 'required|max:120',
            'role' => 'required|in:owner,admin,member,collaborator,viewer',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members/create');
        }
        $data = $validator->validated();
        $email = trim((string) $request->input('email', ''));
        $userId = null;
        if ($email !== '') {
            $user = Connection::fetch('SELECT id FROM users WHERE email = :e', ['e' => strtolower($email)]);
            $userId = $user['id'] ?? null;
        }
        Connection::query(
            'INSERT INTO property_members (property_id, user_id, display_name, email, role, member_type, status, joined_at, created_at)
             VALUES (:pid, :uid, :name, :email, :role, :type, \'active\', NOW(), NOW())',
            [
                'pid' => PropertyContext::propertyId(),
                'uid' => $userId,
                'name' => $data['display_name'],
                'email' => $email !== '' ? strtolower($email) : null,
                'role' => $data['role'],
                'type' => $request->input('member_type', 'residente'),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'member', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Integrante agregado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
        }
        $id = (int) ($params['member'] ?? 0);
        Connection::query(
            'UPDATE property_members SET role = :role, status = :status, member_type = :type, notes = :notes, updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'role' => $request->input('role', 'member'),
                'status' => $request->input('status', 'active'),
                'type' => $request->input('member_type', 'residente'),
                'notes' => $request->input('notes'),
                'id' => $id,
                'pid' => PropertyContext::propertyId(),
            ]
        );
        flash('success', 'Integrante actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
    }

    public function leave(Request $request, array $params): never
    {
        $member = PropertyContext::member();
        Connection::query(
            'UPDATE property_members SET status = \'left\', left_at = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $member['id']]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'member', (int) $member['id'], 'left');
        flash('success', 'Dejaste la propiedad.');
        $this->redirect('/dashboard');
    }
}
