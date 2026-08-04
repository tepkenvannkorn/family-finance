<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Seeder;
use PDO;

/**
 * Seeds sensible defaults for every settings group in spec §11.
 * The Settings module (Phase 8) reads/writes this table — nothing here
 * is hardcoded into application logic, so admins can change any of it
 * without a code change or migration.
 */
final class SettingSeeder extends Seeder
{
    /** @var array<int,array{group:string,key:string,value:string,type:string}> */
    private const DEFAULTS = [
        // 11.1 General
        ['group' => 'general', 'key' => 'app_name', 'value' => 'VK Finance', 'type' => 'string'],
        ['group' => 'general', 'key' => 'family_name', 'value' => 'Our Family', 'type' => 'string'],
        ['group' => 'general', 'key' => 'default_landing_page', 'value' => 'dashboard', 'type' => 'string'],
        ['group' => 'general', 'key' => 'default_language', 'value' => 'en', 'type' => 'string'],
        ['group' => 'general', 'key' => 'default_timezone', 'value' => 'Asia/Phnom_Penh', 'type' => 'string'],
        ['group' => 'general', 'key' => 'default_date_format', 'value' => 'd M Y', 'type' => 'string'],
        ['group' => 'general', 'key' => 'default_time_format', 'value' => 'H:i', 'type' => 'string'],
        ['group' => 'general', 'key' => 'default_currency_display', 'value' => 'original', 'type' => 'string'], // original|KHR|USD
        ['group' => 'general', 'key' => 'maintenance_mode', 'value' => '0', 'type' => 'bool'],

        // 11.2 Currency
        ['group' => 'currency', 'key' => 'auto_sync_enabled', 'value' => '0', 'type' => 'bool'],
        ['group' => 'currency', 'key' => 'sync_interval', 'value' => 'daily', 'type' => 'string'], // every_login|daily|weekly
        ['group' => 'currency', 'key' => 'use_manual_when_api_unavailable', 'value' => '1', 'type' => 'bool'],
        ['group' => 'currency', 'key' => 'decimal_places', 'value' => '2', 'type' => 'int'],
        ['group' => 'currency', 'key' => 'symbol_usd', 'value' => '$', 'type' => 'string'],
        ['group' => 'currency', 'key' => 'symbol_khr', 'value' => '៛', 'type' => 'string'],

        // 11.3 Transaction
        ['group' => 'transaction', 'key' => 'allow_edit_own', 'value' => '1', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'allow_delete_own', 'value' => '1', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'require_delete_confirmation', 'value' => '1', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'allow_future_dates', 'value' => '0', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'allow_past_dates', 'value' => '1', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'allow_negative_balance', 'value' => '1', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'require_notes_for_expense', 'value' => '0', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'require_category', 'value' => '1', 'type' => 'bool'],
        ['group' => 'transaction', 'key' => 'max_upload_size_mb', 'value' => '10', 'type' => 'int'],

        // 11.4 Dashboard
        ['group' => 'dashboard', 'key' => 'default_chart_period', 'value' => 'monthly', 'type' => 'string'],
        ['group' => 'dashboard', 'key' => 'show_recent_transactions', 'value' => '1', 'type' => 'bool'],
        ['group' => 'dashboard', 'key' => 'show_monthly_summary', 'value' => '1', 'type' => 'bool'],
        ['group' => 'dashboard', 'key' => 'show_yearly_summary', 'value' => '1', 'type' => 'bool'],
        ['group' => 'dashboard', 'key' => 'show_balance_cards', 'value' => '1', 'type' => 'bool'],
        ['group' => 'dashboard', 'key' => 'show_quick_statistics', 'value' => '1', 'type' => 'bool'],
        ['group' => 'dashboard', 'key' => 'default_layout', 'value' => '{"widgets":["balance","income_vs_expense","recent_transactions","monthly_trend"]}', 'type' => 'json'],

        // 11.5 User / security-adjacent
        ['group' => 'user', 'key' => 'session_timeout_minutes', 'value' => '120', 'type' => 'int'],
        ['group' => 'user', 'key' => 'password_min_length', 'value' => '10', 'type' => 'int'],
        ['group' => 'user', 'key' => 'password_require_complexity', 'value' => '1', 'type' => 'bool'],
        ['group' => 'user', 'key' => 'max_login_attempts', 'value' => '5', 'type' => 'int'],
        ['group' => 'user', 'key' => 'account_lock_minutes', 'value' => '15', 'type' => 'int'],
        ['group' => 'user', 'key' => 'remember_me_days', 'value' => '30', 'type' => 'int'],
        ['group' => 'user', 'key' => 'two_factor_enabled', 'value' => '0', 'type' => 'bool'],
        ['group' => 'user', 'key' => 'allow_self_password_reset', 'value' => '1', 'type' => 'bool'],

        // 11.6 Appearance
        ['group' => 'appearance', 'key' => 'theme', 'value' => 'system', 'type' => 'string'],
        ['group' => 'appearance', 'key' => 'primary_color', 'value' => '#2563eb', 'type' => 'string'],
        ['group' => 'appearance', 'key' => 'sidebar_position', 'value' => 'left', 'type' => 'string'],
        ['group' => 'appearance', 'key' => 'compact_mode', 'value' => '0', 'type' => 'bool'],
        ['group' => 'appearance', 'key' => 'table_density', 'value' => 'comfortable', 'type' => 'string'],
        ['group' => 'appearance', 'key' => 'font_size', 'value' => 'medium', 'type' => 'string'],

        // 11.7 Notifications
        ['group' => 'notification', 'key' => 'success_enabled', 'value' => '1', 'type' => 'bool'],
        ['group' => 'notification', 'key' => 'error_enabled', 'value' => '1', 'type' => 'bool'],
        ['group' => 'notification', 'key' => 'warning_enabled', 'value' => '1', 'type' => 'bool'],
        ['group' => 'notification', 'key' => 'email_enabled', 'value' => '0', 'type' => 'bool'],
        ['group' => 'notification', 'key' => 'browser_enabled', 'value' => '0', 'type' => 'bool'],

        // 11.9 Security
        ['group' => 'security', 'key' => 'force_https', 'value' => '1', 'type' => 'bool'],
        ['group' => 'security', 'key' => 'session_lifetime_minutes', 'value' => '120', 'type' => 'int'],
        ['group' => 'security', 'key' => 'password_expiration_days', 'value' => '0', 'type' => 'int'], // 0 = disabled
        ['group' => 'security', 'key' => 'audit_logging_enabled', 'value' => '1', 'type' => 'bool'],
        ['group' => 'security', 'key' => 'ip_whitelist', 'value' => '[]', 'type' => 'json'],

        // 11.10 Feature toggles
        ['group' => 'feature_flags', 'key' => 'income_module', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'expense_module', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'reports', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'dashboard', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'categories', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'exchange_rate_sync', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'audit_logs', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'user_registration', 'value' => '0', 'type' => 'bool'], // admin creates users; no public signup
        ['group' => 'feature_flags', 'key' => 'user_management', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'dark_mode', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'export_pdf', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'export_excel', 'value' => '1', 'type' => 'bool'],
        ['group' => 'feature_flags', 'key' => 'export_csv', 'value' => '1', 'type' => 'bool'],
    ];

    public function run(PDO $db): void
    {
        $stmt = $db->prepare(
            'INSERT INTO settings (`group`, `key`, value, type) VALUES (:group, :key, :value, :type)
             ON DUPLICATE KEY UPDATE type = VALUES(type)' // don't clobber a value an admin already changed; keep type in sync
        );

        foreach (self::DEFAULTS as $row) {
            $stmt->execute($row);
        }
    }
}
