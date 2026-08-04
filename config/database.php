<?php

declare(strict_types=1);

/**
 * Database connection configuration.
 * Values are pulled from environment variables loaded by phpdotenv
 * in app/Core/bootstrap.php. Never hardcode credentials here.
 */
return [
    'driver'   => 'mysql',
    // 'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'host'     => $_ENV['DB_HOST'] ?? 'localhost',
    'port'     => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'family',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? 'root',
    'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements — SQL injection hardening
    ],
];
