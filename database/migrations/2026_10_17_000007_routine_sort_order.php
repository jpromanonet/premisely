<?php

declare(strict_types=1);

return [
    'ALTER TABLE routines ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_active',
    'UPDATE routines r
     JOIN (
        SELECT id, ROW_NUMBER() OVER (PARTITION BY property_id ORDER BY title, id) AS n
        FROM routines
        WHERE archived_at IS NULL
     ) ranked ON ranked.id = r.id
     SET r.sort_order = ranked.n',
];
