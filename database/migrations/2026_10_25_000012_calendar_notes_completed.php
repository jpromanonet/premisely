<?php

declare(strict_types=1);

/**
 * Notes can be crossed off; open notes keep carrying until then.
 */

return [
    'ALTER TABLE calendar_notes
        ADD COLUMN completed_at DATETIME NULL AFTER notes',
];
