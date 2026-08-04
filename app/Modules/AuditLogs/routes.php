<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Modules\AuditLogs\Controllers\AuditLogController;

/** @var App\Core\Router $router */

$router->get('/audit-logs', [AuditLogController::class, 'index'], [AuthMiddleware::class, [PermissionMiddleware::class, 'audit_logs.view']]);
