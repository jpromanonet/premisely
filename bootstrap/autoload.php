<?php

declare(strict_types=1);

/**
 * Minimal PSR-4 autoloader (Composer optional).
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Premisely\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once dirname(__DIR__) . '/app/Shared/helpers.php';
