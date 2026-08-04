# Feature tests

These tests exercise code paths that read from the database (via
`App\Core\SettingsCache` or model queries) — things the Unit suite
deliberately avoids so it can run anywhere with zero setup.

## Running them

1. Create a separate test database (never point this at production data):
   ```sql
   CREATE DATABASE family_finance_test;
   ```
2. Copy `.env` to `.env.testing` and point `DB_DATABASE` at the test database.
3. Migrate and seed it:
   ```bash
   php database/migrate.php   # honors .env.testing if APP_ENV=testing, otherwise edit .env temporarily
   php database/seed.php
   ```
4. Run the suite:
   ```bash
   DB_DATABASE=family_finance_test vendor/bin/phpunit
   ```

If `DB_DATABASE` isn't set, every Feature test skips itself (with a clear
reason) rather than failing — so `vendor/bin/phpunit` is always safe to
run, in any environment, even before a test database exists.

## What's covered so far
- `CurrencyConversionFeatureTest` — USD↔KHR conversion against the seeded exchange rate.

## Natural next additions (not yet written)
- `TransactionPolicyFeatureTest` — the `allow_edit_own`/`allow_delete_own` Settings-gated branches that `tests/Unit/TransactionPolicyTest.php` explicitly calls out as out of scope for the DB-free suite.
- A full HTTP-level test (login → create transaction → see it on the dashboard) using a test HTTP client or a curl-based smoke test against a running dev server.
