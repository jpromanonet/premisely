<?php

declare(strict_types=1);

namespace Premisely\Shared;

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
}
