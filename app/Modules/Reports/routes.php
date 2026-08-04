<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\FeatureFlagMiddleware;
use App\Modules\Reports\Controllers\ReportController;

/** @var App\Core\Router $router */

$reports = [AuthMiddleware::class, [FeatureFlagMiddleware::class, 'reports']];

$router->get('/reports', [ReportController::class, 'index'], $reports);
$router->get('/reports/export/csv', [ReportController::class, 'exportCsv'], $reports);
$router->get('/reports/export/excel', [ReportController::class, 'exportExcel'], $reports);
$router->get('/reports/export/pdf', [ReportController::class, 'exportPdf'], $reports);
