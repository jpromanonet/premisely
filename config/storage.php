<?php

declare(strict_types=1);

$override = env('STORAGE_PATH');
if ($override) {
    $storageRoot = rtrim($override, '/\\') . '/uploads';
} else {
    $default = dirname(__DIR__) . '/storage/uploads';
    if (is_writable(dirname($default)) || (@file_exists($default) && is_writable($default))) {
        $storageRoot = $default;
    } else {
        $storageRoot = rtrim(sys_get_temp_dir(), '/\\') . '/premisely/uploads';
    }
}

if (!is_dir($storageRoot)) {
    @mkdir($storageRoot, 0777, true);
}

return [
    'driver' => env('FILESYSTEM_DRIVER', 'local'),
    'root' => $storageRoot,
    'max_mb' => (int) env('UPLOAD_MAX_MB', '10'),
];
