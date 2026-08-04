<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Seeder;
use PDO;
use RuntimeException;

/**
 * Creates the initial administrator account so there's a way to log in
 * on a fresh install. Credentials come from .env (DEFAULT_ADMIN_*),
 * never hardcoded — and the password is hashed with Argon2id via
 * PHP's native password_hash(), per spec §2.
 *
 * Safe to re-run: skips creation if an admin user already exists.
 */
final class AdminUserSeeder extends Seeder
{
    public function run(PDO $db): void
    {
        $existing = $db->query(
            "SELECT COUNT(*) FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'admin'"
        )->fetchColumn();

        if ((int) $existing > 0) {
            return; // don't create a second default admin on re-seed
        }

        $name     = $_ENV['DEFAULT_ADMIN_NAME'] ?? 'Family Admin';
        $email    = $_ENV['DEFAULT_ADMIN_EMAIL'] ?? null;
        $password = $_ENV['DEFAULT_ADMIN_PASSWORD'] ?? null;

        if (!$email || !$password) {
            throw new RuntimeException(
                'DEFAULT_ADMIN_EMAIL and DEFAULT_ADMIN_PASSWORD must be set in .env before seeding.'
            );
        }

        $roleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
        if ($roleId === false) {
            throw new RuntimeException('admin role not found — run RoleSeeder first.');
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, is_active)
             VALUES (:name, :email, :password_hash, :role_id, 1)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $hash,
            'role_id' => $roleId,
        ]);
    }
}
