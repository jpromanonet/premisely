<?php

declare(strict_types=1);

namespace Premisely\Modules\Accounts\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Storage\LocalStorage;
use Premisely\Core\Validation\Validator;
use Throwable;

final class SettingsController extends Controller
{
    public function edit(Request $request, array $params): never
    {
        Auth::requireLogin();
        $user = Connection::fetch('SELECT * FROM users WHERE id = :id', ['id' => Auth::id()]);
        $this->view('settings/profile', [
            'title' => 'Configuración',
            'user' => $user,
            'form_action' => '/settings/profile',
            'password_action' => '/settings/password',
        ]);
    }

    public function update(Request $request, array $params): never
    {
        $this->updateProfile($request, $params);
    }

    /** Aliases for /settings/profile */
    public function profile(Request $request, array $params): never
    {
        $this->edit($request, $params);
    }

    public function updateProfile(Request $request, array $params): never
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $current = Connection::fetch('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
        if (!$current) {
            flash('error', 'Usuario no encontrado.');
            $this->redirect('/login');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'locale' => 'required|max:10',
            'timezone' => 'required|max:64',
            'preferred_currency' => 'required|max:3',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/settings/profile');
        }

        $email = strtolower(trim((string) $request->input('email')));
        $taken = Connection::fetch(
            'SELECT id FROM users WHERE email = :e AND id <> :id LIMIT 1',
            ['e' => $email, 'id' => $userId]
        );
        if ($taken) {
            flash('error', 'Ese email ya está en uso por otra cuenta.');
            $this->redirect('/settings/profile');
        }

        $avatarPath = $current['avatar_path'] ?? null;
        $storage = new LocalStorage((string) config('storage.root'));

        if ($request->input('remove_avatar')) {
            if (!empty($avatarPath)) {
                $storage->delete((string) $avatarPath);
            }
            $avatarPath = null;
        }

        $file = $request->file('avatar');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                flash('error', 'La foto debe ser JPG, PNG, GIF o WebP.');
                $this->redirect('/settings/profile');
            }
            try {
                $newPath = $storage->store($file, 'avatars/' . $current['public_id']);
                if (!empty($avatarPath)) {
                    $storage->delete((string) $avatarPath);
                }
                $avatarPath = $newPath;
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
                $this->redirect('/settings/profile');
            }
        }

        Connection::query(
            'UPDATE users SET name = :name, email = :email, locale = :locale, timezone = :timezone,
             preferred_currency = :currency, notify_email = :notify, avatar_path = :avatar, updated_at = NOW()
             WHERE id = :id',
            [
                'name' => trim((string) $request->input('name')),
                'email' => $email,
                'locale' => (string) $request->input('locale'),
                'timezone' => (string) $request->input('timezone'),
                'currency' => strtoupper((string) $request->input('preferred_currency')),
                'notify' => $request->input('notify_email') ? 1 : 0,
                'avatar' => $avatarPath,
                'id' => $userId,
            ]
        );

        // Keep linked membership rows in sync with the account profile.
        Connection::query(
            'UPDATE property_members SET display_name = :name, email = :email, updated_at = NOW()
             WHERE user_id = :id',
            [
                'name' => trim((string) $request->input('name')),
                'email' => $email,
                'id' => $userId,
            ]
        );

        $fresh = Connection::fetch('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
        if ($fresh) {
            Auth::setUser($fresh);
        }

        flash('success', 'Perfil actualizado.');
        $this->redirect('/settings/profile');
    }

    public function updatePassword(Request $request, array $params): never
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $current = Connection::fetch(
            'SELECT id, password_hash FROM users WHERE id = :id LIMIT 1',
            ['id' => $userId]
        );
        if (!$current) {
            flash('error', 'Usuario no encontrado.');
            $this->redirect('/login');
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/settings/profile');
        }

        if (!password_verify((string) $request->input('current_password'), (string) $current['password_hash'])) {
            flash('error', 'La contraseña actual no es correcta.');
            $this->redirect('/settings/profile');
        }

        Connection::query(
            'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id',
            [
                'hash' => password_hash((string) $request->input('password'), PASSWORD_DEFAULT),
                'id' => $userId,
            ]
        );

        flash('success', 'Contraseña actualizada.');
        $this->redirect('/settings/profile');
    }
}
