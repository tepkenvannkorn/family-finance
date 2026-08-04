<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Services;

use App\Core\Database;
use App\Core\SettingsCache;

/**
 * Converts amounts for DISPLAY only. Stored transaction amounts/currencies
 * are never modified (spec §6/§7). Reports that need a historical rate
 * (e.g. "what was this worth in March") should pass that date; the
 * dashboard/live views can omit it to get the latest rate.
 */
final class CurrencyConverter
{
    public function latestRate(): string
    {
        $stmt = Database::connection()->query(
            "SELECT rate FROM exchange_rates
             WHERE base_currency = 'USD' AND quote_currency = 'KHR'
             ORDER BY fetched_at DESC LIMIT 1"
        );
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (string) $rate : '4100.000000';
    }

    public function rateAsOf(string $date): string
    {
        $stmt = Database::connection()->prepare(
            "SELECT rate FROM exchange_rates
             WHERE base_currency = 'USD' AND quote_currency = 'KHR' AND DATE(fetched_at) <= :date
             ORDER BY fetched_at DESC LIMIT 1"
        );
        $stmt->execute(['date' => $date]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (string) $rate : $this->latestRate();
    }

    /** Converts a KHR<->USD amount using the given USD->KHR rate. */
    public function convert(string $amount, string $fromCurrency, string $toCurrency, string $usdToKhrRate): string
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $decimals = (int) SettingsCache::get('currency', 'decimal_places', 2);

        if ($fromCurrency === 'USD' && $toCurrency === 'KHR') {
            return bcmul($amount, $usdToKhrRate, $decimals);
        }

        if ($fromCurrency === 'KHR' && $toCurrency === 'USD') {
            return bcdiv($amount, $usdToKhrRate, $decimals);
        }

        return $amount;
    }
}
