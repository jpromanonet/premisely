<?php

declare(strict_types=1);

namespace Premisely\Core\Storage;

use RuntimeException;

final class LocalStorage implements StorageInterface
{
    public function __construct(private string $root)
    {
        if (!is_dir($this->root)) {
            mkdir($this->root, 0775, true);
        }
    }

    public function store(array $file, string $directory): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error al subir el archivo.');
        }

        $maxBytes = ((int) config('storage.max_mb', 10)) * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('El archivo supera el tamaño máximo permitido.');
        }

        $original = (string) ($file['name'] ?? 'file');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip'];
        if ($ext === '' || !in_array($ext, $allowed, true)) {
            throw new RuntimeException('Tipo de archivo no permitido.');
        }

        $relativeDir = trim(str_replace(['..', '\\'], ['', '/'], $directory), '/');
        $destDir = $this->root . '/' . $relativeDir;
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $filename = ulid() . '.' . $ext;
        $relative = $relativeDir . '/' . $filename;
        $absolute = $this->root . '/' . $relative;

        if (!move_uploaded_file((string) $file['tmp_name'], $absolute)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        return $relative;
    }

    public function delete(string $path): bool
    {
        $absolute = $this->absolutePath($path);
        if (is_file($absolute)) {
            return unlink($absolute);
        }
        return false;
    }

    public function url(string $path): string
    {
        return url('/files/' . ltrim($path, '/'));
    }

    public function absolutePath(string $path): string
    {
        $path = str_replace(['..', '\\'], ['', '/'], $path);
        return $this->root . '/' . ltrim($path, '/');
    }
}
