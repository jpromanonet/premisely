<?php

declare(strict_types=1);

/**
 * V2 schema: moves, automations, API tokens, notifications, integrations.
 * @return list<string>
 */
return [
    "CREATE TABLE IF NOT EXISTS moves (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        destination_property_id BIGINT UNSIGNED NULL,
        name VARCHAR(200) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        planned_at DATE NULL,
        completed_at DATETIME NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_moves_public_id (public_id),
        KEY idx_moves_property (property_id),
        CONSTRAINT fk_moves_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_moves_destination FOREIGN KEY (destination_property_id) REFERENCES properties(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS move_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        move_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        inventory_item_id BIGINT UNSIGNED NOT NULL,
        disposition VARCHAR(30) NOT NULL DEFAULT 'transfer',
        box_id BIGINT UNSIGNED NULL,
        target_space_id BIGINT UNSIGNED NULL,
        notes TEXT NULL,
        applied_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_move_items_public_id (public_id),
        UNIQUE KEY uq_move_items_item (move_id, inventory_item_id),
        KEY idx_move_items_move (move_id),
        CONSTRAINT fk_move_items_move FOREIGN KEY (move_id) REFERENCES moves(id) ON DELETE CASCADE,
        CONSTRAINT fk_move_items_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_move_items_inventory FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
        CONSTRAINT fk_move_items_box FOREIGN KEY (box_id) REFERENCES storage_boxes(id) ON DELETE SET NULL,
        CONSTRAINT fk_move_items_space FOREIGN KEY (target_space_id) REFERENCES spaces(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS automation_rules (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        type VARCHAR(60) NOT NULL,
        enabled TINYINT(1) NOT NULL DEFAULT 1,
        config_json JSON NULL,
        last_run_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_automation_public_id (public_id),
        UNIQUE KEY uq_automation_type (property_id, type),
        CONSTRAINT fk_automation_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS api_tokens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(120) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        token_prefix CHAR(8) NOT NULL,
        scopes VARCHAR(255) NOT NULL DEFAULT 'read,write',
        last_used_at DATETIME NULL,
        revoked_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_api_tokens_public_id (public_id),
        UNIQUE KEY uq_api_tokens_hash (token_hash),
        KEY idx_api_tokens_user (user_id),
        CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NULL,
        type VARCHAR(60) NOT NULL,
        title VARCHAR(200) NOT NULL,
        body TEXT NULL,
        link_path VARCHAR(255) NULL,
        read_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_notifications_public_id (public_id),
        KEY idx_notifications_user (user_id, read_at),
        KEY idx_notifications_property (property_id),
        CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_notifications_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS integration_settings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id BIGINT UNSIGNED NOT NULL,
        webhook_url VARCHAR(500) NULL,
        webhook_secret VARCHAR(120) NULL,
        ha_enabled TINYINT(1) NOT NULL DEFAULT 0,
        ha_secret VARCHAR(120) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_integration_property (property_id),
        CONSTRAINT fk_integration_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS webhook_deliveries (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id BIGINT UNSIGNED NOT NULL,
        event_type VARCHAR(80) NOT NULL,
        payload_summary VARCHAR(500) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        response_code INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_webhook_property (property_id),
        CONSTRAINT fk_webhook_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
