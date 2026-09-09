<?php

declare(strict_types=1);

/**
 * One-shot migrator for already-installed environments.
 * Open: /premisely/scripts/migrate.php?key=local_admin
 */

$root = dirname(__DIR__);
require $root . '/bootstrap/autoload.php';

use Premisely\Core\Database\Connection;
use Premisely\Core\Database\Migrator;
use Premisely\Core\Support\Env;

Env::load($root . '/.env');

$key = $_GET['key'] ?? '';
$expected = env('DB_PASSWORD', 'local_admin');
if (!hash_equals((string) $expected, (string) $key) && $key !== 'local_admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

try {
    Connection::connect([
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'premisely'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ]);
    $count = Migrator::run($root . '/database/migrations');
    echo "OK migrations applied: {$count}\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
