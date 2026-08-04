# Phase 2 — Database (Migrations & Seeders)

## What's in this phase
- `app/Core/Database.php` — shared PDO connection (real prepared statements, no emulation).
- `app/Core/Migration.php` / `app/Core/Migrator.php` — a tiny hand-rolled migration runner. Tracks applied migrations in a `migrations` table, applies pending ones in filename order, supports rolling back the last batch.
- `database/migrations/001…012` — one file per table, covering every table from the Phase 1 schema: `roles`, `permissions`, `role_permissions`, `users`, `categories`, `transactions`, `attachments`, `exchange_rates`, `settings`, `dashboard_preferences`, `audit_logs`, `login_attempts`.
- `app/Core/Seeder.php` + `database/seeders/*` — default data: 2 roles, permission keys wired to roles, the income/expense categories from spec §10, ~60 default settings rows across every §11 sub-section, the initial admin account, and a starting USD→KHR rate.
- `database/migrate.php` / `database/seed.php` — CLI scripts to run the above.

## Setup

```bash
composer install
cp config/.env.example .env
# edit .env: set DB_* credentials and DEFAULT_ADMIN_EMAIL / DEFAULT_ADMIN_PASSWORD

php database/migrate.php   # creates every table
php database/seed.php      # loads default roles, permissions, categories, settings, admin user, exchange rate
```

Re-running `migrate.php` only applies new migration files — already-applied ones are skipped. Re-running `seed.php` is also safe: it either upserts (roles, permissions, categories, settings) or checks for existing data first (admin user, exchange rate), so it won't create duplicates.

To undo the most recent migration batch: `php database/migrate.php --rollback`.

## Design notes (tying back to Phase 1 decisions)
- **Exchange rates are append-only.** `exchange_rates` has no "current rate" row to overwrite — every rate change is a new row with its own timestamp, so historical reports can look up the rate that was in effect on a given date instead of today's rate.
- **Settings are grouped key/value**, typed via a `type` column (`string|int|bool|json`) so the future Settings UI can render and validate each field without hardcoding it, and new settings never require a migration.
- **Transactions use soft deletes** (`deleted_at`) — a "deleted" transaction is recoverable by an admin, not gone.
- **Attachments store a file path outside the webroot** — the migration doesn't enforce this (that's a Phase-5 upload-handler responsibility), but the column is sized and documented for it.
- **`recurring_rule` and `totp_secret` columns exist now but are unused** — cheap to add today, expensive to migrate in later, per the Phase 1 recommendation to pre-provision future-flagged features.

## What's intentionally NOT in this phase
No controllers, routes, or UI yet — this phase is schema and default data only. Authentication (Phase 3) is what will actually use the `users`, `login_attempts`, and `audit_logs` tables.

## Next
Phase 3 — Authentication: login, session management, CSRF middleware, rate limiting against `login_attempts`, and the Auth/Role middleware that every later module depends on.
