<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $db): void
    {
        $db->exec(<<<SQL
            CREATE TABLE audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,              -- NULL = system-initiated action
                action VARCHAR(100) NOT NULL,           -- e.g. transaction.create, user.role_change, settings.update
                entity_type VARCHAR(100) NULL,
                entity_id INT UNSIGNED NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                metadata_json JSON NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_audit_logs_entity (entity_type, entity_id),
                INDEX idx_audit_logs_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS audit_logs;');
    }
};
