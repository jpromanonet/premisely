<?php

declare(strict_types=1);

namespace Premisely\Core\Middleware;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Request;

/**
 * Ensures the authenticated user is an active member of the property in the route.
 * Accepts {property} as public_id or numeric id.
 */
final class PropertyAccessMiddleware
{
    /** @param array<string, string> $params */
    public function handle(Request $request, array $params, callable $next): mixed
    {
        Auth::requireLogin();

        $key = $params['property'] ?? null;
        if ($key === null || $key === '') {
            flash('error', 'Propiedad no especificada.');
            redirect('/dashboard');
        }

        $property = Connection::fetch(
            'SELECT p.* FROM properties p
             INNER JOIN property_members pm ON pm.property_id = p.id
             WHERE pm.user_id = :uid
               AND pm.status = \'active\'
               AND (p.public_id = :key OR p.id = :id)
               AND p.archived_at IS NULL
             LIMIT 1',
            [
                'uid' => Auth::id(),
                'key' => $key,
                'id' => ctype_digit((string) $key) ? (int) $key : 0,
            ]
        );

        if (!$property) {
            flash('error', 'No tenés acceso a esa propiedad.');
            redirect('/dashboard');
        }

        $member = Connection::fetch(
            'SELECT * FROM property_members
             WHERE property_id = :pid AND user_id = :uid AND status = \'active\' LIMIT 1',
            ['pid' => $property['id'], 'uid' => Auth::id()]
        );

        $requestParams = $params;
        $GLOBALS['current_property'] = $property;
        $GLOBALS['current_member'] = $member;

        return $next($request, $requestParams);
    }
}
