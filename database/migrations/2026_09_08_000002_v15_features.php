<?php

declare(strict_types=1);

/**
 * V1.5 schema: warranties, boxes, meals, clothing, calendar notes.
 * @return list<string>
 */
return [
    "CREATE TABLE IF NOT EXISTS inventory_item_warranties (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        inventory_item_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        provider_name VARCHAR(160) NULL,
        manufacturer VARCHAR(160) NULL,
        starts_on DATE NULL,
        ends_on DATE NULL,
        conditions_text TEXT NULL,
        document_path VARCHAR(255) NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_warranty_public_id (public_id),
        KEY idx_warranty_item (inventory_item_id),
        KEY idx_warranty_ends (ends_on),
        CONSTRAINT fk_warranty_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
        CONSTRAINT fk_warranty_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS storage_boxes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        space_id BIGINT UNSIGNED NULL,
        code VARCHAR(40) NOT NULL,
        name VARCHAR(160) NOT NULL,
        description TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_boxes_public_id (public_id),
        UNIQUE KEY uq_boxes_code (property_id, code),
        KEY idx_boxes_property (property_id),
        CONSTRAINT fk_boxes_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_boxes_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS storage_box_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        box_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        inventory_item_id BIGINT UNSIGNED NULL,
        name VARCHAR(180) NOT NULL,
        quantity DECIMAL(14,3) NOT NULL DEFAULT 1,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_box_items_box (box_id),
        CONSTRAINT fk_box_items_box FOREIGN KEY (box_id) REFERENCES storage_boxes(id) ON DELETE CASCADE,
        CONSTRAINT fk_box_items_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_box_items_inventory FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS meal_plans (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        week_start DATE NOT NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_meal_plans_public_id (public_id),
        UNIQUE KEY uq_meal_plans_week (property_id, week_start),
        CONSTRAINT fk_meal_plans_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS meal_entries (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        meal_plan_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        meal_date DATE NOT NULL,
        slot VARCHAR(20) NOT NULL DEFAULT 'almuerzo',
        title VARCHAR(200) NOT NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_meal_entries_public_id (public_id),
        KEY idx_meal_entries_plan (meal_plan_id),
        KEY idx_meal_entries_date (meal_date),
        CONSTRAINT fk_meal_entries_plan FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE CASCADE,
        CONSTRAINT fk_meal_entries_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS clothing_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        owner_member_id BIGINT UNSIGNED NULL,
        space_id BIGINT UNSIGNED NULL,
        name VARCHAR(180) NOT NULL,
        category VARCHAR(60) NOT NULL DEFAULT 'prenda',
        season VARCHAR(40) NULL,
        size_label VARCHAR(40) NULL,
        `condition` VARCHAR(40) NOT NULL DEFAULT 'bueno',
        status VARCHAR(40) NOT NULL DEFAULT 'disponible',
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_clothing_public_id (public_id),
        KEY idx_clothing_property (property_id),
        CONSTRAINT fk_clothing_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_clothing_member FOREIGN KEY (owner_member_id) REFERENCES property_members(id) ON DELETE SET NULL,
        CONSTRAINT fk_clothing_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS calendar_notes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(200) NOT NULL,
        note_date DATE NOT NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_calendar_notes_public_id (public_id),
        KEY idx_calendar_notes_date (property_id, note_date),
        CONSTRAINT fk_calendar_notes_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
