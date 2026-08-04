<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Services;

use App\Core\SettingsCache;
use RuntimeException;

/**
 * Validates and stores transaction attachments. Files are written under
 * /storage/uploads (outside the public webroot — see public/.htaccess and
 * the root-level deny-all .htaccess) and given a random filename, so
 * nothing here is guessable or directly web-accessible; they're only
 * ever served through the authenticated download route.
 */
final class FileUploadService
{
    private const ALLOWED_MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    /**
     * @param array $file one entry from $_FILES
     * @return array{path: string, original_name: string, mime: string, size: int}
     * @throws RuntimeException on any validation failure
     */
    public function store(array $file, int $transactionId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('No file was uploaded.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed. Please try again.');
        }

        $maxBytes = ((int) SettingsCache::get('transaction', 'max_upload_size_mb', 10)) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            throw new RuntimeException('File is too large. Maximum allowed size is configured in Settings.');
        }

        // Detect the REAL mime type from file contents (magic bytes), not the
        // client-supplied Content-Type or the filename extension — both are
        // trivially spoofable.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIME_TO_EXT[$detectedMime])) {
            throw new RuntimeException('Only JPG, PNG, and PDF files are allowed.');
        }

        $extension = self::ALLOWED_MIME_TO_EXT[$detectedMime];
        $storageRoot = dirname(__DIR__, 4) . '/storage/uploads/transactions/' . $transactionId;

        if (!is_dir($storageRoot) && !mkdir($storageRoot, 0750, true) && !is_dir($storageRoot)) {
            throw new RuntimeException('Could not create storage directory.');
        }

        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $storageRoot . '/' . $randomName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Could not save the uploaded file.');
        }

        return [
            'path' => $destination,
            'original_name' => basename($file['name']),
            'mime' => $detectedMime,
            'size' => (int) $file['size'],
        ];
    }
}
