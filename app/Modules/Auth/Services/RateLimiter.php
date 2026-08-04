<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Database;
use App\Core\SettingsCache;

/**
 * Rate-limits login attempts by BOTH email and IP independently (spec §2:
 * "Rate limiting for login attempts"), so an attacker can't bypass an
 * email-based lock by trying many emails from one IP, or an IP-based
 * lock by distributing across many IPs against one email.
 */
final class RateLimiter
{
    public function tooManyAttempts(string $email, string $ip): bool
    {
        $maxAttempts = (int) SettingsCache::get('user', 'max_login_attempts', 5);
        $lockMinutes = (int) SettingsCache::get('user', 'account_lock_minutes', 15);
        $since = (new \DateTimeImmutable("-{$lockMinutes} minutes"))->format('Y-m-d H:i:s');

        return $this->recentFailedCount($email, $since) >= $maxAttempts
            || $this->recentFailedCountByIp($ip, $since) >= ($maxAttempts * 3); // looser cap per-IP to avoid locking out a shared home IP too easily
    }

    public function recordAttempt(string $email, string $ip, bool $success): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (email, ip_address, success) VALUES (:email, :ip, :success)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip, 'success' => $success ? 1 : 0]);
    }

    private function recentFailedCount(string $email, string $since): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE email = :email AND success = 0 AND created_at >= :since'
        );
        $stmt->execute(['email' => $email, 'since' => $since]);
        return (int) $stmt->fetchColumn();
    }

    private function recentFailedCountByIp(string $ip, string $since): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND success = 0 AND created_at >= :since'
        );
        $stmt->execute(['ip' => $ip, 'since' => $since]);
        return (int) $stmt->fetchColumn();
    }
}
