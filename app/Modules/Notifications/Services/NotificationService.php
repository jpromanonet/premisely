<?php

declare(strict_types=1);

namespace Premisely\Modules\Notifications\Services;

use Premisely\Core\Database\Connection;

final class NotificationService
{
    public static function notify(
        int $userId,
        ?int $propertyId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $linkPath = null
    ): void {
        $dup = Connection::fetch(
            'SELECT id FROM notifications
             WHERE user_id = :uid AND type = :type AND title = :title
               AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
               AND read_at IS NULL
             LIMIT 1',
            ['uid' => $userId, 'type' => $type, 'title' => $title]
        );
        if ($dup) {
            return;
        }

        Connection::query(
            'INSERT INTO notifications (public_id, user_id, property_id, type, title, body, link_path, created_at)
             VALUES (:pid, :uid, :prop, :type, :title, :body, :link, NOW())',
            [
                'pid' => ulid(),
                'uid' => $userId,
                'prop' => $propertyId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $linkPath,
            ]
        );
    }

    /** Notify all active members of a property. */
    public static function notifyProperty(
        int $propertyId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $linkPath = null
    ): void {
        $members = Connection::fetchAll(
            'SELECT user_id FROM property_members WHERE property_id = :p AND status = \'active\' AND user_id IS NOT NULL',
            ['p' => $propertyId]
        );
        foreach ($members as $m) {
            self::notify((int) $m['user_id'], $propertyId, $type, $title, $body, $linkPath);
        }
    }
}
