<?php

declare(strict_types=1);

namespace Premisely\Modules\Files\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Storage\LocalStorage;

final class FileController extends Controller
{
    public function download(Request $request, array $params): never
    {
        Auth::requireLogin();

        $publicId = $params['document'] ?? '';
        $doc = Connection::fetch(
            'SELECT d.*, p.public_id AS property_public_id
             FROM documents d
             INNER JOIN properties p ON p.id = d.property_id
             INNER JOIN property_members pm ON pm.property_id = d.property_id
             WHERE d.public_id = :pid
               AND d.archived_at IS NULL
               AND pm.user_id = :uid
               AND pm.status = \'active\'
             LIMIT 1',
            [
                'pid' => $publicId,
                'uid' => Auth::id(),
            ]
        );

        if (!$doc) {
            flash('error', 'Archivo no encontrado o sin acceso.');
            $this->redirect('/dashboard');
        }

        $storage = new LocalStorage((string) config('storage.root'));
        $absolute = $storage->absolutePath((string) $doc['file_path']);
        if (!is_file($absolute)) {
            flash('error', 'El archivo no existe en el disco.');
            $this->redirect('/properties/' . $doc['property_public_id'] . '/documents');
        }

        $filename = basename((string) $doc['file_path']);
        $mime = $doc['mime_type'] ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($absolute));
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $doc['title']) . '_' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($absolute);
        exit;
    }
}
