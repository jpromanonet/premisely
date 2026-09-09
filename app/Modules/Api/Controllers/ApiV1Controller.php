<?php

declare(strict_types=1);

namespace Premisely\Modules\Api\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class ApiV1Controller extends Controller
{
    public function properties(Request $request, array $params): never
    {
        $rows = Connection::fetchAll(
            'SELECT p.public_id, p.name, p.type, p.status, p.currency, p.address, pm.role
             FROM properties p
             INNER JOIN property_members pm ON pm.property_id = p.id
             WHERE pm.user_id = :uid AND pm.status = \'active\' AND p.archived_at IS NULL
             ORDER BY p.name',
            ['uid' => Auth::id()]
        );
        $this->json(['data' => $rows]);
    }

    public function spaces(Request $request, array $params): never
    {
        $rows = Connection::fetchAll(
            'SELECT public_id, name, type FROM spaces
             WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
            ['p' => PropertyContext::propertyId()]
        );
        $this->json(['data' => $rows]);
    }

    public function inventory(Request $request, array $params): never
    {
        $rows = Connection::fetchAll(
            'SELECT public_id, name, brand, model, status, `condition`, estimated_value, space_id
             FROM inventory_items WHERE property_id = :p AND archived_at IS NULL ORDER BY name LIMIT 500',
            ['p' => PropertyContext::propertyId()]
        );
        $this->json(['data' => $rows]);
    }

    public function stock(Request $request, array $params): never
    {
        $rows = Connection::fetchAll(
            'SELECT public_id, name, quantity, unit, minimum_quantity, target_quantity
             FROM stock_items WHERE property_id = :p AND archived_at IS NULL ORDER BY name LIMIT 500',
            ['p' => PropertyContext::propertyId()]
        );
        $this->json(['data' => $rows]);
    }

    public function tasks(Request $request, array $params): never
    {
        $rows = Connection::fetchAll(
            'SELECT public_id, title, status, priority, due_date
             FROM tasks WHERE property_id = :p AND archived_at IS NULL ORDER BY due_date IS NULL, due_date LIMIT 200',
            ['p' => PropertyContext::propertyId()]
        );
        $this->json(['data' => $rows]);
    }
}
