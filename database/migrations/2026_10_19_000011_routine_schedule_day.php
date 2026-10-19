<?php

declare(strict_types=1);

return [
    'ALTER TABLE routines ADD COLUMN schedule_day TINYINT UNSIGNED NULL AFTER frequency_interval',
    "UPDATE routines
     SET schedule_day = WEEKDAY(COALESCE(next_due_at, created_at)) + 1
     WHERE frequency_type = 'weekly' AND schedule_day IS NULL",
    "UPDATE routines
     SET schedule_day = DAY(COALESCE(next_due_at, created_at))
     WHERE frequency_type = 'monthly' AND schedule_day IS NULL",
];
