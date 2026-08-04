<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Modules\Categories\Controllers\CategoryController;

/** @var App\Core\Router $router */

$manage = [AuthMiddleware::class, [PermissionMiddleware::class, 'categories.manage']];
$manageWrite = [AuthMiddleware::class, [PermissionMiddleware::class, 'categories.manage'], CsrfMiddleware::class];

$router->get('/categories', [CategoryController::class, 'index'], $manage);
$router->post('/categories', [CategoryController::class, 'store'], $manageWrite);
$router->post('/categories/{id}/toggle-active', [CategoryController::class, 'toggleActive'], $manageWrite);
