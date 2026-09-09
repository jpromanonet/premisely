<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Premisely'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'key' => env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'es'),
];
