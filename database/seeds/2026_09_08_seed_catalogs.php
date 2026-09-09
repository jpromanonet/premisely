<?php

declare(strict_types=1);

use Premisely\Core\Database\Connection;

/**
 * Seed baseline catalogs.
 */
return static function (): void {
    $categories = [
        ['rent', 'Alquiler'],
        ['utilities', 'Servicios'],
        ['condo_fees', 'Expensas'],
        ['taxes', 'Impuestos'],
        ['maintenance', 'Mantenimiento'],
        ['repairs', 'Reparaciones'],
        ['consumables', 'Consumibles'],
        ['insurance', 'Seguros'],
        ['inventory', 'Inventario'],
        ['other', 'Otros'],
    ];

    foreach ($categories as [$slug, $name]) {
        $exists = Connection::fetch('SELECT id FROM expense_categories WHERE slug = :s', ['s' => $slug]);
        if (!$exists) {
            Connection::query(
                'INSERT INTO expense_categories (slug, name) VALUES (:s, :n)',
                ['s' => $slug, 'n' => $name]
            );
        }
    }

    $invCats = ['muebles', 'electrodomesticos', 'electronica', 'herramientas', 'decoracion', 'otro'];
    foreach ($invCats as $name) {
        $slug = $name;
        $exists = Connection::fetch(
            'SELECT id FROM inventory_categories WHERE property_id IS NULL AND slug = :s',
            ['s' => $slug]
        );
        if (!$exists) {
            Connection::query(
                'INSERT INTO inventory_categories (property_id, name, slug) VALUES (NULL, :n, :s)',
                ['n' => ucfirst($name), 's' => $slug]
            );
        }
    }

    $stockCats = ['alimentos', 'limpieza', 'higiene', 'otros'];
    foreach ($stockCats as $name) {
        $exists = Connection::fetch(
            'SELECT id FROM stock_categories WHERE property_id IS NULL AND slug = :s',
            ['s' => $name]
        );
        if (!$exists) {
            Connection::query(
                'INSERT INTO stock_categories (property_id, name, slug) VALUES (NULL, :n, :s)',
                ['n' => ucfirst($name), 's' => $name]
            );
        }
    }
};
