<?php

declare(strict_types=1);

return [
    'ALTER TABLE shopping_list_items
        ADD COLUMN brand VARCHAR(120) NULL AFTER name,
        ADD COLUMN category VARCHAR(40) NULL AFTER brand,
        ADD COLUMN bought_quantity DECIMAL(14,3) NULL AFTER quantity',
];
