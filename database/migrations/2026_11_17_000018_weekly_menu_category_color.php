<?php

declare(strict_types=1);

return [
    "ALTER TABLE weekly_menu_ingredient_categories
        ADD COLUMN color VARCHAR(20) NOT NULL DEFAULT 'rose' AFTER name",
];
