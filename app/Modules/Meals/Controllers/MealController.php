<?php

declare(strict_types=1);

namespace Premisely\Modules\Meals\Controllers;

use DateInterval;
use DateTimeImmutable;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class MealController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $weekStart = $this->weekStart((string) $request->query('week', ''));
        $plan = $this->ensurePlan($weekStart);
        $entries = Connection::fetchAll(
            'SELECT * FROM meal_entries WHERE meal_plan_id = :id ORDER BY meal_date, FIELD(slot, \'desayuno\',\'almuerzo\',\'merienda\',\'cena\'), id',
            ['id' => $plan['id']]
        );
        $byDate = [];
        foreach ($entries as $e) {
            $byDate[$e['meal_date']][] = $e;
        }
        $days = [];
        $cursor = new DateTimeImmutable($weekStart);
        for ($i = 0; $i < 7; $i++) {
            $d = $cursor->add(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
            $days[] = $d;
        }

        $this->view('meals/index', [
            'title' => 'Comidas',
            'property' => PropertyContext::property(),
            'plan' => $plan,
            'weekStart' => $weekStart,
            'days' => $days,
            'byDate' => $byDate,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals');
        }
        $weekStart = $this->weekStart((string) $request->query('week', ''));
        $this->view('meals/create', [
            'title' => 'Agregar comida',
            'property' => PropertyContext::property(),
            'weekStart' => $weekStart,
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals');
        }
        $weekStart = $this->weekStart((string) $request->input('week_start', ''));
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            flash('error', 'El título es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals/create?week=' . $weekStart);
        }
        $plan = $this->ensurePlan($weekStart);
        Connection::query(
            'INSERT INTO meal_entries (public_id, meal_plan_id, property_id, meal_date, slot, title, notes, created_at)
             VALUES (:pid, :plan, :prop, :date, :slot, :title, :notes, NOW())',
            [
                'pid' => ulid(),
                'plan' => $plan['id'],
                'prop' => PropertyContext::propertyId(),
                'date' => $request->input('meal_date'),
                'slot' => $request->input('slot', 'almuerzo'),
                'title' => $title,
                'notes' => $request->input('notes'),
            ]
        );
        flash('success', 'Comida agregada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals?week=' . $weekStart);
    }

    public function edit(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals');
        }
        $meal = Connection::fetch(
            'SELECT * FROM meal_entries WHERE public_id = :pid AND property_id = :prop LIMIT 1',
            ['pid' => $params['meal'] ?? '', 'prop' => PropertyContext::propertyId()]
        );
        if (!$meal) {
            flash('error', 'Comida no encontrada.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals');
        }
        $weekStart = $this->weekStart((string) ($meal['meal_date'] ?? ''));
        $this->view('meals/edit', [
            'title' => 'Editar comida',
            'property' => PropertyContext::property(),
            'meal' => $meal,
            'weekStart' => $weekStart,
            'canEdit' => true,
        ]);
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals');
        }
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            flash('error', 'El título es obligatorio.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals/' . ($params['meal'] ?? '') . '/edit');
        }
        Connection::query(
            'UPDATE meal_entries SET meal_date = :date, slot = :slot, title = :title, notes = :notes
             WHERE public_id = :pid AND property_id = :prop',
            [
                'date' => $request->input('meal_date'),
                'slot' => $request->input('slot', 'almuerzo'),
                'title' => $title,
                'notes' => $request->input('notes'),
                'pid' => $params['meal'] ?? '',
                'prop' => PropertyContext::propertyId(),
            ]
        );
        flash('success', 'Comida actualizada.');
        $weekStart = $this->weekStart((string) ($request->input('week_start') ?: $request->input('meal_date') ?: ''));
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals?week=' . $weekStart);
    }

    public function destroy(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals');
        }
        Connection::query(
            'DELETE FROM meal_entries WHERE public_id = :pid AND property_id = :prop',
            ['pid' => $params['meal'] ?? '', 'prop' => PropertyContext::propertyId()]
        );
        flash('success', 'Comida eliminada.');
        if ($request->input('redirect')) {
            $this->redirect((string) $request->input('redirect'));
        }
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/meals');
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

    /** @return array<string, mixed> */
    private function ensurePlan(string $weekStart): array
    {
        $plan = Connection::fetch(
            'SELECT * FROM meal_plans WHERE property_id = :p AND week_start = :w LIMIT 1',
            ['p' => PropertyContext::propertyId(), 'w' => $weekStart]
        );
        if ($plan) {
            return $plan;
        }
        Connection::query(
            'INSERT INTO meal_plans (public_id, property_id, week_start, created_at) VALUES (:pid, :prop, :w, NOW())',
            ['pid' => ulid(), 'prop' => PropertyContext::propertyId(), 'w' => $weekStart]
        );
        return Connection::fetch('SELECT * FROM meal_plans WHERE id = :id', ['id' => Connection::lastInsertId()]) ?? [];
    }
}
