# Launch audit tracker

Living record of every finding from `LAUNCH_READINESS_AUDIT.md` (2026-09-08) and
every defect found since, with what was done, the commit that did it, and whether it
is on the live server. **A row is not "done" until the Live column says so.**

Status vocabulary: `open` · `fixed-local` (committed, not deployed) · `live`
(deployed and verified on finder.opescare.cloud) · `owner` (needs something only
the owner can supply) · `decision` (needs a product/clinical call before code).

Last updated: 2026-09-14.

---

## Blockers

| # | Finding | Status | Commit | Live | Evidence |
|---|---|---|---|---|---|
| B1 | Data-export requests could never be approved (statutory, Law 2010/012) | **live** | `8792211` | yes | `/admin/governance`, 8 routes; approve/refuse with mandatory reason; 13 tests |
| B2 | Record-correction requests, same defect | **live** | `8792211` | yes | Approval = amendment, original marked `entered_in_error` and still readable; unamendable types refused on screen |
| B3 | Deleting a staff user cascaded to 47 tables incl. allergy records and `access_logs` | **live** | `46d688c` | yes | Destroy now suspends; DB refuses `DELETE FROM users` with audit rows (`access_logs.actor_id` → RESTRICT), demonstrated on prod: 156 rows stood |
| B4 | Deleting a facility cascaded to 61 tables incl. visits, prescriptions, consent | **live** | `46d688c` | yes | Destroy now suspends; patients keep access via Health ID; 6 tests |

## Serious

| # | Finding | Status | Commit | Live | Evidence / notes |
|---|---|---|---|---|---|
| S1 | Pharmacy prescription search 500s (`full_name` column absent) | **live** | `8792211` | yes | Also a forbidden patient-name LIKE search; now exact Health ID only; mutation-tested |
| S2 | Horizon supervises `redis`, shipped default `database` | **live** (reframed) | — server only | yes | Audit premise was wrong: prod already used redis. The real defect: **no worker ran for Finder at all** — the only Horizon was the main platform's. `finder-queue.service` installed 2026-09-14; drained 9 stuck jobs; 0 failed. Horizon deliberately not used (no `demo` supervisor; `imports`/`webhooks` queues unlisted) |
| S3 | Backups on the same disk as the database | **owner** | pending | pending | `/admin/cc/backups` page shows status + exact server steps. Needs S3 key + secret from owner; `AWS_ACCESS_KEY_ID`/`SECRET` empty on prod, `BACKUP_S3_ENABLED=false` |
| S4 | Client secrets bcrypt not Argon2id; unsalted SHA-256 still written | **live** | `46d688c` | yes | `ClientSecretHasher` dispatches on prefix; new rows genuinely Argon2id; legacy column set to a non-hex sentinel |
| S5 | Two document-verification systems; 9 orphan tokens no verifier accepts | **decision** | — | — | Delete the orphan `app/Services/Documents/DocumentVerificationService.php` system, or teach the verifier both formats. Needs a call on the 9 existing tokens |
| S6 | Six duplicate class names across namespaces | **open** | — | — | `CarePlanService`, `DocumentVerificationService`, `NotificationEvent`, `PaymentMethod`, `ProviderPerformanceService`, `WaitlistService` |
| S7 | Patient name-prefix enumeration in `GlobalSearchService` (dead, one flag from live) | **open** | — | — | Remove `patientResults()` rather than rely on `include_sensitive=false` |
| S8 | Staff dashboard "waiting" tile structurally always zero | **open** → Phase 3 | — | — | Only writer is a demo seeder. Resolved by the queue build, or hide the tile |
| S9 | Patient-facing PDFs hardcode English (`DocumentDataAssembler` 49, `DocumentOnDemandAssembler` 32, `SurveyService`) | **open** | — | — | i18n audit cannot see strings that never enter the lang tree |

## Minor

| # | Finding | Status | Notes |
|---|---|---|---|
| M1 | USSD controller + config, no route | open | Decide whether USSD ships in v1 |
| M2 | `consent.grant` middleware registered, applied to zero routes | open | Either apply or remove; a registered-but-unused gate misleads readers |
| M3 | `Api\V1\AppointmentController` falls back to body `facility_id` | open | Harmless only because unrouted. Remove the fallback before anyone routes it |
| M4 | Appointment waitlist / `BackfillWaitlistJob` unreachable | open → Phase 3 | Only caller is the unrouted controller in M3 |
| M5 | `admin.roles.index` / `admin.roles.users` have no nav link | open | Route + nav rule |
| M6 | Six V1 admin API controllers with no routes | open | Wire or delete |
| M7 | `BridgeSyncController` advertised `POST /v1/bridge/sync`, declared nowhere | **live** | `46d688c` — contract fixed (accepted types = handled types, silent `default => 'skipped'` removed). **Deliberately still unrouted**: routing it would have made a facility's posted lab results return 200 and store nothing |

## Found after the audit

| Finding | Status | Commit | Live | Evidence |
|---|---|---|---|---|
| FHIR ingest enumeration oracle — 404 vs 403 revealed which Health IDs exist | **live** | `46d688c` | yes | Both refusals byte-identical; test compares whole body; mutation-checked |
| Any authenticated user could download another patient's full export | **live** | `8792211` | yes | Requester-only; refusal identical to "not approved" |
| Appointment delete cascaded into `preconsult_questionnaire_responses` | **live** | `46d688c` | yes | Now `entered_in_error` |
| Partner revocation did not bite (JWT checked, client never) | **live** | `46d688c` | yes | Per-request client status lookup, uncached; revoke → 401 within one request |
| Developer self-service `storeApp()` grants 3 default scopes not in `availableScopes()` | **open** | — | — | Least-privilege violation on the other side of `integration_clients` |
| Partner-pushed FHIR data is stored but displayed nowhere | **decision** | — | — | How partner-reported results appear beside facility-measured ones is a clinical display call |
| `MobileProviderFacilityController:142` returns 403 not 404 for a facility the clinician can't act at | **decision** | — | — | Reasoned in its comment, but contradicts the platform invariant |
| LOINC `specimen`/`scale`/`units` NULL on 19,292 rows | **in progress** | `8792211` | running | `opescare:import-loinc-axes` on prod, 38% at last check; resumable |
| Facility catalogue could not show procedures/services/vaccines (`assignable()` vs `offerable()`) | **live** | `749c897` | yes | 46,768 offerable vs 37,034 assignable |
| Triage queue sorted by arrival, not urgency | **live** | `749c897` | yes | Sorted in SQL by derived priority; both tests mutation-checked |

## Owner actions outstanding

1. **Backup keys** — follow `/admin/cc/backups`. Until then every backup is on the same disk as the database.
2. **ICHI licence** — confirm WHO CC BY-ND 3.0 IGO terms permit redistribution to partners before ICHI data leaves the platform.
3. **Null MX record at Hostinger** — blocks all outbound email, including the notifications the new queue worker now delivers.
4. **Five terminology defects** need a human decision each on `/admin/terminology/gaps` (`L01XX14`, `10^9/L`/`x10^9/L`, `IM`/`intramuscular`, `Td`/`TD`, four group-level ATC codes).

## Phase plan

- **Phase 1** (in-flight work) — done.
- **Phase 2** (audit blockers + ops) — done except S3, which is `owner`.
- **Phase 3** — queue system (S8, M4), fill empty tables (catalogue, partner, decisions above), S5–S7, S9.
- **Phase 4** — dashboards, email template design, final deploy.
