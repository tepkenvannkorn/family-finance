<?php

declare(strict_types=1);

namespace App\Core;

/**
 * One CSRF token per session, verified on every state-changing request
 * (spec §2, §11.9: "CSRF protection (always enabled)").
 * Uses hash_equals for timing-safe comparison.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return "<input type=\"hidden\" name=\"_csrf\" value=\"{$token}\">";
    }

    public static function verify(?string $submitted): bool
    {
        if (!$submitted || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $submitted);
    }
}
