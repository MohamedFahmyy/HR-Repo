<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $connection = null;

    public static function getInstance(): PDO
    {
        return self::getConnection();
    }

    public static function getConnection(): PDO
    {

        if (self::$connection === null) {
            $config = config('database.connections.mysql');
            if (!$config) {
                throw new RuntimeException('Database configuration for mysql not found.');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // If database does not exist yet (e.g. before initial migration), try connecting without dbname
                if ($e->getCode() === 1049) {
                    $adminDsn = sprintf(
                        'mysql:host=%s;port=%d;charset=%s',
                        $config['host'],
                        $config['port'],
                        $config['charset']
                    );
                    $adminPdo = new PDO($adminDsn, $config['username'], $config['password'], $config['options']);
                    $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET {$config['charset']} COLLATE {$config['collation']};");
                    self::$connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                } else {
                    throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
                }
            }
        }

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        return self::query($sql, $params)->fetchColumn($column);
    }

    public static function execute(string $sql, array $params = []): bool
    {
        return self::query($sql, $params)->rowCount() >= 0;
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        if (!self::getConnection()->inTransaction()) {
            return self::getConnection()->beginTransaction();
        }
        return false;
    }

    public static function commit(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->commit();
        }
        return false;
    }

    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }

    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback(self::getConnection());
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }
}
