<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Modules\Users\Controllers\ProfileController;
use App\Modules\Users\Controllers\UserController;

/** @var App\Core\Router $router */

$manageUsers = [AuthMiddleware::class, [PermissionMiddleware::class, 'users.manage']];
$manageUsersWrite = [AuthMiddleware::class, [PermissionMiddleware::class, 'users.manage'], CsrfMiddleware::class];

$router->get('/users', [UserController::class, 'index'], $manageUsers);
$router->get('/users/create', [UserController::class, 'create'], $manageUsers);
$router->post('/users', [UserController::class, 'store'], $manageUsersWrite);
$router->get('/users/{id}/edit', [UserController::class, 'edit'], $manageUsers);
$router->post('/users/{id}', [UserController::class, 'update'], $manageUsersWrite);
$router->post('/users/{id}/toggle-active', [UserController::class, 'toggleActive'], $manageUsersWrite);
$router->post('/users/{id}/unlock', [UserController::class, 'unlock'], $manageUsersWrite);
$router->post('/users/{id}/delete', [UserController::class, 'destroy'], $manageUsersWrite);

$router->get('/profile', [ProfileController::class, 'show'], [AuthMiddleware::class]);
$router->post('/profile', [ProfileController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/profile/password', [ProfileController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);
