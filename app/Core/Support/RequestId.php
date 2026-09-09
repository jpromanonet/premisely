<?php

declare(strict_types=1);

namespace Premisely\Core\Support;

final class RequestId
{
    private static string $id = '';

    public static function boot(): void
    {
        self::$id = Ulid::generate();
    }

    public static function get(): string
    {
        if (self::$id === '') {
            self::boot();
        }
        return self::$id;
    }
}
