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
            'SELECT pm.*,
                    COALESCE(u.name, pm.display_name) AS display_name,
                    COALESCE(u.email, pm.email) AS email,
                    u.avatar_path,
                    u.public_id AS user_public_id
             FROM property_members pm
             LEFT JOIN users u ON u.id = pm.user_id
             WHERE pm.property_id = :p
             ORDER BY pm.status ASC, COALESCE(u.name, pm.display_name)',
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
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $back = '/properties/' . PropertyContext::property()['public_id'] . '/members/create';
        if ($password !== '' && $email === '') {
            flash('error', 'Para crear el usuario hace falta el email.');
            $this->redirect($back);
        }
        if ($password !== '' && strlen($password) < 8) {
            flash('error', 'La contraseña tiene que tener al menos 8 caracteres.');
            $this->redirect($back);
        }
        $userId = null;
        $createdLogin = false;
        if ($email !== '') {
            $user = Connection::fetch('SELECT id, name, email FROM users WHERE email = :e', ['e' => $email]);
            if ($user) {
                $userId = (int) $user['id'];
                $data['display_name'] = (string) $user['name'];
                $email = (string) $user['email'];
                $already = Connection::fetch(
                    'SELECT id FROM property_members WHERE property_id = :pid AND user_id = :uid LIMIT 1',
                    ['pid' => PropertyContext::propertyId(), 'uid' => $userId]
                );
                if ($already) {
                    flash('error', 'Esa persona ya está en la casa.');
                    $this->redirect($back);
                }
            } elseif ($password !== '') {
                $userId = $this->insertUser((string) $data['display_name'], $email, $password);
                $createdLogin = true;
            }
        }
        Connection::query(
            'INSERT INTO property_members (property_id, user_id, display_name, email, role, member_type, status, joined_at, created_at)
             VALUES (:pid, :uid, :name, :email, :role, :type, \'active\', NOW(), NOW())',
            [
                'pid' => PropertyContext::propertyId(),
                'uid' => $userId,
                'name' => $data['display_name'],
                'email' => $email !== '' ? $email : null,
                'role' => $data['role'],
                'type' => $request->input('member_type', 'residente'),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'member', (int) Connection::lastInsertId(), 'created');
        flash('success', $createdLogin
            ? 'Usuario creado. Entra con ' . $email . ' y la clave que pusiste.'
            : 'Integrante agregado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
    }

    public function access(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
        }
        $member = $this->findMember((int) ($params['member'] ?? 0));
        $this->view('members/access', [
            'title' => empty($member['user_id']) ? 'Crear usuario' : 'Cambiar clave',
            'property' => PropertyContext::property(),
            'member' => $member,
            'canManage' => true,
        ]);
    }

    public function saveAccess(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'No tenés permiso.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
        }
        $member = $this->findMember((int) ($params['member'] ?? 0));
        $list = '/properties/' . PropertyContext::property()['public_id'] . '/members';
        $back = $list . '/' . $member['id'] . '/access';
        $password = (string) $request->input('password', '');
        if (strlen($password) < 8) {
            flash('error', 'La contraseña tiene que tener al menos 8 caracteres.');
            $this->redirect($back);
        }
        $email = strtolower(trim((string) ($member['user_id'] ? $member['email'] : $request->input('email', ''))));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'El email no es válido.');
            $this->redirect($back);
        }

        if (!empty($member['user_id'])) {
            Connection::query(
                'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id',
                ['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int) $member['user_id']]
            );
            flash('success', 'Clave nueva. Entra con ' . $email . ' y la clave que pusiste.');
            $this->redirect($list);
        }

        $existing = Connection::fetch('SELECT id FROM users WHERE email = :e', ['e' => $email]);
        if ($existing) {
            $userId = (int) $existing['id'];
            $taken = Connection::fetch(
                'SELECT id FROM property_members WHERE property_id = :pid AND user_id = :uid AND id <> :id LIMIT 1',
                ['pid' => PropertyContext::propertyId(), 'uid' => $userId, 'id' => (int) $member['id']]
            );
            if ($taken) {
                flash('error', 'Ese email ya lo usa otra persona de la casa.');
                $this->redirect($back);
            }
            Connection::query(
                'UPDATE property_members SET user_id = :uid, email = :email, updated_at = NOW() WHERE id = :id AND property_id = :pid',
                ['uid' => $userId, 'email' => $email, 'id' => (int) $member['id'], 'pid' => PropertyContext::propertyId()]
            );
            flash('success', 'Quedó vinculado a la cuenta que ya existía. La clave no cambió.');
            $this->redirect($list);
        }

        $userId = $this->insertUser((string) $member['display_name'], $email, $password);
        Connection::query(
            'UPDATE property_members SET user_id = :uid, email = :email, updated_at = NOW() WHERE id = :id AND property_id = :pid',
            ['uid' => $userId, 'email' => $email, 'id' => (int) $member['id'], 'pid' => PropertyContext::propertyId()]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'member', (int) $member['id'], 'user_created');
        flash('success', 'Usuario creado. Entra con ' . $email . ' y la clave que pusiste.');
        $this->redirect($list);
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

    private function insertUser(string $name, string $email, string $password): int
    {
        Connection::query(
            'INSERT INTO users (public_id, name, email, password_hash, locale, timezone, preferred_currency, created_at)
             VALUES (:public_id, :name, :email, :hash, \'es\', \'America/Argentina/Buenos_Aires\', \'ARS\', NOW())',
            [
                'public_id' => ulid(),
                'name' => $name,
                'email' => $email,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );

        return (int) Connection::lastInsertId();
    }

    /** @return array<string, mixed> */
    private function findMember(int $id): array
    {
        $member = Connection::fetch(
            'SELECT pm.*, COALESCE(u.email, pm.email) AS email
             FROM property_members pm
             LEFT JOIN users u ON u.id = pm.user_id
             WHERE pm.id = :id AND pm.property_id = :pid
             LIMIT 1',
            ['id' => $id, 'pid' => PropertyContext::propertyId()]
        );
        if (!$member || ($member['role'] ?? '') === 'owner') {
            flash('error', 'No se puede cambiar ese integrante.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/members');
        }

        return $member;
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
