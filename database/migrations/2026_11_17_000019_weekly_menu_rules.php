<?php

declare(strict_types=1);

return [
    "CREATE TABLE IF NOT EXISTS weekly_menu_rules (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        body VARCHAR(500) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_wm_rules_public_id (public_id),
        KEY idx_wm_rules_property (property_id, sort_order, id),
        CONSTRAINT fk_wm_rules_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
