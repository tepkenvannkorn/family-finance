<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Seeder;
use PDO;

/**
 * Seeds the default categories listed in spec §10.
 * created_by is left NULL to mark these as system defaults
 * (vs. categories an admin adds later, which record the admin's user id).
 */
final class CategorySeeder extends Seeder
{
    private const INCOME = ['Salary', 'Bonus', 'Investment', 'Business', 'Gift'];

    private const EXPENSE = [
        'Food', 'Fuel', 'Electricity', 'Water', 'Internet', 'Shopping',
        'Medical', 'Education', 'Entertainment', 'Travel', 'Others',
    ];

    public function run(PDO $db): void
    {
        $stmt = $db->prepare(
            'INSERT INTO categories (type, name, sort_order, created_by)
             VALUES (:type, :name, :sort_order, NULL)
             ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order)'
        );

        foreach ([['income', self::INCOME], ['expense', self::EXPENSE]] as [$type, $names]) {
            foreach ($names as $index => $name) {
                $stmt->execute(['type' => $type, 'name' => $name, 'sort_order' => $index]);
            }
        }
    }
}
