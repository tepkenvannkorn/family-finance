<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $db): void
    {
        $db->exec(<<<SQL
            CREATE TABLE users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role_id INT UNSIGNED NOT NULL,
                theme_preference ENUM('light','dark','system') NOT NULL DEFAULT 'system',
                locale VARCHAR(10) NOT NULL DEFAULT 'en',
                totp_secret VARCHAR(255) NULL,          -- reserved for future 2FA (spec §2, §11.5)
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                remember_token VARCHAR(100) NULL,
                failed_login_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                locked_until TIMESTAMP NULL,
                last_login_at TIMESTAMP NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
                INDEX idx_users_role (role_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS users;');
    }
};
