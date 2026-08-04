<?php

declare(strict_types=1);

use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Modules\Settings\Controllers\ExchangeRateController;
use App\Modules\Settings\Controllers\SettingsController;

/** @var App\Core\Router $router */

$manage = [AuthMiddleware::class, [PermissionMiddleware::class, 'settings.manage']];
$manageWrite = [AuthMiddleware::class, [PermissionMiddleware::class, 'settings.manage'], CsrfMiddleware::class];

$router->get('/settings', [SettingsController::class, 'index'], $manage);
$router->post('/settings/{group}', [SettingsController::class, 'update'], $manageWrite);

$exchangeManage = [AuthMiddleware::class, [PermissionMiddleware::class, 'exchange_rates.manage']];
$exchangeWrite = [AuthMiddleware::class, [PermissionMiddleware::class, 'exchange_rates.manage'], CsrfMiddleware::class];

$router->get('/settings/exchange-rates', [ExchangeRateController::class, 'index'], $exchangeManage);
$router->post('/settings/exchange-rates/manual', [ExchangeRateController::class, 'setManual'], $exchangeWrite);
$router->post('/settings/exchange-rates/fetch', [ExchangeRateController::class, 'fetchNow'], $exchangeWrite);
