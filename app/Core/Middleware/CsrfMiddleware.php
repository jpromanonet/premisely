<?php

declare(strict_types=1);

namespace Premisely\Core\Middleware;

use Premisely\Core\Http\Request;
use Premisely\Core\Security\Csrf;

final class CsrfMiddleware
{
    /** @param array<string, string> $params */
    public function handle(Request $request, array $params, callable $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token');
            if (!Csrf::validate(is_string($token) ? $token : null)) {
                flash('error', 'Token CSRF inválido. Recargá la página e intentá de nuevo.');
                if ($request->wantsJson()) {
                    json_response(['error' => 'CSRF invalid'], 419);
                }
                redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
        }
        return $next($request, $params);
    }
}
