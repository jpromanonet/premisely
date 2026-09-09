<?php

declare(strict_types=1);

namespace Premisely\Core\Database;

use PDO;
use PDOStatement;
use RuntimeException;

final class Connection
{
    private static ?PDO $pdo = null;

    /** @param array<string, mixed> $config */
    public static function connect(array $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        self::$pdo = new PDO($dsn, (string) $config['username'], (string) $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            throw new RuntimeException('Database not connected');
        }
        return self::$pdo;
    }

    public static function connected(): bool
    {
        return self::$pdo instanceof PDO;
    }

    /** @param array<string, mixed> $params */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** @param array<string, mixed> $params */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $params @return list<array<string, mixed>> */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** @param array<string, mixed> $params */
    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function lastInsertId(): string
    {
        return self::pdo()->lastInsertId();
    }

    public static function begin(): void
    {
        self::pdo()->beginTransaction();
    }

    public static function commit(): void
    {
        self::pdo()->commit();
    }

    public static function rollBack(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    /**
     * Connect without selecting a database (installer / create DB).
     * @param array<string, mixed> $config
     */
    public static function connectServer(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $config['host'],
            $config['port'],
            $config['charset'] ?? 'utf8mb4'
        );
        return new PDO($dsn, (string) $config['username'], (string) $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
