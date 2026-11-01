<?php

declare(strict_types=1);

namespace Premisely\Shared;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;

final class ExpenseHelper
{
    public static function categoryIdBySlug(string $slug): ?int
    {
        $row = Connection::fetch(
            'SELECT id FROM expense_categories WHERE slug = :s LIMIT 1',
            ['s' => $slug]
        );

        return $row ? (int) $row['id'] : null;
    }

    /** @return list<array<string,mixed>> */
    public static function activeFixed(int $propertyId): array
    {
        return Connection::fetchAll(
            'SELECT r.*, c.name AS category_name, c.slug AS category_slug
             FROM recurring_expenses r
             LEFT JOIN expense_categories c ON c.id = r.category_id
             WHERE r.property_id = :pid AND r.archived_at IS NULL AND r.is_active = 1
             ORDER BY r.title',
            ['pid' => $propertyId]
        );
    }

    /** Amount of active fixed expenses that apply to month Y-m. */
    public static function fixedTotalForMonth(int $propertyId, string $monthYm): float
    {
        $total = 0.0;
        foreach (self::activeFixed($propertyId) as $fixed) {
            if (self::fixedAppliesInMonth($fixed, $monthYm)) {
                $total += (float) $fixed['amount'];
            }
        }

        return $total;
    }

    /**
     * @param list<array{category_name:string,total:float|int|string}> $categories
     * @return list<array{category_name:string,total:float}>
     */
    public static function mergeFixedIntoCategories(int $propertyId, string $monthYm, array $categories): array
    {
        $byName = [];
        foreach ($categories as $row) {
            $name = (string) ($row['category_name'] ?? 'Sin categoría');
            $byName[$name] = ($byName[$name] ?? 0.0) + (float) $row['total'];
        }

        foreach (self::activeFixed($propertyId) as $fixed) {
            if (!self::fixedAppliesInMonth($fixed, $monthYm)) {
                continue;
            }
            $name = (string) ($fixed['category_name'] ?? 'Sin categoría');
            if ($name === '') {
                $name = 'Sin categoría';
            }
            $byName[$name] = ($byName[$name] ?? 0.0) + (float) $fixed['amount'];
        }

        $merged = [];
        foreach ($byName as $name => $total) {
            $merged[] = ['category_name' => $name, 'total' => $total];
        }
        usort($merged, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $merged;
    }

    /**
     * @param list<array{month:string,total:float}> $trend
     * @return list<array{month:string,total:float}>
     */
    public static function mergeFixedIntoTrend(int $propertyId, array $trend): array
    {
        foreach ($trend as &$point) {
            $point['total'] = (float) $point['total'] + self::fixedTotalForMonth($propertyId, (string) $point['month']);
        }
        unset($point);

        return $trend;
    }

    /** @param array<string,mixed> $fixed */
    public static function fixedAppliesInMonth(array $fixed, string $monthYm): bool
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
            return false;
        }

        $created = (string) ($fixed['created_at'] ?? '');
        if ($created !== '' && substr($created, 0, 7) > $monthYm) {
            return false;
        }

        $freq = (string) ($fixed['frequency_type'] ?? 'monthly');
        $anchor = (string) ($fixed['next_due_date'] ?? '');
        if ($anchor === '' || !preg_match('/^\d{4}-\d{2}-\d{2}/', $anchor)) {
            $anchor = $created !== '' ? substr($created, 0, 10) : ($monthYm . '-01');
        }
        $anchorYm = substr($anchor, 0, 7);

        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $anchorYm . '-01');
        $target = \DateTimeImmutable::createFromFormat('Y-m-d', $monthYm . '-01');
        if (!$start || !$target) {
            return $freq === 'monthly';
        }

        $months = ((int) $target->format('Y') - (int) $start->format('Y')) * 12
            + ((int) $target->format('n') - (int) $start->format('n'));

        return match ($freq) {
            'bimonthly' => $months % 2 === 0,
            'quarterly' => $months % 3 === 0,
            'yearly' => $target->format('m') === $start->format('m'),
            default => true,
        };
    }

    /**
     * @param array{
     *   property_id:int,
     *   title:string,
     *   amount:float|int|string,
     *   currency?:string,
     *   spent_at?:string,
     *   notes?:?string,
     *   category_slug?:string,
     *   source_type?:?string,
     *   source_id?:?int
     * } $data
     */
    public static function create(array $data): int
    {
        $categoryId = null;
        if (!empty($data['category_slug'])) {
            $categoryId = self::categoryIdBySlug((string) $data['category_slug']);
        }

        Connection::query(
            'INSERT INTO expenses (
                public_id, property_id, category_id, title, amount, currency,
                spent_at, notes, source_type, source_id, created_by, created_at
             ) VALUES (
                :pid, :property_id, :category_id, :title, :amount, :currency,
                :spent_at, :notes, :source_type, :source_id, :created_by, NOW()
             )',
            [
                'pid' => ulid(),
                'property_id' => (int) $data['property_id'],
                'category_id' => $categoryId,
                'title' => (string) $data['title'],
                'amount' => $data['amount'],
                'currency' => (string) ($data['currency'] ?? 'ARS'),
                'spent_at' => (string) ($data['spent_at'] ?? date('Y-m-d')),
                'notes' => $data['notes'] ?? null,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'created_by' => Auth::id(),
            ]
        );

        return (int) Connection::lastInsertId();
    }
}
