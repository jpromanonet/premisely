<?php

declare(strict_types=1);

return [
    'host' => env('MAIL_HOST', ''),
    'port' => (int) env('MAIL_PORT', '587'),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'from' => env('MAIL_FROM', 'noreply@premisely.local'),
    'from_name' => env('MAIL_FROM_NAME', 'Premisely'),
];
