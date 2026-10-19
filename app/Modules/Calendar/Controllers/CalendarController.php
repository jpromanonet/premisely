<?php

declare(strict_types=1);

namespace Premisely\Modules\Calendar\Controllers;

use DateInterval;
use DateTimeImmutable;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Routines\Controllers\RoutineController;
use Premisely\Shared\PropertyContext;

final class CalendarController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $today = date('Y-m-d');
        $day = (string) ($request->query('day') ?: $today);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $day = $today;
        }
        try {
            $selected = new DateTimeImmutable($day);
        } catch (\Exception) {
            $selected = new DateTimeImmutable($today);
            $day = $today;
        }

        $month = (string) ($request->query('month') ?: $selected->format('Y-m'));
        try {
            $monthStart = new DateTimeImmutable($month . '-01');
        } catch (\Exception) {
            $monthStart = $selected->modify('first day of this month');
            $month = $monthStart->format('Y-m');
        }
        $monthEnd = $monthStart->modify('last day of this month');
        $gridStart = $monthStart->modify('monday this week');
        $gridEnd = $monthEnd->modify('sunday this week');

        Connection::query(
            'UPDATE tasks SET due_date = NULL, updated_at = NOW()
             WHERE property_id = :p AND kind = \'reparacion\' AND due_date IS NOT NULL AND archived_at IS NULL',
            ['p' => $pid]
        );

        $tasks = Connection::fetchAll(
            'SELECT t.public_id, t.title, t.kind, t.status, t.priority, t.due_date, t.completed_at,
                    COALESCE(NULLIF(u.name, \'\'), m.display_name) AS assignee_name
             FROM tasks t
             LEFT JOIN property_members m ON m.id = t.assignee_member_id
             LEFT JOIN users u ON u.id = m.user_id
             WHERE t.property_id = :p AND t.archived_at IS NULL AND t.due_date = :day
               AND (t.kind IS NULL OR t.kind <> \'reparacion\')
             ORDER BY FIELD(t.status, \'pending\', \'in_progress\', \'done\', \'cancelled\'), t.title',
            ['p' => $pid, 'day' => $day]
        );
        $undated = $day === $today
            ? Connection::fetchAll(
                'SELECT t.public_id, t.title, t.kind, t.status, t.priority, t.due_date,
                        COALESCE(NULLIF(u.name, \'\'), m.display_name) AS assignee_name
                 FROM tasks t
                 LEFT JOIN property_members m ON m.id = t.assignee_member_id
                 LEFT JOIN users u ON u.id = m.user_id
                 WHERE t.property_id = :p AND t.archived_at IS NULL AND t.due_date IS NULL
                   AND t.status IN (\'pending\', \'in_progress\')
                   AND (t.kind IS NULL OR t.kind <> \'reparacion\')
                 ORDER BY t.title',
                ['p' => $pid]
            )
            : [];
        $repairTasks = Connection::fetchAll(
            'SELECT t.public_id, t.title, t.status, t.priority,
                    COALESCE(NULLIF(u.name, \'\'), m.display_name) AS assignee_name
             FROM tasks t
             LEFT JOIN property_members m ON m.id = t.assignee_member_id
             LEFT JOIN users u ON u.id = m.user_id
             WHERE t.property_id = :p AND t.archived_at IS NULL AND t.kind = \'reparacion\'
               AND t.status IN (\'pending\', \'in_progress\')
             ORDER BY t.title',
            ['p' => $pid]
        );

        $habitBoard = RoutineController::forDay($pid, $day);

        $meals = Connection::fetchAll(
            'SELECT public_id, slot, title, notes FROM meal_entries
             WHERE property_id = :p AND meal_date = :day
             ORDER BY FIELD(slot, \'desayuno\', \'almuerzo\', \'merienda\', \'cena\'), id',
            ['p' => $pid, 'day' => $day]
        );

        \Premisely\Modules\Repairs\Controllers\RepairController::archiveCopiesOfTasks($pid);

        $repairs = Connection::fetchAll(
            'SELECT public_id, title, status, reported_at
             FROM repair_records
             WHERE property_id = :p AND archived_at IS NULL
               AND status IN (\'pendiente\', \'en_curso\', \'open\', \'in_progress\')
               AND (reported_at = :day OR (:today = :day2 AND reported_at < :day3))
             ORDER BY reported_at, title',
            ['p' => $pid, 'day' => $day, 'today' => $today, 'day2' => $day, 'day3' => $day]
        );

        $notes = Connection::fetchAll(
            'SELECT public_id, title, notes FROM calendar_notes
             WHERE property_id = :p AND archived_at IS NULL AND note_date = :day
             ORDER BY title',
            ['p' => $pid, 'day' => $day]
        );

        $marks = [];
        foreach (Connection::fetchAll(
            'SELECT due_date AS d, COUNT(*) AS n FROM tasks
             WHERE property_id = :p AND archived_at IS NULL AND due_date BETWEEN :a AND :b
               AND (kind IS NULL OR kind <> \'reparacion\')
             GROUP BY due_date',
            ['p' => $pid, 'a' => $gridStart->format('Y-m-d'), 'b' => $gridEnd->format('Y-m-d')]
        ) as $row) {
            $marks[$row['d']] = ($marks[$row['d']] ?? 0) + (int) $row['n'];
        }
        $habitRows = Connection::fetchAll(
            'SELECT frequency_type, frequency_interval, schedule_day, next_due_at, created_at
             FROM routines
             WHERE property_id = :p AND archived_at IS NULL AND is_active = 1',
            ['p' => $pid]
        );
        $markCursor = $gridStart;
        while ($markCursor <= $gridEnd) {
            $key = $markCursor->format('Y-m-d');
            foreach ($habitRows as $habit) {
                $habitType = (string) ($habit['frequency_type'] ?? '');
                $habitDay = (int) ($habit['schedule_day'] ?? 0);
                if (in_array($habitType, ['weekly', 'monthly'], true) && $habitDay > 0) {
                    if (RoutineController::occursOn($habit, $key)) {
                        $marks[$key] = ($marks[$key] ?? 0) + 1;
                    }
                    continue;
                }
                if (substr((string) ($habit['next_due_at'] ?? ''), 0, 10) === $key) {
                    $marks[$key] = ($marks[$key] ?? 0) + 1;
                }
            }
            $markCursor = $markCursor->add(new DateInterval('P1D'));
        }
        foreach (Connection::fetchAll(
            'SELECT meal_date AS d, COUNT(*) AS n FROM meal_entries
             WHERE property_id = :p AND meal_date BETWEEN :a AND :b
             GROUP BY meal_date',
            ['p' => $pid, 'a' => $gridStart->format('Y-m-d'), 'b' => $gridEnd->format('Y-m-d')]
        ) as $row) {
            $marks[$row['d']] = ($marks[$row['d']] ?? 0) + (int) $row['n'];
        }

        $weeks = [];
        $cursor = $gridStart;
        while ($cursor <= $gridEnd) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->format('Y-m-d');
                $week[] = [
                    'date' => $key,
                    'inMonth' => $cursor->format('Y-m') === $month,
                    'count' => $marks[$key] ?? 0,
                ];
                $cursor = $cursor->add(new DateInterval('P1D'));
            }
            $weeks[] = $week;
        }

        $this->view('calendar/index', [
            'title' => 'Día a día',
            'property' => PropertyContext::property(),
            'day' => $day,
            'today' => $today,
            'month' => $month,
            'weeks' => $weeks,
            'prevMonth' => $monthStart->modify('-1 month')->format('Y-m'),
            'nextMonth' => $monthStart->modify('+1 month')->format('Y-m'),
            'prevDay' => $selected->modify('-1 day')->format('Y-m-d'),
            'nextDay' => $selected->modify('+1 day')->format('Y-m-d'),
            'tasks' => $tasks,
            'undated' => $undated,
            'repairTasks' => $repairTasks,
            'habits' => $habitBoard['items'],
            'meals' => $meals,
            'repairs' => $repairs,
            'notes' => $notes,
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
        $noteDate = (string) ($request->input('note_date') ?: date('Y-m-d'));
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . $noteDate);
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
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . $noteDate);
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
        if ($request->input('redirect')) {
            $this->redirect((string) $request->input('redirect'));
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/calendar?day=' . date('Y-m-d'));
    }
}
