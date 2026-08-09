<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\SettingsCache;

final class DashboardPreference
{
    public static function layoutFor(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT layout_json FROM dashboard_preferences WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
        $json = $stmt->fetchColumn();

        if ($json === false) {
            return self::defaultLayout();
        }

        $decoded = json_decode((string) $json, true);
        return is_array($decoded) ? $decoded : self::defaultLayout();
    }

    public static function save(int $userId, array $layout): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO dashboard_preferences (user_id, layout_json) VALUES (:user_id, :layout)
             ON DUPLICATE KEY UPDATE layout_json = VALUES(layout_json)'
        );
        $stmt->execute(['user_id' => $userId, 'layout' => json_encode($layout)]);
    }

    public static function resetToDefault(int $userId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM dashboard_preferences WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
    }

    private static function defaultLayout(): array
    {
        $default = SettingsCache::get('dashboard', 'default_layout', null);
        return $default ?? ['widgets' => ['balance', 'income_vs_expense', 'recent_transactions', 'monthly_trend', 'weekly_trend', 'expense_categories', 'currency_breakdown']];
    }

    // private static function defaultLayout(): array
    // {
    //     return [
    //         'widgets' => [
    //             ['id' => 'income_vs_expense',   'size' => 'lg'],
    //             ['id' => 'monthly_trend',       'size' => 'md'],
    //             ['id' => 'weekly_trend',        'size' => 'md'],
    //             ['id' => 'expense_categories',  'size' => 'md'],
    //             ['id' => 'currency_breakdown',  'size' => 'md'],
    //             ['id' => 'recent_transactions', 'size' => 'lg'],
    //         ],
    //         'hidden' => [],
    //     ];
    // }
}
