<?php

declare(strict_types=1);

namespace Premisely\Modules\Moves\Services;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Modules\Inventory\Services\InventoryService;
use RuntimeException;

final class MoveService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(int $propertyId, array $data): array
    {
        $publicId = ulid();
        Connection::query(
            'INSERT INTO moves (public_id, property_id, destination_property_id, name, status, planned_at, notes, created_by, created_at)
             VALUES (:pid, :prop, :dest, :name, \'draft\', :planned, :notes, :uid, NOW())',
            [
                'pid' => $publicId,
                'prop' => $propertyId,
                'dest' => !empty($data['destination_property_id']) ? (int) $data['destination_property_id'] : null,
                'name' => (string) $data['name'],
                'planned' => $data['planned_at'] ?: null,
                'notes' => $data['notes'] ?? null,
                'uid' => Auth::id(),
            ]
        );
        $id = (int) Connection::lastInsertId();
        ActivityLogger::log($propertyId, 'move', $id, 'created', ['name' => $data['name']]);
        return Connection::fetch('SELECT * FROM moves WHERE id = :id', ['id' => $id]) ?? [];
    }

    public function addItem(int $propertyId, int $moveId, int $inventoryItemId, string $disposition, ?int $boxId, ?int $spaceId, ?string $notes): void
    {
        $item = Connection::fetch(
            'SELECT id FROM inventory_items WHERE id = :id AND property_id = :p AND archived_at IS NULL LIMIT 1',
            ['id' => $inventoryItemId, 'p' => $propertyId]
        );
        if (!$item) {
            throw new RuntimeException('Objeto no encontrado.');
        }
        $allowed = ['transfer', 'sell', 'donate', 'discard', 'leave'];
        if (!in_array($disposition, $allowed, true)) {
            throw new RuntimeException('Disposición inválida.');
        }
        Connection::query(
            'INSERT INTO move_items (public_id, move_id, property_id, inventory_item_id, disposition, box_id, target_space_id, notes, created_at)
             VALUES (:pid, :move, :prop, :item, :disp, :box, :space, :notes, NOW())
             ON DUPLICATE KEY UPDATE disposition = VALUES(disposition), box_id = VALUES(box_id),
               target_space_id = VALUES(target_space_id), notes = VALUES(notes)',
            [
                'pid' => ulid(),
                'move' => $moveId,
                'prop' => $propertyId,
                'item' => $inventoryItemId,
                'disp' => $disposition,
                'box' => $boxId,
                'space' => $spaceId,
                'notes' => $notes,
            ]
        );
    }

    public function apply(int $propertyId, array $move): int
    {
        if (($move['status'] ?? '') === 'completed') {
            throw new RuntimeException('La mudanza ya fue aplicada.');
        }

        $items = Connection::fetchAll(
            'SELECT * FROM move_items WHERE move_id = :m AND applied_at IS NULL',
            ['m' => $move['id']]
        );
        $inventory = new InventoryService();
        $destId = !empty($move['destination_property_id']) ? (int) $move['destination_property_id'] : null;
        $applied = 0;

        Connection::begin();
        try {
            foreach ($items as $row) {
                $itemId = (int) $row['inventory_item_id'];
                $disp = (string) $row['disposition'];
                $spaceId = $row['target_space_id'] !== null ? (int) $row['target_space_id'] : null;
                $boxId = $row['box_id'] !== null ? (int) $row['box_id'] : null;

                if ($disp === 'transfer') {
                    if ($destId && $destId !== $propertyId) {
                        $inventory->transferProperty($propertyId, $itemId, $destId, $spaceId);
                    } else {
                        $inventory->move($propertyId, $itemId, $spaceId);
                    }
                    if ($boxId) {
                        $inv = Connection::fetch('SELECT name FROM inventory_items WHERE id = :id', ['id' => $itemId]);
                        Connection::query(
                            'INSERT INTO storage_box_items (box_id, property_id, inventory_item_id, name, quantity, created_at)
                             VALUES (:box, :prop, :item, :name, 1, NOW())',
                            [
                                'box' => $boxId,
                                'prop' => $destId && $destId !== $propertyId ? $destId : $propertyId,
                                'item' => $itemId,
                                'name' => $inv['name'] ?? 'Objeto',
                            ]
                        );
                    }
                } elseif (in_array($disp, ['sell', 'donate', 'discard', 'leave'], true)) {
                    $inventory->dispose($propertyId, $itemId, $disp);
                }

                Connection::query(
                    'UPDATE move_items SET applied_at = NOW() WHERE id = :id',
                    ['id' => $row['id']]
                );
                $applied++;
            }

            Connection::query(
                'UPDATE moves SET status = \'completed\', completed_at = NOW(), updated_at = NOW() WHERE id = :id',
                ['id' => $move['id']]
            );
            ActivityLogger::log($propertyId, 'move', (int) $move['id'], 'completed', ['items' => $applied]);
            Connection::commit();
        } catch (\Throwable $e) {
            Connection::rollBack();
            throw $e;
        }

        return $applied;
    }
}
