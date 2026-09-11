<?php

declare(strict_types=1);

/**
 * Add property tenure (propia / alquiler).
 * @return list<string>
 */
return [
    "ALTER TABLE properties
        ADD COLUMN tenure VARCHAR(40) NOT NULL DEFAULT 'propia'
        AFTER type",
];
