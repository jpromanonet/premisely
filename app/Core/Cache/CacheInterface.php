<?php

declare(strict_types=1);

namespace Premisely\Core\Cache;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, int $ttlSeconds = 3600): void;

    public function delete(string $key): void;
}
