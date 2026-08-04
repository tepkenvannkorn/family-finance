<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Records important system activity (spec §2: "Audit log for important
 * system activities"). Call from controllers/services after a state
 * change succeeds — never blocks the request if logging itself fails.
 */
final class AuditLogger
{
    public static function log(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = []
    ): void {
        if (!SettingsCache::get('security', 'audit_logging_enabled', true)) {
            return;
        }

        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata_json)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :ip, :ua, :metadata)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'metadata' => $metadata ? json_encode($metadata) : null,
            ]);
        } catch (\Throwable) {
            // Audit logging must never break the user-facing action it's logging.
        }
    }
}
