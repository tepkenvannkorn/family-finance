# Phase 11 — Deployment

## PWA finalization
- `public/manifest.json` — app name, theme color, start URL (`/dashboard`), icon references. **The two icon PNGs
  (`assets/icons/icon-192.png`, `icon-512.png`) are referenced but not generated** — add real icons before relying
  on "Add to Home Screen" showing your branding; without them, most browsers fall back to a generic icon rather than failing.
- `public/service-worker.js` — caches the app shell for basic offline resilience. Deliberately **does not** cache
  `/dashboard/data` or any POST request — this is read-only offline support (Phase 1's scoped-down PWA decision),
  not offline transaction entry with background sync.
- Registered from `app/Views/layouts/app.php`, so it's active on every authenticated page.

## Scheduled jobs
`cron/sync-exchange-rate.php` — wire to cron for the "daily"/"weekly" exchange-rate sync options (Settings →
Currency). Example crontab entries are in the script's header comment and in `docs/developer/deployment.html`.
The "every login" sync option doesn't need cron — hook it into `Authenticator::login()` if you want it: check
`sync_interval === 'every_login'` and call `ExchangeRateService::fetchFromApi()` there. Not wired up by default,
since a login-time external HTTP call adds latency to every sign-in — worth a deliberate choice, not a default.

## Deployment checklist
See `docs/developer/deployment.html` for the full guide (vhost config, file permissions, pre-launch checklist).
Summary:
1. `composer install`
2. `.env` configured, `APP_DEBUG=false`
3. Apache `DocumentRoot` → `public/`
4. `php database/migrate.php && php database/seed.php`
5. Change the default admin password immediately after first login
6. Confirm `.env` and `storage/` are NOT reachable by URL
7. Take a real backup via `/backup` and test-restore it once
8. `vendor/bin/phpunit` passes

## What's intentionally NOT in this phase
No CI/CD pipeline config (GitHub Actions, etc.) — not requested and there's no repository host specified. No
containerization (Docker) — the spec specifies a traditional PHP/Apache/MySQL stack throughout, so that's what's
documented; containerizing it is a mechanical follow-up if wanted, not a design change.
