<?php

declare(strict_types=1);

namespace Premisely\Core\View;

use RuntimeException;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $name, array $data = []): string
    {
        $path = view_path(str_replace('.', '/', $name) . '.php');
        if (!is_file($path)) {
            throw new RuntimeException('View not found: ' . $name);
        }

        $render = static function (string $__path, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            include $__path;
            return (string) ob_get_clean();
        };

        $content = $render($path, $data);

        if (!empty($data['layout'])) {
            $layout = (string) $data['layout'];
        } else {
            $layout = str_starts_with($name, 'auth/') || str_starts_with($name, 'install/') || str_starts_with($name, 'errors/')
                ? 'layouts/guest'
                : 'layouts/app';
        }

        if ($layout === false || $layout === 'none') {
            return $content;
        }

        return $render(view_path(str_replace('.', '/', $layout) . '.php'), array_merge($data, [
            'content' => $content,
        ]));
    }
}
