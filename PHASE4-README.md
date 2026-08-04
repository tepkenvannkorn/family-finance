# Phase 4 — User Management

## What's in this phase

**Model additions** (`app/Models/User.php`, `app/Models/Role.php`):
- `User::search()` — paginated name/email search for the admin list.
- `User::create()`, `updateProfile()`, `updateRole()`, `updatePassword()`, `setActive()`, `unlock()`, `delete()`.
- `User::countActiveAdmins()` and `hasTransactions()` back the safety checks below.
- `Role::all()` / `findById()` for role dropdowns.

**Users module** (`app/Modules/Users`):
- `Controllers/UserController.php` — admin list (search + pagination), create, edit, role change, activate/deactivate, unlock, permanent delete.
- `Controllers/ProfileController.php` — self-service: view/update own name+email, change own password.
- `Requests/UserRequest.php` — shared validation for create/edit (email format + uniqueness, valid role, password rules on create or when a new one is supplied on edit).
- `Services/PasswordPolicy.php` — enforces `password_min_length` / `password_require_complexity` from Settings, shared by admin-set and self-service passwords so both paths use the same rule.
- `Views/index.php`, `form.php` (shared create/edit), `profile.php`.
- `routes.php` — admin routes gated by `PermissionMiddleware::class, 'users.manage'`; profile routes gated by plain `AuthMiddleware`.

**Shared layout**: `app/Views/layouts/app.php` — nav bar (Dashboard / Users [admin only] / Profile / Log out), used by every authenticated view from here on.

**Bug fix carried over from Phase 3**: `Session::flash()`/`pull()` had a mismatch — `flash()` wrote to a separate `_flash` namespace that `pull()` never read from, so flashed errors would silently disappear. Fixed by making `flash()`/`getFlash()` thin aliases over the same `put()`/`pull()` store `pull()` already uses. No caller changes needed.

## Safety checks worth knowing about

- **Can't lock yourself out**: an admin can't deactivate, delete, or demote the *last* remaining active admin — checked in `toggleActive`, `destroy`, and `update` (role change).
- **Can't self-lock-out by accident**: an admin can't deactivate or delete their own currently-logged-in account from this UI.
- **Delete vs. deactivate**: permanent delete is blocked if the user still owns any transactions (would violate the `transactions.created_by` foreign key) — the error message tells the admin to deactivate instead. Deactivation is always available and is the recommended default for "removing" a family member who has transaction history.
- **No user enumeration on create/edit**: duplicate-email validation gives a clear "already in use" message here — that's fine, since this form is admin-only (not the public login form, where the same message would leak account existence).
- **Locked accounts**: admins see a "Locked" badge (from Phase 3's login rate limiting) and can unlock with one click, which also resets the failed-attempt counter.

## Trying it

1. Log in as the seeded default admin.
2. Visit `/users` — search, add a user, edit one, try deactivating/reactivating, try deleting a user with no transactions.
3. Visit `/profile` — update your name/email, change your password.
4. Try (as a test) deactivating or deleting the only admin account — both are blocked with an explanatory message.

## What's intentionally NOT in this phase
No self-registration (spec's `user_registration` feature flag is seeded `off` — admin creates all accounts). No 2FA UI yet (schema is ready per Phase 3 notes). No per-user audit log viewer yet — that's part of Phase 8 (Settings → Audit Logs).

## Next
Phase 5 — Transactions: income/expense CRUD, attachments, search/filter/pagination, ownership policy (edit/delete own vs. admin sees all).
