<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Requests;

use App\Core\Request;
use App\Core\SettingsCache;
use App\Models\Category;

final class TransactionRequest
{
    /** @return string[] validation errors */
    public static function validate(Request $request): array
    {
        $errors = [];

        $type = (string) $request->input('type', '');
        $amount = (string) $request->input('amount', '');
        $currency = (string) $request->input('currency', '');
        $categoryId = (int) $request->input('category_id', 0);
        $description = (string) $request->input('description', '');
        $notes = (string) $request->input('notes', '');
        $date = (string) $request->input('transaction_date', '');

        if (!in_array($type, ['income', 'expense'], true)) {
            $errors[] = 'Please select income or expense.';
        }

        if ($amount === '' || !is_numeric($amount) || (float) $amount <= 0) {
            $errors[] = 'Please enter a valid amount greater than zero.';
        }

        if (!in_array($currency, ['KHR', 'USD'], true)) {
            $errors[] = 'Please select a currency.';
        }

        $requireCategory = (bool) SettingsCache::get('transaction', 'require_category', true);
        $category = $categoryId > 0 ? Category::findById($categoryId) : null;
        if ($requireCategory && (!$category || $category->type !== $type)) {
            $errors[] = 'Please select a valid category matching the transaction type.';
        }

        if (trim($description) === '') {
            $errors[] = 'Please enter a description.';
        }

        $requireNotesForExpense = (bool) SettingsCache::get('transaction', 'require_notes_for_expense', false);
        if ($type === 'expense' && $requireNotesForExpense && trim($notes) === '') {
            $errors[] = 'Notes are required for expense transactions.';
        }

        if ($date === '' || !self::isValidDate($date)) {
            $errors[] = 'Please enter a valid date.';
        } else {
            $today = date('Y-m-d');
            $allowFuture = (bool) SettingsCache::get('transaction', 'allow_future_dates', false);
            $allowPast = (bool) SettingsCache::get('transaction', 'allow_past_dates', true);

            if (!$allowFuture && $date > $today) {
                $errors[] = 'Future-dated transactions are not allowed.';
            }
            if (!$allowPast && $date < $today) {
                $errors[] = 'Past-dated transactions are not allowed.';
            }
        }

        return $errors;
    }

    private static function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
