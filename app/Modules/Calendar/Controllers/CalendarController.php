<?php

declare(strict_types=1);

namespace Premisely\Modules\Calendar\Controllers;

use DateInterval;
use DateTimeImmutable;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class CalendarController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $month = (string) ($request->query('month') ?: date('Y-m'));
        try {
            $start = new DateTimeImmutable($month . '-01');
        } catch (\Exception) {
            $start = new DateTimeImmutable('first day of this month');
            $month = $start->format('Y-m');
        }
        $end = $start->modify('last day of this month');
        $pid = PropertyContext::propertyId();

        $events = [];
        foreach (Connection::fetchAll(
            'SELECT title, due_date AS event_date, \'task\' AS type, status FROM tasks
             WHERE property_id = :p AND archived_at IS NULL AND due_date BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $start->format('Y-m-d'), 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $events[$row['event_date']][] = $row;
        }
        foreach (Connection::fetchAll(
            'SELECT title, DATE(next_due_at) AS event_date, CONCAT(\'routine:\', category) AS type, \'pending\' AS status
             FROM routines WHERE property_id = :p AND archived_at IS NULL AND is_active = 1
               AND next_due_at IS NOT NULL AND DATE(next_due_at) BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $start->format('Y-m-d'), 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $events[$row['event_date']][] = $row;
        }
        foreach (Connection::fetchAll(
            'SELECT title, next_due_at AS event_date, \'maintenance\' AS type, \'pending\' AS status
             FROM maintenance_plans WHERE property_id = :p AND archived_at IS NULL
               AND next_due_at BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $start->format('Y-m-d'), 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $events[$row['event_date']][] = $row;
        }
        foreach (Connection::fetchAll(
            'SELECT name AS title, next_due_date AS event_date, \'service\' AS type, status
             FROM property_services WHERE property_id = :p AND archived_at IS NULL
               AND next_due_date BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $start->format('Y-m-d'), 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $events[$row['event_date']][] = $row;
        }
        foreach (Connection::fetchAll(
            'SELECT public_id, title, note_date AS event_date, \'note\' AS type, \'active\' AS status
             FROM calendar_notes WHERE property_id = :p AND archived_at IS NULL
               AND note_date BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $start->format('Y-m-d'), 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $events[$row['event_date']][] = $row;
        }

        $days = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $days[] = ['date' => $key, 'events' => $events[$key] ?? []];
            $cursor = $cursor->add(new DateInterval('P1D'));
        }

        $this->view('calendar/index', [
            'title' => 'Calendario',
            'property' => PropertyContext::property(),
            'month' => $month,
            'days' => $days,
            'prev' => $start->modify('-1 month')->format('Y-m'),
            'next' => $start->modify('+1 month')->format('Y-m'),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function createNote(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar');
        }
        $this->view('calendar/notes_create', [
            'title' => 'Nueva nota',
            'property' => PropertyContext::property(),
            'canEdit' => true,
        ]);
    }

    public function storeNote(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar');
        }
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            flash('error', 'El título es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar/notes/create');
        }
        Connection::query(
            'INSERT INTO calendar_notes (public_id, property_id, title, note_date, notes, created_by, created_at)
             VALUES (:pid, :prop, :title, :date, :notes, :uid, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'title' => $title,
                'date' => $request->input('note_date') ?: date('Y-m-d'),
                'notes' => $request->input('notes'),
                'uid' => \Premisely\Core\Auth\Auth::id(),
            ]
        );
        flash('success', 'Nota de calendario creada.');
        $month = substr((string) $request->input('note_date', date('Y-m-d')), 0, 7);
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?month=' . $month);
    }

    public function editNote(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar');
        }
        $note = Connection::fetch(
            'SELECT * FROM calendar_notes WHERE public_id = :pid AND property_id = :prop AND archived_at IS NULL LIMIT 1',
            ['pid' => $params['note'] ?? '', 'prop' => PropertyContext::propertyId()]
        );
        if (!$note) {
            flash('error', 'Nota no encontrada.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar');
        }
        $this->view('calendar/notes_edit', [
            'title' => 'Editar nota',
            'property' => PropertyContext::property(),
            'note' => $note,
            'canEdit' => true,
        ]);
    }

    public function updateNote(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar');
        }
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            flash('error', 'El título es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar/notes/' . ($params['note'] ?? '') . '/edit');
        }
        $noteDate = (string) ($request->input('note_date') ?: date('Y-m-d'));
        Connection::query(
            'UPDATE calendar_notes SET title = :title, note_date = :date, notes = :notes
             WHERE public_id = :pid AND property_id = :prop AND archived_at IS NULL',
            [
                'title' => $title,
                'date' => $noteDate,
                'notes' => $request->input('notes'),
                'pid' => $params['note'] ?? '',
                'prop' => PropertyContext::propertyId(),
            ]
        );
        flash('success', 'Nota actualizada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?month=' . substr($noteDate, 0, 7));
    }

    public function destroyNote(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar');
        }

        $key = (string) ($params['note'] ?? '');
        Connection::query(
            'UPDATE calendar_notes SET archived_at = NOW()
             WHERE property_id = :pid AND (public_id = :key OR id = :id) AND archived_at IS NULL',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        flash('success', 'Nota eliminada.');
        $month = (string) ($request->input('month') ?: date('Y-m'));
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?month=' . $month);
    }
}
