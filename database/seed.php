<?php

declare(strict_types=1);

/**
 * Usage: php database/seed.php
 * Safe to re-run — every seeder uses INSERT ... ON DUPLICATE KEY UPDATE
 * or an existence check, so seeding twice won't create duplicates.
 */

require dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\Database;
use Database\Seeders\DatabaseSeeder;

try {
    $ran = (new DatabaseSeeder())->run(Database::connection());
    echo "Seeded:\n  - " . implode("\n  - ", $ran) . "\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Seeding error: " . $e->getMessage() . "\n");
    exit(1);
}
