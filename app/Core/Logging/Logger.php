<?php

declare(strict_types=1);

namespace Premisely\Core\Logging;

use Premisely\Core\Support\RequestId;

final class Logger
{
    private static string $dir = '';

    public static function boot(string $dir): void
    {
        self::$dir = $dir;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        self::write('app.log', 'info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function error(string $message, array $context = []): void
    {
        self::write('error.log', 'error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function security(string $message, array $context = []): void
    {
        self::write('security.log', 'security', $message, $context);
    }

    /** @param array<string, mixed> $context */
    private static function write(string $file, string $level, string $message, array $context): void
    {
        if (self::$dir === '') {
            return;
        }
        $payload = [
            'level' => $level,
            'request_id' => RequestId::get(),
            'user_id' => $_SESSION['user']['id'] ?? null,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('c'),
        ];
        file_put_contents(
            self::$dir . '/' . $file,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
