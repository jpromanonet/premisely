<?php

declare(strict_types=1);

/**
 * Migration: create full V1 Premisely schema.
 * @return list<string>
 */
return [
    // users
    "CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        avatar_path VARCHAR(255) NULL,
        locale VARCHAR(10) NOT NULL DEFAULT 'es',
        timezone VARCHAR(64) NOT NULL DEFAULT 'America/Argentina/Buenos_Aires',
        preferred_currency CHAR(3) NOT NULL DEFAULT 'ARS',
        notify_email TINYINT(1) NOT NULL DEFAULT 1,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_users_public_id (public_id),
        UNIQUE KEY uq_users_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // properties
    "CREATE TABLE IF NOT EXISTS properties (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        name VARCHAR(160) NOT NULL,
        type VARCHAR(40) NOT NULL DEFAULT 'casa',
        address VARCHAR(255) NULL,
        description TEXT NULL,
        cover_path VARCHAR(255) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'activa',
        currency CHAR(3) NOT NULL DEFAULT 'ARS',
        area_m2 DECIMAL(10,2) NULL,
        rooms INT NULL,
        managed_since DATE NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_properties_public_id (public_id),
        KEY idx_properties_status (status),
        CONSTRAINT fk_properties_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // property_members
    "CREATE TABLE IF NOT EXISTS property_members (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        display_name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NULL,
        role VARCHAR(40) NOT NULL DEFAULT 'member',
        member_type VARCHAR(40) NOT NULL DEFAULT 'residente',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        responsibilities TEXT NULL,
        notes TEXT NULL,
        joined_at DATETIME NULL,
        left_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_pm_property (property_id),
        KEY idx_pm_user (user_id),
        UNIQUE KEY uq_pm_property_user (property_id, user_id),
        CONSTRAINT fk_pm_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // spaces
    "CREATE TABLE IF NOT EXISTS spaces (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        parent_id BIGINT UNSIGNED NULL,
        name VARCHAR(120) NOT NULL,
        type VARCHAR(40) NOT NULL DEFAULT 'otro',
        description TEXT NULL,
        photo_path VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_spaces_public_id (public_id),
        KEY idx_spaces_property (property_id),
        KEY idx_spaces_parent (parent_id),
        CONSTRAINT fk_spaces_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_spaces_parent FOREIGN KEY (parent_id) REFERENCES spaces(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // inventory categories
    "CREATE TABLE IF NOT EXISTS inventory_categories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id BIGINT UNSIGNED NULL,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(120) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_inv_cat (property_id, slug),
        CONSTRAINT fk_inv_cat_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // inventory_items
    "CREATE TABLE IF NOT EXISTS inventory_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        space_id BIGINT UNSIGNED NULL,
        owner_member_id BIGINT UNSIGNED NULL,
        category_id BIGINT UNSIGNED NULL,
        name VARCHAR(180) NOT NULL,
        description TEXT NULL,
        brand VARCHAR(120) NULL,
        model VARCHAR(120) NULL,
        serial_number VARCHAR(120) NULL,
        internal_code VARCHAR(80) NULL,
        ownership_type VARCHAR(40) NOT NULL DEFAULT 'propiedad',
        purchase_date DATE NULL,
        purchase_store VARCHAR(160) NULL,
        purchase_price DECIMAL(14,2) NULL,
        purchase_currency CHAR(3) NULL,
        estimated_value DECIMAL(14,2) NULL,
        estimated_value_currency CHAR(3) NULL,
        `condition` VARCHAR(40) NOT NULL DEFAULT 'bueno',
        status VARCHAR(40) NOT NULL DEFAULT 'activo',
        warranty_until DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_inventory_public_id (public_id),
        KEY idx_inventory_property (property_id),
        KEY idx_inventory_space (space_id),
        KEY idx_inventory_name (name),
        CONSTRAINT fk_inventory_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_inventory_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL,
        CONSTRAINT fk_inventory_owner FOREIGN KEY (owner_member_id) REFERENCES property_members(id) ON DELETE SET NULL,
        CONSTRAINT fk_inventory_category FOREIGN KEY (category_id) REFERENCES inventory_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS inventory_item_events (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        inventory_item_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        event_type VARCHAR(60) NOT NULL,
        notes TEXT NULL,
        metadata_json JSON NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_inv_events_item (inventory_item_id),
        CONSTRAINT fk_inv_events_item FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
        CONSTRAINT fk_inv_events_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // stock
    "CREATE TABLE IF NOT EXISTS stock_categories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id BIGINT UNSIGNED NULL,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(120) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_stock_cat (property_id, slug),
        CONSTRAINT fk_stock_cat_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS stock_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        space_id BIGINT UNSIGNED NULL,
        category_id BIGINT UNSIGNED NULL,
        name VARCHAR(180) NOT NULL,
        brand VARCHAR(120) NULL,
        quantity DECIMAL(14,3) NOT NULL DEFAULT 0,
        unit VARCHAR(40) NOT NULL DEFAULT 'u',
        minimum_quantity DECIMAL(14,3) NOT NULL DEFAULT 0,
        target_quantity DECIMAL(14,3) NULL,
        last_price DECIMAL(14,2) NULL,
        currency CHAR(3) NULL,
        expiration_date DATE NULL,
        auto_add_to_shopping TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_stock_public_id (public_id),
        KEY idx_stock_property (property_id),
        CONSTRAINT fk_stock_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_stock_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL,
        CONSTRAINT fk_stock_category FOREIGN KEY (category_id) REFERENCES stock_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS stock_movements (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        stock_item_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        type VARCHAR(40) NOT NULL,
        quantity DECIMAL(14,3) NOT NULL,
        unit_price DECIMAL(14,2) NULL,
        notes TEXT NULL,
        related_type VARCHAR(60) NULL,
        related_id BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_stock_mov_item (stock_item_id),
        CONSTRAINT fk_stock_mov_item FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE CASCADE,
        CONSTRAINT fk_stock_mov_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // shopping
    "CREATE TABLE IF NOT EXISTS shopping_lists (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(160) NOT NULL DEFAULT 'Lista activa',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_shopping_lists_public_id (public_id),
        KEY idx_shopping_lists_property (property_id),
        CONSTRAINT fk_shopping_lists_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS shopping_list_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        shopping_list_id BIGINT UNSIGNED NOT NULL,
        stock_item_id BIGINT UNSIGNED NULL,
        name VARCHAR(180) NOT NULL,
        quantity DECIMAL(14,3) NOT NULL DEFAULT 1,
        unit VARCHAR(40) NOT NULL DEFAULT 'u',
        estimated_price DECIMAL(14,2) NULL,
        actual_price DECIMAL(14,2) NULL,
        priority VARCHAR(20) NOT NULL DEFAULT 'normal',
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        added_by BIGINT UNSIGNED NULL,
        completed_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL,
        KEY idx_sli_list (shopping_list_id),
        CONSTRAINT fk_sli_list FOREIGN KEY (shopping_list_id) REFERENCES shopping_lists(id) ON DELETE CASCADE,
        CONSTRAINT fk_sli_stock FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // tasks
    "CREATE TABLE IF NOT EXISTS tasks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        space_id BIGINT UNSIGNED NULL,
        inventory_item_id BIGINT UNSIGNED NULL,
        assignee_member_id BIGINT UNSIGNED NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NULL,
        priority VARCHAR(20) NOT NULL DEFAULT 'normal',
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        due_date DATE NULL,
        tags VARCHAR(255) NULL,
        created_by BIGINT UNSIGNED NULL,
        completed_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_tasks_public_id (public_id),
        KEY idx_tasks_property (property_id),
        CONSTRAINT fk_tasks_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_tasks_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // routines
    "CREATE TABLE IF NOT EXISTS routines (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        space_id BIGINT UNSIGNED NULL,
        assignee_member_id BIGINT UNSIGNED NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NULL,
        category VARCHAR(40) NOT NULL DEFAULT 'general',
        frequency_type VARCHAR(40) NOT NULL DEFAULT 'weekly',
        frequency_interval INT NOT NULL DEFAULT 1,
        last_executed_at DATETIME NULL,
        next_due_at DATETIME NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_routines_public_id (public_id),
        KEY idx_routines_property (property_id),
        KEY idx_routines_next (next_due_at),
        CONSTRAINT fk_routines_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_routines_space FOREIGN KEY (space_id) REFERENCES spaces(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS routine_executions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        routine_id BIGINT UNSIGNED NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        executed_by BIGINT UNSIGNED NULL,
        notes TEXT NULL,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_routine_exec (routine_id),
        CONSTRAINT fk_routine_exec_routine FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE,
        CONSTRAINT fk_routine_exec_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // maintenance
    "CREATE TABLE IF NOT EXISTS maintenance_plans (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        space_id BIGINT UNSIGNED NULL,
        inventory_item_id BIGINT UNSIGNED NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT NULL,
        frequency_type VARCHAR(40) NOT NULL DEFAULT 'yearly',
        frequency_interval INT NOT NULL DEFAULT 1,
        provider_name VARCHAR(160) NULL,
        estimated_cost DECIMAL(14,2) NULL,
        currency CHAR(3) NULL,
        last_done_at DATE NULL,
        next_due_at DATE NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_maint_plans_public_id (public_id),
        KEY idx_maint_plans_property (property_id),
        CONSTRAINT fk_maint_plans_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS maintenance_records (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        plan_id BIGINT UNSIGNED NULL,
        space_id BIGINT UNSIGNED NULL,
        inventory_item_id BIGINT UNSIGNED NULL,
        title VARCHAR(200) NOT NULL,
        performed_at DATE NOT NULL,
        cost DECIMAL(14,2) NULL,
        currency CHAR(3) NULL,
        provider_name VARCHAR(160) NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_maint_records_public_id (public_id),
        KEY idx_maint_records_property (property_id),
        CONSTRAINT fk_maint_records_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_maint_records_plan FOREIGN KEY (plan_id) REFERENCES maintenance_plans(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // repairs
    "CREATE TABLE IF NOT EXISTS repair_records (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        space_id BIGINT UNSIGNED NULL,
        inventory_item_id BIGINT UNSIGNED NULL,
        title VARCHAR(200) NOT NULL,
        problem_description TEXT NULL,
        reported_at DATE NOT NULL,
        closed_at DATE NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'pendiente',
        provider_name VARCHAR(160) NULL,
        budget DECIMAL(14,2) NULL,
        cost DECIMAL(14,2) NULL,
        currency CHAR(3) NULL,
        parts_replaced TEXT NULL,
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_repairs_public_id (public_id),
        KEY idx_repairs_property (property_id),
        CONSTRAINT fk_repairs_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // providers
    "CREATE TABLE IF NOT EXISTS providers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(160) NOT NULL,
        specialty VARCHAR(120) NULL,
        phone VARCHAR(60) NULL,
        email VARCHAR(190) NULL,
        notes TEXT NULL,
        rating TINYINT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_providers_public_id (public_id),
        KEY idx_providers_property (property_id),
        CONSTRAINT fk_providers_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // services
    "CREATE TABLE IF NOT EXISTS property_services (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        name VARCHAR(160) NOT NULL,
        provider VARCHAR(160) NULL,
        customer_number VARCHAR(120) NULL,
        billing_frequency VARCHAR(40) NOT NULL DEFAULT 'monthly',
        typical_amount DECIMAL(14,2) NULL,
        currency CHAR(3) NULL,
        next_due_date DATE NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_services_public_id (public_id),
        KEY idx_services_property (property_id),
        CONSTRAINT fk_services_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS service_bills (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        service_id BIGINT UNSIGNED NOT NULL,
        period VARCHAR(40) NULL,
        amount DECIMAL(14,2) NOT NULL,
        currency CHAR(3) NULL,
        due_date DATE NULL,
        paid_at DATE NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_service_bills_public_id (public_id),
        KEY idx_service_bills_service (service_id),
        CONSTRAINT fk_service_bills_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_service_bills_service FOREIGN KEY (service_id) REFERENCES property_services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // expenses
    "CREATE TABLE IF NOT EXISTS expense_categories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(60) NOT NULL,
        name VARCHAR(120) NOT NULL,
        UNIQUE KEY uq_expense_cat_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS expenses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NULL,
        title VARCHAR(200) NOT NULL,
        amount DECIMAL(14,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'ARS',
        spent_at DATE NOT NULL,
        notes TEXT NULL,
        source_type VARCHAR(60) NULL,
        source_id BIGINT UNSIGNED NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_expenses_public_id (public_id),
        KEY idx_expenses_property (property_id),
        KEY idx_expenses_spent (spent_at),
        CONSTRAINT fk_expenses_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_expenses_category FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS recurring_expenses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        category_id BIGINT UNSIGNED NULL,
        title VARCHAR(200) NOT NULL,
        amount DECIMAL(14,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'ARS',
        frequency_type VARCHAR(40) NOT NULL DEFAULT 'monthly',
        next_due_date DATE NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_recurring_expenses_public_id (public_id),
        CONSTRAINT fk_recurring_expenses_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // documents / files
    "CREATE TABLE IF NOT EXISTS documents (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        public_id CHAR(26) NOT NULL,
        property_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(200) NOT NULL,
        category VARCHAR(60) NOT NULL DEFAULT 'otro',
        file_path VARCHAR(255) NOT NULL,
        mime_type VARCHAR(120) NULL,
        size_bytes INT UNSIGNED NULL,
        related_type VARCHAR(60) NULL,
        related_id BIGINT UNSIGNED NULL,
        uploaded_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        archived_at DATETIME NULL,
        UNIQUE KEY uq_documents_public_id (public_id),
        KEY idx_documents_property (property_id),
        CONSTRAINT fk_documents_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // activity log
    "CREATE TABLE IF NOT EXISTS activity_log (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        property_id BIGINT UNSIGNED NULL,
        user_id BIGINT UNSIGNED NULL,
        entity_type VARCHAR(60) NOT NULL,
        entity_id BIGINT UNSIGNED NULL,
        action VARCHAR(80) NOT NULL,
        metadata_json JSON NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_activity_property (property_id),
        KEY idx_activity_created (created_at),
        CONSTRAINT fk_activity_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // jobs queue
    "CREATE TABLE IF NOT EXISTS jobs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(80) NOT NULL,
        payload JSON NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        attempts INT NOT NULL DEFAULT 0,
        available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        started_at DATETIME NULL,
        completed_at DATETIME NULL,
        failed_at DATETIME NULL,
        last_error TEXT NULL,
        KEY idx_jobs_status_available (status, available_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // password resets
    "CREATE TABLE IF NOT EXISTS password_resets (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL,
        token CHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        used_at DATETIME NULL,
        KEY idx_password_resets_email (email),
        UNIQUE KEY uq_password_resets_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // migrations tracker
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(190) NOT NULL,
        batch INT NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_schema_migrations (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
