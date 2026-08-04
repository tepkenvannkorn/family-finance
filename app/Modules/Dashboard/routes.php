<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Modules\Dashboard\Controllers\DashboardController;

/** @var App\Core\Router $router */

$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/data', [DashboardController::class, 'data'], [AuthMiddleware::class]);
$router->post('/dashboard/layout', [DashboardController::class, 'saveLayout'], [AuthMiddleware::class]);
$router->post('/dashboard/layout/reset', [DashboardController::class, 'resetLayout'], [AuthMiddleware::class, CsrfMiddleware::class]);
