<?php

declare(strict_types=1);

namespace Premisely\Modules\Activity\Services;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;

final class ActivityLogger
{
    /** @param array<string, mixed>|null $metadata */
    public static function log(
        ?int $propertyId,
        string $entityType,
        ?int $entityId,
        string $action,
        ?array $metadata = null
    ): void {
        Connection::query(
            'INSERT INTO activity_log (property_id, user_id, entity_type, entity_id, action, metadata_json, created_at)
             VALUES (:pid, :uid, :etype, :eid, :action, :meta, NOW())',
            [
                'pid' => $propertyId,
                'uid' => Auth::id(),
                'etype' => $entityType,
                'eid' => $entityId,
                'action' => $action,
                'meta' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function forProperty(int $propertyId, int $limit = 30): array
    {
        return Connection::fetchAll(
            'SELECT a.*, u.name AS user_name
             FROM activity_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.property_id = :pid
             ORDER BY a.created_at DESC
             LIMIT ' . (int) $limit,
            ['pid' => $propertyId]
        );
    }
}
