<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $db): void
    {
        $db->exec(<<<SQL
            CREATE TABLE settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `group` VARCHAR(50) NOT NULL,           -- general, currency, transaction, dashboard, user, appearance, notification, backup, security, feature_flags
                `key` VARCHAR(100) NOT NULL,
                value TEXT NULL,
                type ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
                updated_by INT UNSIGNED NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
                UNIQUE KEY uniq_settings_group_key (`group`, `key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS settings;');
    }
};
