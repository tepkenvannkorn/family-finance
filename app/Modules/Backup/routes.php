<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Modules\Backup\Controllers\BackupController;

/** @var App\Core\Router $router */

$manage = [AuthMiddleware::class, [PermissionMiddleware::class, 'settings.manage']];
$manageWrite = [AuthMiddleware::class, [PermissionMiddleware::class, 'settings.manage'], CsrfMiddleware::class];

$router->get('/backup', [BackupController::class, 'index'], $manage);
$router->get('/backup/export/database', [BackupController::class, 'exportDatabase'], $manage);
$router->get('/backup/export/transactions', [BackupController::class, 'exportTransactions'], $manage);
$router->post('/backup/clear-cache', [BackupController::class, 'clearCache'], $manageWrite);
$router->post('/backup/toggle-maintenance', [BackupController::class, 'toggleMaintenance'], $manageWrite);
