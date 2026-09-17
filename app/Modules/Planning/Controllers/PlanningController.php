<?php

declare(strict_types=1);

namespace Premisely\Modules\Planning\Controllers;

use DateInterval;
use DateTimeImmutable;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class PlanningController extends Controller
{
    public function weekly(Request $request, array $params): never
    {
        $weekStart = $this->weekStart((string) $request->query('week', ''));
        $start = new DateTimeImmutable($weekStart);
        $end = $start->add(new DateInterval('P6D'));
        $pid = PropertyContext::propertyId();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $start->add(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
            $days[$d] = [
                'tasks' => [],
                'routines' => [],
                'meals' => [],
                'maintenance' => [],
                'services' => [],
                'laundry' => [],
            ];
        }

        foreach (Connection::fetchAll(
            'SELECT * FROM tasks WHERE property_id = :p AND archived_at IS NULL AND due_date BETWEEN :a AND :b ORDER BY due_date, title',
            ['p' => $pid, 'a' => $weekStart, 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $days[$row['due_date']]['tasks'][] = $row;
        }

        foreach (Connection::fetchAll(
            'SELECT * FROM routines WHERE property_id = :p AND archived_at IS NULL AND is_active = 1
               AND next_due_at IS NOT NULL AND DATE(next_due_at) BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $weekStart, 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $key = substr((string) $row['next_due_at'], 0, 10);
            if (($row['category'] ?? '') === 'laundry') {
                $days[$key]['laundry'][] = $row;
            } elseif (($row['category'] ?? '') === 'cleaning') {
                $days[$key]['routines'][] = $row;
            } else {
                $days[$key]['routines'][] = $row;
            }
        }

        foreach (Connection::fetchAll(
            'SELECT e.* FROM meal_entries e
             INNER JOIN meal_plans p ON p.id = e.meal_plan_id
             WHERE e.property_id = :pid AND e.meal_date BETWEEN :a AND :b
             ORDER BY e.meal_date, e.slot',
            ['pid' => $pid, 'a' => $weekStart, 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $days[$row['meal_date']]['meals'][] = $row;
        }

        foreach (Connection::fetchAll(
            'SELECT * FROM maintenance_plans WHERE property_id = :p AND archived_at IS NULL AND next_due_at BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $weekStart, 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $days[$row['next_due_at']]['maintenance'][] = $row;
        }

        foreach (Connection::fetchAll(
            'SELECT * FROM property_services WHERE property_id = :p AND archived_at IS NULL AND next_due_date BETWEEN :a AND :b',
            ['p' => $pid, 'a' => $weekStart, 'b' => $end->format('Y-m-d')]
        ) as $row) {
            $days[$row['next_due_date']]['services'][] = $row;
        }

        $this->view('planning/weekly', [
            'title' => 'Planificación semanal',
            'property' => PropertyContext::property(),
            'weekStart' => $weekStart,
            'weekEnd' => $end->format('Y-m-d'),
            'days' => $days,
            'prev' => $start->sub(new DateInterval('P7D'))->format('Y-m-d'),
            'next' => $start->add(new DateInterval('P7D'))->format('Y-m-d'),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    private function weekStart(string $raw): string
    {
        try {
            $date = $raw !== '' ? new DateTimeImmutable($raw) : new DateTimeImmutable('monday this week');
        } catch (\Exception) {
            $date = new DateTimeImmutable('monday this week');
        }
        return $date->modify('monday this week')->format('Y-m-d');
    }
}
