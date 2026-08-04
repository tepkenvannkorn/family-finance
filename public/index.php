<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\SettingsCache;
use App\Core\View;
use App\Modules\Auth\Services\Authenticator;

$root = require dirname(__DIR__) . '/app/Core/bootstrap.php';

// Security headers (spec §11.9 / §6 mitigations) — set on every response.
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'none'");
header('Referrer-Policy: strict-origin-when-cross-origin');

Session::start();

// Resume a session from the "remember me" cookie if present, before routing.
(new Authenticator())->attemptCookieLogin();

// Maintenance mode (spec §11.8): admins can still get in to turn it back off;
// everyone else — including the login page — sees the maintenance page.
$isAdmin = Session::get('role_name') === 'admin';
if (!$isAdmin && (bool) SettingsCache::get('general', 'maintenance_mode', false)) {
    http_response_code(503);
    echo View::render('Backup::maintenance', [], layout: null);
    exit;
}

$router = new Router();

foreach (glob($root . '/app/Modules/*/routes.php') as $routeFile) {
    require $routeFile;
}

$router->dispatch(new Request());
