# Scale readiness — OpesCare Finder

Written 2026-10-19. Target named in the brief: tens of thousands of facilities,
millions of patients, then 100k facilities / national-to-continental reach.

## Status of measurement — read first

The EXPLAIN / timing pass at synthetic volume **has not been completed**. The
local PostgreSQL 16 crashed under combined load from several parallel agents
during the first ScaleSeeder run (50k facilities / 500k patients) and was in
crash recovery for over an hour. No plan or timing below is claimed as measured.
What exists is the tooling to measure, and the fixes that are correct by
construction (index shape matches predicate shape). To finish:

```bash
DB_DATABASE=opescare_finder_test_a5 php artisan migrate:fresh --force
DB_DATABASE=opescare_finder_test_a5 SCALE_FACILITIES=20000 SCALE_PATIENTS=100000 \
  php artisan db:seed --class=ScaleSeeder --force
# then EXPLAIN (ANALYZE, BUFFERS) each query in "Hot paths" below, once with
# and once without the 2026_10_19 indexes (DROP INDEX ... ; re-run)
```

`database/seeders/ScaleSeeder.php` refuses any database whose name lacks
`_test`, generates rows set-based with `generate_series` (factories at this
volume take hours), and tags everything `source_system='scale_synthetic'`,
which is now on every finder's synthetic deny-list so it can never surface
as live availability.

## What was changed

### Geo search (bounding box before Haversine)
- New `App\Support\Geo\BoundingBox` — a lat/lng rectangle that contains the
  search circle; `apply()` adds `latitude BETWEEN … AND longitude BETWEEN …`.
- `CareMapSearchService::searchNearby` — box applied before the per-row
  Haversine `whereRaw`; exact radius still enforced (tested: a point in the
  box corner but outside the circle is excluded).
- `BloodAvailabilitySearchService` and `PharmacyStockSearchService` — box
  applied inside the facility `whereHas`. Listings with NULL coordinates still
  pass (they were always returned with `distance = null`).
- **Correctness bug fixed at scale:** `PharmacyStockSearchService` caps
  candidates at the 500 freshest rows *nationally* before the distance filter.
  At national volume the nearest pharmacy could be cut before the distance
  check ever saw it. The box now applies in SQL before the cap.
- `MedicineFinderService` already had a bounding box; unchanged.

### Substring search (leading-wildcard LIKE)
- `CareMapSearchService` text and city filters now use only
  `LOWER(col) LIKE ?` (a superset of the old case-sensitive variants), with
  `%`/`_`/`\` escaped — `%%%` used to match every facility.
- Migration `2026_10_19_090000_add_trigram_search_indexes_for_finders`:
  GIN `gin_trgm_ops` on `lower(facility_name|city|facility_type|description)`,
  `medicines.lower(name|generic_name|brand_name)`,
  `pharmacy_stock_availability.lower(medicine_name|generic_name|brand_name)`.
  Built `CONCURRENTLY` (`$withinTransaction = false`).
- **pg_trgm on prod:** the extension is already installed on dev and test
  (created by the 2026-09-08 clinical code dictionary migration, which runs on
  prod). pg_trgm is a *trusted* extension on PG13+, so any role with CREATE on
  the database can enable it. The migration checks `pg_extension` first,
  tries `CREATE EXTENSION IF NOT EXISTS` otherwise, and on refusal logs a
  warning and skips — search stays correct, only slower. Not verified against
  the prod role directly (no prod access from this session).

### Index audit
Migration `2026_10_19_090100_add_hot_path_indexes_for_national_scale`
(non-transactional, `CREATE INDEX CONCURRENTLY IF NOT EXISTS`):

| Index | Why |
|---|---|
| `care_facilities (latitude, longitude)` | bounding-box prefilter |
| `care_facilities (listing_status, facility_type)` | every finder filters active + type |
| `health_id_aliases (alias_value, status)` | **Health ID resolve by alias had no index** — `Patient` alias fallback was a full scan |
| `consent_grants (patient_id, facility_id, status, expires_at)` | consent checks / grant lists |
| `access_logs (patient_id, created_at)` | "who saw my record", newest first |
| `appointments (facility_id, scheduled_at)` | facility day sheet |
| `queue_tickets (facility_id, status, created_at)` | queue desk |
| `notifications (notifiable_type, notifiable_id, created_at) WHERE read_at IS NULL` | unread bell |
| `notification_events (recipient_user_id, created_at)` | had no index beyond PK/uuid |
| `encounters (patient_id, started_at)`, `encounters (facility_id)` | **encounters had no patient index** — chart load scanned |
| `blood_availability (facility_id)`, `pharmacy_stock_availability (facility_id)` | stock→facility joins |
| `medicine_pharmacy_stocks (last_reported_at)` | freshest-first ordering |

An interrupted CONCURRENTLY build leaves an INVALID index; drop it and re-run
(the IF NOT EXISTS would otherwise keep the invalid one).

### N+1 / query budgets
`tests/Feature/Scale/FinderQueryBudgetTest.php` asserts the directory search
and the blood search issue the same number of queries for 3 and 30 results,
caps them, and asserts the bounding box is in the SQL. The finders were
already eager-loading; no N+1 was found in them. **Not done:** budgets for
chart, queue, dashboards, mobile home/records (the brief's other 11 pages).

### Request-path work moved to the queue
`PatientPush::queue()` + `App\Jobs\SendPatientPushJob` (queue `default`,
`afterCommit`, `tries = 1` — Expo tickets are not idempotent and a duplicate
clinical push is worse than a missed one). Eight request-path callers switched
(lab release ×2, doctor messaging, consent request, break-glass, family
invite, front desk, pharmacy desk). CLI callers (`push:test`, the reminder
command) keep the synchronous `send()` because they read its result.
Tests run with `QUEUE_CONNECTION=sync`, so behaviour under test is unchanged;
`PatientPushIsQueuedTest` uses `Queue::fake()`. **Not done:** FHIR ingest
fan-out and exports were not moved.

### Payment core
`tests/Feature/Payments/PaymentChargeCoreTest.php` — first direct tests of
`PaymentChargeService`: same-key double tap (one row), same key different
body (409), different keys (pending partial index → `payment_in_progress`),
amount mismatch, already-paid, forged callback (bad token → flat 202, nothing
changes), validly signed callback claiming SUCCESS for a payer the rail
declines (re-poll wins → failed), callback before approval, replayed callback
settles the invoice once, malformed order_id → 202 not 500, stale-model
settlement re-reads after `FOR UPDATE`, Unknown never becomes Failed,
expiry frees the invoice, refund terminality (service and trigger), trigger
rejects backward/sideways transitions and amount/currency/owner changes, and
one-paid-per-invoice index. True two-connection concurrency is not exercised
(RefreshDatabase's wrapping transaction hides rows from a second connection);
the lock is asserted by SQL inspection plus the stale-copy test.

Dead code removed: `app/Services/Payment/` (MobileMoneyService,
MtnMomoGateway, OrangeMoneyGateway) — grep across app, routes, config, tests,
database, bootstrap, resources, docs, scripts found references only inside
the folder itself.

Also: the "allow-list" for availability provenance is in fact a **deny-list**
(`whereNotIn(source_system, SYNTHETIC_SOURCE_SYSTEMS)`) in BloodAvailability,
MedicinePharmacyStock, PharmacyStockAvailability and LabTestAvailability,
despite the docblocks and CLAUDE.md calling it an allow-list. Any new source
string is published by default. Flagged, not changed — it is a product rule.

## Honest limits of the current deployment
- One VPS: nginx + PHP-FPM + PostgreSQL + Redis + queue worker on one host. No
  redundancy; a host failure is an outage.
- No read replica; every finder read hits the primary.
- No CDN; static assets and map tiles' proxies are served by the app host.
- File storage is local disk (signed private URLs); not shareable across nodes.
- Backups still lack off-site (S3) keys (see project memory).
- Public finder endpoints are unauthenticated and throttled per IP only.

## What 100k facilities / continental scale needs
1. **Stateless horizontal app nodes** behind a load balancer (sessions + cache
   + queue already Redis-capable; move uploads to object storage first).
2. **Managed PostgreSQL** with PITR, plus 1–2 read replicas; route finder and
   directory reads to replicas (Laravel `read`/`write` connection split).
   PgBouncer in transaction mode in front.
3. **PostGIS** (`geography` + GiST, `ST_DWithin`) once facilities exceed
   ~100k — the btree box is fine to that order, GiST is the right tool beyond.
4. **Redis cluster / managed Redis** for cache, sessions, queues; separate
   queue workers per queue (`default`, `imports`, `webhooks`), autoscaled.
5. **Object storage** (S3-compatible) for documents and photos.
6. **CDN** for assets and cacheable public directory responses (short TTL).
7. **Partitioning** of append-only tables (`access_logs`, `notification_events`,
   `mobile_payment_events`) by month once they pass ~100M rows.
8. **Search**: trigram GIN holds to a few million rows; beyond that, a
   dedicated search engine (OpenSearch/Meilisearch) for the directory.
9. **Load test plan** — `scripts/load/finder-smoke.k6.js`, profiles
   `smoke | load | stress | soak`, thresholds p95 < 400 ms (directory, blood),
   < 500 ms (medicine), error rate < 1 %. Run against a staging copy, never
   prod without the owner's go-ahead; expect 429s from the per-IP throttle
   (counted as `throttled`, not failures) unless run from several sources.
