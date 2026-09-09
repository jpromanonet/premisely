<?php

declare(strict_types=1);

namespace Premisely\Modules\Inventory\Services;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Modules\Activity\Services\ActivityLogger;

final class InventoryService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(int $propertyId, array $data): array
    {
        $publicId = ulid();
        Connection::query(
            'INSERT INTO inventory_items (
                public_id, property_id, space_id, owner_member_id, category_id, name, description,
                brand, model, serial_number, internal_code, ownership_type, purchase_date,
                purchase_store, purchase_price, purchase_currency, estimated_value,
                estimated_value_currency, `condition`, status, warranty_until, created_at
             ) VALUES (
                :public_id, :property_id, :space_id, :owner_member_id, :category_id, :name, :description,
                :brand, :model, :serial_number, :internal_code, :ownership_type, :purchase_date,
                :purchase_store, :purchase_price, :purchase_currency, :estimated_value,
                :estimated_value_currency, :condition, :status, :warranty_until, NOW()
             )',
            [
                'public_id' => $publicId,
                'property_id' => $propertyId,
                'space_id' => self::nullableInt($data['space_id'] ?? null),
                'owner_member_id' => self::nullableInt($data['owner_member_id'] ?? null),
                'category_id' => self::nullableInt($data['category_id'] ?? null),
                'name' => (string) $data['name'],
                'description' => $data['description'] ?? null,
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'internal_code' => $data['internal_code'] ?? null,
                'ownership_type' => (string) ($data['ownership_type'] ?? 'propiedad'),
                'purchase_date' => $data['purchase_date'] ?: null,
                'purchase_store' => $data['purchase_store'] ?? null,
                'purchase_price' => $data['purchase_price'] !== '' && $data['purchase_price'] !== null ? $data['purchase_price'] : null,
                'purchase_currency' => $data['purchase_currency'] ?? null,
                'estimated_value' => $data['estimated_value'] !== '' && $data['estimated_value'] !== null ? $data['estimated_value'] : null,
                'estimated_value_currency' => $data['estimated_value_currency'] ?? null,
                'condition' => (string) ($data['condition'] ?? 'bueno'),
                'status' => (string) ($data['status'] ?? 'activo'),
                'warranty_until' => $data['warranty_until'] ?: null,
            ]
        );

        $id = (int) Connection::lastInsertId();
        ActivityLogger::log($propertyId, 'inventory_item', $id, 'created', ['name' => $data['name']]);

        return Connection::fetch('SELECT * FROM inventory_items WHERE id = :id', ['id' => $id]) ?? [];
    }

    /** @param array<string, mixed> $data */
    public function update(int $propertyId, int $itemId, array $data): void
    {
        Connection::query(
            'UPDATE inventory_items SET
                space_id = :space_id,
                owner_member_id = :owner_member_id,
                category_id = :category_id,
                name = :name,
                description = :description,
                brand = :brand,
                model = :model,
                serial_number = :serial_number,
                internal_code = :internal_code,
                ownership_type = :ownership_type,
                purchase_date = :purchase_date,
                purchase_store = :purchase_store,
                purchase_price = :purchase_price,
                purchase_currency = :purchase_currency,
                estimated_value = :estimated_value,
                estimated_value_currency = :estimated_value_currency,
                `condition` = :condition,
                status = :status,
                warranty_until = :warranty_until,
                updated_at = NOW()
             WHERE id = :id AND property_id = :property_id AND archived_at IS NULL',
            [
                'space_id' => self::nullableInt($data['space_id'] ?? null),
                'owner_member_id' => self::nullableInt($data['owner_member_id'] ?? null),
                'category_id' => self::nullableInt($data['category_id'] ?? null),
                'name' => (string) $data['name'],
                'description' => $data['description'] ?? null,
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'internal_code' => $data['internal_code'] ?? null,
                'ownership_type' => (string) ($data['ownership_type'] ?? 'propiedad'),
                'purchase_date' => $data['purchase_date'] ?: null,
                'purchase_store' => $data['purchase_store'] ?? null,
                'purchase_price' => $data['purchase_price'] !== '' && $data['purchase_price'] !== null ? $data['purchase_price'] : null,
                'purchase_currency' => $data['purchase_currency'] ?? null,
                'estimated_value' => $data['estimated_value'] !== '' && $data['estimated_value'] !== null ? $data['estimated_value'] : null,
                'estimated_value_currency' => $data['estimated_value_currency'] ?? null,
                'condition' => (string) ($data['condition'] ?? 'bueno'),
                'status' => (string) ($data['status'] ?? 'activo'),
                'warranty_until' => $data['warranty_until'] ?: null,
                'id' => $itemId,
                'property_id' => $propertyId,
            ]
        );

        ActivityLogger::log($propertyId, 'inventory_item', $itemId, 'updated');
    }

    public function move(int $propertyId, int $itemId, ?int $spaceId): void
    {
        Connection::query(
            'UPDATE inventory_items SET space_id = :space_id, updated_at = NOW()
             WHERE id = :id AND property_id = :property_id AND archived_at IS NULL',
            ['space_id' => $spaceId, 'id' => $itemId, 'property_id' => $propertyId]
        );

        Connection::query(
            'INSERT INTO inventory_item_events (inventory_item_id, property_id, user_id, event_type, notes, created_at)
             VALUES (:item_id, :property_id, :user_id, :event_type, :notes, NOW())',
            [
                'item_id' => $itemId,
                'property_id' => $propertyId,
                'user_id' => Auth::id(),
                'event_type' => 'moved',
                'notes' => $spaceId ? 'Movido a espacio #' . $spaceId : 'Sin espacio',
            ]
        );

        ActivityLogger::log($propertyId, 'inventory_item', $itemId, 'moved', ['space_id' => $spaceId]);
    }

    public function transferProperty(int $fromPropertyId, int $itemId, int $toPropertyId, ?int $spaceId = null): void
    {
        Connection::query(
            'UPDATE inventory_items SET property_id = :to_pid, space_id = :space_id, updated_at = NOW()
             WHERE id = :id AND property_id = :from_pid AND archived_at IS NULL',
            [
                'to_pid' => $toPropertyId,
                'space_id' => $spaceId,
                'id' => $itemId,
                'from_pid' => $fromPropertyId,
            ]
        );

        Connection::query(
            'INSERT INTO inventory_item_events (inventory_item_id, property_id, user_id, event_type, notes, created_at)
             VALUES (:item_id, :property_id, :user_id, :event_type, :notes, NOW())',
            [
                'item_id' => $itemId,
                'property_id' => $toPropertyId,
                'user_id' => Auth::id(),
                'event_type' => 'transferred',
                'notes' => 'Trasladado desde propiedad #' . $fromPropertyId,
            ]
        );

        ActivityLogger::log($fromPropertyId, 'inventory_item', $itemId, 'transferred_out', [
            'to_property_id' => $toPropertyId,
        ]);
        ActivityLogger::log($toPropertyId, 'inventory_item', $itemId, 'transferred_in', [
            'from_property_id' => $fromPropertyId,
        ]);
    }

    public function dispose(int $propertyId, int $itemId, string $disposition): void
    {
        $statusMap = [
            'sell' => 'vendido',
            'donate' => 'donado',
            'discard' => 'descartado',
            'leave' => 'dejado',
        ];
        $status = $statusMap[$disposition] ?? 'archivado';
        Connection::query(
            'UPDATE inventory_items SET archived_at = NOW(), status = :status, updated_at = NOW()
             WHERE id = :id AND property_id = :property_id AND archived_at IS NULL',
            ['status' => $status, 'id' => $itemId, 'property_id' => $propertyId]
        );
        Connection::query(
            'INSERT INTO inventory_item_events (inventory_item_id, property_id, user_id, event_type, notes, created_at)
             VALUES (:item_id, :property_id, :user_id, :event_type, :notes, NOW())',
            [
                'item_id' => $itemId,
                'property_id' => $propertyId,
                'user_id' => Auth::id(),
                'event_type' => 'disposed',
                'notes' => 'Disposición: ' . $disposition,
            ]
        );
        ActivityLogger::log($propertyId, 'inventory_item', $itemId, 'disposed', ['disposition' => $disposition]);
    }

    public function archive(int $propertyId, int $itemId): void
    {
        Connection::query(
            'UPDATE inventory_items SET archived_at = NOW(), status = \'archivado\', updated_at = NOW()
             WHERE id = :id AND property_id = :property_id AND archived_at IS NULL',
            ['id' => $itemId, 'property_id' => $propertyId]
        );
        ActivityLogger::log($propertyId, 'inventory_item', $itemId, 'archived');
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}
