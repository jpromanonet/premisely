<?php

declare(strict_types=1);

namespace Premisely\Core\Middleware;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Http\Request;
use Premisely\Core\Http\Response;

final class AuthMiddleware
{
    /** @param array<string, string> $params */
    public function handle(Request $request, array $params, callable $next): mixed
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                Response::json(['error' => 'Unauthenticated'], 401)->send();
            }
            Auth::requireLogin();
        }
        return $next($request, $params);
    }
}
