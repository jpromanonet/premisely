<?php

declare(strict_types=1);

namespace Premisely\Modules\Qr\Controllers;

use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Shared\PropertyContext;

final class QrController extends Controller
{
    public function show(Request $request, array $params): never
    {
        $item = Connection::fetch(
            'SELECT * FROM inventory_items
             WHERE property_id = :p AND public_id = :pid AND archived_at IS NULL LIMIT 1',
            ['p' => PropertyContext::propertyId(), 'pid' => $params['item'] ?? '']
        );
        if (!$item) {
            flash('error', 'Objeto no encontrado.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/inventory');
        }

        $target = url('/properties/' . PropertyContext::property()['public_id'] . '/inventory/' . $item['public_id']);
        $this->view('qr/show', [
            'title' => 'QR · ' . $item['name'],
            'property' => PropertyContext::property(),
            'item' => $item,
            'target' => $target,
            'qrImage' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($target),
        ]);
    }
}
