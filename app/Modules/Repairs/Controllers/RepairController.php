<?php

declare(strict_types=1);

namespace Premisely\Modules\Repairs\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\ExpenseHelper;
use Premisely\Shared\PropertyContext;

final class RepairController extends Controller
{
    public const STATUSES = [
        'pendiente' => 'Pendiente',
        'en_curso' => 'En curso',
        'hecho' => 'Hecho',
        'cancelada' => 'Cancelada',
    ];

    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $repairs = Connection::fetchAll(
            'SELECT r.*, s.name AS space_name
             FROM repair_records r
             LEFT JOIN spaces s ON s.id = r.space_id
             WHERE r.property_id = :pid AND r.archived_at IS NULL
             ORDER BY FIELD(r.status, \'pendiente\', \'en_curso\', \'hecho\', \'cancelada\'), r.reported_at DESC',
            ['pid' => $pid]
        );

        $this->view('repairs/index', [
            'title' => 'Reparaciones',
            'property' => PropertyContext::property(),
            'repairs' => $repairs,
            'statuses' => self::STATUSES,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }
        $pid = PropertyContext::propertyId();
        $this->view('repairs/create', [
            'title' => 'Nueva reparación',
            'property' => PropertyContext::property(),
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => $pid]
            ),
            'statuses' => self::STATUSES,
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'reported_at' => 'required',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs/create');
        }

        $status = (string) ($request->input('status') ?: 'pendiente');
        if (!isset(self::STATUSES[$status])) {
            $status = 'pendiente';
        }

        $pid = PropertyContext::propertyId();
        Connection::query(
            'INSERT INTO repair_records (
                public_id, property_id, space_id, title, problem_description, reported_at,
                status, closed_at, provider_name, budget, currency, notes, created_by, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :title, :problem_description, :reported_at,
                :status, :closed_at, :provider_name, :budget, :currency, :notes, :created_by, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                    ? (int) $request->input('space_id') : null,
                'title' => (string) $request->input('title'),
                'problem_description' => $request->input('problem_description'),
                'reported_at' => (string) $request->input('reported_at'),
                'status' => $status,
                'closed_at' => $status === 'hecho' ? (string) $request->input('reported_at') : null,
                'provider_name' => $request->input('provider_name'),
                'budget' => $request->input('budget') !== '' ? $request->input('budget') : null,
                'currency' => $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS'),
                'notes' => $request->input('notes'),
                'created_by' => Auth::id(),
            ]
        );

        ActivityLogger::log($pid, 'repair_record', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Reparación registrada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
    }

    public function edit(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }
        $repair = $this->findRepair($params['repair'] ?? '');
        $this->view('repairs/edit', [
            'title' => 'Editar reparación',
            'property' => PropertyContext::property(),
            'repair' => $repair,
            'statuses' => self::STATUSES,
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => PropertyContext::propertyId()]
            ),
            'canEdit' => true,
        ]);
    }

    public function save(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }
        $repair = $this->findRepair($params['repair'] ?? '');
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'reported_at' => 'required',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs/' . $repair['public_id'] . '/edit');
        }
        $status = (string) ($request->input('status') ?: $repair['status']);
        if (!isset(self::STATUSES[$status])) {
            $status = (string) $repair['status'];
        }
        $closedAt = $repair['closed_at'] ?? null;
        if ($status === 'hecho') {
            $closedAt = $closedAt ?: date('Y-m-d');
        } elseif (in_array($status, ['pendiente', 'en_curso'], true)) {
            $closedAt = null;
        }
        Connection::query(
            'UPDATE repair_records SET
                title = :title,
                problem_description = :problem_description,
                reported_at = :reported_at,
                status = :status,
                closed_at = :closed_at,
                provider_name = :provider_name,
                budget = :budget,
                cost = :cost,
                space_id = :space_id,
                notes = :notes,
                updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'title' => (string) $request->input('title'),
                'problem_description' => $request->input('problem_description'),
                'reported_at' => (string) $request->input('reported_at'),
                'status' => $status,
                'closed_at' => $closedAt,
                'provider_name' => $request->input('provider_name'),
                'budget' => $request->input('budget') !== '' ? $request->input('budget') : null,
                'cost' => $request->input('cost') !== '' ? $request->input('cost') : null,
                'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                    ? (int) $request->input('space_id') : null,
                'notes' => $request->input('notes'),
                'id' => $repair['id'],
                'pid' => PropertyContext::propertyId(),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'repair_record', (int) $repair['id'], 'updated');
        flash('success', 'Reparación actualizada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        $repair = $this->findRepair($params['repair'] ?? '');
        $status = (string) ($request->input('status') ?: $repair['status']);
        if (!isset(self::STATUSES[$status])) {
            flash('error', 'Estado inválido.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        $closedAt = $repair['closed_at'] ?? null;
        if ($status === 'hecho') {
            $closedAt = $closedAt ?: date('Y-m-d');
        } elseif (in_array($status, ['pendiente', 'en_curso'], true)) {
            $closedAt = null;
        }

        Connection::query(
            'UPDATE repair_records SET
                status = :status,
                closed_at = :closed_at,
                updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'status' => $status,
                'closed_at' => $closedAt,
                'id' => $repair['id'],
                'pid' => PropertyContext::propertyId(),
            ]
        );

        ActivityLogger::log(PropertyContext::propertyId(), 'repair_record', (int) $repair['id'], 'updated');
        flash('success', 'Estado actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
    }

    public function close(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        $repair = $this->findRepair($params['repair'] ?? '');
        $cost = $request->input('cost');
        $currency = $request->input('currency') ?: ($repair['currency'] ?? PropertyContext::property()['currency'] ?? 'ARS');
        $closedAt = (string) ($request->input('closed_at') ?: date('Y-m-d'));
        $pid = PropertyContext::propertyId();

        Connection::begin();
        try {
            Connection::query(
                'UPDATE repair_records SET status = \'hecho\', closed_at = :closed_at,
                 cost = COALESCE(:cost, cost), currency = :currency, notes = COALESCE(:notes, notes), updated_at = NOW()
                 WHERE id = :id',
                [
                    'closed_at' => $closedAt,
                    'cost' => $cost !== '' && $cost !== null ? $cost : null,
                    'currency' => $currency,
                    'notes' => $request->input('notes'),
                    'id' => $repair['id'],
                ]
            );

            if ($request->input('create_expense') && $cost !== '' && $cost !== null && (float) $cost > 0) {
                ExpenseHelper::create([
                    'property_id' => $pid,
                    'title' => 'Reparación: ' . $repair['title'],
                    'amount' => $cost,
                    'currency' => $currency,
                    'spent_at' => $closedAt,
                    'category_slug' => 'repairs',
                    'source_type' => 'repair_record',
                    'source_id' => (int) $repair['id'],
                ]);
            }

            ActivityLogger::log($pid, 'repair_record', (int) $repair['id'], 'closed');
            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollBack();
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        flash('success', 'Reparación marcada como hecha.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
    }

    public function destroy(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        $repair = $this->findRepair($params['repair'] ?? '');
        Connection::query(
            'UPDATE repair_records SET archived_at = NOW(), updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            ['id' => $repair['id'], 'pid' => PropertyContext::propertyId()]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'repair_record', (int) $repair['id'], 'archived');
        flash('success', 'Reparación eliminada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
    }

    /** @return array<string, mixed> */
    private function findRepair(string $key): array
    {
        $repair = Connection::fetch(
            'SELECT * FROM repair_records
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );

        if (!$repair) {
            flash('error', 'Reparación no encontrada.');
            redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        return $repair;
    }
}
