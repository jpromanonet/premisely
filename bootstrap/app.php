<?php

declare(strict_types=1);

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Request;
use Premisely\Core\Http\Response;
use Premisely\Core\Logging\Logger;
use Premisely\Core\Routing\Router;
use Premisely\Core\Security\Csrf;
use Premisely\Core\Support\Env;
use Premisely\Core\Support\RequestId;

$root = dirname(__DIR__);

require_once $root . '/bootstrap/autoload.php';

Env::load($root . '/.env');

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

RequestId::boot();
$logDir = is_writable($root . '/storage/logs') || is_writable($root . '/storage')
    ? $root . '/storage/logs'
    : rtrim((string) (env('STORAGE_PATH') ?: sys_get_temp_dir() . '/premisely'), '/\\') . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
Logger::boot($logDir);

$config = [
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
    'mail' => require $root . '/config/mail.php',
    'storage' => require $root . '/config/storage.php',
    'security' => require $root . '/config/security.php',
];

app()->setConfig($config);
app()->setRoot($root);

if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Request-Id: ' . RequestId::get());
    }

    $sessionName = (string) ($config['security']['session_name'] ?? 'premisely_session');
    $lifetime = (int) ($config['security']['session_lifetime'] ?? 86400);
    $secure = (bool) ($config['security']['session_secure'] ?? false);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start([
            'cookie_lifetime' => $lifetime,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => $secure,
            'use_strict_mode' => true,
            'use_only_cookies' => true,
        ]);
    }

    Csrf::ensureToken();
}

$installed = is_file(storage_path('installed')) || is_file($root . '/storage/installed');

// Prefer connecting whenever DB looks configured (survives /tmp wipe of installed flag).
$dbConfigured = (string) env('DB_DATABASE', '') !== '' && (string) env('DB_USERNAME', '') !== '';

try {
    if ($installed || $dbConfigured || PHP_SAPI === 'cli') {
        Connection::connect($config['database']);
        // Heal installed markers if DB is reachable but flags were lost (e.g. /tmp cleared).
        if (!is_file($root . '/storage/installed')) {
            @mkdir($root . '/storage', 0777, true);
            @file_put_contents($root . '/storage/installed', date('c'));
        }
        if (!is_file(storage_path('installed'))) {
            @file_put_contents(storage_path('installed'), date('c'));
        }
    }
} catch (Throwable $e) {
    Logger::error('database.connect_failed', ['error' => $e->getMessage()]);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'DB error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Premisely</title></head><body>';
    echo '<h1>Premisely</h1><p>No se pudo conectar a la base de datos.</p>';
    if ($debug) {
        echo '<pre>' . e($e->getMessage()) . '</pre>';
    }
    echo '<p><a href="' . e(url('/install')) . '">Ir al instalador</a></p>';
    echo '</body></html>';
    exit;
}

$router = new Router();
require $root . '/routes/web.php';
require $root . '/routes/api.php';

app()->setRouter($router);

return [
    'config' => $config,
    'router' => $router,
    'request' => Request::capture(),
];
