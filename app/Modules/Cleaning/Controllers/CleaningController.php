<?php

declare(strict_types=1);

namespace Premisely\Modules\Cleaning\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Modules\Routines\Controllers\RoutineController;
use Premisely\Shared\PropertyContext;

final class CleaningController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $routines = Connection::fetchAll(
            'SELECT r.*, s.name AS space_name FROM routines r
             LEFT JOIN spaces s ON s.id = r.space_id
             WHERE r.property_id = :p AND r.category = \'cleaning\' AND r.archived_at IS NULL
             ORDER BY s.name, r.title',
            ['p' => PropertyContext::propertyId()]
        );
        $this->view('cleaning/index', [
            'title' => 'Limpieza',
            'heading' => 'Limpieza',
            'property' => PropertyContext::property(),
            'routines' => $routines,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/cleaning');
        }
        $pid = PropertyContext::propertyId();
        $this->view('cleaning/create', [
            'title' => 'Nueva rutina de limpieza',
            'property' => PropertyContext::property(),
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
                ['p' => $pid]
            ),
            'members' => Connection::fetchAll(
                'SELECT id, display_name FROM property_members WHERE property_id = :p AND status = \'active\' ORDER BY display_name',
                ['p' => $pid]
            ),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/cleaning');
        }
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            flash('error', 'El título es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/cleaning/create');
        }
        $next = $request->input('next_due_at') ?: date('Y-m-d H:i:s');
        Connection::query(
            'INSERT INTO routines (public_id, property_id, space_id, assignee_member_id, title, description, category, frequency_type, frequency_interval, next_due_at, is_active, created_at)
             VALUES (:pid, :prop, :space, :assignee, :title, :desc, \'cleaning\', :freq, :interval, :next, 1, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'space' => $request->input('space_id') ?: null,
                'assignee' => $request->input('assignee_member_id') ?: null,
                'title' => $title,
                'desc' => $request->input('description'),
                'freq' => $request->input('frequency_type', 'weekly'),
                'interval' => (int) $request->input('frequency_interval', 1),
                'next' => $next,
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'routine', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Tarea de limpieza creada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/cleaning');
    }

    public function execute(Request $request, array $params): never
    {
        $key = (string) ($params['routine'] ?? '');
        $routine = Connection::fetch(
            'SELECT * FROM routines
             WHERE property_id = :pid AND category = \'cleaning\' AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        if (!$routine) {
            flash('error', 'Tarea no encontrada.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/cleaning');
        }

        $_POST['notes'] = $request->input('notes');
        $_POST['redirect'] = '/properties/' . PropertyContext::property()['public_id'] . '/cleaning';
        $params['routine'] = $routine['public_id'];
        (new RoutineController())->execute(Request::capture(), $params);
    }
}
