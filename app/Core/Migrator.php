<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Tracks which migrations have run (in a `migrations` table) and applies
 * any new ones found in /database/migrations, in filename order.
 * The whole schema is rebuildable from scratch by running this against
 * an empty database — no manual SQL import required (spec §20).
 */
final class Migrator
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, string $migrationsPath)
    {
        $this->db = $db;
        $this->migrationsPath = rtrim($migrationsPath, '/');
        $this->ensureMigrationsTableExists();
    }

    public function run(): array
    {
        $applied = [];
        $already = $this->alreadyRun();

        foreach ($this->migrationFiles() as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $already, true)) {
                continue;
            }

            $migration = $this->instantiate($file);

            $this->db->beginTransaction();
            try {
                $migration->up($this->db);
                $this->recordAsRun($name);
                $this->db->commit();
                $applied[] = $name;
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw new \RuntimeException("Migration failed: {$name} — " . $e->getMessage(), previous: $e);
            }
        }

        return $applied;
    }

    public function rollbackLastBatch(): array
    {
        $stmt = $this->db->query('SELECT id, name FROM migrations ORDER BY id DESC LIMIT 10');
        $rows = $stmt->fetchAll();
        $rolledBack = [];

        foreach ($rows as $row) {
            $file = "{$this->migrationsPath}/{$row['name']}.php";
            if (!is_file($file)) {
                continue;
            }
            $migration = $this->instantiate($file);

            $this->db->beginTransaction();
            try {
                $migration->down($this->db);
                $del = $this->db->prepare('DELETE FROM migrations WHERE id = :id');
                $del->execute(['id' => $row['id']]);
                $this->db->commit();
                $rolledBack[] = $row['name'];
            } catch (\Throwable $e) {
                $this->db->rollBack();
                throw new \RuntimeException("Rollback failed: {$row['name']} — " . $e->getMessage(), previous: $e);
            }
        }

        return $rolledBack;
    }

    private function migrationFiles(): array
    {
        $files = glob("{$this->migrationsPath}/*.php") ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    private function instantiate(string $file): Migration
    {
        $class = require $file; // each migration file returns a `new class extends Migration {...}` instance
        if (!$class instanceof Migration) {
            throw new \RuntimeException("Invalid migration file (must return a Migration instance): {$file}");
        }
        return $class;
    }

    private function ensureMigrationsTableExists(): void
    {
        $this->db->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);
    }

    private function alreadyRun(): array
    {
        return $this->db->query('SELECT name FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    }

    private function recordAsRun(string $name): void
    {
        $stmt = $this->db->prepare('INSERT INTO migrations (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
    }
}
