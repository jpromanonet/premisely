<?php

declare(strict_types=1);

namespace Premisely\Modules\Routines\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;
use DateInterval;
use DateTimeImmutable;

final class RoutineController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $routines = Connection::fetchAll(
            'SELECT r.*, s.name AS space_name, m.display_name AS assignee_name
             FROM routines r
             LEFT JOIN spaces s ON s.id = r.space_id
             LEFT JOIN property_members m ON m.id = r.assignee_member_id
             WHERE r.property_id = :pid AND r.archived_at IS NULL
             ORDER BY r.next_due_at IS NULL, r.next_due_at, r.title',
            ['pid' => $pid]
        );

        $this->view('routines/index', [
            'title' => 'Rutinas',
            'property' => PropertyContext::property(),
            'routines' => $routines,
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
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'frequency_type' => 'required|in:daily,weekly,monthly,yearly',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }

        $interval = max(1, (int) ($request->input('frequency_interval') ?: 1));
        $nextDue = $this->computeNextDue(
            (string) $request->input('frequency_type'),
            $interval,
            new DateTimeImmutable('now')
        );

        $pid = PropertyContext::propertyId();
        Connection::query(
            'INSERT INTO routines (
                public_id, property_id, space_id, assignee_member_id, title, description,
                category, frequency_type, frequency_interval, next_due_at, is_active, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :assignee_member_id, :title, :description,
                :category, :frequency_type, :frequency_interval, :next_due_at, 1, NOW()
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
                'category' => (string) ($request->input('category') ?: 'general'),
                'frequency_type' => (string) $request->input('frequency_type'),
                'frequency_interval' => $interval,
                'next_due_at' => $nextDue->format('Y-m-d H:i:s'),
            ]
        );

        ActivityLogger::log($pid, 'routine', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Rutina creada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
    }

    public function execute(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }

        $routine = $this->findRoutine($params['routine'] ?? '');
        $now = new DateTimeImmutable('now');
        $nextDue = $this->computeNextDue(
            (string) $routine['frequency_type'],
            (int) $routine['frequency_interval'],
            $now
        );

        $pid = PropertyContext::propertyId();
        Connection::begin();
        try {
            Connection::query(
                'INSERT INTO routine_executions (routine_id, property_id, executed_by, notes, executed_at)
                 VALUES (:routine_id, :property_id, :executed_by, :notes, NOW())',
                [
                    'routine_id' => $routine['id'],
                    'property_id' => $pid,
                    'executed_by' => Auth::id(),
                    'notes' => $request->input('notes'),
                ]
            );

            Connection::query(
                'UPDATE routines SET last_executed_at = NOW(), next_due_at = :next, updated_at = NOW()
                 WHERE id = :id',
                [
                    'next' => $nextDue->format('Y-m-d H:i:s'),
                    'id' => $routine['id'],
                ]
            );

            ActivityLogger::log($pid, 'routine', (int) $routine['id'], 'executed');
            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollBack();
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }

        flash('success', 'Rutina ejecutada.');
        $redirect = (string) (
            $request->input('redirect')
            ?: ($params['_default_redirect'] ?? null)
            ?: '/properties/' . PropertyContext::property()['public_id'] . '/routines'
        );
        $this->redirect($redirect);
    }

    public function computeNextDue(string $type, int $interval, DateTimeImmutable $from): DateTimeImmutable
    {
        $map = [
            'daily' => 'P' . $interval . 'D',
            'weekly' => 'P' . ($interval * 7) . 'D',
            'monthly' => 'P' . $interval . 'M',
            'yearly' => 'P' . $interval . 'Y',
        ];

        return $from->add(new DateInterval($map[$type] ?? 'P7D'));
    }

    /** @return array<string, mixed> */
    private function findRoutine(string $key): array
    {
        $routine = Connection::fetch(
            'SELECT * FROM routines
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );

        if (!$routine) {
            flash('error', 'Rutina no encontrada.');
            redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }

        return $routine;
    }
}
