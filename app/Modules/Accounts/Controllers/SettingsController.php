<?php

declare(strict_types=1);

namespace Premisely\Modules\Accounts\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;

final class SettingsController extends Controller
{
    public function edit(Request $request, array $params): never
    {
        Auth::requireLogin();
        $user = Connection::fetch('SELECT * FROM users WHERE id = :id', ['id' => Auth::id()]);
        $this->view('settings/profile', [
            'title' => 'Configuración',
            'user' => $user,
            'form_action' => '/settings',
        ]);
    }

    public function update(Request $request, array $params): never
    {
        Auth::requireLogin();
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:120',
            'locale' => 'required|max:10',
            'timezone' => 'required|max:64',
            'preferred_currency' => 'required|max:3',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/settings');
        }

        Connection::query(
            'UPDATE users SET name = :name, locale = :locale, timezone = :timezone,
             preferred_currency = :currency, notify_email = :notify, updated_at = NOW()
             WHERE id = :id',
            [
                'name' => trim((string) $request->input('name')),
                'locale' => (string) $request->input('locale'),
                'timezone' => (string) $request->input('timezone'),
                'currency' => strtoupper((string) $request->input('preferred_currency')),
                'notify' => $request->input('notify_email') ? 1 : 0,
                'id' => Auth::id(),
            ]
        );

        if ($_SESSION['user'] ?? null) {
            $_SESSION['user']['name'] = trim((string) $request->input('name'));
            $_SESSION['user']['locale'] = (string) $request->input('locale');
            $_SESSION['user']['timezone'] = (string) $request->input('timezone');
            $_SESSION['user']['preferred_currency'] = strtoupper((string) $request->input('preferred_currency'));
        }

        flash('success', 'Configuración guardada.');
        $this->redirect('/settings');
    }

    /** Aliases for /settings/profile */
    public function profile(Request $request, array $params): never
    {
        $this->edit($request, $params);
    }

    public function updateProfile(Request $request, array $params): never
    {
        $this->update($request, $params);
    }
}
