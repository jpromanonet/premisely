<?php

declare(strict_types=1);

namespace Premisely\Shared;

/**
 * Configurable limits for hosted plans. Community has no artificial limits.
 */
final class LimitManager
{
    /** @return array<string, int|null> null = unlimited */
    public static function limits(string $plan = 'community'): array
    {
        return match ($plan) {
            'free_hosted' => [
                'properties' => 2,
                'members' => 5,
                'inventory' => 200,
                'storage_mb' => 200,
            ],
            'supporter_hosted' => [
                'properties' => 20,
                'members' => 50,
                'inventory' => 5000,
                'storage_mb' => 5000,
            ],
            default => [
                'properties' => null,
                'members' => null,
                'inventory' => null,
                'storage_mb' => null,
            ],
        };
    }

    public static function allows(string $plan, string $resource, int $current): bool
    {
        $limits = self::limits($plan);
        $max = $limits[$resource] ?? null;
        if ($max === null) {
            return true;
        }
        return $current < (int) $max;
    }
}
