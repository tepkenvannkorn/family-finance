<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $db): void
    {
        $db->exec(<<<SQL
            CREATE TABLE transactions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type ENUM('income','expense') NOT NULL,
                amount DECIMAL(18,2) NOT NULL,          -- stored in original currency, never converted in place
                currency ENUM('KHR','USD') NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                description VARCHAR(255) NOT NULL,
                notes TEXT NULL,
                transaction_date DATE NOT NULL,
                transaction_time TIME NOT NULL,
                recurring_rule VARCHAR(100) NULL,       -- reserved for future recurring transactions (spec §16)
                created_by INT UNSIGNED NOT NULL,
                updated_by INT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,              -- soft delete: recoverable by admin
                CONSTRAINT fk_transactions_category FOREIGN KEY (category_id) REFERENCES categories(id),
                CONSTRAINT fk_transactions_created_by FOREIGN KEY (created_by) REFERENCES users(id),
                CONSTRAINT fk_transactions_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_transactions_user_date (created_by, transaction_date),
                INDEX idx_transactions_type_category (type, category_id),
                INDEX idx_transactions_deleted_at (deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS transactions;');
    }
};
