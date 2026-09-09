<?php

declare(strict_types=1);

namespace Premisely\Core\Support;

final class App
{
    private static ?self $instance = null;

    private string $root = '';

    /** @var array<string, mixed> */
    private array $config = [];

    private mixed $router = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function setRoot(string $root): void
    {
        $this->root = $root;
    }

    public function root(): string
    {
        return $this->root;
    }

    /** @param array<string, mixed> $config */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function setRouter(mixed $router): void
    {
        $this->router = $router;
    }

    public function router(): mixed
    {
        return $this->router;
    }
}
