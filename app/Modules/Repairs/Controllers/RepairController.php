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
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $repairs = Connection::fetchAll(
            'SELECT r.*, s.name AS space_name
             FROM repair_records r
             LEFT JOIN spaces s ON s.id = r.space_id
             WHERE r.property_id = :pid AND r.archived_at IS NULL
             ORDER BY FIELD(r.status, \'open\', \'in_progress\', \'closed\'), r.reported_at DESC',
            ['pid' => $pid]
        );

        $this->view('repairs/index', [
            'title' => 'Reparaciones',
            'property' => PropertyContext::property(),
            'repairs' => $repairs,
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

        $pid = PropertyContext::propertyId();
        Connection::query(
            'INSERT INTO repair_records (
                public_id, property_id, space_id, title, problem_description, reported_at,
                status, provider_name, budget, currency, notes, created_by, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :title, :problem_description, :reported_at,
                :status, :provider_name, :budget, :currency, :notes, :created_by, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                    ? (int) $request->input('space_id') : null,
                'title' => (string) $request->input('title'),
                'problem_description' => $request->input('problem_description'),
                'reported_at' => (string) $request->input('reported_at'),
                'status' => 'open',
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

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        $repair = $this->findRepair($params['repair'] ?? '');
        $status = (string) ($request->input('status') ?: $repair['status']);
        if (!in_array($status, ['open', 'in_progress', 'closed'], true)) {
            flash('error', 'Estado inválido.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/repairs');
        }

        Connection::query(
            'UPDATE repair_records SET
                title = :title,
                problem_description = :problem_description,
                status = :status,
                provider_name = :provider_name,
                budget = :budget,
                cost = :cost,
                currency = :currency,
                parts_replaced = :parts_replaced,
                notes = :notes,
                updated_at = NOW()
             WHERE id = :id',
            [
                'title' => (string) ($request->input('title') ?: $repair['title']),
                'problem_description' => $request->input('problem_description', $repair['problem_description']),
                'status' => $status,
                'provider_name' => $request->input('provider_name', $repair['provider_name']),
                'budget' => $request->input('budget') !== '' ? $request->input('budget') : $repair['budget'],
                'cost' => $request->input('cost') !== '' ? $request->input('cost') : $repair['cost'],
                'currency' => $request->input('currency') ?: $repair['currency'],
                'parts_replaced' => $request->input('parts_replaced', $repair['parts_replaced']),
                'notes' => $request->input('notes', $repair['notes']),
                'id' => $repair['id'],
            ]
        );

        ActivityLogger::log(PropertyContext::propertyId(), 'repair_record', (int) $repair['id'], 'updated');
        flash('success', 'Reparación actualizada.');
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
                'UPDATE repair_records SET status = \'closed\', closed_at = :closed_at,
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

        flash('success', 'Reparación cerrada.');
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
