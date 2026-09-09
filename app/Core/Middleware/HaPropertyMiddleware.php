<?php

declare(strict_types=1);

namespace Premisely\Core\Middleware;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Request;
use Premisely\Core\Http\Response;

/** Loads property by public_id for inbound HA webhooks (no user session). */
final class HaPropertyMiddleware
{
    /** @param array<string, string> $params */
    public function handle(Request $request, array $params, callable $next): mixed
    {
        $key = $params['property'] ?? '';
        $property = Connection::fetch(
            'SELECT * FROM properties WHERE (public_id = :k OR id = :id) AND archived_at IS NULL LIMIT 1',
            ['k' => $key, 'id' => ctype_digit((string) $key) ? (int) $key : 0]
        );
        if (!$property) {
            Response::json(['error' => 'Property not found'], 404)->send();
        }
        $GLOBALS['current_property'] = $property;
        $GLOBALS['current_member'] = ['role' => 'system'];
        return $next($request, $params);
    }
}
