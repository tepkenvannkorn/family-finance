# Phase 10 — Testing

## Setup
```bash
composer install    # pulls in phpunit/phpunit (dev dependency, declared since Phase 2)
vendor/bin/phpunit
```

## What's covered

**Unit suite** (`tests/Unit`) — zero setup required, runs anywhere:
- `RouterTest` — static route matching, `{param}` extraction, and that a route only matches its registered HTTP method.
- `ViewTest` — output escaping (the core XSS defense) and the raw/escaped distinction.
- `CsrfTest` — token generation/reuse, and that verification accepts only the correct, session-bound token.
- `TransactionPolicyTest` — the DB-free branches of the authorization policy: admin bypass and non-owner denial for edit/delete/view.
- `CurrencyConverterTest` — the same-currency passthrough path (no rate lookup needed).

**Feature suite** (`tests/Feature`) — needs a real test database; each test skips itself cleanly (not a failure) if
`DB_DATABASE` isn't set, so `vendor/bin/phpunit` is safe to run even with no database configured at all. See
`tests/Feature/README.md` for setup. Currently covers cross-currency conversion against a seeded exchange rate.

## Why the split
Several core classes (`SettingsCache`, and anything that reads Settings through it — `PasswordPolicy`,
`TransactionRequest`, the "owner + allow_edit_own" branch of `TransactionPolicy`, etc.) read from the database by
design, so their **full** behavior can only be verified against a real database. Rather than mocking the database
everywhere (which would test the mock more than the code) or skipping DB-touching logic entirely, the suite is split
honestly: pure logic is Unit-tested with no setup, and DB-dependent logic is Feature-tested against a real (test)
database, documented in `tests/Feature/README.md`.

## Natural next additions (not yet written, called out for whoever continues this)
- Feature tests for the Settings-gated `TransactionPolicy` branches.
- Feature tests for `PasswordPolicy` and `TransactionRequest` validation against real Settings rows.
- An HTTP-level smoke test (login → create transaction → see it reflected on the dashboard) — needs either a test
  HTTP client wired to the front controller or a curl-based test against a running dev server.
- A PHPStan or Psalm static-analysis pass, referenced in `developer/coding-standards.html` as a natural addition but not configured here.
