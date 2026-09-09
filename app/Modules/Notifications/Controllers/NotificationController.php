<?php

declare(strict_types=1);

namespace Premisely\Modules\Notifications\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;

final class NotificationController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $rows = Connection::fetchAll(
            'SELECT n.*, p.name AS property_name FROM notifications n
             LEFT JOIN properties p ON p.id = n.property_id
             WHERE n.user_id = :uid
             ORDER BY n.created_at DESC LIMIT 50',
            ['uid' => Auth::id()]
        );
        $this->view('notifications/index', [
            'title' => 'Notificaciones',
            'notifications' => $rows,
        ]);
    }

    public function markRead(Request $request, array $params): never
    {
        Connection::query(
            'UPDATE notifications SET read_at = NOW()
             WHERE public_id = :pid AND user_id = :uid AND read_at IS NULL',
            ['pid' => $params['notification'] ?? '', 'uid' => Auth::id()]
        );
        flash('success', 'Marcada como leída.');
        $this->redirect('/notifications');
    }
}
