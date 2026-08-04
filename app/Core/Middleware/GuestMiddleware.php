<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Response;
use App\Core\Session;

final class GuestMiddleware
{
    public function handle(callable $next): void
    {
        if (Session::get('user_id')) {
            Response::redirect('/dashboard');
        }

        $next();
    }
}
