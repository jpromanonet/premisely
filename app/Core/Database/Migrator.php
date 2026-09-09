<?php

declare(strict_types=1);

namespace Premisely\Core\Database;

use RuntimeException;

final class Migrator
{
    public static function run(string $migrationsPath): int
    {
        Connection::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL,
                batch INT NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_schema_migrations (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $applied = Connection::fetchAll('SELECT migration FROM schema_migrations');
        $appliedNames = array_column($applied, 'migration');
        $batch = (int) (Connection::fetchColumn('SELECT COALESCE(MAX(batch), 0) FROM schema_migrations') ?: 0) + 1;

        $files = glob(rtrim($migrationsPath, '/\\') . '/*.php') ?: [];
        sort($files);
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $appliedNames, true)) {
                continue;
            }
            $statements = require $file;
            if (!is_array($statements)) {
                throw new RuntimeException('Migration must return array of SQL: ' . $name);
            }

            // MySQL DDL auto-commits; do not wrap CREATE TABLE in transactions.
            foreach ($statements as $sql) {
                Connection::pdo()->exec($sql);
            }
            Connection::query(
                'INSERT INTO schema_migrations (migration, batch) VALUES (:m, :b)',
                ['m' => $name, 'b' => $batch]
            );
            $count++;
        }

        return $count;
    }

    public static function seed(string $seedsPath): int
    {
        $files = glob(rtrim($seedsPath, '/\\') . '/*.php') ?: [];
        sort($files);
        $count = 0;
        foreach ($files as $file) {
            $seed = require $file;
            if (is_callable($seed)) {
                $seed();
                $count++;
            }
        }
        return $count;
    }
}
