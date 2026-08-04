<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\View;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    public function testEscapesHtmlSpecialCharacters(): void
    {
        $malicious = '<script>alert("xss")</script>';
        $escaped = View::e($malicious);

        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $escaped);
    }

    public function testEscapesNullAsEmptyString(): void
    {
        $this->assertSame('', View::e(null));
    }

    public function testRawReturnsInputUnchanged(): void
    {
        $html = '<b>bold</b>';
        $this->assertSame($html, View::raw($html));
    }
}
