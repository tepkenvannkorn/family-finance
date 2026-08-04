<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\SettingsCache;
use App\Core\View;
use App\Models\DashboardPreference;
use App\Modules\Dashboard\Services\DashboardAggregator;

final class DashboardController
{
    public function index(Request $request): void
    {
        $userId = (int) Session::get('user_id');
        $roleName = (string) Session::get('role_name');
        $scopedUserId = $roleName === 'admin' ? null : $userId; // admins see the whole family; members see their own

        echo View::render('Dashboard::index', [
            'layout' => DashboardPreference::layoutFor($userId),
            'currencyDisplay' => (string) SettingsCache::get('general', 'default_currency_display', 'original'),
            'scopedUserId' => $scopedUserId,
            'name' => Session::get('name'),
        ]);
    }

    /** JSON data feed consumed by the Chart.js widgets and the balance cards. */
    public function data(Request $request): void
    {
        $userId = (int) Session::get('user_id');
        $roleName = (string) Session::get('role_name');
        $scopedUserId = $roleName === 'admin' ? null : $userId;
        $displayCurrency = (string) $request->input('currency', SettingsCache::get('general', 'default_currency_display', 'original'));

        $aggregator = new DashboardAggregator();
        $today = new \DateTimeImmutable('now');

        Response::json([
            'summary' => $aggregator->summary($scopedUserId, $displayCurrency),
            'income_vs_expense' => $aggregator->incomeVsExpense($scopedUserId),
            'weekly_trend' => $aggregator->weeklyTrend($scopedUserId),
            'expense_by_category' => $aggregator->expenseByCategory(
                $scopedUserId,
                $today->modify('first day of this month')->format('Y-m-d'),
                $today->format('Y-m-d')
            ),
            'recent_transactions' => $aggregator->recentTransactions($scopedUserId, 10),
        ]);
    }

    public function saveLayout(Request $request): void
    {
        $userId = (int) Session::get('user_id');
        $layout = json_decode(file_get_contents('php://input') ?: '{}', true);

        if (!is_array($layout)) {
            Response::json(['ok' => false], 422);
        }

        DashboardPreference::save($userId, $layout);
        Response::json(['ok' => true]);
    }

    public function resetLayout(Request $request): void
    {
        DashboardPreference::resetToDefault((int) Session::get('user_id'));
        Response::redirect('/dashboard');
    }
}
