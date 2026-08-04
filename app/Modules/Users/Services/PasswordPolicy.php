<?php

declare(strict_types=1);

namespace App\Modules\Users\Services;

use App\Core\SettingsCache;

/**
 * Centralizes password strength rules so both admin-created accounts and
 * self-service password changes enforce the same policy, configured via
 * Settings (spec §11.5: password minimum length / complexity) rather than
 * hardcoded.
 */
final class PasswordPolicy
{
    /** @return string[] validation errors, empty if the password passes */
    public static function validate(string $password): array
    {
        $errors = [];
        $minLength = (int) SettingsCache::get('user', 'password_min_length', 10);
        $requireComplexity = (bool) SettingsCache::get('user', 'password_require_complexity', true);

        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters.";
        }

        if ($requireComplexity) {
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must include at least one uppercase letter.';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must include at least one lowercase letter.';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'Password must include at least one number.';
            }
        }

        return $errors;
    }
}
