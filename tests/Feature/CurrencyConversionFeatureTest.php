<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Modules\Transactions\Services\CurrencyConverter;
use PHPUnit\Framework\TestCase;

/**
 * Requires a real (test) MySQL database migrated + seeded — see
 * tests/Feature/README.md. Skips itself cleanly if one isn't configured,
 * so `phpunit` still runs the Unit suite in any environment.
 */
final class CurrencyConversionFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        if (!getenv('DB_DATABASE')) {
            $this->markTestSkipped('No test database configured — see tests/Feature/README.md');
        }

        try {
            Database::connection();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Could not connect to the test database: ' . $e->getMessage());
        }
    }

    public function testConvertsUsdToKhrUsingTheSeededRate(): void
    {
        $converter = new CurrencyConverter();
        $rate = $converter->latestRate();

        $result = $converter->convert('10.00', 'USD', 'KHR', $rate);

        $this->assertEquals(bcmul('10.00', $rate, 2), $result);
    }

    public function testConvertsKhrToUsdUsingTheSeededRate(): void
    {
        $converter = new CurrencyConverter();
        $rate = $converter->latestRate();

        $result = $converter->convert('41000', 'KHR', 'USD', $rate);

        $this->assertEquals(bcdiv('41000', $rate, 2), $result);
    }
}
