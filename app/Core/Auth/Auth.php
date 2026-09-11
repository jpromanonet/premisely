<?php

declare(strict_types=1);

namespace Premisely\Core\Auth;

use Premisely\Core\Database\Connection;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        if (self::loginThrottled()) {
            return false;
        }

        $user = Connection::fetch(
            'SELECT id, public_id, name, email, password_hash, avatar_path, locale, timezone, preferred_currency, is_active
             FROM users WHERE email = :email LIMIT 1',
            ['email' => strtolower(trim($email))]
        );

        if (!$user || !(int) $user['is_active']) {
            self::recordLoginFailure();
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            self::recordLoginFailure();
            return false;
        }

        self::clearLoginFailures();
        session_regenerate_id(true);
        self::setSessionUser($user);

        Connection::query('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);

        return true;
    }

    public static function loginUser(array $user): void
    {
        session_regenerate_id(true);
        self::setSessionUser($user);
    }

    /** @param array<string, mixed> $user */
    private static function setSessionUser(array $user): void
    {
        self::writeSessionUser($user);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool) ($params['secure'] ?? false),
                (bool) ($params['httponly'] ?? true)
            );
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Debés iniciar sesión.');
            redirect('/login');
        }
    }

    /** Set authenticated user without regenerating the session (API tokens). */
    public static function setUser(array $user): void
    {
        self::writeSessionUser($user);
    }

    /** @param array<string, mixed> $user */
    private static function writeSessionUser(array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'public_id' => (string) $user['public_id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'avatar_path' => $user['avatar_path'] ?? null,
            'locale' => (string) ($user['locale'] ?? 'es'),
            'timezone' => (string) ($user['timezone'] ?? 'UTC'),
            'preferred_currency' => (string) ($user['preferred_currency'] ?? 'ARS'),
        ];
        $_SESSION['_last_activity'] = time();
    }

    public static function loginThrottled(): bool
    {
        $key = self::throttleKey();
        $bucket = $_SESSION['_login_throttle'][$key] ?? null;
        if (!is_array($bucket)) {
            return false;
        }
        return (int) ($bucket['locked_until'] ?? 0) > time();
    }

    private static function recordLoginFailure(): void
    {
        $key = self::throttleKey();
        $max = (int) config('security.login_max_attempts', 5);
        $lock = (int) config('security.login_lock_seconds', 300);
        $bucket = $_SESSION['_login_throttle'][$key] ?? ['count' => 0, 'locked_until' => 0];
        $bucket['count'] = (int) $bucket['count'] + 1;
        if ($bucket['count'] >= $max) {
            $bucket['locked_until'] = time() + $lock;
            $bucket['count'] = 0;
        }
        $_SESSION['_login_throttle'][$key] = $bucket;
    }

    private static function clearLoginFailures(): void
    {
        unset($_SESSION['_login_throttle'][self::throttleKey()]);
    }

    private static function throttleKey(): string
    {
        return substr(hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli')), 0, 32);
    }
}
