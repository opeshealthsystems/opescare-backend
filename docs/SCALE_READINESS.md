# Scale readiness — OpesCare Finder at national scale

*Written 2026-10-19 alongside the security/scale audit. Target: national rollout
in Cameroon, on the order of 100,000 facilities and many millions of patients.
This is an honest assessment, not a sales sheet. If the code disagrees, trust
the code and fix this file.*

## The one-line answer

The **application code** is mostly shaped for scale: stateless JWT mobile API,
facility identity from middleware, queue workers for slow work, read/write
split already declared in `config/database.php`. The **deployment** is not:
today it is one VPS running nginx, PHP-FPM, PostgreSQL, the queue worker and
the scheduler together, with backups on the same disk. Nothing below the
application has been load-tested at national volume. Going national is mainly
an infrastructure project, plus a short list of code items.

## 1. Where things stand

| Area | Done | Still needed |
|---|---|---|
| **Authorization** | Patient endpoints scope by the token's `patient_id` (foreign ids give 404). Clinician reads go through `ClinicalRelationshipGate`. `capability:` middleware gates staff surfaces. Partner writes check consent (FHIR ingest, radiology). The 2026-10-19 audit closed six more gaps (see §8). | Pharmacies can open any un-routed prescription by UUID, by design. Revisit this if prescription UUIDs ever leak through QR codes or logs. |
| **Rate limiting** | Auth 5/min, OTP and verify have their own limiters, exports 10/min. From 2026-10-19 there is also a **baseline on every `/api/*` route**: 600/min per bearer token, 300/min per IP for anonymous callers. | Limiter state lives in the cache store (database today). At scale, move it to Redis. Add an edge or WAF limit (Cloudflare or nginx `limit_req`) so floods never reach PHP. |
| **Database** | PostgreSQL 16. The read/write split is declared (`DB_READ_HOST_1/2`, `sticky=true`). Trigram GIN indexes for the finder searches. Btree indexes for consent, access-log, appointment, queue, notification and encounter paths. Patient-side index migration added 2026-10-19 (§8). | A managed primary plus at least one streaming replica, PgBouncer in transaction mode, partitioning for the tables that only grow (§2), and `pg_stat_statements` with slow-query alerts. |
| **Queues** | `finder-queue.service` runs `queue:work` on the `default`, `imports` and `webhooks` queues. Horizon is installed. | Move from the database queue driver to Redis. Run at least two worker hosts with separate pools for `imports`, `webhooks` and notifications (push/SMS), and alert on queue depth and on failed jobs. |
| **Caching** | `serializable_classes=false` is deliberate and must stay. A cached object comes back as `__PHP_Incomplete_Class` on the first cache hit. Only scalars and arrays are cached. | Redis as the cache store. Cache only arrays: facility directory pages, the medicine catalogue, code dictionaries, app config. **Never** cache stock availability beyond its provenance window, and never cache anything patient-scoped in a shared key. |
| **Sessions** | Web sessions use the `database` driver. The mobile API is stateless (JWT plus `patient_access_tokens`). | Move sessions to Redis (horizontal PHP needs a shared store) and use the host-only `__Host-` cookie (owner action already open). |
| **Static assets / CDN** | Assets are versioned files in `public/`. The CSP allows only `'self'` plus nonces. | Put a CDN in front of `/assets`, `/css`, `/js` and map tiles, and add the CDN origin to the CSP. Patient files (photos, imports, PDFs) must **never** go through a public CDN: they are served by signed, private, short-lived URLs (as they already are). |
| **PHP tier** | Stateless apart from sessions, cache and local files. | Several PHP-FPM hosts behind a load balancer. Before that works: sessions and cache in Redis, the `local` private disk moved to object storage (S3-compatible), and the scheduler on exactly one host (`onOneServer()` needs a shared cache lock). |
| **Files** | Private disk on local storage. PDFs are rendered on demand and never stored. | S3-compatible object storage with server-side encryption, in-country or approved-region. |
| **Backups / DR** | spatie/laravel-backup runs nightly to the local disk and verifies each archive. | Off-site copies (the S3 keys are an owner action), continuous WAL archiving for point-in-time recovery, a **documented restore drill**, and RPO/RTO targets (suggested: RPO ≤ 5 min, RTO ≤ 4 h). |
| **Observability** | Sentry is configured (`config/sentry.php`). `LogApiUsage` middleware. Access logs for every patient read. | Uptime checks, APM traces, Postgres/disk/queue dashboards, and alert routing to a person on call. Today nobody is paged. |
| **Multi-region** | None. | Not needed for launch. One region with a warm standby replica in a second availability zone is enough for Cameroon. Multi-region active/active is not justified at this scale and would complicate consent and data residency. |
| **Data residency** | Not decided. | Cameroon's personal-data protection framework, and health-data sensitivity in general, may require in-country hosting or explicit cross-border authorisation. **Counsel must decide this before choosing a provider.** It constrains every line above (database, object storage, backups, CDN for anything beyond static assets, Sentry/APM payloads). |

## 2. Database sizing (order-of-magnitude)

These are assumptions, not measurements: 10 million patients, 100,000
facilities, about 1 encounter per patient per year.

| Table family | Rows after 3 years | Note |
|---|---|---|
| `patients`, `health_id_aliases` | 10–15 M | Encrypted PII columns. Index on `health_id`. Alias lookup is now indexed. |
| `encounters`, `visits`, `diagnoses`, `vital_signs`, `lab_results`, `prescriptions` + items | 30–200 M each | Grow forever. Partition by month on `created_at` or `started_at` before reaching about 50 M rows per table. |
| `access_logs`, `api_usage_logs`, `notification_events`, `notifications` | 1 B+ | Highest volume. Partition by month and move to cold storage or drop on a retention schedule (privacy policy permitting). |
| `care_facilities`, stock tables | 100 k facilities, ~10–50 M stock rows | The bounding-box prefilter plus trigram indexes keep the finders sub-second. Stock rows are upserted, not appended. |

Starting point: primary 8 vCPU / 32 GB RAM / NVMe, with storage sized for
about 1–2 TB in year 3 including indexes. Add a replica of the same size for
reads. PgBouncer sits in front of both. `max_connections` stays low (about
200) and PHP workers reach the database through the pooler.

## 3. PHP / queue tier sizing

- **API:** the mobile app polls, so assume about 5–10 requests per active user
  per session. At 100k concurrent users and around 1,000 rps peak, plan for
  4–6 PHP-FPM hosts (8 vCPU each, `pm.max_children` sized to RAM), then scale
  horizontally behind the load balancer.
- **Workers:** push, SMS and webhook fan-out are the bursty part. Size the
  queues by depth, and keep `imports` on its own pool so a big partner upload
  never delays a consent notification.
- **OPcache and preloading:** keep `config:cache` and `route:cache` in the
  deploy. CLAUDE.md warns that prod serves stale config without them. Never
  run `config:cache` before tests.

## 4. Caching rules (non-negotiable)

1. `serializable_classes=false` stays. Cache `toArray()` output, never models
   or Carbon instances.
2. **An empty finder is correct.** Availability is never cached past its
   freshness window and never back-filled from a cache when the live source is
   empty.
3. Nothing patient-scoped goes into a shared or global cache key. Per-patient
   caching, if ever added, is keyed by `patient_id` and invalidated on write.
4. Rate-limiter and lock state go in Redis, not in the database cache, once
   there is more than one PHP host.

## 5. Load and restore testing (not done)

- Load: seed synthetic national volume (another session has started a
  `ScaleSeeder`), then run k6 or Locust against `/api/mobile/me`,
  `/appointments`, `/pharmacy/medicines`, `/facilities` and the desk queues.
  Record p95 latency and DB CPU.
- Restore: restore last night's backup into a fresh host, boot the app, and
  verify that a known patient's record and Health ID resolve. Time it, and
  repeat every quarter.

## 6. What a national rollout needs, in order

1. Data-residency decision (legal) → choose provider and region.
2. Managed PostgreSQL primary + replica + PgBouncer + WAL archiving.
3. Redis for cache, sessions, queues and rate limits.
4. Object storage for the private disk.
5. Two or more PHP hosts behind a load balancer. Scheduler on one host.
6. CDN for static assets. WAF / edge rate limit.
7. Observability with paging. Restore drill. Load test at synthetic volume.
8. Partition `access_logs`, `notification_events`, `api_usage_logs` and the
   clinical fact tables.

## 7. Code-level scale items still open

- Some list endpoints still return a whole collection. Patient-scoped lists
  are naturally small (one person's records), but every facility-scoped or
  global list must page with a cap. `GET /api/v1/prescriptions` was fixed on
  2026-10-19 (`per_page` ≤ 100). Audit any new list endpoint for the same.
- A message thread loads every message (`MobileMessagingController::show`).
  This is fine for patient threads; add cursor pagination if threads ever grow
  long.
- `FacilityMembershipService::facilityIdsFor()` now runs on every
  facility-context request (to revoke removed staff at once). It is a few
  indexed queries. If it shows up in profiles, memoise it per request; do
  **not** cache it across requests, because that would bring back the stale
  access it closes.

## 8. Changes made in the 2026-10-19 audit

- Baseline `/api/*` throttle: `throttleApi()` plus the token/IP-keyed `api` limiter.
- Blood upsert allow-list: a body `facility_id` can no longer move a row to another facility.
- `/admin/certifications` now needs `platform.admin` and `mfa.verified`.
- `v1/prescriptions` is scope-gated (`patient:read` / `prescription:push`). The prescriber must be posted at the client's facility, the patient must exist, and the list is paginated.
- Radiology `reported_by`, `finalize.radiologist_id` and `distribute.user_ids` must be staff at the client's facility.
- Removed or suspended staff lose their facility session on the next request (`RequireFacilityContext`, `DeskFacilityContext`).
- Patient-side hot-path indexes: see `database/migrations/2026_10_19_100000_add_patient_hot_path_indexes.php`.
