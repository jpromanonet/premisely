<?php

declare(strict_types=1);

namespace Premisely\Modules\Services\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\ExpenseHelper;
use Premisely\Shared\PropertyContext;

final class ServiceController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $services = Connection::fetchAll(
            'SELECT * FROM property_services
             WHERE property_id = :pid AND archived_at IS NULL
             ORDER BY name',
            ['pid' => $pid]
        );
        $bills = Connection::fetchAll(
            'SELECT b.*, s.name AS service_name
             FROM service_bills b
             INNER JOIN property_services s ON s.id = b.service_id
             WHERE b.property_id = :pid
             ORDER BY b.created_at DESC LIMIT 40',
            ['pid' => $pid]
        );

        $this->view('services/index', [
            'title' => 'Servicios',
            'property' => PropertyContext::property(),
            'services' => $services,
            'bills' => $bills,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:160',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
        }

        $pid = PropertyContext::propertyId();
        Connection::query(
            'INSERT INTO property_services (
                public_id, property_id, name, provider, customer_number, billing_frequency,
                typical_amount, currency, next_due_date, status, notes, created_at
             ) VALUES (
                :public_id, :property_id, :name, :provider, :customer_number, :billing_frequency,
                :typical_amount, :currency, :next_due_date, :status, :notes, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'name' => (string) $request->input('name'),
                'provider' => $request->input('provider'),
                'customer_number' => $request->input('customer_number'),
                'billing_frequency' => (string) ($request->input('billing_frequency') ?: 'monthly'),
                'typical_amount' => $request->input('typical_amount') !== '' ? $request->input('typical_amount') : null,
                'currency' => $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS'),
                'next_due_date' => $request->input('next_due_date') ?: null,
                'status' => 'active',
                'notes' => $request->input('notes'),
            ]
        );

        ActivityLogger::log($pid, 'property_service', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Servicio creado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
    }

    public function addBill(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
        }

        $service = $this->findService($params['service'] ?? '');
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
        }

        $pid = PropertyContext::propertyId();
        $amount = $request->input('amount');
        $currency = $request->input('currency') ?: ($service['currency'] ?? PropertyContext::property()['currency'] ?? 'ARS');

        Connection::begin();
        try {
            Connection::query(
                'INSERT INTO service_bills (
                    public_id, property_id, service_id, period, amount, currency, due_date, paid_at, notes, created_at
                 ) VALUES (
                    :public_id, :property_id, :service_id, :period, :amount, :currency, :due_date, :paid_at, :notes, NOW()
                 )',
                [
                    'public_id' => ulid(),
                    'property_id' => $pid,
                    'service_id' => $service['id'],
                    'period' => $request->input('period'),
                    'amount' => $amount,
                    'currency' => $currency,
                    'due_date' => $request->input('due_date') ?: null,
                    'paid_at' => $request->input('paid_at') ?: null,
                    'notes' => $request->input('notes'),
                ]
            );
            $billId = (int) Connection::lastInsertId();

            if ($request->input('create_expense')) {
                ExpenseHelper::create([
                    'property_id' => $pid,
                    'title' => 'Servicio: ' . $service['name'] . ($request->input('period') ? ' (' . $request->input('period') . ')' : ''),
                    'amount' => $amount,
                    'currency' => $currency,
                    'spent_at' => (string) ($request->input('paid_at') ?: ($request->input('due_date') ?: date('Y-m-d'))),
                    'category_slug' => 'utilities',
                    'source_type' => 'service_bill',
                    'source_id' => $billId,
                ]);
            }

            ActivityLogger::log($pid, 'service_bill', $billId, 'created');
            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollBack();
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
        }

        flash('success', 'Factura registrada.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
    }

    /** @return array<string, mixed> */
    private function findService(string $key): array
    {
        $service = Connection::fetch(
            'SELECT * FROM property_services
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );

        if (!$service) {
            flash('error', 'Servicio no encontrado.');
            redirect('/properties/' . PropertyContext::property()['public_id'] . '/services');
        }

        return $service;
    }
}
