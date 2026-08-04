<?php

declare(strict_types=1);

/**
 * Usage:
 *   php database/migrate.php          → apply all pending migrations
 *   php database/migrate.php --rollback  → roll back the most recent batch
 */

$root = require dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\Database;
use App\Core\Migrator;

$migrator = new Migrator(Database::connection(), $root . '/database/migrations');

$rollback = in_array('--rollback', $argv, true);

try {
    if ($rollback) {
        $rolledBack = $migrator->rollbackLastBatch();
        if (empty($rolledBack)) {
            echo "Nothing to roll back.\n";
        } else {
            echo "Rolled back:\n  - " . implode("\n  - ", $rolledBack) . "\n";
        }
    } else {
        $applied = $migrator->run();
        if (empty($applied)) {
            echo "Database is already up to date.\n";
        } else {
            echo "Applied migrations:\n  - " . implode("\n  - ", $applied) . "\n";
        }
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "Migration error: " . $e->getMessage() . "\n");
    exit(1);
}
