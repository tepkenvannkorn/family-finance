<?php

declare(strict_types=1);

namespace App\Modules\Users\Requests;

use App\Core\Request;
use App\Models\Role;
use App\Models\User;
use App\Modules\Users\Services\PasswordPolicy;

final class UserRequest
{
    /**
     * @param bool $requirePassword true for create (password mandatory), false for edit (blank = leave unchanged)
     * @return string[] validation errors
     */
    public static function validate(Request $request, bool $requirePassword, ?int $editingUserId = null): array
    {
        $errors = [];

        $name = (string) $request->input('name', '');
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $roleId = (int) $request->input('role_id', 0);

        if (trim($name) === '') {
            $errors[] = 'Name is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif (User::emailExists($email, $editingUserId)) {
            $errors[] = 'That email is already in use by another account.';
        }

        if (Role::findById($roleId) === null) {
            $errors[] = 'Please select a valid role.';
        }

        if ($requirePassword || $password !== '') {
            if ($requirePassword && $password === '') {
                $errors[] = 'A password is required for new accounts.';
            } elseif ($password !== '') {
                $errors = array_merge($errors, PasswordPolicy::validate($password));
            }
        }

        return $errors;
    }
}
