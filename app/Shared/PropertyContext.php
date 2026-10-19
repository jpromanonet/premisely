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

    /** Pending day-tasks move to today once that day has passed. Repairs stay undated. */
    public static function carryOpenTasks(): void
    {
        $pid = self::propertyId();
        if ($pid <= 0) {
            return;
        }
        $today = date('Y-m-d');
        Connection::query(
            'UPDATE tasks SET due_date = :today, updated_at = NOW()
             WHERE property_id = :p AND archived_at IS NULL
               AND status IN (\'pending\', \'in_progress\')
               AND (kind IS NULL OR kind <> \'reparacion\')
               AND due_date IS NOT NULL
               AND due_date < :before',
            ['today' => $today, 'before' => $today, 'p' => $pid]
        );
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
