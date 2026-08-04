<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Seeder;
use PDO;

/**
 * Seeds permission keys and wires them to roles.
 * Controllers/Policies check these keys (e.g. Auth::can('transactions.delete_own'))
 * rather than checking role names directly, so new roles/permissions
 * can be added later without touching authorization code.
 */
final class PermissionSeeder extends Seeder
{
    /** @var array<string,string> key => description */
    private const PERMISSIONS = [
        'users.manage'                 => 'Create, edit, delete users and assign roles',
        'settings.manage'               => 'View and change all system settings',
        'exchange_rates.manage'          => 'Manually set or configure automatic exchange rates',
        'categories.manage'              => 'Create, edit, deactivate categories',
        'reports.view_all'               => "View every family member's reports, not just their own",
        'audit_logs.view'                => 'View the system audit log',
        'transactions.create'            => 'Add income or expense transactions',
        'transactions.edit_own'          => 'Edit transactions the user created',
        'transactions.delete_own'        => 'Delete transactions the user created',
        'dashboard.view'                 => 'View the personal dashboard',
        'reports.view_own'               => "View the user's own reports",
    ];

    /** @var array<string,string[]> role name => permission keys */
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'users.manage', 'settings.manage', 'exchange_rates.manage', 'categories.manage',
            'reports.view_all', 'audit_logs.view', 'transactions.create', 'transactions.edit_own',
            'transactions.delete_own', 'dashboard.view', 'reports.view_own',
        ],
        'member' => [
            'transactions.create', 'transactions.edit_own', 'transactions.delete_own',
            'dashboard.view', 'reports.view_own',
        ],
    ];

    public function run(PDO $db): void
    {
        $insertPermission = $db->prepare(
            'INSERT INTO permissions (`key`, description) VALUES (:key, :description)
             ON DUPLICATE KEY UPDATE description = VALUES(description)'
        );
        foreach (self::PERMISSIONS as $key => $description) {
            $insertPermission->execute(['key' => $key, 'description' => $description]);
        }

        $roleIdStmt = $db->prepare('SELECT id FROM roles WHERE name = :name');
        $permIdStmt = $db->prepare('SELECT id FROM permissions WHERE `key` = :key');
        $link = $db->prepare(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)'
        );

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionKeys) {
            $roleIdStmt->execute(['name' => $roleName]);
            $roleId = $roleIdStmt->fetchColumn();
            if ($roleId === false) {
                continue; // role wasn't seeded — skip rather than fail the whole run
            }

            foreach ($permissionKeys as $key) {
                $permIdStmt->execute(['key' => $key]);
                $permId = $permIdStmt->fetchColumn();
                if ($permId === false) {
                    continue;
                }
                $link->execute(['role_id' => $roleId, 'permission_id' => $permId]);
            }
        }
    }
}
