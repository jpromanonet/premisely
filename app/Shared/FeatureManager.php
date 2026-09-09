<?php

declare(strict_types=1);

namespace Premisely\Shared;

/**
 * Commercial feature gates. Community = unlimited / all features.
 */
final class FeatureManager
{
    public static function enabled(string $feature, string $edition = 'community'): bool
    {
        // Community edition exposes the full application.
        if ($edition === 'community') {
            return true;
        }

        $hosted = [
            'inventory' => true,
            'stock' => true,
            'shopping' => true,
            'tasks' => true,
            'routines' => true,
            'cleaning' => true,
            'maintenance' => true,
            'repairs' => true,
            'services' => true,
            'expenses' => true,
            'documents' => true,
            'reports' => true,
            'api' => false,
        ];

        return (bool) ($hosted[$feature] ?? true);
    }
}
