# Phase 8 — Settings

## What's in this phase
- **Settings module** (`app/Modules/Settings`) — one generic controller (`SettingsController`) drives all 9 groups from spec §11 (general, currency, transaction, dashboard, user, appearance, notification, security, feature_flags) off a single schema array (`SettingsController::SCHEMA`) — adding a new setting is one array entry, not a new form/route/controller.
- **Exchange rate management** (`ExchangeRateController` + `Services/ExchangeRateService.php`) — manual entry, "fetch now" from a public API, and a plausibility check that rejects a fetched rate swinging more than 15% from the last known rate (protects against a corrupted/tampered API response silently breaking every converted figure in the app). Falls back to the last known-good rate on fetch failure if `use_manual_when_api_unavailable` is on.
- **Categories module** — admin add/deactivate income & expense categories.
- **Audit Logs module** — filterable, paginated viewer over the `audit_logs` table every prior module has been writing to since Phase 3.
- **Backup & Maintenance module** — full database export via `mysqldump` (credentials passed through a temp defaults file, never as a CLI argument, since arguments are visible via `ps`), a full transactions CSV export, settings-cache clearing, and a maintenance-mode toggle.
- **Maintenance mode wiring**: `public/index.php` now checks `general.maintenance_mode` right after session/auth resume — non-admins get a maintenance page (even for `/login`), admins can still get in to turn it back off.
- **New feature-flag middleware** (`FeatureFlagMiddleware`) — applied to Reports as an example; any other module can be gated the same way by adding `[FeatureFlagMiddleware::class, 'flag_key']` to its route middleware array.

## Trying it
1. `/settings` — switch between tabs, change something, save, confirm it takes effect (e.g. toggle `allow_future_dates` off, then try adding a future-dated transaction).
2. `/settings/exchange-rates` — set a rate manually, or try "fetch now" (needs outbound internet access from the server).
3. `/categories` — add a new expense category, use it on a transaction.
4. `/audit-logs` — confirm actions from earlier phases (logins, transaction edits, user changes) show up.
5. `/backup` — toggle maintenance mode, confirm a non-admin sees the maintenance page and an admin doesn't.

## What's intentionally NOT in this phase
No restore-from-backup UI (import is manual: `mysql < backup.sql`) — automating restore safely (validating the dump, taking the app offline, etc.) is more than a household app needs; documented as a manual admin step instead in Phase 11's deployment guide. No trash/restore UI for soft-deleted transactions yet — schema supports it, but building the UI wasn't in this pass's scope.

## Next
Phase 9 — Documentation, Phase 10 — Testing, Phase 11 — Deployment (all three requested together, no further pause between them).
