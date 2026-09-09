<?php

declare(strict_types=1);

namespace Premisely\Core\Cache;

final class FileCache implements CacheInterface
{
    public function __construct(private string $dir)
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return $default;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return $default;
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload) || ($payload['expires_at'] ?? 0) < time()) {
            @unlink($path);
            return $default;
        }
        return $payload['value'] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttlSeconds = 3600): void
    {
        file_put_contents($this->path($key), json_encode([
            'expires_at' => time() + $ttlSeconds,
            'value' => $value,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function path(string $key): string
    {
        return $this->dir . '/' . hash('sha256', $key) . '.cache';
    }
}
