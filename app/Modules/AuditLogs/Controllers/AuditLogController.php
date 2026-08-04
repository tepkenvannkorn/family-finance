<?php

declare(strict_types=1);

namespace App\Modules\AuditLogs\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\View;
use PDO;

final class AuditLogController
{
    private const PER_PAGE = 40;

    public function index(Request $request): void
    {
        $action = (string) $request->input('action', '');
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $where = '';
        $params = [];
        if ($action !== '') {
            $where = 'WHERE a.action LIKE :action';
            $params['action'] = '%' . $action . '%';
        }

        $db = Database::connection();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs a {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             {$where}
             ORDER BY a.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', self::PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        echo View::render('AuditLogs::index', [
            'logs' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'action' => $action,
        ]);
    }
}
