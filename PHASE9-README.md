# Phase 9 — Documentation

A full HTML documentation site lives in `/docs`, matching the folder structure from spec §17.7:

```
docs/
├── index.html
├── assets/{css,js,images}/
├── user-guide/       (login, dashboard, transactions, reports, settings)
├── admin-guide/       (installation, users, permissions, exchange-rates, maintenance)
├── developer/       (architecture, database, coding-standards, api, deployment)
├── faq.html
├── changelog.html
└── release-notes.html
```

**Features**: responsive sidebar navigation (shared across every page), a client-side search box that filters the
sidebar links, dark mode (persisted via `localStorage`), print-friendly CSS (sidebar/search/theme-toggle hidden on
print), breadcrumbs, code syntax-friendly `<pre><code>` blocks, and Mermaid.js diagrams for the architecture
flowchart and database ERD (in `developer/architecture.html` and `developer/database.html`).

Open `docs/index.html` directly in a browser, or host the whole `/docs` folder as a static site (it has no server
dependency at all).

## How it's built
Every page shares one HTML structure (sidebar + main content) generated from a single Python template script during
development, so the nav is guaranteed consistent across all 19 pages — but the output is plain static HTML with no
build step or server-side dependency for the person reading it.

## Content coverage
Every page listed in the spec's folder structure exists and has real, specific content (not lorem-ipsum placeholders)
reflecting how the app actually behaves as of Phase 8 — screenshots are placeholder boxes (`<img class="placeholder">`)
ready to be swapped for real screenshots once the UI is running in your environment.

## Keeping docs in sync going forward
Per spec §17.6, any future feature work should update the matching page(s) here in the same change — e.g. a new
Settings sub-section should get a line in `admin-guide/installation.html` or a new page under `user-guide/`, and any
schema change should update the ERD in `developer/database.html`.
