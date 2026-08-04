# Phase 3 — Authentication

## What's in this phase

**Core framework pieces** (used by every future module, not just Auth):
- `app/Core/Request.php` / `Response.php` — thin HTTP wrappers.
- `app/Core/Router.php` — pattern-matching router with a middleware pipeline. Modules register routes via their own `routes.php`, auto-loaded by the front controller — add/remove a module by adding/removing that one file (spec §19).
- `app/Core/Session.php` — hardened session handling: `HttpOnly`/`Secure`/`SameSite=Lax` cookies, ID regeneration on login, an absolute session timeout pulled from Settings, and a User-Agent fingerprint check that force-logs-out a session if the UA suddenly changes mid-session.
- `app/Core/Csrf.php` — one token per session, verified via `hash_equals`.
- `app/Core/SettingsCache.php` — reads the `settings` table seeded in Phase 2, cast by type, cached in-memory + to a file for 5 minutes so we're not hitting the DB on every request.
- `app/Core/View.php` — minimal template renderer; output is escaped by default via `View::e()`, raw HTML only via an explicit `View::raw()` call.
- `app/Core/AuditLogger.php` — writes to `audit_logs`; never throws (a logging failure must never break the action it's logging).
- `app/Core/Middleware/` — `AuthMiddleware` (must be logged in), `GuestMiddleware` (must NOT be logged in, for `/login`), `CsrfMiddleware`, `PermissionMiddleware` (parameterized — checks a specific permission key against the session's role).

**Auth module** (`app/Modules/Auth`):
- `Services/Authenticator.php` — login/logout, session regeneration, and remember-me cookie issuing/verification (selector = user id, validator = random token; only the SHA-256 hash of the token is stored, compared with `hash_equals`).
- `Services/RateLimiter.php` — checks `login_attempts` by email AND by IP independently before allowing an attempt.
- `Requests/LoginRequest.php` — input validation.
- `Controllers/AuthController.php` — login form, login submit, logout.
- `Views/login.php` — the login page.
- `routes.php` — `GET/POST /login`, `POST /logout`.

**Front controller**: `public/index.php` sets security headers, starts the session, attempts a remember-me cookie login, loads every module's routes, dispatches. `public/.htaccess` routes all requests through it; a root `.htaccess` denies all access as a defense-in-depth fallback if the Apache vhost is ever pointed at the project root instead of `public/`.

A placeholder `Dashboard` module (`GET /dashboard`) exists only so the login flow has somewhere real to land — it's replaced by the full widget dashboard in Phase 6.

## How the login flow works end to end

1. `GET /login` → `GuestMiddleware` (bounces already-logged-in users to `/dashboard`) → renders the form with a CSRF token.
2. `POST /login` → `GuestMiddleware` + `CsrfMiddleware` → `LoginRequest` validates input → `RateLimiter` checks recent failures by email/IP → `User::findByEmail` → account-active / account-locked / password checks, in that order, all funneling into the **same** "Invalid email or password" message so failed attempts don't reveal whether the email exists.
3. On success: `Authenticator::login()` regenerates the session ID (session-fixation protection), stores `user_id`/`role_id`/`role_name` in session, resets the failed-attempt counter, optionally issues the remember-me cookie, and writes an `auth.login` audit log entry.
4. On failure: the failed attempt is recorded on both the `users` row (for account locking) and `login_attempts` (for rate limiting), and the user is redirected back with a flashed error.
5. `POST /logout` → requires auth + CSRF → clears the remember-me cookie and token, logs `auth.logout`, destroys the session.

## Setup / trying it

Points your Apache vhost's `DocumentRoot` at `public/`. Example vhost snippet:

```apache
<VirtualHost *:443>
    DocumentRoot /path/to/family-finance-app/public
    <Directory /path/to/family-finance-app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Then, having already run Phase 2's `migrate.php` and `seed.php`:

1. Visit `/login`.
2. Sign in with the `DEFAULT_ADMIN_EMAIL` / `DEFAULT_ADMIN_PASSWORD` from your `.env`.
3. You'll land on the placeholder dashboard showing your name and role, with a working logout button.

## Design notes tying back to the spec

- **RBAC is permission-key-based, not role-name-based** (`PermissionMiddleware::class, 'users.manage'` on a route) — matches Phase 2's `PermissionSeeder`, and means adding a new role later is a data change, not a code change.
- **No user enumeration**: wrong email and wrong password produce an identical error message and identical timing-relevant code path.
- **Account lockout is per-user** (`users.locked_until`), separate from IP/email rate limiting (`login_attempts`) — the two mechanisms cover different attack shapes (one attacker hammering one account vs. one attacker spraying many accounts).
- **2FA is not implemented yet** — `users.totp_secret` (Phase 2) and `settings.user.two_factor_enabled` (seeded, currently `0`) are already in place so turning it on later doesn't require a migration.

## What's intentionally NOT in this phase
No user management UI (create/edit/reset password for other users) — that's Phase 4. No permission-editor UI — permissions are seeded, not admin-editable yet, per the Phase 1 decision to keep RBAC simple for a household app.

## Next
Phase 4 — User Management: admin CRUD for users, role assignment, password resets, self-service profile/password change.
