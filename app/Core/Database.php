<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Single shared PDO connection for the whole application.
 * Always uses real prepared statements (no emulation) so that every
 * query — here or in any Model/Repository — is protected against
 * SQL injection by construction, not by convention.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
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
                // Never leak DSN/credentials in the exception message shown to users.
                throw new RuntimeException('Database connection failed. Check your .env configuration.', previous: $e);
            }
        }

        return self::$connection;
    }
}
