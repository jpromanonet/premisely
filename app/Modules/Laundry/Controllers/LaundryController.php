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
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/laundry');
        }
        $this->view('laundry/create', [
            'title' => 'Nueva tarea',
            'property' => PropertyContext::property(),
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
                ['p' => PropertyContext::propertyId()]
            ),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/laundry');
        }
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            flash('error', 'El título es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/laundry/create');
        }
        $next = $request->input('next_due_at') ?: date('Y-m-d H:i:s');
        Connection::query(
            'INSERT INTO routines (public_id, property_id, space_id, title, description, category, frequency_type, frequency_interval, next_due_at, is_active, created_at)
             VALUES (:pid, :prop, :space, :title, :desc, \'laundry\', :freq, :interval, :next, 1, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'space' => $request->input('space_id') ?: null,
                'title' => $title,
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

    public function edit(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/laundry');
        }
        $routine = Connection::fetch(
            'SELECT * FROM routines
             WHERE property_id = :pid AND category = \'laundry\' AND archived_at IS NULL
               AND (public_id = :key OR id = :id) LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $params['routine'] ?? '',
                'id' => ctype_digit((string) ($params['routine'] ?? '')) ? (int) $params['routine'] : 0,
            ]
        );
        if (!$routine) {
            flash('error', 'Tarea no encontrada.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/laundry');
        }
        $pid = PropertyContext::propertyId();
        $prop = PropertyContext::property()['public_id'];
        $this->view('routines/edit', [
            'title' => 'Editar lavandería',
            'heading' => 'Editar tarea de lavandería',
            'property' => PropertyContext::property(),
            'routine' => $routine,
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
                ['p' => $pid]
            ),
            'members' => [],
            'listPath' => '/properties/' . $prop . '/laundry',
            'formAction' => '/properties/' . $prop . '/laundry/' . $routine['public_id'],
            'fixedCategory' => 'laundry',
            'canEdit' => true,
        ]);
    }

    public function update(Request $request, array $params): never
    {
        $_POST['category'] = 'laundry';
        $_POST['redirect'] = '/properties/' . PropertyContext::property()['public_id'] . '/laundry';
        (new RoutineController())->update(Request::capture(), $params);
    }

    public function execute(Request $request, array $params): never
    {
        $_POST['redirect'] = '/properties/' . PropertyContext::property()['public_id'] . '/laundry';
        (new RoutineController())->execute(Request::capture(), $params);
    }

    public function destroy(Request $request, array $params): never
    {
        $_POST['redirect'] = '/properties/' . PropertyContext::property()['public_id'] . '/laundry';
        (new RoutineController())->archive(Request::capture(), $params);
    }

    public function toggle(Request $request, array $params): never
    {
        $_POST['redirect'] = '/properties/' . PropertyContext::property()['public_id'] . '/laundry';
        (new RoutineController())->toggleActive(Request::capture(), $params);
    }
}
