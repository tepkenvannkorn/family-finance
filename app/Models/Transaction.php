<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Transaction
{
    public int $id;
    public string $type;              // income | expense
    public string $amount;            // decimal as string — never do float math on money
    public string $currency;          // KHR | USD
    public int $categoryId;
    public string $description;
    public ?string $notes;
    public string $transactionDate;
    public string $transactionTime;
    public int $createdBy;
    public ?int $updatedBy;
    public ?string $deletedAt;

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM transactions WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function create(array $data): self
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO transactions (type, amount, currency, category_id, description, notes, transaction_date, transaction_time, created_by)
             VALUES (:type, :amount, :currency, :category_id, :description, :notes, :transaction_date, :transaction_time, :created_by)'
        );
        $stmt->execute([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'notes' => $data['notes'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'transaction_time' => $data['transaction_time'],
            'created_by' => $data['created_by'],
        ]);

        return self::findById((int) Database::connection()->lastInsertId());
    }

    public function update(array $data, int $updatedBy): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE transactions SET
                type = :type, amount = :amount, currency = :currency, category_id = :category_id,
                description = :description, notes = :notes, transaction_date = :transaction_date,
                transaction_time = :transaction_time, updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'notes' => $data['notes'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'transaction_time' => $data['transaction_time'],
            'updated_by' => $updatedBy,
            'id' => $this->id,
        ]);
    }

    /** Soft delete — recoverable by an admin, per Phase 1's design decision. */
    public function softDelete(): void
    {
        $stmt = Database::connection()->prepare('UPDATE transactions SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    private static function hydrate(array $row): self
    {
        $t = new self();
        $t->id = (int) $row['id'];
        $t->type = $row['type'];
        $t->amount = $row['amount'];
        $t->currency = $row['currency'];
        $t->categoryId = (int) $row['category_id'];
        $t->description = $row['description'];
        $t->notes = $row['notes'];
        $t->transactionDate = $row['transaction_date'];
        $t->transactionTime = $row['transaction_time'];
        $t->createdBy = (int) $row['created_by'];
        $t->updatedBy = $row['updated_by'] !== null ? (int) $row['updated_by'] : null;
        $t->deletedAt = $row['deleted_at'];
        return $t;
    }
}
