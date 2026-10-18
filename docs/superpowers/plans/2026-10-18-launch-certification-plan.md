# Launch certification plan — OpesCare Finder (2026-10-18 → public launch Tuesday 2026-10-20)

Owner: OPES Health Systems. Goal: a platform an investor's technical due-diligence
team (YC, TechCrunch-stage funds) can audit without finding a fault we did not
already know about and document. Certification is evidence-based: every claim in
the final certificate links to a test, a scan output or a query result.

## Ground truth at plan time
| Measure | Value |
|---|---|
| PHP files under `app/` | 1,274 |
| Migrations / Postgres tables | 261 / 551 |
| Routes (all / GET) | 945 / 495 |
| Blade views | 451 |
| Test files / tests | 341 / ~2,450 |
| Session, cache, queue drivers | **all `database`** |
| Hosting | one VPS (finder.opescare.cloud), PHP 8.3-FPM, Postgres on the same host |

## Phase 0 — Architecture & schema alignment (today, first)
Check the code against `CLAUDE.md` scope and invariants before polishing anything.
Findings already visible:
1. **Scope drift.** Models for insurance claims (`InsuranceClaim`, `Claim*`,
   `InsurancePlan`, `PatientInsurancePolicy`), FHIR mapping (`Fhir*`), subscription
   billing (`Subscription*`, `OrganizationSubscription`) and a generic
   `Inventory*` module exist, although CLAUDE.md lists insurance, FHIR and
   subscription billing as out of scope (subscriptions were partly brought in as
   patient fee plans). Action: classify each as *live & intended* / *dormant
   (no route, no writer)* / *reachable but out of scope*; dormant → document as
   dormant; reachable & out of scope → owner decision before launch, never silent.
2. **Two facility registers** (`care_facilities` vs `facilities`) — documented;
   verify every desk reads through the one join.
3. **Invariants**: facility from context, enum typing, provenance allow-list,
   PostgreSQL-only SQL — re-run the static checks.
Deliverable: `docs/architecture/ARCHITECTURE.md` (C4 context/container/component,
data flow for prescription → dispense, lab order → result, referral, consent) and
`docs/architecture/SCHEMA.md` (ERD per domain, generated from the live schema).

## Phase 1 — Finish the desk UI to the owner's references (in flight)
Agents A–E (design system + shell, pharmacy dashboard, pharmacy stock, lab/imaging,
landing). Gate: screenshots side-by-side with the three reference images, flat
cards, owner sign-off.

## Phase 2 — Functional audit: every page, form, action, document
Automated, so it is repeatable and evidence-producing:
- **Route crawler test** (`tests/Feature/Certification/EveryPageRendersTest.php`):
  for each of the 495 GET routes × each role that may open it (from
  `config/staff_capabilities.php` + portal buckets) seed a fixture, request it,
  assert no 5xx, no missing translation key in the HTML, no `href` to a route that
  404s, every page reachable from a nav link (extends the existing nav tests).
- **Form/action matrix**: every POST/PUT/PATCH/DELETE route → a test that the
  happy path succeeds for an allowed role, is refused (403) for a disallowed role,
  404s cross-facility, rejects invalid input (422), and is CSRF-protected.
- **Document templates**: render every secure-sheet PDF and email template with
  fixture data; assert no exception, QR present, EN and FR.
- **Workflows end-to-end** (feature tests, then a browser walk on the demo
  tenants): patient sign-up → Health ID → booking → check-in → triage → consult →
  prescription → pharmacy dispense → payment; lab order → collect → result →
  validate → release → patient; imaging intake → report → distribute; referral
  out → accept → complete; consent request → grant → scoped read → revoke;
  break-glass; MPI merge/unmerge; facility onboarding & staff invite.
- Mobile app parity: every API endpoint the Expo app calls exists and is tested.
Output: `docs/certification/FUNCTIONAL_MATRIX.md` (generated).

## Phase 3 — Database audit, table by table
- Schema lint for all 551 tables: PK present; FKs on every `*_id`; indexes on FKs
  and hot filters; `NOT NULL` where the model requires it; enum columns have CHECK
  constraints or a typed cast; timestamps; soft-delete consistency; PHI columns
  encrypted (DOB done — list the rest: phone, national ID, address, notes).
- Dead tables: no model, no migration writer, no reads → document or drop.
- Data audit on prod (read-only queries): orphan rows per FK, enum values outside
  the allowed set, duplicate identities, demo rows leaking into non-demo views,
  stock rows outside the provenance allow-list.
Output: `docs/certification/DATABASE_AUDIT.md`.

## Phase 4 — Security (OWASP ASVS L2, health-data grade)
Authorization matrix tests for every route; IDOR sweep of every patient/facility
object endpoint; rate limits on every unauthenticated POST and on login/OTP;
session fixation/rotation; MFA coverage; CSP/HSTS/headers; upload pipeline;
SSRF on webhooks; mass assignment; SQL built from strings; secrets in repo and
history (finish the scrub); `composer audit` / `npm audit`; logging never contains
PHI or secrets; backup encryption and restore drill; dependency licences.
External tooling run against staging: OWASP ZAP baseline + authenticated scan,
`nuclei` templates, `testssl.sh`. **No system is "free from all attacks"**; the
certificate states the controls, the scan results and the residual risks.

## Phase 5 — Scale (20 M → 100 M users)
Honest position: the code can be made horizontally scalable; a single VPS cannot
serve tens of millions of users. Work in this repo:
- Move sessions, cache, queue and rate-limit store **off Postgres to Redis**.
- Stateless web tier (no local-disk state; files to S3-compatible storage).
- Query review: `EXPLAIN` the 40 hottest queries (finders, queues, dashboards),
  add missing composite indexes, kill N+1, paginate every list in SQL.
- Partition append-only giants (audit/access logs, notifications, stock movements)
  by month; retention policy.
- Read replica support (`database.connections.pgsql.read`), PgBouncer.
- Finder reads cached as scalars per the never-cache-objects rule; CDN for assets.
- Load test with k6 against a staging copy: target and measured RPS, p95 latency,
  extrapolated capacity per node; the infrastructure plan (managed Postgres,
  Redis, autoscaling app nodes, object storage, CDN) with cost per 1 M users.
Output: `docs/certification/SCALABILITY.md` with measured numbers, not claims.

## Phase 6 — Code quality, documentation, ownership
- PHPStan/Larastan level 6+ with a baseline driven to zero for new code; Pint.
- Every class has a docblock stating purpose, inputs, invariants it protects.
- File header on first-party source: `Copyright (c) 2026 OPES Health Systems.
  All rights reserved.` plus `LICENSE`/`NOTICE` stating ownership.
- Commit attribution: from now on commits carry no AI co-author trailer, per the
  owner's instruction. Past history is not rewritten without an explicit owner
  decision (it is destructive and was just rewritten once).
- Docs: README, ARCHITECTURE, SCHEMA, API (OpenAPI already 100% of routes),
  RUNBOOK (deploy, rollback, incident), SECURITY.md (disclosure policy).

## Phase 7 — Certification & launch
Full suite + all phase gates green → `docs/certification/CERTIFICATE_2026-10-20.md`
listing every control, the evidence path, open residual risks and owner sign-offs.
Deploy Tuesday with the HANDOVER recipe, smoke as each role, monitor logs for 24 h.

## Order and parallelism
Phase 0 now (read-only, no collision with the UI agents). Phases 2–6 run as agents
in waves of ≤5 (host limit) once Phase 1 lands, each owning disjoint files and its
own test database. Phase 7 last.

## Phase 0 results (2026-10-18)
| Area | Reachable? | Writers | Verdict |
|---|---|---|---|
| Insurance claims (`InsuranceClaim`, `Claim*`, `InsurancePlan`, `InsuranceProvider`, `CareFacilityInsurance`) | no route, no controller | none | **Dormant schema** — document; propose drop migration after launch |
| `PatientInsurancePolicy` | read in 1 controller | none | read-only remnant; verify the view degrades to empty |
| `/solutions/insurers` marketing page | public GET | — | **Out of scope messaging** — owner decision: remove or reword before launch |
| FHIR R4 (`/api/fhir/R4/*`, `FhirIngestController`, `FhirIngestEvent`) | **live**, bearer-scoped partner ingest (DiagnosticReport, Immunization, Observation, transaction) + mobile FHIR export | yes | **CLAUDE.md is wrong** ("FHIR absent"). Code wins: inbound FHIR R4 ingest for partners is a shipped feature (commit "FHIR R4 partner ingest…", tests in `tests/Feature/Fhir`). Doc corrected. |
| Subscriptions (`/api/mobile/subscriptions*`) | live | yes | Intended: patient fee plans; care never gated on payment (`CareIsNeverGatedOnPaymentTest`) |
| `FacilityClaim` / "claims" routes | live | yes | Directory **listing** claims (a facility claiming its listing), not insurance — fine |
| `Inventory*` generic module | blood inventory API only | blood only | Blood bank is in scope; generic inventory items dormant |
| Session / cache / queue / rate-limit stores | Postgres | — | **Scale blocker** → Phase 5 moves them to Redis |
