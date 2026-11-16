<?php

declare(strict_types=1);

namespace Premisely\Shared;

use Premisely\Core\Database\Connection;

final class PropertyContext
{
    /** @return array<string, mixed> */
    public static function property(): array
    {
        return $GLOBALS['current_property'] ?? [];
    }

    /** @return array<string, mixed> */
    public static function member(): array
    {
        return $GLOBALS['current_member'] ?? [];
    }

    public static function propertyId(): int
    {
        return (int) (self::property()['id'] ?? 0);
    }

    public static function role(): string
    {
        return (string) (self::member()['role'] ?? 'viewer');
    }

    public static function canManage(): bool
    {
        return in_array(self::role(), ['owner', 'admin'], true);
    }

    public static function canEdit(): bool
    {
        return in_array(self::role(), ['owner', 'admin', 'member', 'collaborator'], true);
    }

    /**
     * Calendar items stay on their start day and remain visible forward while open.
     * No longer bumps due_date/note_date to today (that made some appear only "today"
     * and others show on past days).
     */
    public static function carryOpenTasks(): void
    {
        // Intentionally empty: visibility is due_date/note_date <= selected day.
    }

    /** @return list<array<string, mixed>> */
    public static function assignableMembers(): array
    {
        return Connection::fetchAll(
            'SELECT pm.id, COALESCE(NULLIF(u.name, \'\'), pm.display_name) AS display_name
             FROM property_members pm
             LEFT JOIN users u ON u.id = pm.user_id
             WHERE pm.property_id = :p AND pm.status = \'active\'
             ORDER BY display_name',
            ['p' => self::propertyId()]
        );
    }
}
