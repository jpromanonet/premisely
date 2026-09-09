<?php

declare(strict_types=1);

namespace Premisely\Modules\Api\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class ApiTokenController extends Controller
{
    public function index(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'Solo owner/admin puede gestionar tokens.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id']);
        }
        $tokens = Connection::fetchAll(
            'SELECT public_id, name, token_prefix, scopes, last_used_at, created_at, revoked_at
             FROM api_tokens WHERE user_id = :uid ORDER BY created_at DESC',
            ['uid' => Auth::id()]
        );
        $plainToken = $_SESSION['_new_api_token'] ?? null;
        unset($_SESSION['_new_api_token']);
        $this->view('api/tokens', [
            'title' => 'API tokens',
            'property' => PropertyContext::property(),
            'tokens' => $tokens,
            'plainToken' => $plainToken,
            'canManage' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'Sin permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/api-tokens');
        }
        $name = trim((string) $request->input('name', 'Token'));
        $plain = 'prm_' . bin2hex(random_bytes(24));
        $hash = hash('sha256', $plain);
        Connection::query(
            'INSERT INTO api_tokens (public_id, user_id, name, token_hash, token_prefix, scopes, created_at)
             VALUES (:pid, :uid, :name, :hash, :prefix, :scopes, NOW())',
            [
                'pid' => ulid(),
                'uid' => Auth::id(),
                'name' => $name,
                'hash' => $hash,
                'prefix' => substr($plain, 0, 8),
                'scopes' => 'read,write',
            ]
        );
        $_SESSION['_new_api_token'] = $plain;
        flash('success', 'Token creado. Copialo ahora: no se vuelve a mostrar.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/api-tokens');
    }

    public function revoke(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'Sin permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/api-tokens');
        }
        Connection::query(
            'UPDATE api_tokens SET revoked_at = NOW() WHERE public_id = :pid AND user_id = :uid AND revoked_at IS NULL',
            ['pid' => $params['token'] ?? '', 'uid' => Auth::id()]
        );
        flash('success', 'Token revocado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/api-tokens');
    }
}
