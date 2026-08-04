<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Policies;

use App\Core\SettingsCache;
use App\Models\Transaction;

/**
 * Answers yes/no authorization questions so controllers don't embed RBAC
 * logic. Admins can always edit/delete any transaction (spec §3.1: "View
 * all reports" implies full oversight); members are further gated by the
 * allow_edit_own / allow_delete_own toggles in Settings (spec §11.3).
 */
final class TransactionPolicy
{
    public static function canEdit(Transaction $transaction, int $userId, string $roleName): bool
    {
        if ($roleName === 'admin') {
            return true;
        }
        if ($transaction->createdBy !== $userId) {
            return false;
        }
        return (bool) SettingsCache::get('transaction', 'allow_edit_own', true);
    }

    public static function canDelete(Transaction $transaction, int $userId, string $roleName): bool
    {
        if ($roleName === 'admin') {
            return true;
        }
        if ($transaction->createdBy !== $userId) {
            return false;
        }
        return (bool) SettingsCache::get('transaction', 'allow_delete_own', true);
    }

    public static function canView(Transaction $transaction, int $userId, string $roleName): bool
    {
        return $roleName === 'admin' || $transaction->createdBy === $userId;
    }
}
