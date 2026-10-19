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
        $view = self::habitView((string) $request->query('view', 'daily'));
        $board = self::forDay(PropertyContext::propertyId(), date('Y-m-d'), '', true);
        $types = self::habitViewTypes($view);
        $items = array_values(array_filter(
            $board['items'],
            static fn (array $row): bool => in_array((string) $row['frequency_type'], $types, true)
        ));
        $paused = array_values(array_filter(
            $board['paused'],
            static fn (array $row): bool => in_array((string) $row['frequency_type'], $types, true)
        ));
        $done = 0;
        $total = 0;
        foreach ($items as $item) {
            if (empty($item['due_today'])) {
                continue;
            }
            $total++;
            if (!empty($item['done_today'])) {
                $done++;
            }
        }

        $this->view('routines/index', [
            'title' => 'Hábitos',
            'property' => PropertyContext::property(),
            'items' => $items,
            'paused' => $paused,
            'done' => $done,
            'total' => $total,
            'view' => $view,
            'today' => date('Y-m-d'),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }
        $pid = PropertyContext::propertyId();
        $this->view('routines/create', [
            'title' => 'Nuevo hábito',
            'property' => PropertyContext::property(),
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => $pid]
            ),
            'members' => PropertyContext::assignableMembers(),
            'canEdit' => true,
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
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines/create');
        }

        $interval = max(1, (int) ($request->input('frequency_interval') ?: 1));
        $type = (string) $request->input('frequency_type');
        $schedule = self::scheduleFromRequest($request, $type);
        if ($schedule === null) {
            flash('error', $type === 'weekly' ? 'Elegí un día de la semana.' : 'Elegí un día del mes.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines/create');
        }
        $nextDue = $schedule > 0
            ? self::upcoming($type, $interval, $schedule, new DateTimeImmutable('today'), true)
            : new DateTimeImmutable('today');

        $pid = PropertyContext::propertyId();
        $nextOrder = (int) Connection::fetchColumn(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM routines WHERE property_id = :pid AND archived_at IS NULL',
            ['pid' => $pid]
        );
        Connection::query(
            'INSERT INTO routines (
                public_id, property_id, space_id, assignee_member_id, title, description,
                category, frequency_type, frequency_interval, schedule_day, next_due_at, is_active, sort_order, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :assignee_member_id, :title, :description,
                :category, :frequency_type, :frequency_interval, :schedule_day, :next_due_at, 1, :sort_order, NOW()
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
                'frequency_type' => $type,
                'frequency_interval' => $interval,
                'schedule_day' => $schedule > 0 ? $schedule : null,
                'next_due_at' => $nextDue->format('Y-m-d H:i:s'),
                'sort_order' => $nextOrder,
            ]
        );

        ActivityLogger::log($pid, 'routine', (int) Connection::lastInsertId(), 'created');
        flash('success', self::createdMessage($type, $schedule));
        $this->redirect(self::habitListPath((string) $request->input('frequency_type', 'daily')));
    }

    public function edit(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect($this->routineListPath($request));
        }
        $routine = $this->findRoutine($params['routine'] ?? '');
        $pid = PropertyContext::propertyId();
        $this->view('routines/edit', [
            'title' => 'Editar rutina',
            'heading' => 'Editar rutina',
            'property' => PropertyContext::property(),
            'routine' => $routine,
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => $pid]
            ),
            'members' => PropertyContext::assignableMembers(),
            'listPath' => '/properties/' . PropertyContext::property()['public_id'] . '/routines',
            'formAction' => '/properties/' . PropertyContext::property()['public_id'] . '/routines/' . $routine['public_id'],
            'canEdit' => true,
        ]);
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect($this->routineListPath($request));
        }

        $routine = $this->findRoutine($params['routine'] ?? '');
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'frequency_type' => 'required|in:daily,weekly,monthly,yearly',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $cat = (string) ($request->input('category') ?: $routine['category']);
            $pub = PropertyContext::property()['public_id'];
            $editBase = match ($cat) {
                'cleaning' => '/properties/' . $pub . '/cleaning/',
                'laundry' => '/properties/' . $pub . '/laundry/',
                default => '/properties/' . $pub . '/routines/',
            };
            $this->redirect($editBase . $routine['public_id'] . '/edit');
        }

        $interval = max(1, (int) ($request->input('frequency_interval') ?: 1));
        $type = (string) $request->input('frequency_type');
        $schedule = self::scheduleFromRequest($request, $type);
        if ($schedule === null) {
            flash('error', $type === 'weekly' ? 'Elegí un día de la semana.' : 'Elegí un día del mes.');
            $pub = PropertyContext::property()['public_id'];
            $this->redirect('/properties/' . $pub . '/routines/' . $routine['public_id'] . '/edit');
        }
        if ($schedule > 0) {
            $nextDue = self::upcoming($type, $interval, $schedule, new DateTimeImmutable('today'), true)->format('Y-m-d H:i:s');
        } else {
            $nextRaw = trim((string) $request->input('next_due_at', ''));
            if ($nextRaw !== '') {
                $nextDue = str_replace('T', ' ', $nextRaw);
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $nextDue)) {
                    $nextDue .= ':00';
                }
            } else {
                $nextDue = $routine['next_due_at'];
            }
        }

        Connection::query(
            'UPDATE routines SET
                title = :title,
                description = :description,
                category = :category,
                frequency_type = :frequency_type,
                frequency_interval = :frequency_interval,
                schedule_day = :schedule_day,
                next_due_at = :next_due_at,
                space_id = :space_id,
                assignee_member_id = :assignee_member_id,
                is_active = :is_active,
                updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'title' => (string) $request->input('title'),
                'description' => $request->input('description'),
                'category' => (string) ($request->input('category') ?: $routine['category']),
                'frequency_type' => $type,
                'frequency_interval' => $interval,
                'schedule_day' => $schedule > 0 ? $schedule : null,
                'next_due_at' => $nextDue ?: null,
                'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                    ? (int) $request->input('space_id') : null,
                'assignee_member_id' => $request->input('assignee_member_id') !== '' && $request->input('assignee_member_id') !== null
                    ? (int) $request->input('assignee_member_id') : null,
                'is_active' => (int) $request->input('is_active', 1) === 1 ? 1 : 0,
                'id' => $routine['id'],
                'pid' => PropertyContext::propertyId(),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'routine', (int) $routine['id'], 'updated');
        flash('success', 'Rutina actualizada.');
        $this->redirect($this->routineListPath($request, (string) ($request->input('category') ?: $routine['category'])));
    }

    public function execute(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }

        $routine = $this->findRoutine($params['routine'] ?? '');
        $doneToday = substr((string) ($routine['last_executed_at'] ?? ''), 0, 10) === date('Y-m-d');
        if ($doneToday) {
            $redirect = (string) ($request->input('redirect') ?: '/properties/' . PropertyContext::property()['public_id'] . '/routines');
            $this->redirect($redirect);
        }
        $type = (string) $routine['frequency_type'];
        $schedule = (int) ($routine['schedule_day'] ?? 0);
        if (in_array($type, ['weekly', 'monthly'], true) && $schedule > 0) {
            $nextDue = self::upcoming($type, (int) $routine['frequency_interval'], $schedule, new DateTimeImmutable('today'), false);
        } else {
            $nextDue = $this->computeNextDue($type, (int) $routine['frequency_interval'], new DateTimeImmutable('now'));
        }

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

        $redirect = (string) (
            $request->input('redirect')
            ?: ($params['_default_redirect'] ?? null)
            ?: '/properties/' . PropertyContext::property()['public_id'] . '/routines'
        );
        $this->redirect($redirect);
    }

    public function undo(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }

        $routine = $this->findRoutine($params['routine'] ?? '');
        $pid = PropertyContext::propertyId();
        $last = Connection::fetch(
            'SELECT id, executed_at FROM routine_executions
             WHERE routine_id = :id AND property_id = :pid
             ORDER BY executed_at DESC, id DESC LIMIT 1',
            ['id' => $routine['id'], 'pid' => $pid]
        );
        if ($last && substr((string) $last['executed_at'], 0, 10) === date('Y-m-d')) {
            Connection::query('DELETE FROM routine_executions WHERE id = :id', ['id' => $last['id']]);
            $prev = Connection::fetch(
                'SELECT executed_at FROM routine_executions
                 WHERE routine_id = :id AND property_id = :pid
                 ORDER BY executed_at DESC, id DESC LIMIT 1',
                ['id' => $routine['id'], 'pid' => $pid]
            );
            Connection::query(
                'UPDATE routines SET last_executed_at = :last, next_due_at = :next, updated_at = NOW() WHERE id = :id',
                [
                    'last' => $prev['executed_at'] ?? null,
                    'next' => self::dueAfterUndo($routine)->format('Y-m-d H:i:s'),
                    'id' => $routine['id'],
                ]
            );
            ActivityLogger::log($pid, 'routine', (int) $routine['id'], 'undone');
        }

        $redirect = (string) ($request->input('redirect') ?: '/properties/' . PropertyContext::property()['public_id'] . '/routines');
        $this->redirect($redirect);
    }

    public function updateFrequency(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }
        $routine = $this->findRoutine($params['routine'] ?? '');
        [$type, $interval] = self::parseFrequency((string) $request->input('frequency', ''));
        $previousType = (string) $routine['frequency_type'];
        $schedule = (int) ($routine['schedule_day'] ?? 0);
        if ($type === 'weekly' && ($schedule < 1 || $schedule > 7)) {
            $schedule = (int) date('N');
        } elseif ($type === 'monthly' && ($schedule < 1 || $schedule > 31)) {
            $schedule = (int) date('j');
        } elseif (!in_array($type, ['weekly', 'monthly'], true)) {
            $schedule = 0;
        }
        $nextDue = $schedule > 0
            ? self::upcoming($type, $interval, $schedule, new DateTimeImmutable('today'), true)
            : new DateTimeImmutable('today');
        Connection::query(
            'UPDATE routines SET frequency_type = :type, frequency_interval = :interval,
                schedule_day = :schedule_day, next_due_at = :next_due_at, updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'type' => $type,
                'interval' => $interval,
                'schedule_day' => $schedule > 0 ? $schedule : null,
                'next_due_at' => $nextDue->format('Y-m-d H:i:s'),
                'id' => $routine['id'],
                'pid' => PropertyContext::propertyId(),
            ]
        );
        if (in_array($type, ['weekly', 'monthly'], true) && $previousType !== $type) {
            flash('success', 'Elegí el día en que aparece.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines/' . $routine['public_id'] . '/edit');
        }
        $this->redirect(self::habitListPath($type));
    }

    public function reorder(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/routines');
        }
        $routine = $this->findRoutine($params['routine'] ?? '');
        $view = self::habitView((string) $request->input('view', ''));
        $sql = 'SELECT id FROM routines WHERE property_id = :pid AND archived_at IS NULL AND is_active = 1';
        $bind = ['pid' => PropertyContext::propertyId()];
        if ($request->input('view') !== null && $request->input('view') !== '') {
            $types = self::habitViewTypes($view);
            $placeholders = [];
            foreach ($types as $i => $type) {
                $key = 't' . $i;
                $placeholders[] = ':' . $key;
                $bind[$key] = $type;
            }
            $sql .= ' AND frequency_type IN (' . implode(',', $placeholders) . ')';
        }
        $sql .= ' ORDER BY sort_order ASC, title ASC, id ASC';
        $ids = array_map(static fn ($row) => (int) $row['id'], Connection::fetchAll($sql, $bind));
        $current = array_search((int) $routine['id'], $ids, true);
        if ($current === false) {
            $this->redirect((string) ($request->input('redirect') ?: '/properties/' . PropertyContext::property()['public_id'] . '/routines'));
        }
        $direction = (string) $request->input('direction', '');
        $target = $current;
        if ($direction === 'up') {
            $target = max(0, $current - 1);
        } elseif ($direction === 'down') {
            $target = min(count($ids) - 1, $current + 1);
        } elseif ($request->input('position') !== null && $request->input('position') !== '') {
            $target = max(0, min(count($ids) - 1, ((int) $request->input('position')) - 1));
        }
        $moved = $ids[$current];
        array_splice($ids, $current, 1);
        array_splice($ids, $target, 0, [$moved]);
        foreach ($ids as $index => $id) {
            Connection::query(
                'UPDATE routines SET sort_order = :ord WHERE id = :id AND property_id = :pid',
                ['ord' => $index + 1, 'id' => $id, 'pid' => PropertyContext::propertyId()]
            );
        }
        $redirect = (string) ($request->input('redirect') ?: '/properties/' . PropertyContext::property()['public_id'] . '/routines');
        $this->redirect($redirect);
    }

    private static function habitView(string $view): string
    {
        return in_array($view, ['daily', 'weekly', 'monthly'], true) ? $view : 'daily';
    }

    /** @return list<string> */
    private static function habitViewTypes(string $view): array
    {
        return match (self::habitView($view)) {
            'weekly' => ['weekly'],
            'monthly' => ['monthly', 'yearly'],
            default => ['daily'],
        };
    }

    private static function habitListPath(string $frequencyType): string
    {
        $view = match ($frequencyType) {
            'weekly' => 'weekly',
            'monthly', 'yearly' => 'monthly',
            default => 'daily',
        };

        return '/properties/' . PropertyContext::property()['public_id'] . '/routines?view=' . $view;
    }

    /** @return array{0: string, 1: int} */
    private static function parseFrequency(string $raw): array
    {
        $allowed = [
            'daily:1' => ['daily', 1],
            'daily:2' => ['daily', 2],
            'weekly:1' => ['weekly', 1],
            'weekly:2' => ['weekly', 2],
            'monthly:1' => ['monthly', 1],
            'yearly:1' => ['yearly', 1],
        ];
        if (isset($allowed[$raw])) {
            return $allowed[$raw];
        }
        return ['weekly', 1];
    }

    public function archive(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect($this->routineListPath($request));
        }

        $routine = $this->findRoutine($params['routine'] ?? '');
        Connection::query(
            'UPDATE routines SET archived_at = NOW(), is_active = 0, updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            ['id' => $routine['id'], 'pid' => PropertyContext::propertyId()]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'routine', (int) $routine['id'], 'archived');
        flash('success', 'Hábito eliminado.');
        $this->redirect($this->routineListPath($request, (string) ($routine['category'] ?? 'general')));
    }

    public function toggleActive(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect($this->routineListPath($request));
        }

        $routine = $this->findRoutine($params['routine'] ?? '');
        $active = (int) $routine['is_active'] === 1 ? 0 : 1;
        Connection::query(
            'UPDATE routines SET is_active = :a, updated_at = NOW() WHERE id = :id AND property_id = :pid',
            ['a' => $active, 'id' => $routine['id'], 'pid' => PropertyContext::propertyId()]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'routine', (int) $routine['id'], $active ? 'activated' : 'paused');
        flash('success', $active ? 'Rutina activada.' : 'Rutina pausada.');
        $this->redirect($this->routineListPath($request, (string) ($routine['category'] ?? 'general')));
    }

    private function routineListPath(Request $request, string $category = 'general'): string
    {
        $pid = PropertyContext::property()['public_id'];
        if ($request->input('redirect')) {
            return (string) $request->input('redirect');
        }
        return match ($category) {
            'cleaning' => '/properties/' . $pid . '/cleaning',
            'laundry' => '/properties/' . $pid . '/laundry',
            default => '/properties/' . $pid . '/routines',
        };
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

    public static function upcoming(string $type, int $interval, int $schedule, DateTimeImmutable $from, bool $includeFrom): DateTimeImmutable
    {
        $interval = max(1, $interval);
        $from = $from->setTime(0, 0);
        if ($type === 'weekly') {
            $schedule = max(1, min(7, $schedule));
            $cursor = $includeFrom ? $from : $from->modify('+1 day');
            for ($i = 0; $i < 7 && (int) $cursor->format('N') !== $schedule; $i++) {
                $cursor = $cursor->modify('+1 day');
            }
            if (!$includeFrom && $interval > 1) {
                $cursor = $cursor->modify('+' . (($interval - 1) * 7) . ' days');
            }

            return $cursor;
        }

        $schedule = max(1, min(31, $schedule));
        $month = $from->modify('first day of this month');
        for ($i = 0; $i < 36; $i++) {
            if ($i % $interval === 0) {
                $candidate = self::monthDay($month, $schedule);
                if ($includeFrom ? $candidate >= $from : $candidate > $from) {
                    return $candidate;
                }
            }
            $month = $month->modify('+1 month');
        }

        return $from;
    }

    /** @param array<string, mixed> $row */
    public static function occursOn(array $row, string $day): bool
    {
        $type = (string) ($row['frequency_type'] ?? '');
        $schedule = (int) ($row['schedule_day'] ?? 0);
        $interval = max(1, (int) ($row['frequency_interval'] ?? 1));
        $created = substr((string) ($row['created_at'] ?? $day), 0, 10);
        if ($created !== '' && $day < $created) {
            return false;
        }
        $date = new DateTimeImmutable($day);
        if ($type === 'weekly' && $schedule >= 1 && $schedule <= 7) {
            if ((int) $date->format('N') !== $schedule) {
                return false;
            }
            if ($interval === 1) {
                return true;
            }
            $anchor = new DateTimeImmutable($created !== '' ? $created : $day);
            while ((int) $anchor->format('N') !== $schedule) {
                $anchor = $anchor->modify('+1 day');
            }
            if ($date < $anchor) {
                return false;
            }

            return ((int) $anchor->diff($date)->days) % ($interval * 7) === 0;
        }
        if ($type === 'monthly' && $schedule >= 1 && $schedule <= 31) {
            if ((int) $date->format('j') !== min($schedule, (int) $date->format('t'))) {
                return false;
            }
            if ($interval === 1) {
                return true;
            }
            $anchor = new DateTimeImmutable($created !== '' ? $created : $day);
            $months = ((int) $date->format('Y') - (int) $anchor->format('Y')) * 12
                + ((int) $date->format('n') - (int) $anchor->format('n'));

            return $months >= 0 && $months % $interval === 0;
        }

        return false;
    }

    private static function monthDay(DateTimeImmutable $month, int $schedule): DateTimeImmutable
    {
        $day = min($schedule, (int) $month->format('t'));

        return $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day);
    }

    private static function scheduleFromRequest(Request $request, string $type): ?int
    {
        if ($type === 'weekly') {
            $day = (int) $request->input('weekday');

            return ($day >= 1 && $day <= 7) ? $day : null;
        }
        if ($type === 'monthly') {
            $day = (int) $request->input('monthday');

            return ($day >= 1 && $day <= 31) ? $day : null;
        }

        return 0;
    }

    private static function createdMessage(string $type, int $schedule): string
    {
        $weekdays = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        if ($type === 'weekly' && isset($weekdays[$schedule])) {
            return 'Hábito creado. Aparece los ' . $weekdays[$schedule] . '.';
        }
        if ($type === 'monthly' && $schedule >= 1) {
            return 'Hábito creado. Aparece el día ' . $schedule . ' de cada mes.';
        }

        return 'Hábito creado. Ya está en el día de hoy.';
    }

    /** @param array<string, mixed> $routine */
    private static function dueAfterUndo(array $routine): DateTimeImmutable
    {
        $type = (string) ($routine['frequency_type'] ?? '');
        $schedule = (int) ($routine['schedule_day'] ?? 0);
        if (in_array($type, ['weekly', 'monthly'], true) && $schedule > 0) {
            return self::upcoming($type, (int) ($routine['frequency_interval'] ?? 1), $schedule, new DateTimeImmutable('today'), true);
        }

        return new DateTimeImmutable('today');
    }

    /**
     * Hábitos del día: pendientes arriba y los hechos hoy abajo, hasta que el día se renueva.
     *
     * @return array{items: list<array<string, mixed>>, paused: list<array<string, mixed>>, done: int, total: int}
     */
    public static function forDay(int $propertyId, string $day, string $category = '', bool $all = false): array
    {
        $allowed = ['general', 'cleaning', 'laundry', 'garden', 'pets', 'other'];
        $sql = 'SELECT r.*, COALESCE(NULLIF(u.name, \'\'), m.display_name) AS assignee_name
             FROM routines r
             LEFT JOIN property_members m ON m.id = r.assignee_member_id
             LEFT JOIN users u ON u.id = m.user_id
             WHERE r.property_id = :pid AND r.archived_at IS NULL';
        $bind = ['pid' => $propertyId];
        if (in_array($category, $allowed, true)) {
            $sql .= ' AND r.category = :cat';
            $bind['cat'] = $category;
        }
        $sql .= ' ORDER BY r.sort_order ASC, r.title ASC, r.id ASC';
        $rows = Connection::fetchAll($sql, $bind);

        $datesByRoutine = [];
        foreach (Connection::fetchAll(
            'SELECT routine_id, DATE(executed_at) AS d FROM routine_executions
             WHERE property_id = :pid AND executed_at >= DATE_SUB(:day, INTERVAL 180 DAY)',
            ['pid' => $propertyId, 'day' => $day . ' 00:00:00']
        ) as $exec) {
            $datesByRoutine[(int) $exec['routine_id']][] = (string) $exec['d'];
        }

        $items = [];
        $paused = [];
        $dueTotal = 0;
        $dueDone = 0;
        $isToday = $day === date('Y-m-d');
        $listPositions = [];
        $listCounts = ['daily' => 0, 'weekly' => 0, 'monthly' => 0];
        foreach ($rows as $row) {
            if ((int) $row['is_active'] !== 1) {
                continue;
            }
            $bucket = match ((string) $row['frequency_type']) {
                'weekly' => 'weekly',
                'monthly', 'yearly' => 'monthly',
                default => 'daily',
            };
            $listCounts[$bucket]++;
            $listPositions[(int) $row['id']] = $listCounts[$bucket];
        }
        foreach ($rows as $row) {
            $doneOnDay = substr((string) ($row['last_executed_at'] ?? ''), 0, 10) === $day;
            $dueOn = substr((string) ($row['next_due_at'] ?? ''), 0, 10);
            $type = (string) $row['frequency_type'];
            $schedule = (int) ($row['schedule_day'] ?? 0);
            $pinned = in_array($type, ['weekly', 'monthly'], true) && $schedule > 0;
            if ($pinned) {
                $onDay = self::occursOn($row, $day);
                $countsToday = $onDay || $doneOnDay;
                $row['overdue'] = $onDay && !$doneOnDay && $day < date('Y-m-d');
            } else {
                $isDue = $dueOn === '' || $dueOn <= $day;
                $countsToday = $isToday ? ($doneOnDay || $isDue) : ($doneOnDay || $dueOn === $day);
                $row['overdue'] = !$doneOnDay && $dueOn !== '' && $dueOn < $day;
            }
            $row['done_today'] = $doneOnDay;
            $row['due_today'] = $countsToday;
            $row['list_position'] = $listPositions[(int) $row['id']] ?? 0;
            $gap = match ((string) $row['frequency_type']) {
                'daily' => max(1, (int) $row['frequency_interval']),
                'weekly' => max(1, (int) $row['frequency_interval']) * 7,
                'monthly' => max(1, (int) $row['frequency_interval']) * 31,
                default => max(1, (int) $row['frequency_interval']) * 366,
            };
            $row['streak'] = self::streak($datesByRoutine[(int) $row['id']] ?? [], $gap, $day);
            if ((int) $row['is_active'] !== 1) {
                $paused[] = $row;
                continue;
            }
            if ($countsToday) {
                $dueTotal++;
                if ($doneOnDay) {
                    $dueDone++;
                }
            }
            if ($all || $countsToday) {
                $items[] = $row;
            }
        }

        return [
            'items' => $items,
            'paused' => $paused,
            'done' => $dueDone,
            'total' => $dueTotal,
        ];
    }

    /** @param list<string> $dates */
    private static function streak(array $dates, int $maxGapDays, string $today): int
    {
        $dates = array_values(array_unique($dates));
        rsort($dates);
        if ($dates === []) {
            return 0;
        }
        $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
        if ($dates[0] !== $today && $dates[0] !== $yesterday) {
            return 0;
        }
        $count = 1;
        $limit = count($dates);
        for ($i = 1; $i < $limit; $i++) {
            $gap = (strtotime($dates[$i - 1]) - strtotime($dates[$i])) / 86400;
            if ($gap >= 1 && $gap <= $maxGapDays + 1) {
                $count++;
                continue;
            }
            break;
        }
        return $count;
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
