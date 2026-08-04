<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Reads the `settings` table (spec §11: "stored in the database... read
 * from the database and cache them for performance"). Cached once per
 * request in memory, and mirrored to a file cache so we don't hit the
 * database on every single page load.
 *
 * Usage: SettingsCache::get('transaction', 'allow_future_dates', false)
 */
final class SettingsCache
{
    /** @var array<string,array<string,mixed>>|null */
    private static ?array $cache = null;

    private static string $cacheFile = '';

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$cache[$group][$key] ?? $default;
    }

    public static function group(string $group): array
    {
        self::load();
        return self::$cache[$group] ?? [];
    }

    public static function set(string $group, string $key, mixed $value, string $type, ?int $updatedBy = null): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO settings (`group`, `key`, value, type, updated_by)
             VALUES (:group, :key, :value, :type, :updated_by)
             ON DUPLICATE KEY UPDATE value = VALUES(value), type = VALUES(type), updated_by = VALUES(updated_by)'
        );
        $stmt->execute([
            'group' => $group,
            'key' => $key,
            'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            'type' => $type,
            'updated_by' => $updatedBy,
        ]);

        self::$cache = null;
        self::clearFileCache();
    }

    public static function flush(): void
    {
        self::$cache = null;
        self::clearFileCache();
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cacheFile = dirname(__DIR__, 2) . '/storage/cache/settings.json';

        if (is_file(self::$cacheFile) && (time() - filemtime(self::$cacheFile)) < 300) {
            $raw = json_decode((string) file_get_contents(self::$cacheFile), true);
            if (is_array($raw)) {
                self::$cache = $raw;
                return;
            }
        }

        self::$cache = self::loadFromDatabase();
        self::writeFileCache();
    }

    private static function loadFromDatabase(): array
    {
        $rows = Database::connection()
            ->query('SELECT `group`, `key`, value, type FROM settings')
            ->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group']][$row['key']] = self::cast($row['value'], $row['type']);
        }

        return $grouped;
    }

    private static function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => $value === '1' || $value === 'true',
            'int' => (int) $value,
            'json' => json_decode((string) $value, true),
            default => $value,
        };
    }

    private static function writeFileCache(): void
    {
        $dir = dirname(self::$cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents(self::$cacheFile, json_encode(self::$cache));
    }

    private static function clearFileCache(): void
    {
        $file = dirname(__DIR__, 2) . '/storage/cache/settings.json';
        if (is_file($file)) {
            unlink($file);
        }
    }
}
