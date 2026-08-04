<?php

declare(strict_types=1);

use App\Core\Migration;

return new class extends Migration {
    public function up(PDO $db): void
    {
        $db->exec(<<<SQL
            CREATE TABLE categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type ENUM('income','expense') NOT NULL,
                name VARCHAR(100) NOT NULL,
                icon VARCHAR(50) NULL,
                color VARCHAR(20) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_by INT UNSIGNED NULL,           -- NULL = system default category
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_categories_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                UNIQUE KEY uniq_category_type_name (type, name),
                INDEX idx_categories_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS categories;');
    }
};
