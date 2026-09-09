<?php

declare(strict_types=1);

namespace Premisely\Modules\Integrations\Controllers;

use Premisely\Core\Auth\Auth;
use Premisely\Core\Database\Connection;
use Premisely\Core\Http\Controller;
use Premisely\Core\Http\Request;
use Premisely\Modules\Activity\Services\ActivityLogger;
use Premisely\Modules\Notifications\Services\NotificationService;
use Premisely\Shared\PropertyContext;

final class IntegrationController extends Controller
{
    public function index(Request $request, array $params): never
    {
        $settings = $this->ensureSettings(PropertyContext::propertyId());
        $deliveries = Connection::fetchAll(
            'SELECT * FROM webhook_deliveries WHERE property_id = :p ORDER BY id DESC LIMIT 20',
            ['p' => PropertyContext::propertyId()]
        );
        $this->view('integrations/index', [
            'title' => 'Integraciones',
            'property' => PropertyContext::property(),
            'settings' => $settings,
            'deliveries' => $deliveries,
            'canManage' => PropertyContext::canManage(),
        ]);
    }

    public function save(Request $request, array $params): never
    {
        if (!PropertyContext::canManage()) {
            flash('error', 'Sin permisos.');
            $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/integrations');
        }
        $pid = PropertyContext::propertyId();
        $this->ensureSettings($pid);
        Connection::query(
            'UPDATE integration_settings SET
                webhook_url = :url,
                webhook_secret = :wsecret,
                ha_enabled = :ha,
                ha_secret = :hasecret,
                updated_at = NOW()
             WHERE property_id = :p',
            [
                'url' => trim((string) $request->input('webhook_url', '')) ?: null,
                'wsecret' => trim((string) $request->input('webhook_secret', '')) ?: null,
                'ha' => $request->input('ha_enabled') ? 1 : 0,
                'hasecret' => trim((string) $request->input('ha_secret', '')) ?: null,
                'p' => $pid,
            ]
        );
        flash('success', 'Integraciones guardadas.');
        $this->redirect('/properties/' . PropertyContext::property()['public_id'] . '/integrations');
    }

    public function homeAssistant(Request $request, array $params): never
    {
        $property = PropertyContext::property();
        $settings = $this->ensureSettings((int) $property['id']);
        if (!(int) $settings['ha_enabled']) {
            $this->json(['error' => 'Home Assistant disabled'], 403);
        }

        $json = [];
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $secret = (string) ($settings['ha_secret'] ?? '');
        $provided = (string) ($_SERVER['HTTP_X_PREMISELY_SECRET']
            ?? $request->input('secret', '')
            ?? ($json['secret'] ?? ''));
        if ($secret === '' || !hash_equals($secret, $provided)) {
            $this->json(['error' => 'Invalid secret'], 401);
        }

        $event = (string) ($json['event'] ?? $request->input('event') ?: 'ha.event');
        $message = (string) ($json['message'] ?? $request->input('message') ?: 'Evento Home Assistant');
        ActivityLogger::log((int) $property['id'], 'integration', (int) $property['id'], $event, [
            'source' => 'home_assistant',
            'message' => $message,
        ]);
        NotificationService::notifyProperty(
            (int) $property['id'],
            'home_assistant',
            'HA: ' . $event,
            $message,
            '/properties/' . $property['public_id'] . '/integrations'
        );
        $this->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function ensureSettings(int $propertyId): array
    {
        $row = Connection::fetch(
            'SELECT * FROM integration_settings WHERE property_id = :p LIMIT 1',
            ['p' => $propertyId]
        );
        if ($row) {
            return $row;
        }
        Connection::query(
            'INSERT INTO integration_settings (property_id, created_at) VALUES (:p, NOW())',
            ['p' => $propertyId]
        );
        return Connection::fetch(
            'SELECT * FROM integration_settings WHERE property_id = :p LIMIT 1',
            ['p' => $propertyId]
        ) ?? [];
    }
}
