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
