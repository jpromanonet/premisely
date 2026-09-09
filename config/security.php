<?php

declare(strict_types=1);

return [
    'session_name' => env('SESSION_NAME', 'premisely_session'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', '86400'),
    'session_secure' => filter_var(env('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN),
    'csrf_key' => '_csrf_token',
    'login_max_attempts' => 5,
    'login_lock_seconds' => 300,
];
