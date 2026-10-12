# OpesCare Finder — read this first

OpesCare Finder is a **standalone consumer health-finder for Cameroon**, bilingual
**EN/FR**: find a medicine in stock at a nearby pharmacy, find blood availability,
book an appointment with a provider, and browse the national facility directory.
Server-rendered **Laravel + Blade** with a REST API, on **PostgreSQL**.

> If anything here disagrees with the code, trust the code and fix this doc.

## Picking this project up — start here
This folder is the whole project. Open a session **in `C:\laragon\www\opescare-finder`**,
not in the platform monorepo.

| What you need | Where it is |
|---|---|
| **State of the project, owner actions, open decisions** | `docs/HANDOVER.md` (and `docs/HANDOVER.html`, the same thing styled) |
| **What these dashboards are and are NOT** | `docs/DASHBOARD_SCOPE.md` — the yardstick every audit used |
| **Per-facility-type readiness** | `docs/FACILITY_READINESS.md`, `docs/audits/*_DASHBOARD_AUDIT_2.md` |
| **Security status and what is still owed** | `docs/SECURITY_REMEDIATION.md` |
| **Demo logins for every facility type** | `docs/DEMO_ACCOUNTS.md` |
| **Decisions and traps across sessions** | project memory: `~/.claude/projects/C--laragon-www-opescare-finder/memory/MEMORY.md` (47 facts, split out of the monorepo's memory on 2026-10-12) |
| **How to deploy** | `docs/HANDOVER.md` → "How to ship safely"; there is no CI and no git remote |
| **The phone app** | sibling folder `C:\laragon\www\opescare-finder-mobile` — add it to a session with `/add-dir` when you need it; it ships through EAS, not with this repo |

Two traps that have cost real time here: git history was rewritten on 2026-10-12,
so **commit hashes quoted in older docs no longer resolve — find commits by their
message**; and after any deploy the config cache must be rebuilt or prod serves the
old config.

## Where it came from — and what that does NOT mean
- Extracted from the OpesCare platform monorepo (`opescare@ff6aafc8`) on
  **2026-09-02**. Local git baseline: `f6e3053` (full tree before pruning).
  Full record: `docs/EXTRACTION.md`.
- The platform's **sealed** Health-ID/FHIR files remain sealed **in the platform
  repo**. This repo has its **own lifecycle** — no files are sealed here, but the
  invariants below are non-negotiable product rules.
- **Fresh `APP_KEY` and JWT keypair** (`storage/keys/`) — never shared with the
  platform. Do not copy secrets in either direction.

## The three checkouts on this machine
Exactly three OpesCare working folders exist; any other `opescare*` folder is cruft.

| Folder | What it is |
|---|---|
| `C:\laragon\www\opescare-finder` | **this** repo — the Finder web app (finder.opescare.cloud) |
| `C:\laragon\www\opescare-finder-mobile` | the Finder's Expo app, shipped via EAS |
| `C:\laragon\www\opescare` | the platform monorepo (opescare.cloud), `main` |

Secrets never cross between this repo and the platform (see above). `opescare-legacy`
was retired on 2026-10-12 — it was a duplicate platform checkout with no unique code.

## The four services (everything else is out of scope)
| Service | What it does |
|---|---|
| Drug finder | search live pharmacy stock for a medicine near the user |
| Blood finder | locate blood products by type at blood banks |
| Appointment booking | book a slot with a provider at a facility |
| Facility directory | browse/search the national facility register |

## Deliberately absent — do not re-add
*(Reviewed 2026-09-05. The list below is what remains out of scope; the
paragraph after it records what has since been brought IN, deliberately and at
the owner's direction. Per the root brief: where a doc disagrees with the code,
trust the code and fix the doc.)*

Inventory ops, DHIS2/PACS integrations, insurance claims and subscription
billing. They live in the **platform** (`opescare` repo), not here. A feature
request that needs them belongs in the platform, not in Finder.

## No longer absent — brought in on purpose
This fork has grown past the four services above, each time by an explicit
decision. Treat these as first-class parts of Finder, not as scope creep to be
reverted:

| Area | State |
|---|---|
| Health ID, patient identity | Live. Registration, recovery, QR, `PatientIdentityService`. Duplicate detection is centralised in `Patient::findExistingIdentity()` — see its docblock before touching any patient-creation path. |
| Clinical records, encounters | Live. Longitudinal record, prescriptions, labs, vitals, referrals. |
| Consent-grant plumbing | Live. Request → approve → scoped read, break-glass, access log. `RequireConsentGrant` gates every patient endpoint. |
| Clinician portal | Live. 39 mobile endpoints, facility switching, rosters. |
| One-off payments | Live. MTN MoMo / Orange Money against an invoice, `simulated` on demo hosts. This is *pay a bill for care*, NOT insurance or subscriptions — those stay out. |
| Telemedicine | Still thin: book-to-consult only, no in-app video. |
| FHIR R4 / HL7 | Still absent. Interop stays in the platform. |

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
