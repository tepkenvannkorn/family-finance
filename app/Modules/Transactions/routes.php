<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Modules\Transactions\Controllers\TransactionController;

/** @var App\Core\Router $router */

$auth = [AuthMiddleware::class];
$authWrite = [AuthMiddleware::class, CsrfMiddleware::class];

$router->get('/transactions', [TransactionController::class, 'index'], $auth);
$router->get('/transactions/search', [TransactionController::class, 'searchPartial'], $auth);
$router->get('/transactions/create', [TransactionController::class, 'create'], $auth);
$router->post('/transactions', [TransactionController::class, 'store'], $authWrite);
$router->get('/transactions/{id}/edit', [TransactionController::class, 'edit'], $auth);
$router->post('/transactions/{id}', [TransactionController::class, 'update'], $authWrite);
$router->post('/transactions/{id}/delete', [TransactionController::class, 'destroy'], $authWrite);
$router->get('/transactions/{id}/attachments/{attachmentId}', [TransactionController::class, 'downloadAttachment'], $auth);
$router->post('/transactions/{id}/attachments/{attachmentId}/delete', [TransactionController::class, 'deleteAttachment'], $authWrite);
