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
        $pdf = new SimplePdf();
        $pdf->title('Informe semanal · ' . (string) ($property['name'] ?? 'Premisely'));
        $pdf->subtitle(
            'Semana ' . $data['weekNumber'] . ' de ' . $data['weekYear']
            . ' · ' . $data['rangeLabel']
        );
        $pdf->blank();

        $pdf->heading('Tareas hechas (' . count($data['tasks']) . ')');
        if ($data['tasks'] === []) {
            $pdf->line('Ninguna.');
        } else {
            foreach ($data['tasks'] as $row) {
                $pdf->line('• ' . (string) $row['title']);
            }
        }

        $pdf->heading('Reparaciones hechas (' . count($data['repairs']) . ')');
        if ($data['repairs'] === []) {
            $pdf->line('Ninguna.');
        } else {
            foreach ($data['repairs'] as $row) {
                $pdf->line('• ' . (string) $row['title']);
            }
        }

        $pdf->heading('Temas médicos hechos (' . count($data['medical']) . ')');
        if ($data['medical'] === []) {
            $pdf->line('Ninguno.');
        } else {
            foreach ($data['medical'] as $row) {
                $pdf->line('• ' . (string) $row['title']);
            }
        }

        $pdf->heading('Gastos de la semana (' . format_money($data['spendTotal']) . ' ' . $currency . ')');
        if ($data['spend'] === []) {
            $pdf->line('Ninguno.');
        } else {
            foreach ($data['spend'] as $row) {
                $done = !empty($row['completed_at']) ? ' [hecho]' : '';
                $pdf->line(
                    '• ' . (string) $row['title']
                    . ' — ' . format_money($row['amount']) . ' '
                    . (string) ($row['currency'] ?? $currency)
                    . $done
                );
            }
            $pdf->line('Total: ' . format_money($data['spendTotal']) . ' ' . $currency);
        }

        $pdf->heading(
            'Hábitos · ' . (int) $data['habitsDoneCount'] . ' hechos en la semana'
        );
        if ($data['habits'] === []) {
            $pdf->line('Sin registros esta semana.');
        } else {
            foreach ($data['habits'] as $row) {
                $streak = (int) ($row['streak'] ?? 0);
                $times = (int) ($row['times'] ?? 0);
                $pdf->line(
                    '• ' . (string) $row['title']
                    . ' — ' . $times . ' vez' . ($times === 1 ? '' : 'es')
                    . ($streak > 0 ? ' · racha ' . $streak : '')
                );
            }
        }

        $pdf->heading('Notas hechas (' . count($data['notes']) . ')');
        if ($data['notes'] === []) {
            $pdf->line('Ninguna.');
        } else {
            foreach ($data['notes'] as $row) {
                $pdf->line('• ' . (string) $row['title']);
            }
        }

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
