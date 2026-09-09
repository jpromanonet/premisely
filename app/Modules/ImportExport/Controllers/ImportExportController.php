<?php

declare(strict_types=1);

namespace Premisely\Modules\ImportExport\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Core\Storage\LocalStorage;
use Premisely\Modules\Inventory\Services\InventoryService;
use Premisely\Modules\Stock\Services\StockService;
use Premisely\Shared\PropertyContext;
use ZipArchive;

final class ImportExportController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $this->view('importexport/index', [
            'title' => 'Importar / Exportar',
            'property' => PropertyContext::property(),
            'canEdit' => PropertyContext::canEdit(),
        ]);
    }

    public function exportInventory(Request $request, array $params): never
    {
        $format = (string) $request->query('format', 'csv');
        $rows = Connection::fetchAll(
            'SELECT i.public_id, i.name, i.brand, i.model, i.status, i.`condition`, i.estimated_value, s.name AS space
             FROM inventory_items i
             LEFT JOIN spaces s ON s.id = i.space_id
             WHERE i.property_id = :p AND i.archived_at IS NULL ORDER BY i.name',
            ['p' => PropertyContext::propertyId()]
        );
        $this->sendExport('inventory', $rows, $format);
    }

    public function exportStock(Request $request, array $params): never
    {
        $format = (string) $request->query('format', 'csv');
        $rows = Connection::fetchAll(
            'SELECT public_id, name, brand, quantity, unit, minimum_quantity, target_quantity, last_price
             FROM stock_items WHERE property_id = :p AND archived_at IS NULL ORDER BY name',
            ['p' => PropertyContext::propertyId()]
        );
        $this->sendExport('stock', $rows, $format);
    }

    public function exportDocumentsZip(Request $request, array $params): never
    {
        if (!class_exists(ZipArchive::class)) {
            flash('error', 'ZIP no disponible en este servidor.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
        }
        $docs = Connection::fetchAll(
            'SELECT title, file_path FROM documents WHERE property_id = :p AND archived_at IS NULL',
            ['p' => PropertyContext::propertyId()]
        );
        $storage = new LocalStorage((string) config('storage.root'));
        $tmp = sys_get_temp_dir() . '/premisely_docs_' . ulid() . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE) !== true) {
            flash('error', 'No se pudo crear el ZIP.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
        }
        foreach ($docs as $doc) {
            $abs = $storage->absolutePath((string) $doc['file_path']);
            if (is_file($abs)) {
                $zip->addFile($abs, basename((string) $doc['file_path']));
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="documents.zip"');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    public function importInventory(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'Sin permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
        }
        $rows = $this->parseUpload($request);
        $service = new InventoryService();
        $n = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? $row['nombre'] ?? ''));
            if ($name === '') {
                continue;
            }
            $service->create(PropertyContext::propertyId(), [
                'name' => $name,
                'brand' => $row['brand'] ?? $row['marca'] ?? null,
                'model' => $row['model'] ?? $row['modelo'] ?? null,
                'condition' => $row['condition'] ?? $row['condicion'] ?? 'bueno',
                'status' => $row['status'] ?? 'activo',
            ]);
            $n++;
        }
        flash('success', "Importados {$n} objetos de inventario.");
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
    }

    public function importStock(Request $request, array $params): never
    {
        if (!PropertyContext::canEdit()) {
            flash('error', 'Sin permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
        }
        $rows = $this->parseUpload($request);
        $service = new StockService();
        $n = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? $row['nombre'] ?? ''));
            if ($name === '') {
                continue;
            }
            $service->create(PropertyContext::propertyId(), [
                'name' => $name,
                'brand' => $row['brand'] ?? null,
                'quantity' => $row['quantity'] ?? $row['cantidad'] ?? 0,
                'unit' => $row['unit'] ?? $row['unidad'] ?? 'u',
                'minimum_quantity' => $row['minimum_quantity'] ?? $row['minimo'] ?? 0,
                'target_quantity' => $row['target_quantity'] ?? null,
            ]);
            $n++;
        }
        flash('success', "Importados {$n} ítems de stock.");
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
    }

    /** @param list<array<string, mixed>> $rows */
    private function sendExport(string $name, array $rows, string $format): never
    {
        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $name . '.json"');
            echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        $out = fopen('php://output', 'w');
        if ($rows === []) {
            fputcsv($out, ['name']);
            fclose($out);
            exit;
        }
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /** @return list<array<string, mixed>> */
    private function parseUpload(Request $request): array
    {
        $file = $request->file('file');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Seleccioná un archivo CSV o JSON.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
        }
        $content = (string) file_get_contents((string) $file['tmp_name']);
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext === 'json' || str_starts_with(ltrim($content), '[') || str_starts_with(ltrim($content), '{')) {
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                flash('error', 'JSON inválido.');
                $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/importexport');
            }
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                return $decoded['data'];
            }
            return array_is_list($decoded) ? $decoded : [$decoded];
        }
        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        $header = null;
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            if ($header === null) {
                $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $cols);
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $cols[$i] ?? null;
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
