<?php

declare(strict_types=1);

return [
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Verdulería / frutas', 'verduleria'
     WHERE NOT EXISTS (
         SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'verduleria'
     )",
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Carnicería / granjita', 'carniceria'
     WHERE NOT EXISTS (
         SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'carniceria'
     )",
];
