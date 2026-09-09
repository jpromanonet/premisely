<?php

declare(strict_types=1);

namespace Premisely\Core\Security;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function ensureToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function token(): string
    {
        return self::ensureToken();
    }

    public static function validate(?string $token): bool
    {
        $session = $_SESSION[self::SESSION_KEY] ?? '';
        return is_string($token) && $session !== '' && hash_equals((string) $session, $token);
    }
}
