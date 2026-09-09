<?php

declare(strict_types=1);

namespace Premisely\Core\Middleware;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Request;
use Premisely\Core\Http\Response;

/**
 * Accepts Bearer API token or existing session. Always JSON 401 on failure for /api paths.
 */
final class ApiAuthMiddleware
{
    /** @param array<string, string> $params */
    public function handle(Request $request, array $params, callable $next): mixed
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            $plain = $m[1];
            $hash = hash('sha256', $plain);
            $token = Connection::fetch(
                'SELECT t.*, u.public_id AS user_public_id, u.name AS user_name, u.email, u.locale, u.timezone, u.preferred_currency, u.is_active
                 FROM api_tokens t
                 INNER JOIN users u ON u.id = t.user_id
                 WHERE t.token_hash = :hash AND t.revoked_at IS NULL
                 LIMIT 1',
                ['hash' => $hash]
            );
            if (!$token || !(int) $token['is_active']) {
                Response::json(['error' => 'Invalid token'], 401)->send();
            }
            Auth::setUser([
                'id' => (int) $token['user_id'],
                'public_id' => (string) $token['user_public_id'],
                'name' => (string) $token['user_name'],
                'email' => (string) $token['email'],
                'locale' => (string) ($token['locale'] ?? 'es'),
                'timezone' => (string) ($token['timezone'] ?? 'UTC'),
                'preferred_currency' => (string) ($token['preferred_currency'] ?? 'ARS'),
            ]);
            Connection::query('UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id', ['id' => $token['id']]);
            $GLOBALS['api_token'] = $token;
            return $next($request, $params);
        }

        if (Auth::check()) {
            return $next($request, $params);
        }

        Response::json(['error' => 'Unauthenticated'], 401)->send();
    }
}
