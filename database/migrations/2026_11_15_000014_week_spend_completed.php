<?php

declare(strict_types=1);

return [
    'ALTER TABLE week_spend_items
        ADD COLUMN completed_at DATETIME NULL AFTER week_start',
];
