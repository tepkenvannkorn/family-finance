<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Seeder;
use PDO;

/**
 * Seeds the two roles the app ships with. Schema supports more roles
 * later (spec §3: "Future roles should be easy to add") — adding one
 * is an INSERT here plus entries in RolePermissionSeeder, no migration needed.
 */
final class RoleSeeder extends Seeder
{
    public function run(PDO $db): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Full access: users, settings, exchange rates, all reports.'],
            ['name' => 'member', 'description' => 'Can record and manage their own income/expense transactions.'],
        ];

        $stmt = $db->prepare(
            'INSERT INTO roles (name, description) VALUES (:name, :description)
             ON DUPLICATE KEY UPDATE description = VALUES(description)'
        );

        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }
}
