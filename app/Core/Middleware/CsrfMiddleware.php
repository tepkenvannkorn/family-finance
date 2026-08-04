<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Csrf;
use App\Core\Response;
use App\Core\Session;

final class CsrfMiddleware
{
    public function handle(callable $next): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $submitted = $_POST['_csrf'] ?? null;

            if (!Csrf::verify($submitted)) {
                Session::flash('error', 'Your session expired. Please try again.');
                Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
        }

        $next();
    }
}
