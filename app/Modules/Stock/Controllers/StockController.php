<?php

declare(strict_types=1);

namespace Premisely\Modules\Stock\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Stock\Services\StockService;
use Premisely\Shared\PropertyContext;
use Throwable;

final class StockController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $pid = PropertyContext::propertyId();
        $items = Connection::fetchAll(
            'SELECT st.*, c.name AS category_name, c.slug AS category_slug
             FROM stock_items st
             LEFT JOIN stock_categories c ON c.id = st.category_id
             WHERE st.property_id = :pid AND st.archived_at IS NULL
             ORDER BY st.name',
            ['pid' => $pid]
        );

        $categories = $this->categories($pid);
        $rubro = (string) $request->query('rubro', '');
        $known = [];
        foreach ($categories as $category) {
            $known[(string) $category['slug']] = true;
        }
        if ($rubro !== '' && $rubro !== 'sin' && !isset($known[$rubro])) {
            $rubro = '';
        }

        $this->view('stock/index', [
            'title' => 'Stock / Consumibles',
            'property' => PropertyContext::property(),
            'items' => $items,
            'categories' => $categories,
            'rubro' => $rubro,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function create(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
        }
        $pid = PropertyContext::propertyId();
        $this->view('stock/create', [
            'title' => 'Nuevo ítem',
            'property' => PropertyContext::property(),
            'categories' => $this->categories($pid),
            'canEdit' => true,
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:180',
            'quantity' => 'numeric',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock/create');
        }

        (new StockService())->create(PropertyContext::propertyId(), $request->all());
        flash('success', 'Ítem de stock creado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
    }

    public function update(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
        }

        $item = $this->findItem($params['item'] ?? '');
        $back = '/properties/' . PropertyContext::property()['public_id'] . '/stock#item-' . $item['public_id'];
        $validator = Validator::make($request->all(), ['name' => 'required|max:180']);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect($back);
        }

        (new StockService())->update(PropertyContext::propertyId(), (int) $item['id'], $request->all());
        flash('success', 'Stock actualizado.');
        $this->redirect($back);
    }

    public function adjust(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            if ($request->wantsJson()) {
                $this->json(['error' => 'Sin permisos'], 403);
            }
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
        }

        $item = $this->findItem($params['item'] ?? '');
        $back = '/properties/' . PropertyContext::property()['public_id'] . '/stock#item-' . $item['public_id'];
        try {
            $updated = (new StockService())->adjustQuantity(
                PropertyContext::propertyId(),
                (int) $item['id'],
                [
                    'type' => (string) $request->input('type'),
                    'quantity' => $request->input('quantity'),
                    'unit_price' => $request->input('unit_price'),
                    'notes' => $request->input('notes'),
                    'direction' => $request->input('direction'),
                ]
            );
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                $this->json(['error' => $e->getMessage()], 422);
            }
            flash('error', $e->getMessage());
            $this->redirect($back);
        }

        if ($request->wantsJson()) {
            $this->json(['ok' => true, 'item' => $updated]);
        }

        $left = format_qty((float) ($updated['quantity'] ?? 0));
        flash('success', 'Quedaron ' . $left . '.');
        $this->redirect($back);
    }

    public function archive(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
        }

        $item = $this->findItem($params['item'] ?? '');
        (new StockService())->archive(PropertyContext::propertyId(), (int) $item['id']);
        flash('success', 'Ítem archivado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
    }

    /** @return list<array<string, mixed>> */
    private function categories(int $pid): array
    {
        $rubros = shopping_rubros();
        $rows = Connection::fetchAll(
            'SELECT id, slug FROM stock_categories WHERE property_id IS NULL'
        );
        $bySlug = [];
        foreach ($rows as $row) {
            $bySlug[(string) $row['slug']] = $row;
        }
        $ordered = [];
        foreach ($rubros as $slug => $label) {
            if (!isset($bySlug[$slug])) {
                continue;
            }
            $ordered[] = [
                'id' => (int) $bySlug[$slug]['id'],
                'slug' => $slug,
                'name' => $label,
            ];
        }

        return $ordered;
    }

    /** @return array<string, mixed> */
    private function findItem(string $key): array
    {
        $item = Connection::fetch(
            'SELECT * FROM stock_items
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit($key) ? (int) $key : 0,
            ]
        );

        if (!$item) {
            flash('error', 'Ítem no encontrado.');
            redirect('/properties/' . PropertyContext::property()['public_id'] . '/stock');
        }

        return $item;
    }
}
