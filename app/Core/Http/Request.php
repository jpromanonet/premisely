<?php

declare(strict_types=1);

namespace Premisely\Core\Http;

final class Request
{
    /** @param array<string, mixed> $query @param array<string, mixed> $request @param array<string, mixed> $server @param array<string, mixed> $files */
    public function __construct(
        private array $query,
        private array $request,
        private array $server,
        private array $files,
        private string $method,
        private string $path,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        return new self($_GET, $_POST, $_SERVER, $_FILES, $method, self::resolvePath());
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    public function wantsJson(): bool
    {
        $accept = (string) ($this->server['HTTP_ACCEPT'] ?? '');
        return str_contains($accept, 'application/json')
            || str_starts_with($this->path, '/api/');
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private static function resolvePath(): string
    {
        if (isset($_GET['r']) && is_string($_GET['r']) && $_GET['r'] !== '') {
            $r = $_GET['r'];
            if (str_contains($r, '?')) {
                $r = explode('?', $r, 2)[0];
            }
            return self::normalize($r);
        }

        foreach (['PATH_INFO', 'ORIG_PATH_INFO'] as $key) {
            $info = $_SERVER[$key] ?? '';
            if (is_string($info) && $info !== '' && $info !== '/') {
                return self::normalize($info);
            }
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = self::stripBasePath($path);

        if (str_starts_with($path, '/index.php')) {
            $rest = substr($path, strlen('/index.php'));
            return self::normalize(($rest === false || $rest === '') ? '/' : $rest);
        }

        return self::normalize($path === '' ? '/' : $path);
    }

    private static function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private static function stripBasePath(string $path): string
    {
        $appUrl = (string) (config('app.url') ?? '');
        if ($appUrl !== '') {
            $base = parse_url($appUrl, PHP_URL_PATH) ?: '';
            $base = rtrim((string) $base, '/');
            if ($base !== '' && (str_starts_with($path, $base . '/') || $path === $base)) {
                return substr($path, strlen($base)) ?: '/';
            }
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && $scriptDir !== '' && $scriptDir !== '.' && str_starts_with($path, $scriptDir)) {
            return substr($path, strlen($scriptDir)) ?: '/';
        }

        return $path;
    }
}
