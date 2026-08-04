# VK Finance

A private, self-hosted family income & expense manager — PHP 8.3+, Apache, MySQL 8+, Tailwind CSS, Alpine.js, Chart.js.

Built incrementally across 11 phases, each documented separately:

| Phase | Readme | Covers |
|---|---|---|
| 1 | `phase1-architecture.md` (delivered separately) | Architecture, schema design, folder structure, roadmap |
| 2 | `database/README.md` | Migrations, seeders |
| 3 | `PHASE3-README.md` | Authentication, sessions, CSRF, RBAC middleware |
| 4 | `PHASE4-README.md` | User management, self-service profile |
| 5 | `PHASE5-README.md` | Transactions, attachments, live search |
| 6 | (this delivery) | Dashboard, charts, customizable widget layout |
| 7 | (this delivery) | Reports, CSV/Excel/PDF export |
| 8 | `PHASE8-README.md` | Settings, exchange rates, categories, audit logs, backup |
| 9 | `PHASE9-README.md` | `/docs` — full HTML documentation site |
| 10 | `PHASE10-README.md` | PHPUnit test suite |
| 11 | `PHASE11-README.md` | PWA finalization, cron, deployment checklist |

## Quickstart

```bash
composer install
cp config/.env.example .env    # fill in DB credentials + default admin email/password
php database/migrate.php
php database/seed.php
vendor/bin/phpunit             # optional, but should pass
```

Point your Apache vhost's `DocumentRoot` at `public/` (see `docs/developer/deployment.html` for a full example),
then log in with your `.env` admin credentials and change that password immediately.

## Full documentation
Open `docs/index.html` in a browser for the complete user guide, administrator guide, and developer documentation.

## Known scope boundaries (see individual phase readmes for detail)
- No public REST API, 2FA UI, recurring transactions, budgets, or multi-family support — all listed as future work
  in the original spec; schema groundwork (nullable columns, feature flags) is in place where it was cheap to add.
- No restore-from-backup UI or soft-deleted-transaction restore UI — both are manual/CLI steps today, documented
  in the Administrator Guide.
- PWA icons are referenced but not generated — add real ones before shipping.
