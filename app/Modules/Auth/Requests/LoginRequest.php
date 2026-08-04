<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use App\Core\Request;

final class LoginRequest
{
    /** @return string[] validation errors, empty if valid */
    public static function validate(Request $request): array
    {
        $errors = [];
        $email = $request->input('email', '');
        $password = $request->input('password', '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        return $errors;
    }
}
