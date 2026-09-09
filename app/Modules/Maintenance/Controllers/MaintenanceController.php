<?php

declare(strict_types=1);

namespace Premisely\Modules\Maintenance\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\ExpenseHelper;
use Premisely\Shared\PropertyContext;
use DateInterval;
use DateTimeImmutable;

final class MaintenanceController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $plans = Connection::fetchAll(
            'SELECT * FROM maintenance_plans
             WHERE property_id = :pid AND archived_at IS NULL
             ORDER BY next_due_at IS NULL, next_due_at, title',
            ['pid' => $pid]
        );
        $records = Connection::fetchAll(
            'SELECT * FROM maintenance_records
             WHERE property_id = :pid
             ORDER BY performed_at DESC LIMIT 30',
            ['pid' => $pid]
        );

        $this->view('maintenance/index', [
            'title' => 'Mantenimiento',
            'property' => PropertyContext::property(),
            'plans' => $plans,
            'records' => $records,
            'spaces' => Connection::fetchAll(
                'SELECT id, name FROM spaces WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
                ['pid' => $pid]
            ),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function storePlan(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/maintenance');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'frequency_type' => 'required|in:monthly,yearly,weekly',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/maintenance');
        }

        $interval = max(1, (int) ($request->input('frequency_interval') ?: 1));
        $next = (new DateTimeImmutable('now'))->add(new DateInterval(
            match ((string) $request->input('frequency_type')) {
                'weekly' => 'P' . ($interval * 7) . 'D',
                'monthly' => 'P' . $interval . 'M',
                default => 'P' . $interval . 'Y',
            }
        ));

        $pid = PropertyContext::propertyId();
        Connection::query(
            'INSERT INTO maintenance_plans (
                public_id, property_id, space_id, title, description, frequency_type, frequency_interval,
                provider_name, estimated_cost, currency, next_due_at, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :title, :description, :frequency_type, :frequency_interval,
                :provider_name, :estimated_cost, :currency, :next_due_at, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                    ? (int) $request->input('space_id') : null,
                'title' => (string) $request->input('title'),
                'description' => $request->input('description'),
                'frequency_type' => (string) $request->input('frequency_type'),
                'frequency_interval' => $interval,
                'provider_name' => $request->input('provider_name'),
                'estimated_cost' => $request->input('estimated_cost') !== '' ? $request->input('estimated_cost') : null,
                'currency' => $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS'),
                'next_due_at' => $next->format('Y-m-d'),
            ]
        );

        ActivityLogger::log($pid, 'maintenance_plan', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Plan de mantenimiento creado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/maintenance');
    }

    public function storeRecord(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/maintenance');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'performed_at' => 'required',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/maintenance');
        }

        $pid = PropertyContext::propertyId();
        $planId = $request->input('plan_id');
        $planId = ($planId === null || $planId === '') ? null : (int) $planId;
        $cost = $request->input('cost');
        $currency = $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS');

        Connection::begin();
        try {
            Connection::query(
                'INSERT INTO maintenance_records (
                    public_id, property_id, plan_id, space_id, title, performed_at, cost, currency,
                    provider_name, notes, created_by, created_at
                 ) VALUES (
                    :public_id, :property_id, :plan_id, :space_id, :title, :performed_at, :cost, :currency,
                    :provider_name, :notes, :created_by, NOW()
                 )',
                [
                    'public_id' => ulid(),
                    'property_id' => $pid,
                    'plan_id' => $planId,
                    'space_id' => $request->input('space_id') !== '' && $request->input('space_id') !== null
                        ? (int) $request->input('space_id') : null,
                    'title' => (string) $request->input('title'),
                    'performed_at' => (string) $request->input('performed_at'),
                    'cost' => $cost !== '' && $cost !== null ? $cost : null,
                    'currency' => $currency,
                    'provider_name' => $request->input('provider_name'),
                    'notes' => $request->input('notes'),
                    'created_by' => Auth::id(),
                ]
            );
            $recordId = (int) Connection::lastInsertId();

            if ($planId) {
                Connection::query(
                    'UPDATE maintenance_plans SET last_done_at = :done, updated_at = NOW() WHERE id = :id AND property_id = :pid',
                    [
                        'done' => (string) $request->input('performed_at'),
                        'id' => $planId,
                        'pid' => $pid,
                    ]
                );
            }

            if ($request->input('create_expense') && $cost !== '' && $cost !== null && (float) $cost > 0) {
                ExpenseHelper::create([
                    'property_id' => $pid,
                    'title' => 'Mantenimiento: ' . $request->input('title'),
                    'amount' => $cost,
                    'currency' => $currency,
                    'spent_at' => (string) $request->input('performed_at'),
                    'category_slug' => 'maintenance',
                    'source_type' => 'maintenance_record',
                    'source_id' => $recordId,
                ]);
            }

            ActivityLogger::log($pid, 'maintenance_record', $recordId, 'created');
            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollBack();
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/maintenance');
        }

        flash('success', 'Registro de mantenimiento guardado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/maintenance');
    }
}
