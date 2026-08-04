<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Core\SettingsCache;
use App\Models\ExchangeRate;
use RuntimeException;

/**
 * Fetches USD->KHR from a public exchange-rate API when auto-sync is
 * enabled (spec §7/§11.2), falling back to the last manual/fetched rate
 * on failure. Every accepted rate is validated against a sane bound of
 * the previous one, so a corrupted/tampered API response can't silently
 * blow up every converted figure in the app.
 */
final class ExchangeRateService
{
    private const API_URL = 'https://open.er-api.com/v6/latest/USD';
    private const MAX_PLAUSIBLE_SWING_PERCENT = 15; // reject a fetched rate that jumps more than this vs. the last known rate

    public function setManualRate(string $rate, int $adminUserId): ExchangeRate
    {
        if (!is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException('Please enter a valid positive exchange rate.');
        }
        return ExchangeRate::record($rate, 'manual', $adminUserId);
    }

    /** @throws RuntimeException if the fetch fails or the fetched value looks implausible */
    public function fetchFromApi(): ExchangeRate
    {
        $useManualOnFailure = (bool) SettingsCache::get('currency', 'use_manual_when_api_unavailable', true);

        try {
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $response = @file_get_contents(self::API_URL, false, $context);

            if ($response === false) {
                throw new RuntimeException('Could not reach the exchange rate service.');
            }

            $data = json_decode($response, true);
            $fetchedRate = $data['rates']['KHR'] ?? null;

            if ($fetchedRate === null || !is_numeric($fetchedRate) || (float) $fetchedRate <= 0) {
                throw new RuntimeException('Exchange rate service returned an invalid response.');
            }

            $this->assertPlausible((string) $fetchedRate);

            return ExchangeRate::record((string) $fetchedRate, 'api', null);
        } catch (RuntimeException $e) {
            if ($useManualOnFailure) {
                $latest = ExchangeRate::latest();
                if ($latest) {
                    return $latest; // keep using the last known-good rate rather than breaking conversions
                }
            }
            throw $e;
        }
    }

    private function assertPlausible(string $newRate): void
    {
        $previous = ExchangeRate::latest();
        if (!$previous) {
            return; // nothing to compare against yet
        }

        $swing = abs(((float) $newRate - (float) $previous->rate) / (float) $previous->rate) * 100;
        if ($swing > self::MAX_PLAUSIBLE_SWING_PERCENT) {
            throw new RuntimeException(
                "Fetched rate ({$newRate}) differs from the last known rate by more than " . self::MAX_PLAUSIBLE_SWING_PERCENT . "% — rejected as implausible. Set it manually if this is correct."
            );
        }
    }
}
