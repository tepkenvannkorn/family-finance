<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Database;
use App\Core\Response;
use App\Core\Session;

/**
 * Route-level authorization by permission key (not role name), so new
 * roles can be granted existing permissions later without touching route
 * definitions. Always paired with AuthMiddleware first — assumes a user
 * is already logged in.
 */
final class PermissionMiddleware
{
    public function __construct(private string $permissionKey)
    {
    }

    public function handle(callable $next): void
    {
        $roleId = Session::get('role_id');

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id AND p.key = :key'
        );
        $stmt->execute(['role_id' => $roleId, 'key' => $this->permissionKey]);

        if ((int) $stmt->fetchColumn() === 0) {
            Response::forbidden();
        }

        $next();
    }
}
