<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $db): void
    {
        $db->exec(<<<SQL
            CREATE TABLE exchange_rates (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                base_currency ENUM('USD') NOT NULL DEFAULT 'USD',
                quote_currency ENUM('KHR') NOT NULL DEFAULT 'KHR',
                rate DECIMAL(18,6) NOT NULL,
                source ENUM('manual','api') NOT NULL,
                fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_by INT UNSIGNED NULL,           -- NULL = fetched automatically by a system job
                CONSTRAINT fk_exchange_rates_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_exchange_rates_lookup (base_currency, quote_currency, fetched_at DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS exchange_rates;');
    }
};
