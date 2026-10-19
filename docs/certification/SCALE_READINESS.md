# Scale readiness — OpesCare Finder

Written 2026-10-19. Target named in the brief: tens of thousands of facilities,
millions of patients, then 100k facilities / national-to-continental reach.

## Measured — 2026-10-19 (single dev host)

An earlier attempt (50k facilities / 500k patients) crashed the local
PostgreSQL under load from parallel agents. This pass ran alone at 20k / 100k.

**Host and caveats.** One Windows 11 workstation, local PostgreSQL 16 with
default settings (no `shared_buffers` / `work_mem` tuning), database
`opescare_finder_test_a5`, no other heavy DB process. Timings are
`EXPLAIN (ANALYZE, BUFFERS)` execution times for the SQL the services emit
(captured from the query log with bindings inlined), on a warm cache. They are
**database time only**, not request latency: PHP, Blade, the network and the
per-request middleware queries all come on top. Prod is a different, smaller
VPS that shares the box with other services. Read these as evidence of plan
shape first, and of absolute speed second.

**Volume** (`ScaleSeeder`, SCALE_FACILITIES=20000, SCALE_PATIENTS=100000,
seeded in 78 s, DB 346 MB):

| Table | Rows |
|---|---|
| care_facilities | 20,000 (plus 2,000 tenant `facilities`) |
| patients / visits / appointments / consent_grants | 100,000 each |
| access_logs / encounters | 200,000 each |
| medicine_pharmacy_stocks | 40,000 (2,000 medicines) |
| blood_availability | 32,000 |
| notifications | 30,000 (500 staff users) |
| queue_tickets | 25,005 |
| health_id_aliases | 19,757 |

The seeder was extended for this run. It now creates 500 synthetic staff
users, because `access_logs.actor_id` is a real FK to `users` and the first
run failed on it. It also generates visits, encounters, queue tickets and
notifications, which it did not before. Synthetic stock is tagged
`scale_synthetic`, so the provenance allow-list correctly hides it. For the
finder measurements only, the synthetic stock and blood rows in the test DB
were relabelled `portal` so that the plans walk real rows. The test DB was
wiped afterwards, and the app code has nothing like this.

| Hot path | Plan with 2026-10-19 indexes | Time | Without the index | Time |
|---|---|---|---|---|
| Directory: text + city + bbox + radius | Bitmap AND of `care_facilities_lat_lng_idx` + `city_trgm` | 1.2 ms | Seq Scan care_facilities | 6.4 ms |
| Directory: name substring only | Bitmap on `care_facilities_name_trgm` | 0.9 ms | Seq Scan | 14.8 ms |
| Nearby blood (O+, 50 km) | `blood_avail_public_search_idx` + facility bbox index | 4.3 ms | facility Seq Scan | 9.7 ms |
| Medicine stock nearby ("paracetamol", 50 km) | facility ids from `care_facilities_lat_lng_idx` (0.6 ms), then Bitmap on `medicine_pharmacy_stocks (care_facility_id, stock_status)` (3.5 ms) | 4.1 ms | was **Seq Scan medicine_pharmacy_stocks** (40k) + hash join | 13.7 ms (re-measured; 11.2 ms first run) |
| Health ID resolve | `patients_health_id_unique` | 0.04 ms | (unique, not dropped) | — |
| Health ID resolve by alias | `health_id_aliases_value_status_idx` | 0.6 ms | **Seq Scan** | 4.8 ms |
| Consent check (patient, facility, active, unexpired) | `consent_grants_patient_fac_status_idx` (backward) | 0.2 ms | `consent_grants_patient_id_index` + filter | 1.1 ms |
| Access log by patient, newest first | `access_logs_patient_created_idx` (backward) | 1.0 ms | `access_logs_patient_id_index` + sort | 0.9 ms |
| Chart encounters by patient | `encounters_patient_started_idx` (backward) | 0.5 ms | **Parallel Seq Scan** (200k) | 97 ms |
| Appointments: facility day sheet | `appointments_facility_scheduled_idx` | 0.07 ms | not dropped | — |
| Queue desk (facility, active statuses) | `queue_tickets_facility_status_idx` | 0.35 ms | not dropped | — |
| Unread bell (20 newest) | partial `notifications_unread_idx` | 0.24 ms | not dropped | — |

For the "without" column the indexes were dropped inside `BEGIN … ROLLBACK`,
so they were never actually lost. The dropped set: alias, encounters, lat/lng,
name and city trigram, the access-log composite, and the consent composite.

**What the numbers say.**
- Every patient-keyed path is an index scan and stays under a millisecond.
  These lookups are O(log n), so they will be in the same range at 10× volume.
- The two indexes that matter most are the two the audit flagged as missing.
  **Encounters by patient** drops from 97 ms to 0.5 ms. A seq scan grows
  linearly, so without the index each chart open would cost roughly 1 s at 2M
  encounters. **Alias resolve** was a seq scan that grows with every merge.
- The access-log and consent composites buy little at 2 rows per patient.
  They pay off for patients with long histories, where they avoid a sort.
- **Medicine stock nearby no longer seq-scans the stock table.** Written as
  `EXISTS` against the facility box, the planner scanned all 40k stock rows
  and hash-joined them to the ~100 facilities in the box (13.7 ms, 1,594
  buffers). `PharmacyStockSearchService` now resolves the active listings in
  the box once (one indexed query, ids only) and reads both stock tables with
  `care_facility_id IN (…)` / `facility_id IN (…)`, so the stock lookup is a
  bitmap scan on the existing `(care_facility_id, stock_status)` index and
  touches only that box's rows (0.6 + 3.5 ms; most of the 3.5 ms is the
  2,000-row `medicines` term filter). No new index was needed. Above 5,000
  listings in one box the id list is not bound and the old relation
  constraint is used. Results are byte-identical on the scale data (same
  rows, distances, matched stock ids, order); the provenance allow-list is
  still applied by `reportedByRealSource()` on both tables.
- Extrapolation: the bbox + trigram directory plans are bitmap scans. Their
  cost scales with the number of rows *inside the box*, not with national
  totals, so at 100k facilities a city-sized box should still answer in single
  digits of ms. Dense cities (Douala, Yaoundé) will put more rows in the box
  than this uniform random spread does.

**Load test (k6).** `k6` is not installed on this host, and it was not
installed for this pass. To run the script against a local or staging copy
(never prod, never opescare.com):

```bash
k6 run -e BASE_URL=http://127.0.0.1:8000 -e PROFILE=smoke scripts/load/finder-smoke.k6.js
# PROFILE = smoke | load | stress | soak; expect 429s from the per-IP throttle
```

To reproduce the measurements:

```bash
DB_DATABASE=opescare_finder_test_a5 php artisan migrate:fresh --force
DB_DATABASE=opescare_finder_test_a5 SCALE_FACILITIES=20000 SCALE_PATIENTS=100000 \
  php artisan db:seed --class=ScaleSeeder --force
# EXPLAIN (ANALYZE, BUFFERS) each hot path; to compare, drop an index inside
# BEGIN/ROLLBACK. Measure BEFORE any test run: RefreshDatabase wipes the data.
```

`database/seeders/ScaleSeeder.php` refuses any database whose name lacks
`_test`. It generates rows set-based with `generate_series` (factories at this
volume take hours) and tags everything `source_system='scale_synthetic'`.

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
already eager-loading; no N+1 was found in them.

`tests/Feature/Scale/StaffAndPortalQueryBudgetTest.php` measures each page
twice, once with a little data and once with several times more. It asserts
that the count stays flat (+1 slack) and stays under a cap:

| Page | Cap | Measured |
|---|---|---|
| Chart show | ≤70 | ~50 |
| Queue desk | ≤45 | 36 |
| Staff dashboard | ≤80 | ~75 |
| Patient portal home | ≤70 | — |
| Mobile doctor home, chart, encounters, prescriptions | ≤40 each | — |
| Mobile patient me, timeline, clinical, labs, prescriptions, appointments | ≤25 each | — |

`tests/Feature/Perf/PatientEndpointsQueryCountTest.php` already covers 18
patient endpoints the same way.

**N+1 found and fixed:** the queue desk issued two queries per ticket
(`facility_role_assignments` + `roles`). The cause was the
`@capability('clinical_chart')` directive, which re-resolved the user's role
inside the row loop. The role is now resolved once at the top of
`portals/queue/index.blade.php`. The directive itself is now memoised too:
`StaffCapabilities::can()` resolves the role once per request per (user,
global role, active facility), held on the Request's attributes so nothing
survives the request, and a facility switch mid-request resolves afresh.
`tests/Feature/Scale/CapabilityDirectiveMemoTest.php` renders 3 and 30 rows
with the same query count and checks the facility switch.

### Request-path work moved to the queue
`PatientPush::queue()` + `App\Jobs\SendPatientPushJob` (queue `default`,
`afterCommit`, `tries = 1` — Expo tickets are not idempotent and a duplicate
clinical push is worse than a missed one). Eight request-path callers switched
(lab release ×2, doctor messaging, consent request, break-glass, family
invite, front desk, pharmacy desk). CLI callers (`push:test`, the reminder
command) keep the synchronous `send()` because they read its result.
Tests run with `QUEUE_CONNECTION=sync`, so behaviour under test is unchanged;
`PatientPushIsQueuedTest` uses `Queue::fake()`.

**FHIR ingest and exports: reviewed, and deliberately left synchronous.**
- **FHIR ingest has no fan-out to move.** `FhirIngestController` calls
  `FhirIngestService`, which writes the partner rows in one transaction and
  returns the stored resource. Nothing else fires: no push, notification,
  mail, HTTP call or event listener on the Partner* models. Queuing it would
  break the FHIR contract (the client gets `201` plus the stored resource, and
  a transaction Bundle is all-or-nothing) and would save nothing.
- **Exports cannot be queued without a product decision.**
  `MedicalRecordExportController` renders the PDF or FHIR bundle in memory and
  returns it in the response. A job would have to store the PDF, which the
  documents-on-demand rule forbids: patient PDFs are never written to disk.
  Both endpoints are throttled at 10/min per user. If PDF render time becomes
  a problem, the option is a queued render to a short-lived encrypted blob
  with a pickup endpoint. That changes the storage rule, so it is the owner's
  call, not a refactor.

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
*Update, later on 2026-10-19:* the SQL the finders emit now uses a true
allow-list (`source_system IN ('portal','partner_api', …)` or a
`partner_api:` prefix). It was observed in the query log during the
measurement pass above.

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

## 1M facilities / 10M patients — attempted 2026-10-19, aborted

**Not measured.** The load was stopped partway because the local PostgreSQL
backend was killed by Windows and the server went into crash recovery. No
timings from this attempt exist; the 20k/100k table above is still the latest
measurement.

**What happened.** Database `opescare_finder_scale_1m` (fresh, `migrate --force`,
all app indexes) was loaded in 1M-patient slices with `INSERT … SELECT
generate_series`, one statement per table per slice, `synchronous_commit=off`
and autovacuum disabled on the big tables in that database only.
`ScaleSeeder` now also accepts database names containing `_scale`.

| Step | Time |
|---|---|
| 1,000,000 care_facilities (2 × 500k) | 65 s + 59 s |
| 100,000 tenant facilities, 25,000 staff users | 3 s |
| Patient slice 1 (1M patients, 200k aliases, 1M consents, 1M appointments, 1M visits, 1M encounters, 2M access logs, 100k queue tickets) | 10.5 min |
| Patient slice 2 | 14 min |
| Patient slice 3 | 34 min (access logs alone 20.5 min) |
| Patient slice 4, consents + appointments only | 9 min + 50 min |

Each slice was slower than the last. Inserts with random UUID keys land on
random pages of every btree index. Once the indexes outgrew memory, every
insert waited on disk reads (`IO / DataFileRead`). This host has 7.7 GB RAM
and PostgreSQL runs with the default `shared_buffers=128MB`. To finish, the
54 non-unique secondary indexes on the eight patient tables were dropped
(their definitions were saved) so they could be rebuilt after the load. During
the next step, `encounters` slice 4 at 11:15, the backend was terminated with
`0xC000012D` (STATUS_COMMIT_LIMIT): Windows had run out of commit memory, with
about 4 GB of 30.7 GB free afterwards, shared with other desktop processes.
The server restarted itself into crash recovery (a full-data-directory fsync
across every database on the instance). Per the safety rules, nothing was
restarted and loading stopped.

State at abort: about 4M patients, 1M facilities, about 8 GB of data. The
secondary indexes were not rebuilt, so this database is not valid for plan
measurement in any case.

**What this tells us (without timings).**
- This laptop cannot build a 10M-patient dataset with production indexes in
  any reasonable time. A national-scale rehearsal needs a dedicated host or a
  cloud VM: at least 32 GB RAM, `shared_buffers` of about 8 GB, NVMe, and
  nothing else on the instance.
- The slowdown is caused by random UUID primary and foreign keys spread
  across many btree indexes. Read plans are not the problem. Production will
  see the same write amplification as tables grow. That argues for
  time-ordered IDs (UUIDv7) on append-heavy tables (`access_logs`,
  `notifications`, `encounters`) and for monthly partitioning of
  `access_logs` (already item 7 above). Not changed here; that is a schema
  decision.
- `appointments` carries 13 indexes and `consent_grants` 7. Several of them
  (`is_demo`, `demo_seed_key`, `demo_reset_group`, nullable FK indexes) cost
  writes at volume for little read value. Worth an audit before national
  rollout.

**To retry on a suitable host:** use the batched loader approach (slice per 1M
patients). Drop the secondary indexes before the bulk load and rebuild them
with `maintenance_work_mem` ≥ 1 GB afterwards, then `VACUUM ANALYZE`. Run the
same `EXPLAIN (ANALYZE, BUFFERS)` set as the 20k/100k table.
