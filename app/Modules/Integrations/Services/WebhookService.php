<?php

declare(strict_types=1);

namespace Premisely\Modules\Integrations\Services;

use Premisely\Core\Database\Connection;

final class WebhookService
{
    /** @param array<string, mixed> $payload */
    public function dispatch(int $propertyId, string $eventType, array $payload = []): void
    {
        $settings = Connection::fetch(
            'SELECT * FROM integration_settings WHERE property_id = :p LIMIT 1',
            ['p' => $propertyId]
        );
        $url = trim((string) ($settings['webhook_url'] ?? ''));
        if ($url === '') {
            return;
        }

        $body = json_encode([
            'event' => $eventType,
            'property_id' => $propertyId,
            'payload' => $payload,
            'sent_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type: application/json',
            'User-Agent: Premisely/2.0',
        ];
        $secret = (string) ($settings['webhook_secret'] ?? '');
        if ($secret !== '') {
            $headers[] = 'X-Premisely-Signature: ' . hash_hmac('sha256', (string) $body, $secret);
        }

        $status = 'sent';
        $code = null;
        try {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => $body,
                    'timeout' => 5,
                    'ignore_errors' => true,
                ],
            ]);
            $result = @file_get_contents($url, false, $ctx);
            if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
                $code = (int) $m[1];
            }
            if ($result === false) {
                $status = 'failed';
            } elseif ($code !== null && $code >= 400) {
                $status = 'failed';
            }
        } catch (\Throwable) {
            $status = 'failed';
        }

        Connection::query(
            'INSERT INTO webhook_deliveries (property_id, event_type, payload_summary, status, response_code, created_at)
             VALUES (:p, :e, :s, :st, :c, NOW())',
            [
                'p' => $propertyId,
                'e' => $eventType,
                's' => mb_substr((string) $body, 0, 480),
                'st' => $status,
                'c' => $code,
            ]
        );
    }
}
