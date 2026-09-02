# OpesCare Finder — read this first

OpesCare Finder is a **standalone consumer health-finder for Cameroon**, bilingual
**EN/FR**: find a medicine in stock at a nearby pharmacy, find blood availability,
book an appointment with a provider, and browse the national facility directory.
Server-rendered **Laravel + Blade** with a REST API, on **PostgreSQL**.

> If anything here disagrees with the code, trust the code and fix this doc.

## Where it came from — and what that does NOT mean
- Extracted from the OpesCare platform monorepo (`opescare@ff6aafc8`) on
  **2026-09-02**. Local git baseline: `f6e3053` (full tree before pruning).
  Full record: `docs/EXTRACTION.md`.
- The platform's **sealed** Health-ID/FHIR files remain sealed **in the platform
  repo**. This repo has its **own lifecycle** — no files are sealed here, but the
  invariants below are non-negotiable product rules.
- **Fresh `APP_KEY` and JWT keypair** (`storage/keys/`) — never shared with the
  platform. Do not copy secrets in either direction.

## The four services (everything else is out of scope)
| Service | What it does |
|---|---|
| Drug finder | search live pharmacy stock for a medicine near the user |
| Blood finder | locate blood products by type at blood banks |
| Appointment booking | book a slot with a provider at a facility |
| Facility directory | browse/search the national facility register |

## Deliberately absent — do not re-add
Clinical records, encounters, Health-ID, FHIR R4 / HL7, consent-grant plumbing,
insurance, billing/subscriptions, inventory ops, telemedicine, DHIS2/PACS
integrations. They live in the **platform** (`opescare` repo), not here. A
feature request that needs them belongs in the platform, not in Finder.

## Data invariants
- **PostgreSQL** — never MySQL date functions (`DATE_FORMAT`); use
  `TO_CHAR(col, 'YYYY-MM')` in raw queries.
- **Typed backed enums** — never compare with `=== 'string'`; use `match()`,
  `->value`, or the enum's helper methods.
- **Availability provenance is an allow-list.** Finder results come only from
  live stock tables whose `source_system` is on the allow-list. **An empty
  finder is correct** — never publish seeded/demo/fixture stock as live
  availability, and never widen the allow-list to make a demo look fuller.
- **Facility identity comes from the authenticated session/middleware context**
  (`$request->attributes`) — never from request bodies, headers, query strings,
  or hard-coded fallbacks.

## Conventions — honor on every change
- **i18n (EN/FR):** every user-facing string via `__('namespace.key')` or the
  `@enum(...)` Blade directive — never hardcode. `lang/en/*` and `lang/fr/*`
  MUST stay 1:1: `php scripts/i18n-audit.php` must report 0 errors (it checks
  keys, encoding, and placeholders).
- **UI:** Lucide icons only (never emoji); brand color **#0F4C81** (never
  purple); currency **XAF/FCFA**.
- **Routing:** every feature needs a route + a nav link + a `route:list`
  check — never leave a page reachable by URL only.

## Verify before calling something "done"
```bash
php artisan test                    # green suite is the gate
php scripts/i18n-audit.php          # EN/FR parity — must be 0 errors
php artisan view:cache              # every Blade compiles
php artisan route:list              # routes are wired
```

## Running locally
See `README.md`. Dev DB is `opescare_finder`; tests run against a separate
database (`DB_DATABASE=<test-db> php artisan test ...` — the suite refreshes
the schema, so never point it at the dev DB). Deploy target:
**finder.opescare.cloud**.
