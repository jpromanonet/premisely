<?php

declare(strict_types=1);

return [
    "CREATE TABLE IF NOT EXISTS weekly_menu_snacks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        slot VARCHAR(40) NOT NULL,
        title VARCHAR(200) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_wm_snacks_public_id (public_id),
        KEY idx_wm_snacks_slot (property_id, slot, id),
        CONSTRAINT fk_wm_snacks_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
