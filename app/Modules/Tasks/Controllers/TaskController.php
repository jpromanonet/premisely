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
        $pid = PropertyContext::propertyId();
        $tasks = Connection::fetchAll(
            'SELECT t.*, s.name AS space_name, m.display_name AS assignee_name
             FROM tasks t
             LEFT JOIN spaces s ON s.id = t.space_id
             LEFT JOIN property_members m ON m.id = t.assignee_member_id
             WHERE t.property_id = :pid AND t.archived_at IS NULL
             ORDER BY FIELD(t.status, \'pending\', \'in_progress\', \'done\', \'cancelled\'), t.due_date IS NULL, t.due_date',
            ['pid' => $pid]
        );

        $this->view('tasks/index', [
            'title' => 'Tareas',
            'property' => PropertyContext::property(),
            'tasks' => $tasks,
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => $pid]
            ),
            'members' => Connection::fetchAll(
                'SELECT id, display_name FROM property_members WHERE property_id = :pid AND status = \'active\' ORDER BY display_name',
                ['pid' => $pid]
            ),
            'canEdit' => PropertyContext::canEdit(),
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
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
        }

        $pid = PropertyContext::propertyId();
        Connection::query(
            'INSERT INTO tasks (
                public_id, property_id, space_id, assignee_member_id, title, description,
                priority, status, due_date, tags, created_by, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :assignee_member_id, :title, :description,
                :priority, :status, :due_date, :tags, :created_by, NOW()
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
                'status' => 'pending',
                'due_date' => $request->input('due_date') ?: null,
                'tags' => $request->input('tags'),
                'created_by' => Auth::id(),
            ]
        );

        ActivityLogger::log($pid, 'task', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Tarea creada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
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
             updated_at = NOW()
             WHERE id = :id',
            [
                'status' => $status,
                'status2' => $status,
                'id' => $task['id'],
            ]
        );

        ActivityLogger::log(PropertyContext::propertyId(), 'task', (int) $task['id'], 'status_updated', [
            'status' => $status,
        ]);

        if ($request->wantsJson()) {
            $this->json(['ok' => true, 'status' => $status]);
        }

        flash('success', 'Estado actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/tasks');
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
        flash('success', 'Tarea archivada.');
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
