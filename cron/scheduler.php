<?php

declare(strict_types=1);

/**
 * Cron entrypoint — run every 5–15 minutes on the server.
 * Example: php /path/to/premisely/cron/scheduler.php
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use Premisely\Core\Database\Connection;
use Premisely\Core\Logging\Logger;
use Premisely\Modules\Automations\Services\AutomationRunner;
use Premisely\Modules\Notifications\Services\NotificationService;

Logger::info('cron.scheduler.start');

$jobs = Connection::fetchAll(
    "SELECT * FROM jobs
     WHERE status = 'pending' AND available_at <= NOW()
     ORDER BY id ASC
     LIMIT 20"
);

foreach ($jobs as $job) {
    Connection::query(
        "UPDATE jobs SET status = 'running', started_at = NOW(), attempts = attempts + 1 WHERE id = :id",
        ['id' => $job['id']]
    );
    try {
        $type = (string) $job['type'];
        $payload = json_decode((string) ($job['payload'] ?? '{}'), true) ?: [];
        if ($type === 'notify_property' && isset($payload['property_id'], $payload['title'])) {
            NotificationService::notifyProperty(
                (int) $payload['property_id'],
                (string) ($payload['notify_type'] ?? 'job'),
                (string) $payload['title'],
                $payload['body'] ?? null,
                $payload['link'] ?? null
            );
        } elseif ($type === 'run_automations') {
            (new AutomationRunner())->runAll();
        }
        Connection::query(
            "UPDATE jobs SET status = 'completed', completed_at = NOW() WHERE id = :id",
            ['id' => $job['id']]
        );
    } catch (Throwable $e) {
        Connection::query(
            "UPDATE jobs SET status = 'failed', failed_at = NOW(), last_error = :err WHERE id = :id",
            ['id' => $job['id'], 'err' => $e->getMessage()]
        );
        Logger::error('cron.job_failed', ['job_id' => $job['id'], 'error' => $e->getMessage()]);
    }
}

$autoActions = (new AutomationRunner())->runAll();

$overdue = Connection::fetchAll(
    "SELECT id, property_id, title FROM routines
     WHERE is_active = 1 AND archived_at IS NULL AND next_due_at IS NOT NULL AND next_due_at < NOW()
     LIMIT 50"
);
foreach ($overdue as $routine) {
    Logger::info('cron.routine_overdue', [
        'routine_id' => $routine['id'],
        'property_id' => $routine['property_id'],
        'title' => $routine['title'],
    ]);
}

Logger::info('cron.scheduler.done', [
    'jobs' => count($jobs),
    'automation_actions' => $autoActions,
    'overdue_routines' => count($overdue),
]);
echo 'OK ' . date('c') . PHP_EOL;
