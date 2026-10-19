<?php

declare(strict_types=1);

return [
    "ALTER TABLE tasks ADD COLUMN kind VARCHAR(40) NOT NULL DEFAULT 'tarea' AFTER priority",
];
