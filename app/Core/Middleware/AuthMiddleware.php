<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(callable $next): void
    {
        if (!Session::get('user_id')) {
            Session::flash('error', 'Please log in to continue.');
            Response::redirect('/login');
        }

        $next();
    }
}
