<?php

declare(strict_types=1);

namespace Premisely\Modules\Documents\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Storage\LocalStorage;
use Premisely\Core\Validation\Validator;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Shared\PropertyContext;
use Throwable;

final class DocumentController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $documents = Connection::fetchAll(
            'SELECT d.*, u.name AS uploader_name
             FROM documents d
             LEFT JOIN users u ON u.id = d.uploaded_by
             WHERE d.property_id = :pid AND d.archived_at IS NULL
             ORDER BY d.created_at DESC',
            ['pid' => PropertyContext::propertyId()]
        );

        $this->view('documents/index', [
            'title' => 'Documentos',
            'property' => PropertyContext::property(),
            'documents' => $documents,
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function store(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/documents');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
        ]);
        if ($validator->fails()) {
            flash('error', $validator->firstError());
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/documents');
        }

        $file = $request->file('file');
        if (!$file) {
            flash('error', 'Seleccioná un archivo.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/documents');
        }

        $pid = PropertyContext::propertyId();
        $property = PropertyContext::property();

        try {
            $storage = new LocalStorage((string) config('storage.root'));
            $relative = $storage->store($file, 'properties/' . $property['public_id'] . '/documents');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            $this->redirect('/properties/' . $property['public_id'] . '/documents');
        }

        Connection::query(
            'INSERT INTO documents (
                public_id, property_id, title, category, file_path, mime_type, size_bytes, uploaded_by, created_at
             ) VALUES (
                :public_id, :property_id, :title, :category, :file_path, :mime_type, :size_bytes, :uploaded_by, NOW()
             )',
            [
                'public_id' => ulid(),
                'property_id' => $pid,
                'title' => (string) $request->input('title'),
                'category' => (string) ($request->input('category') ?: 'otro'),
                'file_path' => $relative,
                'mime_type' => $file['type'] ?? null,
                'size_bytes' => $file['size'] ?? null,
                'uploaded_by' => Auth::id(),
            ]
        );

        ActivityLogger::log($pid, 'document', (int) Connection::lastInsertId(), 'created');
        flash('success', 'Documento subido.');
        $this->redirect('/properties/' . $property['public_id'] . '/documents');
    }

    public function archive(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'No tenés permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/documents');
        }

        $key = $params['document'] ?? '';
        $doc = Connection::fetch(
            'SELECT * FROM documents
             WHERE property_id = :pid AND archived_at IS NULL
               AND (public_id = :key OR id = :id)
             LIMIT 1',
            [
                'pid' => PropertyContext::propertyId(),
                'key' => $key,
                'id' => ctype_digit((string) $key) ? (int) $key : 0,
            ]
        );

        if (!$doc) {
            flash('error', 'Documento no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/documents');
        }

        Connection::query(
            'UPDATE documents SET archived_at = NOW() WHERE id = :id',
            ['id' => $doc['id']]
        );
        ActivityLogger::log(PropertyContext::propertyId(), 'document', (int) $doc['id'], 'archived');
        flash('success', 'Documento archivado.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/documents');
    }
}
