<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\SettingsCache;
use App\Core\View;
use App\Models\User;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Services\Authenticator;
use App\Modules\Auth\Services\RateLimiter;

final class AuthController
{
    public function showLogin(Request $request): void
    {
        echo View::render('Auth::login', [
            'errors' => Session::pull('login_errors', []),
            'old_email' => Session::pull('old_email', ''),
        ], layout: null); // login page is standalone, not wrapped in the authenticated app shell
    }

    public function login(Request $request): void
    {
        $errors = LoginRequest::validate($request);
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $remember = (bool) $request->input('remember', false);
        $ip = $request->ip();

        $rateLimiter = new RateLimiter();

        if (empty($errors) && $rateLimiter->tooManyAttempts($email, $ip)) {
            $errors[] = 'Too many failed attempts. Please wait before trying again.';
        }

        $user = empty($errors) ? User::findByEmail($email) : null;

        if (empty($errors) && (!$user || !$user->isActive)) {
            $errors[] = 'Invalid email or password.'; // no user enumeration: same message either way
        }

        if (empty($errors) && $user->isLocked()) {
            $errors[] = 'This account is temporarily locked due to repeated failed attempts. Please try again later.';
        }

        if (empty($errors) && !$user->verifyPassword($password)) {
            $user->recordFailedLogin(
                maxAttempts: (int) SettingsCache::get('user', 'max_login_attempts', 5),
                lockMinutes: (int) SettingsCache::get('user', 'account_lock_minutes', 15)
            );
            $rateLimiter->recordAttempt($email, $ip, success: false);
            $errors[] = 'Invalid email or password.';
        }

        if (!empty($errors)) {
            Session::flash('login_errors', $errors);
            Session::flash('old_email', $email);
            Response::redirect('/login');
        }

        $rateLimiter->recordAttempt($email, $ip, success: true);
        (new Authenticator())->login($user, $remember);

        Response::redirect('/dashboard');
    }

    public function logout(Request $request): void
    {
        (new Authenticator())->logout();
        Response::redirect('/login');
    }
}
