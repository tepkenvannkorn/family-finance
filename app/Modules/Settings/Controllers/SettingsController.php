<?php

declare(strict_types=1);

namespace App\Modules\Settings\Controllers;

use App\Core\AuditLogger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\SettingsCache;
use App\Core\View;

final class SettingsController
{
    /** group => [key => type] — drives which fields render and how they're cast/validated on save */
    private const SCHEMA = [
        'general' => [
            'app_name' => 'string', 'family_name' => 'string', 'default_landing_page' => 'string',
            'default_language' => 'string', 'default_timezone' => 'string', 'default_date_format' => 'string',
            'default_time_format' => 'string', 'default_currency_display' => 'string',
        ],
        'currency' => [
            'auto_sync_enabled' => 'bool', 'sync_interval' => 'string', 'use_manual_when_api_unavailable' => 'bool',
            'decimal_places' => 'int', 'symbol_usd' => 'string', 'symbol_khr' => 'string',
        ],
        'transaction' => [
            'allow_edit_own' => 'bool', 'allow_delete_own' => 'bool', 'require_delete_confirmation' => 'bool',
            'allow_future_dates' => 'bool', 'allow_past_dates' => 'bool', 'allow_negative_balance' => 'bool',
            'require_notes_for_expense' => 'bool', 'require_category' => 'bool', 'max_upload_size_mb' => 'int',
        ],
        'dashboard' => [
            'default_chart_period' => 'string', 'show_recent_transactions' => 'bool', 'show_monthly_summary' => 'bool',
            'show_yearly_summary' => 'bool', 'show_balance_cards' => 'bool', 'show_quick_statistics' => 'bool',
        ],
        'user' => [
            'session_timeout_minutes' => 'int', 'password_min_length' => 'int', 'password_require_complexity' => 'bool',
            'max_login_attempts' => 'int', 'account_lock_minutes' => 'int', 'remember_me_days' => 'int',
            'two_factor_enabled' => 'bool', 'allow_self_password_reset' => 'bool',
        ],
        'appearance' => [
            'theme' => 'string', 'primary_color' => 'string', 'sidebar_position' => 'string',
            'compact_mode' => 'bool', 'table_density' => 'string', 'font_size' => 'string',
        ],
        'notification' => [
            'success_enabled' => 'bool', 'error_enabled' => 'bool', 'warning_enabled' => 'bool',
            'email_enabled' => 'bool', 'browser_enabled' => 'bool',
        ],
        'security' => [
            'force_https' => 'bool', 'session_lifetime_minutes' => 'int', 'password_expiration_days' => 'int',
            'audit_logging_enabled' => 'bool',
        ],
        'feature_flags' => [
            'income_module' => 'bool', 'expense_module' => 'bool', 'reports' => 'bool', 'dashboard' => 'bool',
            'categories' => 'bool', 'exchange_rate_sync' => 'bool', 'audit_logs' => 'bool', 'user_registration' => 'bool',
            'user_management' => 'bool', 'dark_mode' => 'bool', 'export_pdf' => 'bool', 'export_excel' => 'bool', 'export_csv' => 'bool',
        ],
    ];

    public function index(Request $request): void
    {
        $group = (string) $request->input('group', 'general');
        if (!isset(self::SCHEMA[$group])) {
            $group = 'general';
        }

        echo View::render('Settings::index', [
            'group' => $group,
            'groups' => array_keys(self::SCHEMA),
            'fields' => self::SCHEMA[$group],
            'values' => SettingsCache::group($group),
            'success' => Session::pull('success'),
        ]);
    }

    public function update(Request $request, string $group): void
    {
        if (!isset(self::SCHEMA[$group])) {
            Response::notFound();
        }

        $adminId = (int) Session::get('user_id');

        foreach (self::SCHEMA[$group] as $key => $type) {
            $raw = $request->input($key);
            $value = match ($type) {
                'bool' => $raw !== null ? '1' : '0', // unchecked checkboxes simply aren't in the POST body
                'int' => (string) (int) $raw,
                default => (string) $raw,
            };
            SettingsCache::set($group, $key, $value, $type, $adminId);
        }

        AuditLogger::log($adminId, 'settings.update', 'settings_group', null, ['group' => $group]);

        Session::flash('success', 'Settings saved.');
        Response::redirect("/settings?group={$group}");
    }
}
