<?php

declare(strict_types=1);

namespace Premisely\Modules\Tasks\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;

final class TaskController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $day = (string) ($request->query('day') ?: date('Y-m-d'));
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . $day);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }
        $pid = PropertyContext::propertyId();
        $this->view('tasks/create', [
            'title' => 'Nueva tarea',
            'property' => PropertyContext::property(),
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => $pid]
            ),
            'members' => PropertyContext::assignableMembers(),
            'due' => (string) $request->query('due', date('Y-m-d')),
            'kind' => (string) $request->query('kind', 'tarea') === 'reparacion' ? 'reparacion' : 'tarea',
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'priority' => 'in:low,normal,high,urgent',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks/create');
        }

        $pid = PropertyContext::propertyId();
        Connection::query(
            'INSERT INTO tasks (
                public_id, property_id, space_id, assignee_member_id, title, description,
                priority, kind, status, due_date, tags, created_by, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :assignee_member_id, :title, :description,
                :priority, :kind, :status, :due_date, :tags, :created_by, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                    ? (int) $request->input('space_id') : null,
                'assignee_member_id' => $request->input('assignee_member_id') !== '' && $request->input('assignee_member_id') !== null
                    ? (int) $request->input('assignee_member_id') : null,
                'title' => (string) $request->input('title'),
                'description' => $request->input('description'),
                'priority' => (string) ($request->input('priority') ?: 'normal'),
                'kind' => (string) $request->input('kind') === 'reparacion' ? 'reparacion' : 'tarea',
                'status' => 'pending',
                'due_date' => (string) $request->input('kind') === 'reparacion' ? null : date('Y-m-d'),
                'tags' => $request->input('tags'),
                'created_by' => Auth::id(),
            ]
        );

        ActivityLogger::log($pid, 'task', (int) Connection::lastInsertId(), 'created');
        flash('success', (string) $request->input('kind') === 'reparacion' ? 'Trabajo cargado.' : 'Tarea creada.');
        if ($request->input('redirect')) {
            $this->redirect((string) $request->input('redirect'));
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . date('Y-m-d'));
    }

    public function edit(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }
        $task = $this->findTask($params['task'] ?? '');
        $pid = PropertyContext::propertyId();
        $this->view('tasks/edit', [
            'title' => 'Editar tarea',
            'property' => PropertyContext::property(),
            'task' => $task,
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => $pid]
            ),
            'members' => PropertyContext::assignableMembers(),
            'canEdit' => true,
        ]);
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }

        $task = $this->findTask($params['task'] ?? '');
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'priority' => 'in:low,normal,high,urgent',
            'status' => 'in:pending,in_progress,done,cancelled',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks/' . $task['public_id'] . '/edit');
        }

        $status = (string) ($request->input('status') ?: $task['status']);
        $isRepair = (string) $request->input('kind') === 'reparacion';
        $existingDue = (string) ($task['due_date'] ?? '');
        $today = date('Y-m-d');
        $due = $isRepair ? null : (($existingDue !== '' && $existingDue >= $today) ? $existingDue : $today);
        Connection::query(
            'UPDATE tasks SET
                title = :title,
                description = :description,
                priority = :priority,
                kind = :kind,
                status = :status,
                due_date = :due_date,
                space_id = :space_id,
                assignee_member_id = :assignee_member_id,
                tags = :tags,
                completed_at = CASE WHEN :status2 = \'done\' THEN COALESCE(completed_at, NOW()) ELSE NULL END,
                updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'title' => (string) $request->input('title'),
                'description' => $request->input('description'),
                'priority' => (string) ($request->input('priority') ?: 'normal'),
                'kind' => $isRepair ? 'reparacion' : 'tarea',
                'status' => $status,
                'status2' => $status,
                'due_date' => $due,
                'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                    ? (int) $request->input('space_id') : null,
                'assignee_member_id' => $request->input('assignee_member_id') !== '' && $request->input('assignee_member_id') !== null
                    ? (int) $request->input('assignee_member_id') : null,
                'tags' => $request->input('tags'),
                'id' => $task['id'],
                'pid' => PropertyContext::propertyId(),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'task', (int) $task['id'], 'updated');
        flash('success', 'Tarea actualizada.');
        if ($request->input('redirect')) {
            $this->redirect((string) $request->input('redirect'));
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . ($due ?: date('Y-m-d')));
    }

    public function updateStatus(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            if ($request->wantsJson()) {
                $this->json(['error' => 'Sin permisos'], 403);
            }
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }

        $task = $this->findTask($params['task'] ?? '');
        $status = (string) $request->input('status');
        if (!in_array($status, ['pending', 'in_progress', 'done', 'cancelled'], true)) {
            if ($request->wantsJson()) {
                $this->json(['error' => 'Estado inválido'], 422);
            }
            flash('error', 'Estado inválido.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }

        Connection::query(
            'UPDATE tasks SET status = :status,
             completed_at = CASE WHEN :status2 = \'done\' THEN NOW() ELSE NULL END,
             due_date = CASE
               WHEN :status3 = \'done\' AND (kind IS NULL OR kind <> \'reparacion\') THEN CURDATE()
               ELSE due_date
             END,
             updated_at = NOW()
             WHERE id = :id',
            [
                'status' => $status,
                'status2' => $status,
                'status3' => $status,
                'id' => $task['id'],
            ]
        );

        ActivityLogger::log(PropertyContext::propertyId(), 'task', (int) $task['id'], 'status_updated', [
            'status' => $status,
        ]);

        if ($request->wantsJson()) {
            $this->json(['ok' => true, 'status' => $status]);
        }

        flash('success', $status === 'done' ? 'Marcada como hecha.' : 'Estado actualizado.');
        if ($request->input('redirect')) {
            $this->redirect((string) $request->input('redirect'));
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . date('Y-m-d'));
    }

    public function archive(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }

        $task = $this->findTask($params['task'] ?? '');
        Connection::query(
            'UPDATE tasks SET archived_at = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $task['id']]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'task', (int) $task['id'], 'archived');
        flash('success', 'Tarea eliminada.');
        if ($request->input('redirect')) {
            $this->redirect((string) $request->input('redirect'));
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
    }

    /** @return array<string, mixed> */
    private function findTask(string $key): array
    {
        $task = Connection::fetch(
            'SELECT * FROM tasks
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );

        if (!$task) {
            flash('error', 'Tarea no encontrada.');
            redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }

        return $task;
    }
}
