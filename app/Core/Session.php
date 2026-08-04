<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Wraps PHP's native session with the hardening spec §2/§11.9 calls for:
 * HttpOnly + Secure + SameSite cookies, ID regeneration on login,
 * and a lightweight fingerprint binding to make session-cookie theft
 * (e.g. via XSS elsewhere, or a copied cookie) less useful to an attacker.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetimeMinutes = (int) (SettingsCache::get('security', 'session_lifetime_minutes', 120));
        $forceHttps = (bool) SettingsCache::get('security', 'force_https', true);

        session_set_cookie_params([
            'lifetime' => 0, // session cookie; absolute timeout enforced separately below
            'path' => '/',
            'domain' => '',
            'secure' => $forceHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('sabay_session');
        session_start();

        self::enforceAbsoluteTimeout($lifetimeMinutes);
        self::bindFingerprint();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_fingerprint'] = self::fingerprint();
        $_SESSION['_last_activity'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    /**
     * Flash and pull are the same underlying store: flash() writes a
     * value meant to be read exactly once on the next request, and
     * pull() (or getFlash()) is that one read — it deletes on access.
     * Keeping them as one mechanism (rather than a separate "_flash"
     * namespace) avoids the two ever getting out of sync.
     */
    public static function flash(string $key, mixed $value): void
    {
        self::put($key, $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return self::pull($key, $default);
    }

    private static function enforceAbsoluteTimeout(int $lifetimeMinutes): void
    {
        $now = time();
        $last = $_SESSION['_last_activity'] ?? null;

        if ($last !== null && ($now - $last) > ($lifetimeMinutes * 60)) {
            self::destroy();
            session_start(); // start a fresh, empty session so the request doesn't error out
        }

        $_SESSION['_last_activity'] = $now;
    }

    private static function bindFingerprint(): void
    {
        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = self::fingerprint();
            return;
        }

        if (!hash_equals($_SESSION['_fingerprint'], self::fingerprint())) {
            // User-Agent changed mid-session — likely a stolen/copied cookie. Force re-login.
            self::destroy();
            session_start();
            $_SESSION['_fingerprint'] = self::fingerprint();
        }
    }

    private static function fingerprint(): string
    {
        return hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    }
}
