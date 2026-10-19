<?php

declare(strict_types=1);

namespace Premisely\Modules\Stock\Services;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Modules\Activity\Services\ActivityLogger;
use InvalidArgumentException;
use RuntimeException;

final class StockService
{
    private const MOVEMENT_TYPES = ['purchase', 'consume', 'adjustment', 'discard', 'expired'];

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(int $propertyId, array $data): array
    {
        $publicId = ulid();
        Connection::query(
            'INSERT INTO stock_items (
                public_id, property_id, space_id, category_id, name, brand, quantity, unit,
                minimum_quantity, target_quantity, last_price, currency, expiration_date,
                auto_add_to_shopping, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :category_id, :name, :brand, :quantity, :unit,
                :minimum_quantity, :target_quantity, :last_price, :currency, :expiration_date,
                :auto_add_to_shopping, NOW()
             )',
            [
                'public_id' => $publicId,
                'property_id' => $propertyId,
                'space_id' => self::nullableInt($data['space_id'] ?? null),
                'category_id' => self::nullableInt($data['category_id'] ?? null),
                'name' => (string) $data['name'],
                'brand' => $data['brand'] ?? null,
                'quantity' => (float) ($data['quantity'] ?? 0),
                'unit' => (string) ($data['unit'] ?? 'u'),
                'minimum_quantity' => (float) ($data['minimum_quantity'] ?? 0),
                'target_quantity' => isset($data['target_quantity']) && $data['target_quantity'] !== '' && $data['target_quantity'] !== null
                    ? (float) $data['target_quantity'] : null,
                'last_price' => isset($data['last_price']) && $data['last_price'] !== '' && $data['last_price'] !== null
                    ? $data['last_price'] : null,
                'currency' => $data['currency'] ?? null,
                'expiration_date' => !empty($data['expiration_date']) ? $data['expiration_date'] : null,
                'auto_add_to_shopping' => 1,
            ]
        );

        $id = (int) Connection::lastInsertId();
        ActivityLogger::log($propertyId, 'stock_item', $id, 'created', ['name' => $data['name']]);
        $created = Connection::fetch('SELECT * FROM stock_items WHERE id = :id', ['id' => $id]) ?? [];
        if ($created !== []) {
            $this->syncWithShopping($propertyId, $created);
        }

        return $created;
    }

    /** @param array<string, mixed> $data */
    public function update(int $propertyId, int $itemId, array $data): void
    {
        $current = Connection::fetch(
            'SELECT * FROM stock_items WHERE id = :id AND property_id = :property_id AND archived_at IS NULL',
            ['id' => $itemId, 'property_id' => $propertyId]
        );
        if (!$current) {
            return;
        }
        $has = static fn (string $key): bool => array_key_exists($key, $data);
        $optionalNumber = static function (string $key) use ($data, $current, $has): mixed {
            if (!$has($key)) {
                return $current[$key];
            }
            $value = $data[$key];

            return $value !== '' && $value !== null ? $value : null;
        };

        Connection::query(
            'UPDATE stock_items SET
                space_id = :space_id,
                category_id = :category_id,
                name = :name,
                brand = :brand,
                unit = :unit,
                minimum_quantity = :minimum_quantity,
                target_quantity = :target_quantity,
                last_price = :last_price,
                currency = :currency,
                expiration_date = :expiration_date,
                auto_add_to_shopping = :auto_add_to_shopping,
                updated_at = NOW()
             WHERE id = :id AND property_id = :property_id AND archived_at IS NULL',
            [
                'space_id' => $has('space_id') ? self::nullableInt($data['space_id']) : $current['space_id'],
                'category_id' => $has('category_id') ? self::nullableInt($data['category_id']) : $current['category_id'],
                'name' => (string) $data['name'],
                'brand' => $has('brand') ? ($data['brand'] ?: null) : $current['brand'],
                'unit' => $has('unit') ? (string) $data['unit'] : (string) $current['unit'],
                'minimum_quantity' => $has('minimum_quantity') ? (float) $data['minimum_quantity'] : (float) $current['minimum_quantity'],
                'target_quantity' => $optionalNumber('target_quantity'),
                'last_price' => $optionalNumber('last_price'),
                'currency' => $has('currency') ? ($data['currency'] ?: null) : $current['currency'],
                'expiration_date' => $has('expiration_date') ? ($data['expiration_date'] ?: null) : $current['expiration_date'],
                'auto_add_to_shopping' => 1,
                'id' => $itemId,
                'property_id' => $propertyId,
            ]
        );
        ActivityLogger::log($propertyId, 'stock_item', $itemId, 'updated');
        $fresh = Connection::fetch('SELECT * FROM stock_items WHERE id = :id', ['id' => $itemId]);
        if ($fresh) {
            $minimumChanged = (float) $current['minimum_quantity'] !== (float) $fresh['minimum_quantity']
                || (string) ($current['target_quantity'] ?? '') !== (string) ($fresh['target_quantity'] ?? '');
            $this->syncWithShopping($propertyId, $fresh, (string) $current['name'], $minimumChanged);
        }
    }

    /**
     * @param array{type:string,quantity:float|int|string,unit_price?:float|int|string|null,notes?:?string} $data
     * @return array<string, mixed>
     */
    public function adjustQuantity(int $propertyId, int $itemId, array $data): array
    {
        $type = (string) ($data['type'] ?? '');
        if (!in_array($type, self::MOVEMENT_TYPES, true)) {
            throw new InvalidArgumentException('Tipo de movimiento inválido.');
        }

        $qty = (float) ($data['quantity'] ?? 0);
        if ($qty <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $item = Connection::fetch(
            'SELECT * FROM stock_items WHERE id = :id AND property_id = :pid AND archived_at IS NULL LIMIT 1',
            ['id' => $itemId, 'pid' => $propertyId]
        );
        if (!$item) {
            throw new RuntimeException('Ítem de stock no encontrado.');
        }

        $delta = match ($type) {
            'purchase', 'adjustment' => $qty,
            'consume', 'discard', 'expired' => -$qty,
            default => throw new InvalidArgumentException('Tipo de movimiento inválido.'),
        };

        // adjustment can mean set relative; for V1 positive adjustment adds, negative via consume/discard
        if ($type === 'adjustment' && isset($data['direction']) && $data['direction'] === 'down') {
            $delta = -$qty;
        }

        $newQty = (float) $item['quantity'] + $delta;
        if ($newQty < 0) {
            $newQty = 0;
        }

        Connection::begin();
        try {
            Connection::query(
                'UPDATE stock_items SET quantity = :qty, last_price = COALESCE(:price, last_price), updated_at = NOW()
                 WHERE id = :id',
                [
                    'qty' => $newQty,
                    'price' => $data['unit_price'] !== '' && $data['unit_price'] !== null ? $data['unit_price'] : null,
                    'id' => $itemId,
                ]
            );

            Connection::query(
                'INSERT INTO stock_movements (
                    stock_item_id, property_id, user_id, type, quantity, unit_price, notes, created_at
                 ) VALUES (
                    :stock_item_id, :property_id, :user_id, :type, :quantity, :unit_price, :notes, NOW()
                 )',
                [
                    'stock_item_id' => $itemId,
                    'property_id' => $propertyId,
                    'user_id' => Auth::id(),
                    'type' => $type,
                    'quantity' => $qty,
                    'unit_price' => $data['unit_price'] !== '' && $data['unit_price'] !== null ? $data['unit_price'] : null,
                    'notes' => $data['notes'] ?? null,
                ]
            );

            $item['quantity'] = $newQty;
            $this->syncWithShopping($propertyId, $item);

            ActivityLogger::log($propertyId, 'stock_item', $itemId, 'adjusted', [
                'type' => $type,
                'quantity' => $qty,
                'new_quantity' => $newQty,
            ]);

            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollBack();
            throw $e;
        }

        return Connection::fetch('SELECT * FROM stock_items WHERE id = :id', ['id' => $itemId]) ?? [];
    }

    public function archive(int $propertyId, int $itemId): void
    {
        Connection::query(
            'UPDATE stock_items SET archived_at = NOW(), updated_at = NOW()
             WHERE id = :id AND property_id = :pid AND archived_at IS NULL',
            ['id' => $itemId, 'pid' => $propertyId]
        );
        ActivityLogger::log($propertyId, 'stock_item', $itemId, 'archived');
    }

    /**
     * Suma una compra al stock. Si el producto no existe, lo crea con marca, rubro y cantidad.
     *
     * @param array<string, mixed> $item
     */
    public function intakeFromShopping(int $propertyId, array $item, float $bought, mixed $price): int
    {
        $stockId = (int) ($item['stock_item_id'] ?? 0);
        if ($stockId > 0) {
            $still = Connection::fetch(
                'SELECT id FROM stock_items WHERE id = :id AND property_id = :pid AND archived_at IS NULL',
                ['id' => $stockId, 'pid' => $propertyId]
            );
            if (!$still) {
                $stockId = 0;
            }
        }
        if ($stockId <= 0) {
            $wanted = self::fold((string) ($item['name'] ?? ''));
            if ($wanted !== '') {
                foreach (Connection::fetchAll(
                    'SELECT id, name FROM stock_items WHERE property_id = :pid AND archived_at IS NULL',
                    ['pid' => $propertyId]
                ) as $row) {
                    if (self::fold((string) $row['name']) === $wanted) {
                        $stockId = (int) $row['id'];
                        break;
                    }
                }
            }
        }

        $brand = trim((string) ($item['brand'] ?? ''));
        $unit = trim((string) ($item['unit'] ?? ''));
        if ($unit === '') {
            $unit = 'u';
        }
        $priceValue = $price !== '' && $price !== null ? $price : null;

        if ($stockId > 0) {
            $this->adjustQuantity($propertyId, $stockId, [
                'type' => 'purchase',
                'quantity' => $bought,
                'unit_price' => $priceValue,
                'notes' => 'Compra desde la lista',
            ]);
            $params = [
                'category_id' => $this->categoryIdBySlug((string) ($item['category'] ?? '')),
                'id' => $stockId,
                'pid' => $propertyId,
            ];
            $brandSql = '';
            if ($brand !== '') {
                $brandSql = 'brand = :brand, ';
                $params['brand'] = $brand;
            }
            Connection::query(
                'UPDATE stock_items SET ' . $brandSql . 'category_id = :category_id, updated_at = NOW()
                 WHERE id = :id AND property_id = :pid AND archived_at IS NULL',
                $params
            );

            return $stockId;
        }

        $created = $this->create($propertyId, [
            'name' => (string) ($item['name'] ?? ''),
            'brand' => $brand !== '' ? $brand : null,
            'unit' => $unit,
            'quantity' => $bought,
            'category_id' => $this->categoryIdBySlug((string) ($item['category'] ?? '')),
            'minimum_quantity' => 0,
            'auto_add_to_shopping' => 1,
            'last_price' => $priceValue,
        ]);

        return (int) ($created['id'] ?? 0);
    }

    private function categoryIdBySlug(string $slug): ?int
    {
        if ($slug === '' || !array_key_exists($slug, shopping_rubros())) {
            return null;
        }
        $row = Connection::fetch(
            'SELECT id FROM stock_categories WHERE property_id IS NULL AND slug = :slug ORDER BY id LIMIT 1',
            ['slug' => $slug]
        );

        return $row ? (int) $row['id'] : null;
    }

    /** Pone en compras lo que llegó al mínimo y saca lo que ya está cubierto. */
    public function syncAllWithShopping(int $propertyId): void
    {
        $items = Connection::fetchAll(
            'SELECT * FROM stock_items WHERE property_id = :pid AND archived_at IS NULL',
            ['pid' => $propertyId]
        );
        foreach ($items as $item) {
            $this->syncWithShopping($propertyId, $item, null, false);
        }
    }

    /** Quita de compras lo que ya está cubierto en stock. No toca el stock. */
    public function dropCoveredShopping(int $propertyId): void
    {
        $pending = $this->pendingItems($propertyId);
        if ($pending === []) {
            return;
        }
        $stock = Connection::fetchAll(
            'SELECT id, name, quantity, minimum_quantity FROM stock_items
             WHERE property_id = :pid AND archived_at IS NULL',
            ['pid' => $propertyId]
        );
        $drop = [];
        foreach ($stock as $item) {
            if (!self::isStocked($item)) {
                continue;
            }
            foreach (self::matchPending($pending, $item) as $row) {
                $drop[(int) $row['id']] = true;
            }
        }
        foreach (array_keys($drop) as $id) {
            Connection::query(
                'DELETE FROM shopping_list_items WHERE id = :id AND status = \'pending\'',
                ['id' => $id]
            );
        }
    }

    public function copyIdentityToStock(int $propertyId, ?int $stockItemId, string $previousName, string $name, string $unit, ?string $brand = null, ?string $rubro = null, bool $applyRubro = false): int
    {
        $id = (int) $stockItemId;
        if ($id <= 0 && trim($previousName) !== '') {
            $rows = Connection::fetchAll(
                'SELECT id, name FROM stock_items WHERE property_id = :pid AND archived_at IS NULL',
                ['pid' => $propertyId]
            );
            $wanted = self::fold($previousName);
            foreach ($rows as $row) {
                if (self::fold((string) $row['name']) === $wanted) {
                    $id = (int) $row['id'];
                    break;
                }
            }
        }
        if ($id <= 0) {
            return 0;
        }
        $params = [
            'name' => $name,
            'unit' => $unit,
            'brand' => trim((string) $brand) !== '' ? trim((string) $brand) : null,
            'id' => $id,
            'pid' => $propertyId,
        ];
        $rubroSql = '';
        if ($applyRubro) {
            $rubroSql = ', category_id = :category_id';
            $params['category_id'] = $this->categoryIdBySlug((string) $rubro);
        }
        Connection::query(
            'UPDATE stock_items SET name = :name, unit = :unit, brand = :brand' . $rubroSql . ', updated_at = NOW()
             WHERE id = :id AND property_id = :pid AND archived_at IS NULL',
            $params
        );

        return $id;
    }

    /** @param array<string, mixed> $item */
    public function syncWithShopping(int $propertyId, array $item, ?string $previousName = null, bool $refreshQuantity = true): void
    {
        $pending = $this->pendingItems($propertyId);
        $matches = self::matchPending($pending, $item, $previousName);
        if (self::isStocked($item)) {
            foreach ($matches as $row) {
                Connection::query(
                    'DELETE FROM shopping_list_items WHERE id = :id AND status = \'pending\'',
                    ['id' => $row['id']]
                );
            }

            return;
        }
        if ($matches !== []) {
            $this->mirrorShopping($item, $matches, $refreshQuantity);

            return;
        }
        $this->ensureOnShoppingList($propertyId, $item, (float) $item['quantity']);
    }

    /** @param array<string, mixed> $item */
    public function ensureOnShoppingList(int $propertyId, array $item, float $currentQty): void
    {
        $list = Connection::fetch(
            'SELECT * FROM shopping_lists
             WHERE property_id = :pid AND status = \'active\' AND archived_at IS NULL
             ORDER BY id DESC LIMIT 1',
            ['pid' => $propertyId]
        );

        if (!$list) {
            Connection::query(
                'INSERT INTO shopping_lists (public_id, property_id, name, status, created_at)
                 VALUES (:public_id, :property_id, :name, :status, NOW())',
                [
                    'public_id' => ulid(),
                    'property_id' => $propertyId,
                    'name' => 'Lista activa',
                    'status' => 'active',
                ]
            );
            $listId = (int) Connection::lastInsertId();
        } else {
            $listId = (int) $list['id'];
        }

        $target = $item['target_quantity'] !== null && $item['target_quantity'] !== ''
            ? (float) $item['target_quantity']
            : ((float) $item['minimum_quantity'] + 1);
        $needed = max(1, $target - $currentQty);
        $existing = null;
        foreach (self::matchPending($this->pendingItems($propertyId), $item) as $row) {
            $existing = $row;
            break;
        }

        if ($existing) {
            $this->mirrorShopping($item, [$existing], true);

            return;
        }

        Connection::query(
            'INSERT INTO shopping_list_items (
                shopping_list_id, stock_item_id, name, brand, category, quantity, unit, priority, status, added_by, created_at
             ) VALUES (
                :list_id, :stock_item_id, :name, :brand, :category, :quantity, :unit, :priority, :status, :added_by, NOW()
             )',
            [
                'list_id' => $listId,
                'stock_item_id' => $item['id'],
                'name' => $item['name'],
                'brand' => ($item['brand'] ?? '') !== '' ? $item['brand'] : null,
                'category' => $this->shoppingCategory($item),
                'quantity' => $needed,
                'unit' => $item['unit'],
                'priority' => 'normal',
                'status' => 'pending',
                'added_by' => Auth::id(),
            ]
        );
    }

    /**
     * @param array<string, mixed> $item
     * @param list<array<string, mixed>> $matches
     */
    private function mirrorShopping(array $item, array $matches, bool $refreshQuantity): void
    {
        $needed = null;
        if ($refreshQuantity) {
            $target = $item['target_quantity'] !== null && $item['target_quantity'] !== ''
                ? (float) $item['target_quantity']
                : ((float) $item['minimum_quantity'] + 1);
            $needed = max(1, $target - (float) $item['quantity']);
        }
        $category = $this->shoppingCategory($item);
        foreach ($matches as $row) {
            $params = [
                'name' => $item['name'],
                'unit' => $item['unit'] ?? 'u',
                'sid' => $item['id'],
                'id' => $row['id'],
            ];
            $extra = '';
            $brand = trim((string) ($item['brand'] ?? ''));
            if ($brand !== '') {
                $extra .= ', brand = :brand';
                $params['brand'] = $brand;
            } else {
                $extra .= ', brand = NULL';
            }
            if ($category !== null) {
                $extra .= ', category = :category';
                $params['category'] = $category;
            } else {
                $extra .= ', category = NULL';
            }
            if ($needed !== null) {
                $extra .= ', quantity = :quantity';
                $params['quantity'] = $needed;
            }
            Connection::query(
                'UPDATE shopping_list_items
                 SET name = :name, unit = :unit, stock_item_id = :sid' . $extra . '
                 WHERE id = :id AND status = \'pending\'',
                $params
            );
        }
    }

    /** @param array<string, mixed> $item */
    private function shoppingCategory(array $item): ?string
    {
        $id = (int) ($item['category_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $row = Connection::fetch('SELECT slug FROM stock_categories WHERE id = :id', ['id' => $id]);
        $slug = (string) ($row['slug'] ?? '');

        return array_key_exists($slug, shopping_rubros()) ? $slug : null;
    }

    /** @param array<string, mixed> $item */
    private static function isStocked(array $item): bool
    {
        return (float) $item['quantity'] > (float) $item['minimum_quantity'];
    }

    /** @return list<array<string, mixed>> */
    private function pendingItems(int $propertyId): array
    {
        return Connection::fetchAll(
            'SELECT sli.id, sli.name, sli.stock_item_id
             FROM shopping_list_items sli
             INNER JOIN shopping_lists sl ON sl.id = sli.shopping_list_id
             WHERE sl.property_id = :pid AND sl.status = \'active\' AND sl.archived_at IS NULL
               AND sli.status = \'pending\'',
            ['pid' => $propertyId]
        );
    }

    /**
     * @param list<array<string, mixed>> $pending
     * @param array<string, mixed> $item
     * @return list<array<string, mixed>>
     */
    private static function matchPending(array $pending, array $item, ?string $previousName = null): array
    {
        $name = self::fold((string) ($item['name'] ?? ''));
        $before = self::fold((string) $previousName);
        $id = (int) ($item['id'] ?? 0);
        $matches = [];
        foreach ($pending as $row) {
            $linked = $id > 0 && (int) ($row['stock_item_id'] ?? 0) === $id;
            $rowName = self::fold((string) ($row['name'] ?? ''));
            $sameName = $rowName !== '' && ($rowName === $name || ($before !== '' && $rowName === $before));
            if ($linked || $sameName) {
                $matches[] = $row;
            }
        }

        return $matches;
    }

    private static function fold(string $name): string
    {
        $name = mb_strtolower(trim($name));

        return strtr($name, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}
