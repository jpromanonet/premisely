<?php

declare(strict_types=1);

return [
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Almacén / supermercado', 'almacen'
     WHERE NOT EXISTS (SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'almacen')",
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Verdulería / frutas', 'verduleria'
     WHERE NOT EXISTS (SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'verduleria')",
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Carnicería / granjita', 'carniceria'
     WHERE NOT EXISTS (SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'carniceria')",
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Congelados', 'congelados'
     WHERE NOT EXISTS (SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'congelados')",
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Limpieza', 'limpieza'
     WHERE NOT EXISTS (SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'limpieza')",
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Higiene personal', 'higiene'
     WHERE NOT EXISTS (SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'higiene')",
    "INSERT INTO stock_categories (property_id, name, slug)
     SELECT NULL, 'Miscelánea', 'miscelanea'
     WHERE NOT EXISTS (SELECT 1 FROM stock_categories WHERE property_id IS NULL AND slug = 'miscelanea')",
    "UPDATE stock_categories SET name = 'Almacén / supermercado' WHERE property_id IS NULL AND slug = 'almacen'",
    "UPDATE stock_categories SET name = 'Verdulería / frutas' WHERE property_id IS NULL AND slug = 'verduleria'",
    "UPDATE stock_categories SET name = 'Carnicería / granjita' WHERE property_id IS NULL AND slug = 'carniceria'",
    "UPDATE stock_categories SET name = 'Congelados' WHERE property_id IS NULL AND slug = 'congelados'",
    "UPDATE stock_categories SET name = 'Limpieza' WHERE property_id IS NULL AND slug = 'limpieza'",
    "UPDATE stock_categories SET name = 'Higiene personal' WHERE property_id IS NULL AND slug = 'higiene'",
    "UPDATE stock_categories SET name = 'Miscelánea' WHERE property_id IS NULL AND slug = 'miscelanea'",
    "UPDATE stock_items st
     INNER JOIN stock_categories cur ON cur.id = st.category_id
     LEFT JOIN stock_categories canon
       ON canon.property_id IS NULL AND canon.slug = cur.slug
      AND canon.slug IN ('almacen', 'verduleria', 'carniceria', 'congelados', 'limpieza', 'higiene', 'miscelanea')
     SET st.category_id = canon.id",
];
