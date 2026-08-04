<?php

declare(strict_types=1);

namespace App\Modules\Backup\Controllers;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\SettingsCache;
use App\Core\View;

final class BackupController
{
    public function index(Request $request): void
    {
        echo View::render('Backup::index', [
            'maintenanceMode' => (bool) SettingsCache::get('general', 'maintenance_mode', false),
            'success' => Session::pull('success'),
            'error' => Session::pull('error'),
        ]);
    }

    /** Streams a full mysqldump of the database — requires the mysqldump binary on the host. */
    public function exportDatabase(Request $request): void
    {
        $config = require dirname(__DIR__, 4) . '/config/database.php';
        $filename = 'family-finance-backup-' . date('Y-m-d-His') . '.sql';

        // Password passed via a temp defaults file, never on the command line
        // (arguments are visible to other processes via `ps`).
        $tmpCnf = tempnam(sys_get_temp_dir(), 'mysqlcnf');
        file_put_contents($tmpCnf, "[client]\nuser={$config['username']}\npassword={$config['password']}\nhost={$config['host']}\nport={$config['port']}\n");
        chmod($tmpCnf, 0600);

        $cmd = sprintf(
            'mysqldump --defaults-extra-file=%s --single-transaction %s 2>/dev/null',
            escapeshellarg($tmpCnf),
            escapeshellarg($config['database'])
        );

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $handle = popen($cmd, 'r');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
            }
            pclose($handle);
        }
        unlink($tmpCnf);

        AuditLogger::log((int) Session::get('user_id'), 'backup.export_database');
        exit;
    }

    /** All transactions (not soft-deleted), every column, as CSV — a full data export independent of the Reports date filters. */
    public function exportTransactions(Request $request): void
    {
        $stmt = Database::connection()->query(
            'SELECT t.*, c.name AS category_name, u.name AS created_by_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN users u ON u.id = t.created_by
             WHERE t.deleted_at IS NULL
             ORDER BY t.transaction_date, t.transaction_time'
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="all-transactions-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Date', 'Time', 'Type', 'Category', 'Description', 'Amount', 'Currency', 'Notes', 'Created By']);
        foreach ($stmt as $row) {
            fputcsv($out, [
                $row['id'], $row['transaction_date'], $row['transaction_time'], $row['type'],
                $row['category_name'], $row['description'], $row['amount'], $row['currency'],
                $row['notes'], $row['created_by_name'],
            ]);
        }
        fclose($out);

        AuditLogger::log((int) Session::get('user_id'), 'backup.export_transactions');
        exit;
    }

    public function clearCache(Request $request): void
    {
        SettingsCache::flush();
        AuditLogger::log((int) Session::get('user_id'), 'backup.clear_cache');
        Session::flash('success', 'Cache cleared.');
        Response::redirect('/backup');
    }

    public function toggleMaintenance(Request $request): void
    {
        $current = (bool) SettingsCache::get('general', 'maintenance_mode', false);
        SettingsCache::set('general', 'maintenance_mode', $current ? '0' : '1', 'bool', (int) Session::get('user_id'));
        AuditLogger::log((int) Session::get('user_id'), 'backup.toggle_maintenance', null, null, ['enabled' => !$current]);
        Session::flash('success', $current ? 'Maintenance mode disabled.' : 'Maintenance mode enabled.');
        Response::redirect('/backup');
    }
}
