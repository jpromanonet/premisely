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
                'target_quantity' => $data['target_quantity'] !== '' && $data['target_quantity'] !== null
                    ? (float) $data['target_quantity'] : null,
                'last_price' => $data['last_price'] !== '' && $data['last_price'] !== null
                    ? $data['last_price'] : null,
                'currency' => $data['currency'] ?? null,
                'expiration_date' => $data['expiration_date'] ?: null,
                'auto_add_to_shopping' => !empty($data['auto_add_to_shopping']) ? 1 : 0,
            ]
        );

        $id = (int) Connection::lastInsertId();
        ActivityLogger::log($propertyId, 'stock_item', $id, 'created', ['name' => $data['name']]);

        return Connection::fetch('SELECT * FROM stock_items WHERE id = :id', ['id' => $id]) ?? [];
    }

    /** @param array<string, mixed> $data */
    public function update(int $propertyId, int $itemId, array $data): void
    {
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
                'space_id' => self::nullableInt($data['space_id'] ?? null),
                'category_id' => self::nullableInt($data['category_id'] ?? null),
                'name' => (string) $data['name'],
                'brand' => $data['brand'] ?? null,
                'unit' => (string) ($data['unit'] ?? 'u'),
                'minimum_quantity' => (float) ($data['minimum_quantity'] ?? 0),
                'target_quantity' => $data['target_quantity'] !== '' && $data['target_quantity'] !== null
                    ? (float) $data['target_quantity'] : null,
                'last_price' => $data['last_price'] !== '' && $data['last_price'] !== null
                    ? $data['last_price'] : null,
                'currency' => $data['currency'] ?? null,
                'expiration_date' => $data['expiration_date'] ?: null,
                'auto_add_to_shopping' => !empty($data['auto_add_to_shopping']) ? 1 : 0,
                'id' => $itemId,
                'property_id' => $propertyId,
            ]
        );
        ActivityLogger::log($propertyId, 'stock_item', $itemId, 'updated');
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

            if (
                $newQty <= (float) $item['minimum_quantity']
                && (int) $item['auto_add_to_shopping'] === 1
            ) {
                $this->ensureOnShoppingList($propertyId, $item, $newQty);
            }

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

        $existing = Connection::fetch(
            'SELECT id FROM shopping_list_items
             WHERE shopping_list_id = :lid AND stock_item_id = :sid AND status = \'pending\' LIMIT 1',
            ['lid' => $listId, 'sid' => $item['id']]
        );

        if ($existing) {
            return;
        }

        $target = $item['target_quantity'] !== null ? (float) $item['target_quantity'] : ((float) $item['minimum_quantity'] + 1);
        $needed = max(1, $target - $currentQty);

        Connection::query(
            'INSERT INTO shopping_list_items (
                shopping_list_id, stock_item_id, name, quantity, unit, priority, status, added_by, created_at
             ) VALUES (
                :list_id, :stock_item_id, :name, :quantity, :unit, :priority, :status, :added_by, NOW()
             )',
            [
                'list_id' => $listId,
                'stock_item_id' => $item['id'],
                'name' => $item['name'],
                'quantity' => $needed,
                'unit' => $item['unit'],
                'priority' => 'normal',
                'status' => 'pending',
                'added_by' => Auth::id(),
            ]
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}
