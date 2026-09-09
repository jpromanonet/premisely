<?php

declare(strict_types=1);

namespace Premisely\Modules\Automations\Services;

use Premisely\Core\Database\Connection;
use Premisely\Modules\Integrations\Services\WebhookService;
use Premisely\Modules\Notifications\Services\NotificationService;
use Premisely\Modules\Stock\Services\StockService;

final class AutomationRunner
{
    public const TYPES = [
        'stock_low_to_shopping' => 'Stock bajo → lista de compras',
        'warranty_soon_notify' => 'Garantía por vencer → notificación',
        'service_soon_notify' => 'Servicio por vencer → notificación',
        'maintenance_complete_next_due' => 'Al registrar mantenimiento → recalcular próxima fecha',
    ];

    public function ensureDefaults(int $propertyId): void
    {
        foreach (array_keys(self::TYPES) as $type) {
            $exists = Connection::fetch(
                'SELECT id FROM automation_rules WHERE property_id = :p AND type = :t LIMIT 1',
                ['p' => $propertyId, 't' => $type]
            );
            if ($exists) {
                continue;
            }
            $enabled = $type === 'stock_low_to_shopping' || $type === 'maintenance_complete_next_due' ? 1 : 0;
            Connection::query(
                'INSERT INTO automation_rules (public_id, property_id, type, enabled, created_at)
                 VALUES (:pid, :prop, :type, :en, NOW())',
                ['pid' => ulid(), 'prop' => $propertyId, 'type' => $type, 'en' => $enabled]
            );
        }
    }

    public function runForProperty(int $propertyId, string $propertyPublicId): int
    {
        $this->ensureDefaults($propertyId);
        $rules = Connection::fetchAll(
            'SELECT * FROM automation_rules WHERE property_id = :p AND enabled = 1',
            ['p' => $propertyId]
        );
        $actions = 0;
        foreach ($rules as $rule) {
            $actions += match ($rule['type']) {
                'stock_low_to_shopping' => $this->stockLowToShopping($propertyId, $propertyPublicId),
                'warranty_soon_notify' => $this->warrantySoon($propertyId, $propertyPublicId),
                'service_soon_notify' => $this->serviceSoon($propertyId, $propertyPublicId),
                default => 0,
            };
            Connection::query(
                'UPDATE automation_rules SET last_run_at = NOW() WHERE id = :id',
                ['id' => $rule['id']]
            );
        }
        return $actions;
    }

    public function runAll(): int
    {
        $properties = Connection::fetchAll(
            'SELECT id, public_id FROM properties WHERE archived_at IS NULL'
        );
        $total = 0;
        foreach ($properties as $p) {
            $total += $this->runForProperty((int) $p['id'], (string) $p['public_id']);
        }
        return $total;
    }

    public function isEnabled(int $propertyId, string $type): bool
    {
        $row = Connection::fetch(
            'SELECT enabled FROM automation_rules WHERE property_id = :p AND type = :t LIMIT 1',
            ['p' => $propertyId, 't' => $type]
        );
        return $row ? (bool) $row['enabled'] : false;
    }

    public function afterMaintenanceRecord(int $propertyId, ?int $planId): void
    {
        if (!$planId || !$this->isEnabled($propertyId, 'maintenance_complete_next_due')) {
            return;
        }
        $plan = Connection::fetch(
            'SELECT * FROM maintenance_plans WHERE id = :id AND property_id = :p LIMIT 1',
            ['id' => $planId, 'p' => $propertyId]
        );
        if (!$plan) {
            return;
        }
        $freq = (string) ($plan['frequency_type'] ?? 'yearly');
        $interval = max(1, (int) ($plan['frequency_interval'] ?? 1));
        $sqlInterval = match ($freq) {
            'weekly' => 'INTERVAL ' . ($interval * 7) . ' DAY',
            'monthly' => 'INTERVAL ' . $interval . ' MONTH',
            'quarterly' => 'INTERVAL ' . ($interval * 3) . ' MONTH',
            default => 'INTERVAL ' . $interval . ' YEAR',
        };
        Connection::query(
            "UPDATE maintenance_plans SET next_due_at = DATE_ADD(CURDATE(), {$sqlInterval}), updated_at = NOW()
             WHERE id = :id",
            ['id' => $planId]
        );
    }

    private function stockLowToShopping(int $propertyId, string $publicId): int
    {
        $items = Connection::fetchAll(
            'SELECT * FROM stock_items
             WHERE property_id = :p AND archived_at IS NULL AND quantity <= minimum_quantity',
            ['p' => $propertyId]
        );
        $service = new StockService();
        $n = 0;
        foreach ($items as $item) {
            $service->ensureOnShoppingList($propertyId, $item, (float) $item['quantity']);
            $n++;
        }
        if ($n > 0) {
            NotificationService::notifyProperty(
                $propertyId,
                'stock_low',
                'Stock bajo: ' . $n . ' producto(s)',
                'Se agregaron a la lista de compras automáticamente.',
                '/properties/' . $publicId . '/shopping'
            );
            (new WebhookService())->dispatch($propertyId, 'stock.low', ['count' => $n]);
        }
        return $n;
    }

    private function warrantySoon(int $propertyId, string $publicId): int
    {
        $rows = Connection::fetchAll(
            'SELECT w.*, i.name AS item_name FROM inventory_item_warranties w
             INNER JOIN inventory_items i ON i.id = w.inventory_item_id
             WHERE w.property_id = :p AND w.archived_at IS NULL
               AND w.ends_on IS NOT NULL
               AND w.ends_on BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)',
            ['p' => $propertyId]
        );
        foreach ($rows as $row) {
            NotificationService::notifyProperty(
                $propertyId,
                'warranty_soon',
                'Garantía por vencer: ' . $row['item_name'],
                'Vence el ' . $row['ends_on'],
                '/properties/' . $publicId . '/inventory/' . ($row['inventory_item_id'] ?? '')
            );
        }
        return count($rows);
    }

    private function serviceSoon(int $propertyId, string $publicId): int
    {
        $rows = Connection::fetchAll(
            'SELECT * FROM property_services
             WHERE property_id = :p AND archived_at IS NULL
               AND next_due_date IS NOT NULL
               AND next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)',
            ['p' => $propertyId]
        );
        foreach ($rows as $row) {
            NotificationService::notifyProperty(
                $propertyId,
                'service_soon',
                'Servicio próximo: ' . $row['name'],
                'Vence el ' . $row['next_due_date'],
                '/properties/' . $publicId . '/services'
            );
        }
        return count($rows);
    }
}
