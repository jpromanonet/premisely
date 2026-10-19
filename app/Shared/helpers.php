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
        $fragment = '';
        if (str_contains($path, '#')) {
            [$path, $fragment] = explode('#', $path, 2);
            $fragment = '#' . $fragment;
        }
        $path = '/' . ltrim($path, '/');
        if ($path === '//') {
            $path = '/';
        }
        if (str_contains($path, '?')) {
            [$path, $qs] = explode('?', $path, 2);
            $embedded = [];
            parse_str($qs, $embedded);
            $query = array_merge($embedded, $query);
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

        return $href . $fragment;
    }
}

if (!function_exists('form_target')) {
    /** Action for GET forms. The route goes in a hidden field so the query is not discarded. */
    function form_target(): string
    {
        $full = url('/');
        $cut = strpos($full, '?');

        return $cut === false ? $full : substr($full, 0, $cut);
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

if (!function_exists('shopping_rubros')) {
    /** @return array<string, string> */
    function shopping_rubros(): array
    {
        return [
            'almacen' => 'Almacén / supermercado',
            'verduleria' => 'Verdulería / frutas',
            'carniceria' => 'Carnicería / granjita',
            'congelados' => 'Congelados',
            'limpieza' => 'Limpieza',
            'higiene' => 'Higiene personal',
            'miscelanea' => 'Miscelánea',
        ];
    }
}

if (!function_exists('rubro_palette')) {
    /** @return array<string, array{bg:string,ink:string}> */
    function rubro_palette(): array
    {
        return [
            'almacen' => ['bg' => 'C5EDE8', 'ink' => '12343A'],
            'alimentos' => ['bg' => 'F3D6A8', 'ink' => '6A3E10'],
            'verduleria' => ['bg' => 'CDE8A8', 'ink' => '2A4A12'],
            'carniceria' => ['bg' => 'F6C7B0', 'ink' => '6B2E16'],
            'congelados' => ['bg' => 'C9DFF5', 'ink' => '1A3E66'],
            'limpieza' => ['bg' => 'DDD0F2', 'ink' => '3E2A66'],
            'higiene' => ['bg' => 'F8E3B0', 'ink' => '6A4A10'],
            'miscelanea' => ['bg' => 'E4E8EE', 'ink' => '3A4450'],
            'otros' => ['bg' => 'E8EEF2', 'ink' => '3A4450'],
            'sin' => ['bg' => 'EEF2F6', 'ink' => '5C6B7A'],
        ];
    }
}

if (!function_exists('format_qty')) {
    function format_qty(mixed $value): string
    {
        $n = (float) $value;
        if (abs($n - round($n)) < 0.0005) {
            return number_format((float) round($n), 0, ',', '.');
        }
        $text = number_format($n, 2, ',', '.');

        return rtrim(rtrim($text, '0'), ',');
    }
}

if (!function_exists('format_qty_input')) {
    function format_qty_input(mixed $value): string
    {
        $n = (float) $value;
        if (abs($n - round($n)) < 0.0005) {
            return (string) (int) round($n);
        }

        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
}

if (!function_exists('format_money')) {
    function format_money(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('unit_label')) {
    function unit_label(mixed $quantity, ?string $unit): string
    {
        $one = abs((float) $quantity - 1) < 0.0005;
        $key = strtolower(trim((string) $unit));
        $map = [
            '' => ['unidad', 'unidades'],
            'u' => ['unidad', 'unidades'],
            'un' => ['unidad', 'unidades'],
            'unidad' => ['unidad', 'unidades'],
            'unidades' => ['unidad', 'unidades'],
            'paquete' => ['paquete', 'paquetes'],
            'paquetes' => ['paquete', 'paquetes'],
            'rollo' => ['rollo', 'rollos'],
            'botella' => ['botella', 'botellas'],
            'lata' => ['lata', 'latas'],
            'caja' => ['caja', 'cajas'],
        ];
        if (isset($map[$key])) {
            return $one ? $map[$key][0] : $map[$key][1];
        }

        return $key !== '' ? trim((string) $unit) : ($one ? 'unidad' : 'unidades');
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
