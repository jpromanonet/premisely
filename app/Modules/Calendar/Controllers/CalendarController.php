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
            'SELECT title, note_date AS event_date, \'note\' AS type, \'active\' AS status
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

    public function storeNote(Request $request, array $params): never
    {
        Connection::query(
            'INSERT INTO calendar_notes (public_id, property_id, title, note_date, notes, created_by, created_at)
             VALUES (:pid, :prop, :title, :date, :notes, :uid, NOW())',
            [
                'pid' => ulid(),
                'prop' => PropertyContext::propertyId(),
                'title' => $request->input('title'),
                'date' => $request->input('note_date') ?: date('Y-m-d'),
                'notes' => $request->input('notes'),
                'uid' => \Premisely\Core\Auth\Auth::id(),
            ]
        );
        flash('success', 'Nota de calendario creada.');
        $month = substr((string) $request->input('note_date', date('Y-m-d')), 0, 7);
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?month=' . $month);
    }
}
