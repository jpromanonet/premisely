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
    /** @var array<string,string> */
    private const FREQUENCIES = [
        'monthly' => 'Mensual',
        'bimonthly' => 'Bimestral',
        'quarterly' => 'Trimestral',
        'yearly' => 'Anual',
    ];

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
        $fixed = Connection::fetchAll(
            'SELECT r.*, c.name AS category_name, c.slug AS category_slug
             FROM recurring_expenses r
             LEFT JOIN expense_categories c ON c.id = r.category_id
             WHERE r.property_id = :pid AND r.archived_at IS NULL
             ORDER BY r.is_active DESC, r.title',
            ['pid' => $pid]
        );

        $this->view('expenses/index', [
            'title' => 'Gastos',
            'property' => PropertyContext::property(),
            'expenses' => $expenses,
            'fixedExpenses' => $fixed,
            'frequencies' => self::FREQUENCIES,
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

        $expense = $this->findExpense($params['expense'] ?? '');
        Connection::query(
            'UPDATE expenses SET archived_at = NOW(), updated_at = NOW() WHERE id = :id',
            ['id' => $expense['id']]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'expense', (int) $expense['id'], 'archived');
        flash('success', 'Gasto archivado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
    }

    public function edit(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }
        $expense = $this->findExpense($params['expense'] ?? '');
        $this->view('expenses/form', [
            'title' => 'Editar gasto',
            'property' => PropertyContext::property(),
            'expense' => $expense,
            'categories' => Connection::fetchAll('SELECT * FROM expense_categories ORDER BY name'),
            'canEdit' => true,
        ]);
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }

        $expense = $this->findExpense($params['expense'] ?? '');
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'amount' => 'required|numeric',
            'spent_at' => 'required',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses/' . $expense['public_id'] . '/edit');
        }

        $categoryId = null;
        if ($request->input('category_id')) {
            $categoryId = (int) $request->input('category_id');
        }

        Connection::query(
            'UPDATE expenses SET
                title = :title,
                amount = :amount,
                currency = :currency,
                spent_at = :spent_at,
                category_id = :category_id,
                notes = :notes,
                updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'title' => (string) $request->input('title'),
                'amount' => $request->input('amount'),
                'currency' => $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS'),
                'spent_at' => (string) $request->input('spent_at'),
                'category_id' => $categoryId,
                'notes' => $request->input('notes'),
                'id' => $expense['id'],
                'pid' => PropertyContext::propertyId(),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'expense', (int) $expense['id'], 'updated');
        flash('success', 'Gasto actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
    }

    public function createFixed(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }
        $this->view('expenses/fixed_form', [
            'title' => 'Nuevo gasto fijo',
            'property' => PropertyContext::property(),
            'fixed' => null,
            'categories' => Connection::fetchAll('SELECT * FROM expense_categories ORDER BY name'),
            'frequencies' => self::FREQUENCIES,
            'canEdit' => true,
        ]);
    }

    public function storeFixed(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'amount' => 'required|numeric',
            'frequency_type' => 'required|in:monthly,bimonthly,quarterly,yearly',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses/fixed/create');
        }

        $pid = PropertyContext::propertyId();
        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;

        Connection::query(
            'INSERT INTO recurring_expenses (
                public_id, property_id, category_id, title, amount, currency,
                frequency_type, next_due_date, is_active, created_at
             ) VALUES (
                :public_id, :property_id, :category_id, :title, :amount, :currency,
                :frequency_type, :next_due_date, :is_active, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'category_id' => $categoryId,
                'title' => (string) $request->input('title'),
                'amount' => $request->input('amount'),
                'currency' => $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS'),
                'frequency_type' => (string) $request->input('frequency_type'),
                'next_due_date' => $request->input('next_due_date') ?: null,
                'is_active' => 1,
            ]
        );

        ActivityLogger::log($pid, 'recurring_expense', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Gasto fijo creado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
    }

    public function editFixed(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }
        $fixed = $this->findFixed($params['fixed'] ?? '');
        $this->view('expenses/fixed_form', [
            'title' => 'Editar gasto fijo',
            'property' => PropertyContext::property(),
            'fixed' => $fixed,
            'categories' => Connection::fetchAll('SELECT * FROM expense_categories ORDER BY name'),
            'frequencies' => self::FREQUENCIES,
            'canEdit' => true,
        ]);
    }

    public function updateFixed(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }

        $fixed = $this->findFixed($params['fixed'] ?? '');
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'amount' => 'required|numeric',
            'frequency_type' => 'required|in:monthly,bimonthly,quarterly,yearly',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses/fixed/' . $fixed['public_id'] . '/edit');
        }

        $categoryId = $request->input('category_id') ? (int) $request->input('category_id') : null;
        $isActive = $request->input('is_active') === '0' ? 0 : 1;

        Connection::query(
            'UPDATE recurring_expenses SET
                title = :title,
                amount = :amount,
                currency = :currency,
                category_id = :category_id,
                frequency_type = :frequency_type,
                next_due_date = :next_due_date,
                is_active = :is_active,
                updated_at = NOW()
             WHERE id = :id AND property_id = :pid',
            [
                'title' => (string) $request->input('title'),
                'amount' => $request->input('amount'),
                'currency' => $request->input('currency') ?: (PropertyContext::property()['currency'] ?? 'ARS'),
                'category_id' => $categoryId,
                'frequency_type' => (string) $request->input('frequency_type'),
                'next_due_date' => $request->input('next_due_date') ?: null,
                'is_active' => $isActive,
                'id' => $fixed['id'],
                'pid' => PropertyContext::propertyId(),
            ]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'recurring_expense', (int) $fixed['id'], 'updated');
        flash('success', 'Gasto fijo actualizado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
    }

    public function destroyFixed(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }

        $fixed = $this->findFixed($params['fixed'] ?? '');
        Connection::query(
            'UPDATE recurring_expenses SET archived_at = NOW(), is_active = 0, updated_at = NOW() WHERE id = :id',
            ['id' => $fixed['id']]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'recurring_expense', (int) $fixed['id'], 'archived');
        flash('success', 'Gasto fijo eliminado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
    }

    /** @return array<string,mixed> */
    private function findExpense(string $key): array
    {
        $expense = Connection::fetch(
            'SELECT * FROM expenses
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        if (!$expense) {
            flash('error', 'Gasto no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }
        return $expense;
    }

    /** @return array<string,mixed> */
    private function findFixed(string $key): array
    {
        $fixed = Connection::fetch(
            'SELECT * FROM recurring_expenses
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );
        if (!$fixed) {
            flash('error', 'Gasto fijo no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/expenses');
        }
        return $fixed;
    }
}
