<?php

declare(strict_types=1);

/**
 * Weekly spend tracker on the calendar day board (not linked to expenses).
 */

return [
    "CREATE TABLE IF NOT EXISTS week_spend_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(200) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency CHAR(3) NOT NULL DEFAULT 'ARS',
        week_start DATE NOT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_week_spend_public_id (public_id),
        KEY idx_week_spend_week (property_id, week_start),
        CONSTRAINT fk_week_spend_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
