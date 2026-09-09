<?php

declare(strict_types=1);

/**
 * Cron entrypoint — run every 5–15 minutes on the server.
 * Example: php /path/to/premisely/cron/scheduler.php
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use Premisely\Core\Database\Connection;
use Premisely\Core\Logging\Logger;

Logger::info('cron.scheduler.start');

// Process small job batches
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
        // Placeholder processors — emails/notifications expand later
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

// Mark overdue routines as needing attention via activity (lightweight)
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

Logger::info('cron.scheduler.done', ['jobs' => count($jobs), 'overdue_routines' => count($overdue)]);
echo 'OK ' . date('c') . PHP_EOL;
