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
use Premisely\Core\Http\Response;
use Premisely\Shared\ExpenseHelper;
use Premisely\Shared\PropertyContext;
use Premisely\Shared\ShoppingWorkbook;

final class ShoppingController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $list = $this->activeList(true);
        (new StockService())->syncAllWithShopping(PropertyContext::propertyId());
        $items = Connection::fetchAll(
            'SELECT * FROM shopping_list_items
             WHERE shopping_list_id = :lid AND status = \'pending\'
             ORDER BY priority DESC, created_at DESC',
            ['lid' => $list['id']]
        );

        $rubros = shopping_rubros();
        $rubro = (string) $request->query('rubro', '');
        if ($rubro !== '' && $rubro !== 'sin' && !isset($rubros[$rubro])) {
            $rubro = '';
        }

        $this->view('shopping/index', [
            'title' => 'Lista de compras',
            'property' => PropertyContext::property(),
            'list' => $list,
            'items' => $items,
            'rubros' => $rubros,
            'rubro' => $rubro,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function export(Request $request, array $params): never
    {
        $list = $this->activeList(true);
        (new StockService())->syncAllWithShopping(PropertyContext::propertyId());
        $items = Connection::fetchAll(
            'SELECT name, brand, category, quantity, unit FROM shopping_list_items
             WHERE shopping_list_id = :lid AND status = \'pending\'
             ORDER BY name',
            ['lid' => $list['id']]
        );
        $rubros = shopping_rubros();
        $order = array_flip(array_keys($rubros));
        usort($items, static function (array $a, array $b) use ($order): int {
            $ia = $order[(string) ($a['category'] ?? '')] ?? 99;
            $ib = $order[(string) ($b['category'] ?? '')] ?? 99;
            if ($ia !== $ib) {
                return $ia <=> $ib;
            }

            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        $palette = rubro_palette();
        $grouped = [];
        foreach ($items as $item) {
            $qty = (float) $item['quantity'];
            $key = (string) ($item['category'] ?? '');
            if (!isset($rubros[$key])) {
                $key = 'sin';
            }
            $grouped[$key][] = [
                'product' => (string) $item['name'],
                'brand' => (string) ($item['brand'] ?? ''),
                'quantity' => trim(format_qty($qty) . ' ' . unit_label($qty, (string) ($item['unit'] ?? ''))),
            ];
        }
        $groups = [];
        foreach (array_merge(array_keys($rubros), ['sin']) as $key) {
            if (($grouped[$key] ?? []) === []) {
                continue;
            }
            $tone = $palette[$key] ?? $palette['sin'];
            $groups[] = [
                'category' => $key === 'sin' ? 'Sin rubro' : $rubros[$key],
                'color' => $tone['bg'],
                'items' => $grouped[$key],
            ];
        }

        $date = date('d/m/Y');
        $title = 'Lista de Compras - ' . $date;
        try {
            $binary = ShoppingWorkbook::render($title, $groups);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        $filename = 'Lista de Compras - ' . str_replace('/', '-', $date) . '.xlsx';
        (new Response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="lista-de-compras.xlsx"; filename*=UTF-8\'\'' . rawurlencode($filename),
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'no-store',
        ]))->send();
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
            'rubros' => shopping_rubros(),
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

        $rubro = (string) $request->input('category', '');
        if (!array_key_exists($rubro, shopping_rubros())) {
            $rubro = '';
        }
        Connection::query(
            'INSERT INTO shopping_list_items (
                shopping_list_id, stock_item_id, name, brand, category, quantity, unit, estimated_price, priority, status, added_by, created_at
             ) VALUES (
                :list_id, :stock_item_id, :name, :brand, :category, :quantity, :unit, :estimated_price, :priority, :status, :added_by, NOW()
             )',
            [
                'list_id' => $list['id'],
                'stock_item_id' => $stockItemId,
                'name' => trim((string) $request->input('name')),
                'brand' => trim((string) $request->input('brand', '')) ?: null,
                'category' => $rubro !== '' ? $rubro : null,
                'quantity' => max(1, (float) ($request->input('quantity') ?: 1)),
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

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        $itemId = (int) ($params['item'] ?? 0);
        $back = '/properties/' . PropertyContext::property()['public_id'] . '/shopping#item-' . $itemId;
        $list = $this->activeList(false);
        $name = trim((string) $request->input('name', ''));
        if (!$list || $name === '') {
            flash('error', 'El nombre es obligatorio.');
            $this->redirect($itemId > 0 ? $back : '/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }
        $current = Connection::fetch(
            'SELECT stock_item_id, name FROM shopping_list_items
             WHERE id = :id AND shopping_list_id = :lid AND status = \'pending\' LIMIT 1',
            ['id' => $itemId, 'lid' => $list['id']]
        );
        $rubro = (string) $request->input('category', '');
        if (!array_key_exists($rubro, shopping_rubros())) {
            $rubro = '';
        }
        $unit = (string) ($request->input('unit') ?: 'u');
        Connection::query(
            'UPDATE shopping_list_items SET
                name = :name, brand = :brand, category = :category, quantity = :quantity, unit = :unit
             WHERE id = :id AND shopping_list_id = :lid AND status = \'pending\'',
            [
                'name' => $name,
                'brand' => trim((string) $request->input('brand', '')) ?: null,
                'category' => $rubro !== '' ? $rubro : null,
                'quantity' => max(1, (float) ($request->input('quantity') ?: 1)),
                'unit' => $unit,
                'id' => $itemId,
                'lid' => $list['id'],
            ]
        );
        if ($current) {
            $stockId = (new StockService())->copyIdentityToStock(
                PropertyContext::propertyId(),
                !empty($current['stock_item_id']) ? (int) $current['stock_item_id'] : null,
                (string) $current['name'],
                $name,
                $unit,
                trim((string) $request->input('brand', '')),
                $rubro,
                true
            );
            if ($stockId > 0 && empty($current['stock_item_id'])) {
                Connection::query(
                    'UPDATE shopping_list_items SET stock_item_id = :sid WHERE id = :id AND status = \'pending\'',
                    ['sid' => $stockId, 'id' => $itemId]
                );
            }
        }
        flash('success', 'Ítem actualizado.');
        $this->redirect($back);
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
        $createExpense = (bool) $request->input('create_expense');
        $bought = (float) str_replace(',', '.', (string) ($request->input('bought_quantity', $request->input('quantity', ''))));
        if ($bought <= 0) {
            flash('error', 'Decí cuántas unidades compraste.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }
        $pid = PropertyContext::propertyId();

        try {
            Connection::query(
                'UPDATE shopping_list_items SET status = \'completed\', completed_by = :uid,
                 completed_at = NOW(), actual_price = :price, bought_quantity = :qty
                 WHERE id = :id',
                [
                    'uid' => Auth::id(),
                    'price' => $actualPrice !== '' && $actualPrice !== null ? $actualPrice : null,
                    'qty' => $bought,
                    'id' => $itemId,
                ]
            );

            $stockId = (new StockService())->intakeFromShopping($pid, $item, $bought, $actualPrice);
            if ($stockId > 0) {
                Connection::query(
                    'UPDATE shopping_list_items SET stock_item_id = :stock WHERE id = :id',
                    ['stock' => $stockId, 'id' => $itemId]
                );
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
            if ($stockId > 0) {
                $fresh = Connection::fetch(
                    'SELECT * FROM stock_items WHERE id = :id AND property_id = :pid AND archived_at IS NULL',
                    ['id' => $stockId, 'pid' => $pid]
                );
                if ($fresh) {
                    (new StockService())->syncWithShopping($pid, $fresh);
                }
            }
        } catch (\Throwable $e) {
            Connection::rollBack();
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/shopping');
        }

        flash('success', 'Comprado. Quedó en el stock.');
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
