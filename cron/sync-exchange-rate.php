<?php

declare(strict_types=1);

/**
 * Scheduled exchange-rate sync. Wire this to cron for the "daily" or
 * "weekly" options under Settings → Currency → sync interval, e.g.:
 *   0 6 * * *  php /var/www/family-finance-app/cron/sync-exchange-rate.php        (daily, 6am)
 *   0 6 * * 1  php /var/www/family-finance-app/cron/sync-exchange-rate.php        (weekly, Monday 6am)
 *
 * The "every login" option needs no cron entry — it's handled inline by
 * checking Settings during the login flow (see Phase 11 README for the
 * one-line hook if you want to wire that up too).
 */

require dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\AuditLogger;
use App\Core\SettingsCache;
use App\Modules\Settings\Services\ExchangeRateService;

if (!SettingsCache::get('currency', 'auto_sync_enabled', false)) {
    echo "Auto-sync is disabled in Settings — nothing to do.\n";
    exit(0);
}

try {
    $rate = (new ExchangeRateService())->fetchFromApi();
    AuditLogger::log(null, 'exchange_rate.cron_fetch', 'exchange_rate', $rate->id, ['rate' => $rate->rate]);
    echo "Synced exchange rate: {$rate->rate} KHR per USD\n";
} catch (\RuntimeException $e) {
    fwrite(STDERR, 'Exchange rate sync failed: ' . $e->getMessage() . "\n");
    exit(1);
}
