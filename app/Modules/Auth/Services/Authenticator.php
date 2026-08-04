<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\AuditLogger;
use App\Core\Session;
use App\Core\SettingsCache;
use App\Models\User;

/**
 * Owns the actual login/logout state changes. Kept separate from
 * AuthController so the same logic can be reused (e.g. by a future
 * API layer) without going through HTTP controller plumbing.
 */
final class Authenticator
{
    private const REMEMBER_COOKIE = 'remember_me';

    public function login(User $user, bool $remember): void
    {
        Session::regenerate(); // new session ID on privilege change — prevents session fixation

        Session::put('user_id', $user->id);
        Session::put('role_id', $user->roleId);
        Session::put('role_name', $user->roleName);
        Session::put('name', $user->name);

        $user->recordSuccessfulLogin();

        if ($remember) {
            $this->issueRememberCookie($user);
        }

        AuditLogger::log($user->id, 'auth.login', 'user', $user->id);
    }

    public function logout(): void
    {
        $userId = Session::get('user_id');

        if ($userId && isset($_COOKIE[self::REMEMBER_COOKIE])) {
            $user = User::findById((int) $userId);
            $user?->setRememberToken(null);
        }

        setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/', '', true, true);
        AuditLogger::log($userId ? (int) $userId : null, 'auth.logout', 'user', $userId ? (int) $userId : null);

        Session::destroy();
    }

    /** Attempts to resume a session from the remember-me cookie. Called once per request by the front controller. */
    public function attemptCookieLogin(): void
    {
        if (Session::get('user_id') || !isset($_COOKIE[self::REMEMBER_COOKIE])) {
            return;
        }

        [$id, $plainToken] = array_pad(explode(':', $_COOKIE[self::REMEMBER_COOKIE], 2), 2, null);
        if (!$id || !$plainToken) {
            return;
        }

        $user = User::findById((int) $id);
        if (!$user || !$user->rememberToken || !hash_equals($user->rememberToken, hash('sha256', $plainToken))) {
            return;
        }

        $this->login($user, remember: true); // refresh cookie + rotate token on each auto-login
    }

    private function issueRememberCookie(User $user): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $user->setRememberToken(hash('sha256', $plainToken));

        $days = (int) SettingsCache::get('user', 'remember_me_days', 30);

        setcookie(
            self::REMEMBER_COOKIE,
            $user->id . ':' . $plainToken,
            [
                'expires' => time() + ($days * 86400),
                'path' => '/',
                'secure' => (bool) SettingsCache::get('security', 'force_https', true),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}
