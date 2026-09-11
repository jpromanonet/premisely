<?php

declare(strict_types=1);

namespace Premisely\Modules\Expenses\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\ExpenseHelper;
use Premisely\Shared\PropertyContext;

final class ExpenseController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $expenses = Connection::fetchAll(
            'SELECT e.*, c.name AS category_name, c.slug AS category_slug
             FROM expenses e
             LEFT JOIN expense_categories c ON c.id = e.category_id
             WHERE e.property_id = :pid AND e.archived_at IS NULL
             ORDER BY e.spent_at DESC, e.id DESC
             LIMIT 200',
            ['pid' => $pid]
        );

        $this->view('expenses/index', [
            'title' => 'Gastos',
            'property' => PropertyContext::property(),
            'expenses' => $expenses,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }
        $this->view('expenses/create', [
            'title' => 'Nuevo gasto',
            'property' => PropertyContext::property(),
            'categories' => Connection::fetchAll('SELECT * FROM expense_categories ORDER BY name'),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'amount' => 'required|numeric',
            'spent_at' => 'required',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses/create');
        }

        $pid = PropertyContext::propertyId();
        $categoryId = null;
        if ($request->input('category_id')) {
            $categoryId = (int) $request->input('category_id');
        } elseif ($request->input('category_slug')) {
            $categoryId = ExpenseHelper::categoryIdBySlug((string) $request->input('category_slug'));
        }

        Connection::query(
            'INSERT INTO expenses (
                public_id, property_id, category_id, title, amount, currency, spent_at, notes, created_by, created_at
             ) VALUES (
                :public_id, :property_id, :category_id, :title, :amount, :currency, :spent_at, :notes, :created_by, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'category_id' => $categoryId,
                'title' => (string) $request->input('title'),
                'amount' => $request->input('amount'),
                'currency' => $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS'),
                'spent_at' => (string) $request->input('spent_at'),
                'notes' => $request->input('notes'),
                'created_by' => Auth::id(),
            ]
        );

        ActivityLogger::log($pid, 'expense', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Gasto registrado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
    }

    public function archive(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }

        $key = $params['expense'] ?? '';
        $expense = Connection::fetch(
            'SELECT * FROM expenses
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit((string) $key) ? (int) $key : 0,
            ]
        );

        if (!$expense) {
            flash('error', 'Gasto no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }

        Connection::query(
            'UPDATE expenses SET archived_at = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $expense['id']]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'expense', (int) $expense['id'], 'archived');
        flash('success', 'Gasto archivado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
    }
}
