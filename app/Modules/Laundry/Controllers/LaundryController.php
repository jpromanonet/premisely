<?php

declare(strict_types=1);

namespace Premisely\Modules\Laundry\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Modules\Routines\Controllers\RoutineController;
use Premisely\Shared\PropertyContext;

final class LaundryController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $routines = Connection::fetchAll(
            'SELECT r.*, s.name AS space_name FROM routines r
             LEFT JOIN spaces s ON s.id = r.space_id
             WHERE r.property_id = :p AND r.category = \'laundry\' AND r.archived_at IS NULL
             ORDER BY r.next_due_at IS NULL, r.next_due_at, r.title',
            ['p' => PropertyContext::propertyId()]
        );
        $this->view('laundry/index', [
            'title' => 'Lavandería',
            'property' => PropertyContext::property(),
            'routines' => $routines,
            'spaces' => Connection::fetchAll(
                'SELECT * FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
                ['p' => PropertyContext::propertyId()]
            ),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function store(Request $request, array $params): never
    {
        $next = $request->input('next_due_at') ?: date('Y-m-d H:i:s');
        Connection::query(
            'INSERT INTO routines (public_id, property_id, space_id, title, description, category, frequency_type, frequency_interval, next_due_at, is_active, created_at)
             VALUES (:pid, :prop, :space, :title, :desc, \'laundry\', :freq, :interval, :next, 1, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'space' => $request->input('space_id') ?: null,
                'title' => $request->input('title'),
                'desc' => $request->input('description'),
                'freq' => $request->input('frequency_type', 'weekly'),
                'interval' => (int) $request->input('frequency_interval', 1),
                'next' => $next,
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'routine', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Tarea de lavandería creada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/laundry');
    }

    public function execute(Request $request, array $params): never
    {
        $_POST['redirect'] = '/properties/' . PropertyContext::property()['public_id'] . '/laundry';
        (new RoutineController())->execute(Request::capture(), $params);
    }
}
