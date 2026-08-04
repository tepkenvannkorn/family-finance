# Phase 5 — Transactions

## What's in this phase

**New models**: `Category`, `Transaction`, `Attachment` (`app/Models/`).

**Transactions module** (`app/Modules/Transactions`):
- `Repositories/TransactionRepository.php` — all filtering (date range, type, currency, category, user, keyword, amount range), sorting, and pagination lives here as one reusable query builder — used by the list page now and by Reports in Phase 7.
- `Policies/TransactionPolicy.php` — `canView`/`canEdit`/`canDelete`: admins can always act on any transaction; members are further gated by the `allow_edit_own`/`allow_delete_own` Settings toggles from Phase 2, and only ever on their own records.
- `Services/CurrencyConverter.php` — converts amounts for **display only**, using `bcmul`/`bcdiv` for exact decimal math (never floats with money). `rateAsOf($date)` supports historical reports later; stored transactions are never touched, per the Phase 1 decision.
- `Services/FileUploadService.php` — validates uploads by **actual file content** (`finfo` magic-byte detection), not filename extension or client-supplied MIME type; enforces the `max_upload_size_mb` setting; stores under `/storage/uploads/transactions/{id}/` with a random filename, outside the webroot.
- `Requests/TransactionRequest.php` — validation that respects the Settings toggles from Phase 2 (`require_category`, `require_notes_for_expense`, `allow_future_dates`, `allow_past_dates`).
- `Controllers/TransactionController.php` — full CRUD, plus:
  - `searchPartial()` — returns just the results-table HTML fragment, used for live search/filter without a page reload.
  - `downloadAttachment()` — the *only* way to reach an uploaded file; checks `TransactionPolicy::canView` first, so a guessed or shared attachment ID still can't be pulled by someone with no business seeing that transaction.
  - `deleteAttachment()` — gated by `canEdit`.
- Views: `index.php` (filter bar wired to the partial via Alpine.js `fetch`), `_rows.php` (the reusable table+pagination partial), `form.php` (create/edit, with attachment upload and list).

**Layout change**: Alpine.js added to the shared layout (`app/Views/layouts/app.php`) — needed for live search here, and will be reused for the drag/resize dashboard widgets in Phase 6.

**Dependency added**: `ext-bcmath` and `ext-fileinfo` in `composer.json`.

## How live search works
The filter bar is an Alpine component. Every filter change (debounced 400ms for text/number fields, immediate for selects/dates) calls `fetch('/transactions/search?...')`, which hits `searchPartial()` and gets back rendered HTML for just the table — not JSON the client has to template itself, and not a full page reload. Since the injected HTML has its own Alpine-bound pagination buttons, `Alpine.initTree()` re-binds them after each swap (a bare `innerHTML` replace doesn't auto-activate new directives).

## Ownership & security notes
- **Soft delete**: `transactions.deleted_at` — a deleted transaction is recoverable by an admin (schema supports it; a "trash / restore" admin view is a natural Phase 8 addition, not built yet).
- **Members only ever see their own transactions** — enforced server-side in `parseFilters()`, which overwrites any `user_id` a member might pass in the query string. This isn't just a UI restriction; the repository query itself is scoped.
- **Attachments are never a public URL.** They're served through `downloadAttachment()`, which re-checks the viewing policy on every request — so even if someone bookmarks or shares a link, it only works for people who could already see that transaction.
- **Upload validation trusts nothing from the client**: extension, filename, and the browser's reported Content-Type are all ignored in favor of magic-byte detection of the actual file content.

## Trying it
1. Visit `/transactions` — try the search box, type/currency/category filters, date range, and amount range; watch the table update without a page reload.
2. Add a transaction with an attached JPG/PNG/PDF receipt.
3. Log in as a non-admin member and confirm you only ever see your own transactions, and that edit/delete respect whatever `allow_edit_own`/`allow_delete_own` are currently set to (both default to enabled).
4. Try uploading a renamed `.exe` as `.jpg` — it's rejected (content doesn't match an allowed type).

## What's intentionally NOT in this phase
No dashboard totals/charts yet (Phase 6). No trash/restore UI for soft-deleted transactions yet. No exchange-rate management UI yet — `CurrencyConverter` reads whatever's in `exchange_rates` (seeded in Phase 2); the admin-facing rate management screen is Phase 8.

## Next
Phase 6 — Dashboard: totals (income/expense/balance), the required charts (income vs. expense, monthly/weekly trend, expense categories, currency breakdown, recent transactions), and drag/resize/save widget layout per user.
