<?php

declare(strict_types=1);

return [
    "CREATE TABLE IF NOT EXISTS weekly_menu_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        weekday TINYINT UNSIGNED NOT NULL,
        slot VARCHAR(20) NOT NULL DEFAULT 'almuerzo',
        title VARCHAR(200) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_weekly_menu_items_public_id (public_id),
        KEY idx_weekly_menu_day (property_id, weekday, slot),
        CONSTRAINT fk_weekly_menu_items_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS weekly_menu_meta (
        property_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
        ingredients TEXT NULL,
        rules TEXT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_weekly_menu_meta_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
