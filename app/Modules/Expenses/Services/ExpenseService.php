<?php

declare(strict_types=1);

namespace Premisely\Modules\Expenses\Services;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;

final class ExpenseService
{
    public static function createFromSource(
        int $propertyId,
        string $categorySlug,
        string $title,
        float $amount,
        string $currency,
        string $spentAt,
        string $sourceType,
        int $sourceId,
        ?string $notes = null
    ): int {
        $category = Connection::fetch(
            'SELECT id FROM expense_categories WHERE slug = :s LIMIT 1',
            ['s' => $categorySlug]
        );

        Connection::query(
            'INSERT INTO expenses
            (public_id, property_id, category_id, title, amount, currency, spent_at, notes, source_type, source_id, created_by, created_at)
             VALUES (:pid, :prop, :cat, :title, :amount, :cur, :spent, :notes, :stype, :sid, :uid, NOW())',
            [
                'pid' => ulid(),
                'prop' => $propertyId,
                'cat' => $category['id'] ?? null,
                'title' => $title,
                'amount' => $amount,
                'cur' => $currency,
                'spent' => $spentAt,
                'notes' => $notes,
                'stype' => $sourceType,
                'sid' => $sourceId,
                'uid' => Auth::id(),
            ]
        );

        return (int) Connection::lastInsertId();
    }
}
