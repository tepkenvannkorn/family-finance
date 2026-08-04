<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Transactions\Services\CurrencyConverter;
use PHPUnit\Framework\TestCase;

final class CurrencyConverterTest extends TestCase
{
    public function testConvertingToTheSameCurrencyReturnsTheOriginalAmountUnchanged(): void
    {
        $converter = new CurrencyConverter();

        // Same-currency conversion short-circuits before touching Settings/DB,
        // so this is safe to test without a database connection.
        $this->assertSame('123.45', $converter->convert('123.45', 'USD', 'USD', '4100.00'));
        $this->assertSame('50000', $converter->convert('50000', 'KHR', 'KHR', '4100.00'));
    }

    // Cross-currency conversion (USD<->KHR) depends on App\Core\SettingsCache for the
    // configured decimal-places setting, which reads from the database — covered in
    // tests/Feature (requires a configured test database).
}
