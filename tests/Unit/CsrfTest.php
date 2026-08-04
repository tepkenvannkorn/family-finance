<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsGeneratedAndReused(): void
    {
        $first = Csrf::token();
        $second = Csrf::token();

        $this->assertSame($first, $second, 'token() should return the same token for the same session');
        $this->assertSame(64, strlen($first), 'expects a 32-byte token hex-encoded to 64 characters');
    }

    public function testVerifyAcceptsTheCorrectToken(): void
    {
        $token = Csrf::token();
        $this->assertTrue(Csrf::verify($token));
    }

    public function testVerifyRejectsAWrongToken(): void
    {
        Csrf::token();
        $this->assertFalse(Csrf::verify('not-the-right-token'));
    }

    public function testVerifyRejectsNullOrMissingToken(): void
    {
        $this->assertFalse(Csrf::verify(null));

        $_SESSION = [];
        $this->assertFalse(Csrf::verify('anything'));
    }
}
