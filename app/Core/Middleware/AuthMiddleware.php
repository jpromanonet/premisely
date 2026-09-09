<?php

declare(strict_types=1);

namespace Premisely\Core\Middleware;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Http\Request;

final class AuthMiddleware
{
    /** @param array<string, string> $params */
    public function handle(Request $request, array $params, callable $next): mixed
    {
        Auth::requireLogin();
        return $next($request, $params);
    }
}
