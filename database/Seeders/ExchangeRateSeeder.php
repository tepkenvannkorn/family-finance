<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Seeder;
use PDO;

/**
 * Seeds one starting USD -> KHR rate so currency conversion has something
 * to work with immediately after install. The Settings module (Phase 8)
 * lets the admin update this manually or turn on automatic sync.
 * Approximate market rate as of writing — admin should verify on first login.
 */
final class ExchangeRateSeeder extends Seeder
{
    private const INITIAL_RATE = '4100.00';

    public function run(PDO $db): void
    {
        $exists = $db->query('SELECT COUNT(*) FROM exchange_rates')->fetchColumn();
        if ((int) $exists > 0) {
            return;
        }

        $stmt = $db->prepare(
            "INSERT INTO exchange_rates (base_currency, quote_currency, rate, source, created_by)
             VALUES ('USD', 'KHR', :rate, 'manual', NULL)"
        );
        $stmt->execute(['rate' => self::INITIAL_RATE]);
    }
}
