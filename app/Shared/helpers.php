<?php

declare(strict_types=1);

/**
 * Global helpers for Premisely.
 */

use Premisely\Core\Support\App;
use Premisely\Core\Support\Ulid;
use Premisely\Core\View\View;
use Premisely\Core\Security\Csrf;

if (!function_exists('app')) {
    function app(): App
    {
        return App::instance();
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }
        $value = getenv($key);
        if ($value !== false) {
            return (string) $value;
        }
        return $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return app()->config($key, $default);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = rtrim(app()->root(), '/\\');
        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '/\\');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $override = env('STORAGE_PATH');
        if ($override !== null && $override !== '') {
            $base = rtrim($override, '/\\');
        } else {
            $base = base_path('storage');
            if (!is_writable($base) && is_writable(sys_get_temp_dir())) {
                $base = rtrim(sys_get_temp_dir(), '/\\') . '/premisely';
            }
        }
        if (!is_dir($base)) {
            @mkdir($base, 0777, true);
        }
        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '/\\');
    }
}

if (!function_exists('view_path')) {
    function view_path(string $path = ''): string
    {
        return base_path('resources/views' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('url')) {
    function url(string $path = '/', array $query = []): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $appUrl = rtrim((string) config('app.url', ''), '/');
        if ($appUrl === '') {
            $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $appUrl = ($script === '/' || $script === '\\' || $script === '.') ? '' : rtrim($script, '/');
        }

        // Compatible con Apache sin rewrite: /premisely/index.php?r=/ruta
        $useQueryRouting = true;
        $href = $useQueryRouting
            ? $appUrl . '/index.php?r=' . rawurlencode($path === '' ? '/' : $path)
            : $appUrl . ($path === '/' ? '/' : $path);

        if ($query !== []) {
            $href .= (str_contains($href, '?') ? '&' : '?') . http_build_query($query);
        }

        return $href;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $rel = 'public/assets/' . ltrim($path, '/');
        $url = $appUrl . '/' . $rel;
        $file = base_path($rel);
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }
        return $url;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $code = 302): never
    {
        $target = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : url($path);
        header('Location: ' . $target, true, $code);
        exit;
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        return View::render($name, $data);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = $_SESSION['_old'] ?? [];
        return $old[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('ulid')) {
    function ulid(): string
    {
        return Ulid::generate();
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('property_type_label')) {
    function property_type_label(?string $type): string
    {
        return match ((string) $type) {
            'casa' => 'Casa',
            'departamento' => 'Departamento',
            'oficina' => 'Oficina',
            'local' => 'Local',
            'quinta' => 'Quinta',
            'casa_vacaciones' => 'Casa de vacaciones',
            'otra', 'otro' => 'Otra',
            default => ucfirst((string) $type),
        };
    }
}

if (!function_exists('property_tenure_label')) {
    function property_tenure_label(?string $tenure): string
    {
        return match ((string) $tenure) {
            'alquiler' => 'Alquiler',
            'propia' => 'Propia',
            default => 'Propia',
        };
    }
}

if (!function_exists('member_role_label')) {
    function member_role_label(?string $role): string
    {
        return match ((string) $role) {
            'owner' => 'Administrador',
            'admin' => 'Admin',
            'member' => 'Miembro',
            'collaborator' => 'Colaborador',
            'viewer' => 'Lectura',
            default => (string) $role,
        };
    }
}
