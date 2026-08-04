<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Core\AuditLogger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Role;
use App\Models\User;
use App\Modules\Users\Requests\UserRequest;

final class UserController
{
    private const PER_PAGE = 20;

    public function index(Request $request): void
    {
        $keyword = (string) $request->input('q', '');
        $page = max(1, (int) $request->input('page', 1));

        $result = User::search($keyword, $page, self::PER_PAGE);

        echo View::render('Users::index', [
            'users' => $result['users'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'keyword' => $keyword,
            'currentUserId' => (int) Session::get('user_id'),
            'success' => Session::pull('success'),
            'error' => Session::pull('error'),
        ]);
    }

    public function create(Request $request): void
    {
        echo View::render('Users::form', [
            'user' => null,
            'roles' => Role::all(),
            'errors' => Session::pull('form_errors', []),
            'old' => Session::pull('old', []),
        ]);
    }

    public function store(Request $request): void
    {
        $errors = UserRequest::validate($request, requirePassword: true);

        if (!empty($errors)) {
            Session::flash('form_errors', $errors);
            Session::flash('old', $request->all());
            Response::redirect('/users/create');
        }

        $user = User::create(
            (string) $request->input('name'),
            (string) $request->input('email'),
            (string) $request->input('password'),
            (int) $request->input('role_id')
        );

        AuditLogger::log((int) Session::get('user_id'), 'user.create', 'user', $user->id, ['email' => $user->email]);

        Session::flash('success', 'User created.');
        Response::redirect('/users');
    }

    public function edit(Request $request, string $id): void
    {
        $user = User::findById((int) $id);
        if (!$user) {
            Response::notFound();
        }

        echo View::render('Users::form', [
            'user' => $user,
            'roles' => Role::all(),
            'errors' => Session::pull('form_errors', []),
            'old' => Session::pull('old', []),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $user = User::findById((int) $id);
        if (!$user) {
            Response::notFound();
        }

        $errors = UserRequest::validate($request, requirePassword: false, editingUserId: $user->id);

        // Safety net: don't allow demoting/disabling the very last active admin —
        // that would lock every admin out of the system with no way back in.
        $newRoleId = (int) $request->input('role_id');
        $newRole = Role::findById($newRoleId);
        if ($user->roleName === 'admin' && $newRole?->name !== 'admin' && User::countActiveAdmins() <= 1) {
            $errors[] = "You cannot remove the last remaining administrator's admin role.";
        }

        if (!empty($errors)) {
            Session::flash('form_errors', $errors);
            Session::flash('old', $request->all());
            Response::redirect("/users/{$id}/edit");
        }

        $user->updateProfile((string) $request->input('name'), (string) $request->input('email'));
        $user->updateRole($newRoleId);

        $newPassword = (string) $request->input('password', '');
        if ($newPassword !== '') {
            $user->updatePassword($newPassword);
            AuditLogger::log((int) Session::get('user_id'), 'user.password_reset', 'user', $user->id);
        }

        AuditLogger::log((int) Session::get('user_id'), 'user.update', 'user', $user->id);

        Session::flash('success', 'User updated.');
        Response::redirect('/users');
    }

    public function toggleActive(Request $request, string $id): void
    {
        $user = User::findById((int) $id);
        if (!$user) {
            Response::notFound();
        }

        if ($user->id === (int) Session::get('user_id')) {
            Session::flash('error', 'You cannot deactivate your own account.');
            Response::redirect('/users');
        }

        if ($user->isActive && $user->roleName === 'admin' && User::countActiveAdmins() <= 1) {
            Session::flash('error', 'You cannot deactivate the last remaining administrator.');
            Response::redirect('/users');
        }

        $user->setActive(!$user->isActive);
        AuditLogger::log(
            (int) Session::get('user_id'),
            $user->isActive ? 'user.activate' : 'user.deactivate',
            'user',
            $user->id
        );

        Session::flash('success', $user->isActive ? 'User activated.' : 'User deactivated.');
        Response::redirect('/users');
    }

    public function unlock(Request $request, string $id): void
    {
        $user = User::findById((int) $id);
        if (!$user) {
            Response::notFound();
        }

        $user->unlock();
        AuditLogger::log((int) Session::get('user_id'), 'user.unlock', 'user', $user->id);

        Session::flash('success', 'Account unlocked.');
        Response::redirect('/users');
    }

    public function destroy(Request $request, string $id): void
    {
        $user = User::findById((int) $id);
        if (!$user) {
            Response::notFound();
        }

        if ($user->id === (int) Session::get('user_id')) {
            Session::flash('error', 'You cannot delete your own account.');
            Response::redirect('/users');
        }

        if ($user->roleName === 'admin' && User::countActiveAdmins() <= 1) {
            Session::flash('error', 'You cannot delete the last remaining administrator.');
            Response::redirect('/users');
        }

        try {
            $user->delete();
            AuditLogger::log((int) Session::get('user_id'), 'user.delete', 'user', $user->id, ['email' => $user->email]);
            Session::flash('success', 'User permanently deleted.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect('/users');
    }
}
