<?php

declare(strict_types=1);

namespace Database\Seeders;

use PDO;

/**
 * Runs all seeders in the order their foreign keys require:
 * roles/permissions before users, users before exchange rates.
 */
final class DatabaseSeeder
{
    /** @var class-string[] */
    private const SEEDERS = [
        RoleSeeder::class,
        PermissionSeeder::class,
        CategorySeeder::class,
        SettingSeeder::class,
        AdminUserSeeder::class,
        ExchangeRateSeeder::class,
    ];

    public function run(PDO $db): array
    {
        $ran = [];
        foreach (self::SEEDERS as $seederClass) {
            (new $seederClass())->run($db);
            $ran[] = $seederClass;
        }
        return $ran;
    }
}
