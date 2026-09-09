<?php

declare(strict_types=1);

namespace Premisely\Modules\Search\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class SearchController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $q = trim((string) $request->query('q', $request->input('q', '')));
        $pid = PropertyContext::propertyId();
        $results = [
            'inventory' => [],
            'stock' => [],
            'tasks' => [],
            'spaces' => [],
        ];

        if ($q !== '' && mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';

            $results['inventory'] = Connection::fetchAll(
                'SELECT public_id, name, brand, model, status
                 FROM inventory_items
                 WHERE property_id = :pid AND archived_at IS NULL
                   AND (name LIKE :q OR brand LIKE :q2 OR model LIKE :q3 OR serial_number LIKE :q4)
                 ORDER BY name LIMIT 20',
                ['pid' => $pid, 'q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like]
            );

            $results['stock'] = Connection::fetchAll(
                'SELECT public_id, name, brand, quantity, unit
                 FROM stock_items
                 WHERE property_id = :pid AND archived_at IS NULL
                   AND (name LIKE :q OR brand LIKE :q2)
                 ORDER BY name LIMIT 20',
                ['pid' => $pid, 'q' => $like, 'q2' => $like]
            );

            $results['tasks'] = Connection::fetchAll(
                'SELECT public_id, title, status, due_date
                 FROM tasks
                 WHERE property_id = :pid AND archived_at IS NULL
                   AND (title LIKE :q OR description LIKE :q2 OR tags LIKE :q3)
                 ORDER BY title LIMIT 20',
                ['pid' => $pid, 'q' => $like, 'q2' => $like, 'q3' => $like]
            );

            $results['spaces'] = Connection::fetchAll(
                'SELECT public_id, name, type
                 FROM spaces
                 WHERE property_id = :pid AND archived_at IS NULL
                   AND (name LIKE :q OR description LIKE :q2)
                 ORDER BY name LIMIT 20',
                ['pid' => $pid, 'q' => $like, 'q2' => $like]
            );
        }

        $this->view('search/index', [
            'title' => 'Buscar',
            'property' => PropertyContext::property(),
            'q' => $q,
            'results' => $results,
        ]);
    }
}
