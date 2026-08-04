<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Core\AuditLogger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;
use App\Modules\Users\Services\PasswordPolicy;

final class ProfileController
{
    public function show(Request $request): void
    {
        $user = User::findById((int) Session::get('user_id'));

        echo View::render('Users::profile', [
            'user' => $user,
            'errors' => Session::pull('form_errors', []),
            'success' => Session::pull('success'),
        ]);
    }

    public function update(Request $request): void
    {
        $user = User::findById((int) Session::get('user_id'));
        $name = (string) $request->input('name', '');
        $email = (string) $request->input('email', '');

        $errors = [];
        if (trim($name) === '') {
            $errors[] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        } elseif (User::emailExists($email, $user->id)) {
            $errors[] = 'That email is already in use by another account.';
        }

        if (!empty($errors)) {
            Session::flash('form_errors', $errors);
            Response::redirect('/profile');
        }

        $user->updateProfile($name, $email);
        AuditLogger::log($user->id, 'user.profile_update', 'user', $user->id);

        Session::flash('success', 'Profile updated.');
        Response::redirect('/profile');
    }

    public function changePassword(Request $request): void
    {
        if (!\App\Core\SettingsCache::get('user', 'allow_self_password_reset', true)) {
            Response::forbidden();
        }

        $user = User::findById((int) Session::get('user_id'));
        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('confirm_password', '');

        $errors = [];

        if (!$user->verifyPassword($current)) {
            $errors[] = 'Your current password is incorrect.';
        }

        if ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (empty($errors)) {
            $errors = PasswordPolicy::validate($new);
        }

        if (!empty($errors)) {
            Session::flash('form_errors', $errors);
            Response::redirect('/profile');
        }

        $user->updatePassword($new);
        AuditLogger::log($user->id, 'user.password_change', 'user', $user->id);

        Session::flash('success', 'Password changed.');
        Response::redirect('/profile');
    }
}
