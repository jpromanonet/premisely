<?php

declare(strict_types=1);

namespace Premisely\Modules\Shopping\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Modules\Stock\Services\StockService;
use Premisely\Shared\ExpenseHelper;
use Premisely\Shared\PropertyContext;

final class ShoppingController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $list = $this->activeList(true);
        $items = Connection::fetchAll(
            'SELECT * FROM shopping_list_items
             WHERE shopping_list_id = :lid
             ORDER BY FIELD(status, \'pending\', \'completed\'), priority DESC, created_at DESC',
            ['lid' => $list['id']]
        );

        $this->view('shopping/index', [
            'title' => 'Lista de compras',
            'property' => PropertyContext::property(),
            'list' => $list,
            'items' => $items,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }
        $stockItems = Connection::fetchAll(
            'SELECT id, name, unit FROM stock_items
             WHERE property_id = :pid AND archived_at IS NULL ORDER BY name',
            ['pid' => PropertyContext::propertyId()]
        );
        $this->view('shopping/create', [
            'title' => 'Agregar ítem',
            'property' => PropertyContext::property(),
            'stockItems' => $stockItems,
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:180',
            'quantity' => 'numeric',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping/create');
        }

        $list = $this->activeList(true);
        $stockItemId = $request->input('stock_item_id');
        $stockItemId = ($stockItemId === null || $stockItemId === '') ? null : (int) $stockItemId;

        Connection::query(
            'INSERT INTO shopping_list_items (
                shopping_list_id, stock_item_id, name, quantity, unit, estimated_price, priority, status, added_by, created_at
             ) VALUES (
                :list_id, :stock_item_id, :name, :quantity, :unit, :estimated_price, :priority, :status, :added_by, NOW()
             )',
            [
                'list_id' => $list['id'],
                'stock_item_id' => $stockItemId,
                'name' => (string) $request->input('name'),
                'quantity' => (float) ($request->input('quantity') ?: 1),
                'unit' => (string) ($request->input('unit') ?: 'u'),
                'estimated_price' => $request->input('estimated_price') !== '' ? $request->input('estimated_price') : null,
                'priority' => (string) ($request->input('priority') ?: 'normal'),
                'status' => 'pending',
                'added_by' => Auth::id(),
            ]
        );

        ActivityLogger::log(PropertyContext::propertyId(), 'shopping_list_item', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Ítem agregado a la lista.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
    }

    public function complete(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        $itemId = (int) ($params['item'] ?? 0);
        $list = $this->activeList(true);
        $item = Connection::fetch(
            'SELECT * FROM shopping_list_items WHERE id = :id AND shopping_list_id = :lid LIMIT 1',
            ['id' => $itemId, 'lid' => $list['id']]
        );

        if (!$item || $item['status'] === 'completed') {
            flash('error', 'Ítem no encontrado o ya completado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        $actualPrice = $request->input('actual_price');
        $updateStock = (bool) $request->input('update_stock');
        $createExpense = (bool) $request->input('create_expense');
        $pid = PropertyContext::propertyId();

        Connection::begin();
        try {
            Connection::query(
                'UPDATE shopping_list_items SET status = \'completed\', completed_by = :uid,
                 completed_at = NOW(), actual_price = :price
                 WHERE id = :id',
                [
                    'uid' => Auth::id(),
                    'price' => $actualPrice !== '' && $actualPrice !== null ? $actualPrice : null,
                    'id' => $itemId,
                ]
            );

            if ($updateStock && $item['stock_item_id']) {
                (new StockService())->adjustQuantity($pid, (int) $item['stock_item_id'], [
                    'type' => 'purchase',
                    'quantity' => (float) $item['quantity'],
                    'unit_price' => $actualPrice !== '' && $actualPrice !== null ? $actualPrice : null,
                    'notes' => 'Compra desde lista #' . $list['id'],
                ]);
            }

            if ($createExpense && $actualPrice !== '' && $actualPrice !== null && (float) $actualPrice > 0) {
                ExpenseHelper::create([
                    'property_id' => $pid,
                    'title' => 'Compra: ' . $item['name'],
                    'amount' => $actualPrice,
                    'currency' => PropertyContext::property()['currency'] ?? 'ARS',
                    'category_slug' => 'consumables',
                    'source_type' => 'shopping_list_item',
                    'source_id' => $itemId,
                ]);
            }

            ActivityLogger::log($pid, 'shopping_list_item', $itemId, 'completed');
            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollBack();
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        flash('success', 'Ítem marcado como comprado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
    }

    public function delete(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        $itemId = (int) ($params['item'] ?? 0);
        $list = $this->activeList(false);
        if ($list) {
            Connection::query(
                'DELETE FROM shopping_list_items WHERE id = :id AND shopping_list_id = :lid',
                ['id' => $itemId, 'lid' => $list['id']]
            );
            ActivityLogger::log(PropertyContext::propertyId(), 'shopping_list_item', $itemId, 'deleted');
        }

        flash('success', 'Ítem eliminado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
    }

    /** @return array<string, mixed>|null */
    private function activeList(bool $createIfMissing): ?array
    {
        $pid = PropertyContext::propertyId();
        $list = Connection::fetch(
            'SELECT * FROM shopping_lists
             WHERE property_id = :pid AND status = \'active\' AND archived_at IS NULL
             ORDER BY id DESC LIMIT 1',
            ['pid' => $pid]
        );

        if ($list || !$createIfMissing) {
            return $list;
        }

        Connection::query(
            'INSERT INTO shopping_lists (public_id, property_id, name, status, created_at)
             VALUES (:public_id, :property_id, :name, :status, NOW())',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'name' => 'Lista activa',
                'status' => 'active',
            ]
        );

        return Connection::fetch('SELECT * FROM shopping_lists WHERE id = :id', [
            'id' => Connection::lastInsertId(),
        ]);
    }
}
