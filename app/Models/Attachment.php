<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Attachment
{
    public int $id;
    public int $transactionId;
    public string $filePath;
    public string $originalFilename;
    public string $mimeType;
    public int $sizeBytes;
    public int $uploadedBy;

    /** @return self[] */
    public static function forTransaction(int $transactionId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM attachments WHERE transaction_id = :id ORDER BY created_at ASC'
        );
        $stmt->execute(['id' => $transactionId]);
        return array_map(self::hydrate(...), $stmt->fetchAll());
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM attachments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function create(int $transactionId, string $filePath, string $originalFilename, string $mimeType, int $sizeBytes, int $uploadedBy): self
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO attachments (transaction_id, file_path, original_filename, mime_type, size_bytes, uploaded_by)
             VALUES (:transaction_id, :file_path, :original_filename, :mime_type, :size_bytes, :uploaded_by)'
        );
        $stmt->execute([
            'transaction_id' => $transactionId,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'uploaded_by' => $uploadedBy,
        ]);

        return self::findById((int) Database::connection()->lastInsertId());
    }

    public function delete(): void
    {
        if (is_file($this->filePath)) {
            @unlink($this->filePath);
        }
        $stmt = Database::connection()->prepare('DELETE FROM attachments WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    private static function hydrate(array $row): self
    {
        $a = new self();
        $a->id = (int) $row['id'];
        $a->transactionId = (int) $row['transaction_id'];
        $a->filePath = $row['file_path'];
        $a->originalFilename = $row['original_filename'];
        $a->mimeType = $row['mime_type'];
        $a->sizeBytes = (int) $row['size_bytes'];
        $a->uploadedBy = (int) $row['uploaded_by'];
        return $a;
    }
}
