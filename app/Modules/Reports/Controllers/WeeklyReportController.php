<?php

declare(strict_types=1);

namespace Premisely\Modules\Reports\Controllers;

use DateTimeImmutable;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Http\Response;
use Premisely\Shared\PropertyContext;
use Premisely\Shared\SimplePdf;

final class WeeklyReportController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $data = $this->buildWeekData($request);
        $this->view('weekly_report/index', array_merge($data, [
            'title' => 'Informe semanal',
            'property' => PropertyContext::property(),
            'canEdit' => PropertyContext::canEdit(),
        ]));
    }

    public function pdf(Request $request, array $params): never
    {
        $data = $this->buildWeekData($request);
        $property = PropertyContext::property();
        $currency = (string) ($property['currency'] ?? 'ARS');
        $colors = [
            'task' => [0.055, 0.431, 0.416],
            'repair' => [0.788, 0.416, 0.173],
            'medical' => [0.227, 0.435, 0.659],
            'spend' => [0.702, 0.227, 0.227],
            'habit' => [0.831, 0.627, 0.090],
            'note' => [0.361, 0.396, 0.439],
        ];

        $pdf = new SimplePdf();
        $pdf->banner(
            'Informe semanal',
            (string) ($property['name'] ?? 'Premisely') . '  ·  ' . $data['rangeLabel'],
            'Semana ' . ltrim((string) $data['weekNumber'], '0') . ' del ' . $data['weekYear']
        );
        $pdf->metrics([
            ['label' => 'Tareas resueltas', 'value' => (string) count($data['tasks']), 'color' => $colors['task']],
            ['label' => 'Reparaciones terminadas', 'value' => (string) count($data['repairs']), 'color' => $colors['repair']],
            ['label' => 'Temas médicos resueltos', 'value' => (string) count($data['medical']), 'color' => $colors['medical']],
            ['label' => 'Hábitos repetidos', 'value' => (string) (int) $data['habitsDoneCount'], 'color' => $colors['habit']],
            ['label' => 'Notas leídas', 'value' => (string) count($data['notes']), 'color' => $colors['note']],
        ]);

        $pdf->section('Tareas hechas', $colors['task'], (string) count($data['tasks']));
        if ($data['tasks'] === []) {
            $pdf->empty('Ninguna esta semana.');
        } else {
            foreach ($data['tasks'] as $row) {
                $pdf->item((string) $row['title'], format_when($row['completed_at'] ?? ''), true);
            }
        }

        $pdf->section('Reparaciones hechas', $colors['repair'], (string) count($data['repairs']));
        if ($data['repairs'] === []) {
            $pdf->empty('Ninguna esta semana.');
        } else {
            foreach ($data['repairs'] as $row) {
                $pdf->item((string) $row['title'], format_when($row['completed_at'] ?? ''), true);
            }
        }

        $pdf->section('Temas médicos resueltos', $colors['medical'], (string) count($data['medical']));
        if ($data['medical'] === []) {
            $pdf->empty('Ninguno esta semana.');
        } else {
            foreach ($data['medical'] as $row) {
                $pdf->item((string) $row['title'], format_when($row['completed_at'] ?? ''), true);
            }
        }

        $pdf->section(
            'Gastos de la semana',
            $colors['spend'],
            format_money($data['spendTotal']) . ' ' . $currency
        );
        if ($data['spend'] === []) {
            $pdf->empty('Nada planificado esta semana.');
        } else {
            foreach ($data['spend'] as $row) {
                $done = !empty($row['completed_at']);
                $meta = format_money($row['amount']) . ' ' . (string) ($row['currency'] ?? $currency)
                    . ($done ? ' · Hecho' : ' · Pendiente');
                $pdf->item((string) $row['title'], $meta, $done);
            }
            $pdf->total('Total de la semana: ' . format_money($data['spendTotal']) . ' ' . $currency, $colors['spend']);
        }

        $pdf->section(
            'Hábitos repetidos',
            $colors['habit'],
            (string) (int) $data['habitsDoneCount']
        );
        if ($data['habits'] === []) {
            $pdf->empty('Sin hábitos marcados esta semana.');
        } else {
            foreach ($data['habits'] as $row) {
                $streak = (int) ($row['streak'] ?? 0);
                $times = (int) ($row['times'] ?? 0);
                $meta = $times . ' ' . ($times === 1 ? 'vez' : 'veces')
                    . ($streak > 0 ? ' · racha ' . $streak : '');
                $pdf->item((string) $row['title'], $meta, true);
            }
        }

        $pdf->section('Notas leídas', $colors['note'], (string) count($data['notes']));
        if ($data['notes'] === []) {
            $pdf->empty('Ninguna esta semana.');
        } else {
            foreach ($data['notes'] as $row) {
                $pdf->item((string) $row['title'], format_when($row['completed_at'] ?? ''), true);
            }
        }

        $pdf->footer('Premisely · Informe semanal · Semana ' . $data['weekNumber'] . ' de ' . $data['weekYear']);

        $filename = 'informe-semana-' . $data['weekYear'] . '-W' . $data['weekNumber'] . '.pdf';
        $binary = $pdf->render();
        (new Response(
            $binary,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => (string) strlen($binary),
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]
        ))->send();
    }

    /** @return array<string, mixed> */
    private function buildWeekData(Request $request): array
    {
        $pid = PropertyContext::propertyId();
        $raw = (string) ($request->query('week') ?: date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $raw = date('Y-m-d');
        }
        try {
            $anchor = new DateTimeImmutable($raw);
        } catch (\Exception) {
            $anchor = new DateTimeImmutable('today');
        }
        $weekStart = $anchor->modify('monday this week');
        $weekEnd = $weekStart->modify('+6 days');
        $prevWeek = $weekStart->modify('-7 days')->format('Y-m-d');
        $nextWeek = $weekStart->modify('+7 days')->format('Y-m-d');
        $startTs = $weekStart->format('Y-m-d') . ' 00:00:00';
        $endTs = $weekEnd->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        $startDay = $weekStart->format('Y-m-d');
        $endDay = $weekEnd->format('Y-m-d');
        $months = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $rangeLabel = $weekStart->format('j') . ' '
            . $months[(int) $weekStart->format('n')]
            . ' – ' . $weekEnd->format('j') . ' '
            . $months[(int) $weekEnd->format('n')]
            . ' ' . $weekEnd->format('Y');

        $tasks = Connection::fetchAll(
            'SELECT public_id, title, completed_at
             FROM tasks
             WHERE property_id = :p AND archived_at IS NULL AND status = \'done\'
               AND completed_at IS NOT NULL
               AND completed_at >= :a AND completed_at < :b
               AND (kind IS NULL OR kind NOT IN (\'reparacion\', \'medico\'))
             ORDER BY completed_at, title',
            ['p' => $pid, 'a' => $startTs, 'b' => $endTs]
        );

        $repairs = Connection::fetchAll(
            'SELECT public_id, title, completed_at, \'task\' AS source
             FROM tasks
             WHERE property_id = :p AND archived_at IS NULL AND status = \'done\'
               AND kind = \'reparacion\'
               AND completed_at IS NOT NULL
               AND completed_at >= :a AND completed_at < :b
             ORDER BY completed_at, title',
            ['p' => $pid, 'a' => $startTs, 'b' => $endTs]
        );
        foreach (Connection::fetchAll(
            'SELECT public_id, title, closed_at AS completed_at, \'record\' AS source
             FROM repair_records
             WHERE property_id = :p AND archived_at IS NULL
               AND status IN (\'hecho\', \'done\')
               AND closed_at IS NOT NULL
               AND closed_at >= :a AND closed_at <= :b
             ORDER BY closed_at, title',
            ['p' => $pid, 'a' => $startDay, 'b' => $endDay]
        ) as $row) {
            $repairs[] = $row;
        }

        $medical = Connection::fetchAll(
            'SELECT public_id, title, completed_at
             FROM tasks
             WHERE property_id = :p AND archived_at IS NULL AND status = \'done\'
               AND kind = \'medico\'
               AND completed_at IS NOT NULL
               AND completed_at >= :a AND completed_at < :b
             ORDER BY completed_at, title',
            ['p' => $pid, 'a' => $startTs, 'b' => $endTs]
        );

        $spend = Connection::fetchAll(
            'SELECT public_id, title, amount, currency, completed_at
             FROM week_spend_items
             WHERE property_id = :p AND archived_at IS NULL AND week_start = :week
             ORDER BY completed_at IS NULL DESC, id',
            ['p' => $pid, 'week' => $startDay]
        );
        $spendTotal = 0.0;
        foreach ($spend as $row) {
            $spendTotal += (float) $row['amount'];
        }

        $notes = Connection::fetchAll(
            'SELECT public_id, title, completed_at, note_date
             FROM calendar_notes
             WHERE property_id = :p AND archived_at IS NULL
               AND completed_at IS NOT NULL
               AND completed_at >= :a AND completed_at < :b
             ORDER BY completed_at, title',
            ['p' => $pid, 'a' => $startTs, 'b' => $endTs]
        );

        $habitRows = Connection::fetchAll(
            'SELECT r.id, r.public_id, r.title, r.frequency_type, r.frequency_interval,
                    COUNT(e.id) AS times
             FROM routine_executions e
             INNER JOIN routines r ON r.id = e.routine_id
             WHERE e.property_id = :p AND r.archived_at IS NULL
               AND e.executed_at >= :a AND e.executed_at < :b
             GROUP BY r.id, r.public_id, r.title, r.frequency_type, r.frequency_interval
             ORDER BY times DESC, r.title',
            ['p' => $pid, 'a' => $startTs, 'b' => $endTs]
        );
        $datesByRoutine = [];
        foreach (Connection::fetchAll(
            'SELECT routine_id, executed_at FROM routine_executions
             WHERE property_id = :p AND executed_at >= :from AND executed_at < :b',
            [
                'p' => $pid,
                'from' => $weekStart->modify('-180 days')->format('Y-m-d') . ' 00:00:00',
                'b' => $endTs,
            ]
        ) as $exec) {
            $datesByRoutine[(int) $exec['routine_id']][] = substr((string) $exec['executed_at'], 0, 10);
        }
        $streakAsOf = $weekEnd->format('Y-m-d');
        if ($streakAsOf > date('Y-m-d')) {
            $streakAsOf = date('Y-m-d');
        }
        $habits = [];
        $habitsDoneCount = 0;
        foreach ($habitRows as $row) {
            $times = (int) $row['times'];
            $habitsDoneCount += $times;
            $gap = match ((string) $row['frequency_type']) {
                'daily' => max(1, (int) $row['frequency_interval']),
                'weekly' => max(1, (int) $row['frequency_interval']) * 7,
                'monthly' => max(1, (int) $row['frequency_interval']) * 31,
                default => max(1, (int) $row['frequency_interval']) * 366,
            };
            $habits[] = [
                'public_id' => $row['public_id'],
                'title' => $row['title'],
                'times' => $times,
                'streak' => self::streak($datesByRoutine[(int) $row['id']] ?? [], $gap, $streakAsOf),
            ];
        }

        return [
            'weekStart' => $startDay,
            'weekEnd' => $endDay,
            'prevWeek' => $prevWeek,
            'nextWeek' => $nextWeek,
            'weekNumber' => $weekStart->format('W'),
            'weekYear' => $weekStart->format('o'),
            'rangeLabel' => $rangeLabel,
            'isCurrentWeek' => $startDay === (new DateTimeImmutable('today'))->modify('monday this week')->format('Y-m-d'),
            'tasks' => $tasks,
            'repairs' => $repairs,
            'medical' => $medical,
            'spend' => $spend,
            'spendTotal' => $spendTotal,
            'habits' => $habits,
            'habitsDoneCount' => $habitsDoneCount,
            'notes' => $notes,
        ];
    }

    /** @param list<string> $dates */
    private static function streak(array $dates, int $maxGapDays, string $asOf): int
    {
        $dates = array_values(array_unique(array_filter(
            $dates,
            static fn (string $d): bool => $d <= $asOf
        )));
        rsort($dates);
        if ($dates === []) {
            return 0;
        }
        $yesterday = date('Y-m-d', strtotime($asOf . ' -1 day'));
        if ($dates[0] !== $asOf && $dates[0] !== $yesterday) {
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
}
