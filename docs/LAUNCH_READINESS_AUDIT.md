# OpesCare Finder — Launch Readiness Audit

**Date:** 2026-09-08 · **Auditor:** read-only sweep of the local dev checkout
(`C:\laragon\www\opescare-finder`, branch `main`, HEAD `749c897`) against the dev
database `opescare_finder`.
**Method:** `route:list` (706 routes), FK/`information_schema` queries, row counts on
all 546 tables, `scripts/i18n-audit.php`, targeted greps, and read-only `tinker`
probes. **No production access was used and nothing was modified.**

---

## Summary — the honest version

**Genuinely ready.** The clinical core is in better shape than the size of the
schema suggests. Every one of the 706 routes resolves to a real controller and a
real method — no dead targets, no `auth:sanctum` on a guard that was never
installed. i18n is clean: 96 files, 11,883 keys, EN/FR 1:1, UTF-8 valid,
placeholders consistent, 0 errors. The chart-access rule is real and centralised
(`ClinicalRelationshipGate`, five named bases, refusal logged, audit failure fails
the read). Facility identity genuinely comes from `$request->attributes` on the
routed provider surfaces; the facility-switch endpoint is correctly guarded.
No enum-cast field is compared against a string literal anywhere in the app.

**Not ready.** Three things would harm a patient or make a promise false on day one:

1. Two statutory patient-rights workflows (data export, record correction) are
   **write-only** — the approval side has no route and no screen, so every request
   a patient files is unfulfillable forever.
2. Two admin buttons **hard-delete through `ON DELETE CASCADE` into clinical
   tables** — deleting a departing clinician silently destroys their patients'
   allergy records, diagnoses, notes, problem lists, and the audit trail of what
   they read.
3. The pharmacy prescription-queue search **500s** on every use (queries a column
   that does not exist).

And one unresolved operational question that only a production check can settle:
Horizon supervises `redis`, but the shipped default queue connection is
`database`. If prod matches the default, every queued job is silently dropped.

The dormant surface is enormous — 546 tables, 525 models, 136 controllers, but only
133 controllers are routed and only ~87 tables hold a row. Most of that is
inherited platform baggage and is harmless. The findings below are the parts that
are *not* harmless.

---

## BLOCKER

### B1 — Patient data-export requests can never be approved (statutory right, write-only)

**What.** The app and the patient portal both offer the Law No. 2010/012 right to
a copy of your record. A request is created `pending`. Nothing in the codebase can
ever move it to `approved`, and the download refuses anything that is not.

**Where.**
- Create (routed, live): `routes/api.php` → `app/Http/Controllers/Api/Mobile/MobileGovernanceController.php:293` `createExportRequest`
- Sets `status = 'pending'`: `app/Modules/Governance/Services/DataExportService.php:27`
- Download refuses: `app/Modules/Governance/Services/DataExportService.php:84` — `if ($request->status !== 'approved') throw ...`
- The **only** approver: `app/Http/Controllers/Api/V1/Admin/AdminGovernanceController.php:112` `approveExportRequest` → `:119` `approveExport()`
- Mobile UI, shipped: `opescare-finder-mobile/app/privacy/export.tsx:288` renders a `privacy.exportStatusHelp.approved` state

**Evidence.** `AdminGovernanceController` appears in **zero** routes
(`grep -c AdminGovernanceController` over `route:list --json` = 0; it is on the
"controller on disk, no route" list). `grep -rn approveExport app/ routes/` outside
the service returns only that unrouted controller. There is no admin Blade view for
data-export requests (`grep -rln correction\|data.export resources/views/portals/admin/` → none).
`data_export_requests` = 0 rows locally, consistent with "nobody has filed one yet".

**Why it matters.** The product states a legal right in the UI and cannot honour
it. On launch day requests accumulate in a queue no operator can see. Partial
mitigation: `opescare-finder-mobile/app/export-records.tsx` is a separate immediate
export, so patients are not entirely without a copy — but the moderated flow the
compliance copy describes is inert.

### B2 — Record-correction requests: same defect, same shape

**What.** A patient can file "this entry in my record is wrong". No routed
endpoint and no screen can approve or reject it.

**Where.**
- Create (routed): `routes/api.php:145` → `MobileGovernanceController::createCorrectionRequest`
- Approve/reject: `app/Http/Controllers/Api/V1/Admin/AdminGovernanceController.php` `approveCorrectionRequest` / `rejectCorrectionRequest` — **unrouted**
- No admin view: `grep -rln correction resources/views/portals/admin/` → no match

**Why it matters.** Record accuracy is a patient-safety control, not just a
compliance one. A patient who reports a wrong allergy or a wrong diagnosis has no
path to having it looked at.

### B3 — Deleting a staff user destroys clinical records and the audit trail

**What.** `AdminUserManagementController::destroy` hard-deletes a `User`. `User`
has **no** `SoftDeletes`. Five FKs cascade from `users`, four of them into clinical
tables.

**Where.** `app/Http/Controllers/MedicalId/AdminUserManagementController.php:446` —
`$user->delete();`
Routed and reachable from the admin portal:
`POST portals/admin/users/{id}/delete` → `portals.admin.users.destroy`
(also `DELETE admin/users/{id}`).

**Evidence** (`information_schema`, `delete_rule = 'CASCADE'`, references `users`):

| child table | column |
|---|---|
| `access_logs` | `actor_id` |
| `allergy_records` | `provider_id` |
| `clinical_notes` | `provider_id` |
| `diagnoses` | `provider_id` |
| `problem_lists` | `provider_id` |

The `destroy` method guards only "not yourself" and "not the last super-admin". It
does not check for authored clinical rows or audit rows.

**Why it matters.** A clinician leaves; an admin tidies up the user list; every
allergy that clinician recorded is deleted from their patients' charts. Allergy
loss is a direct route to a fatal prescribing error. Separately, deleting
`access_logs` by `actor_id` erases the record of who read whose chart — the exact
event the platform exists to prove. Commit `86c5f48` already fixed this class of
bug for import rollback; the admin user screen was not covered.

### B4 — Deleting a facility destroys its patients' clinical history

**What.** `AdminFacilityManagementController::destroy` hard-deletes a `Facility`
after checking only that no `patients.facility_id` points at it.

**Where.** `app/Http/Controllers/MedicalId/AdminFacilityManagementController.php:145-156`
Routed: `POST portals/admin/facilities/{id}/delete`, `DELETE admin/facilities/{id}`.

**Evidence.** Cascades from `facilities`: `visits`, `prescriptions`, `lab_orders`,
`imaging_orders`, `immunization_records`, `radiology_reports`, `consent_grants`.
None of these is checked by the guard.

**Why it matters.** Health IDs are portable by design — most care at a facility is
delivered to patients *registered elsewhere*, so `patients.facility_id` is exactly
the wrong proxy. A facility with zero registered patients can hold thousands of
visits, prescriptions and immunisations. One click erases them from the national
longitudinal record, and takes the facility's consent grants with it. `facilities`
= 482 rows.

---

## SERIOUS

### S1 — Pharmacy prescription-queue search throws a 500 on every use

**Where.** `app/Http/Controllers/MedicalId/PharmacyPortalController.php:126`
```php
$q->whereHas('patient', fn($p) => $p->where('full_name', 'like', "%{$search}%"));
```
**Evidence.** `patients` has no `full_name` column (columns are `first_name`,
`last_name`, `middle_name`). Confirmed by running the query read-only:
`SQLSTATE[42703]: Undefined column: 7 ERROR: column "full_name" does not exist`.
`full_name` on `Patient` is an accessor; accessors do not exist in SQL. This is the
only such usage in the app.
**Why.** A pharmacist typing a name into the dispensing queue gets a white-screen
500 at the counter. `prescriptions` = 13, `prescription_items` = 23 — the screen is
in real use.

### S2 — Horizon supervises `redis`; the shipped queue connection is `database`

**Where.** `config/horizon.php:200-236` — every supervisor is `'connection' => 'redis'`,
`'queue' => ['default']`. `.env.example` and `config/queue.php:16` default
`QUEUE_CONNECTION=database`. `docs/ops-backups.md:101` confirms prod runs a systemd
unit named `opescare-horizon`.
**Why.** If production's `QUEUE_CONNECTION` is `database`, Horizon watches an empty
Redis queue while jobs pile into the `jobs` table and are never executed —
appointment reminders, push receipts, webhooks and payment follow-ups all die
silently, with a green Horizon dashboard. **Unconfirmed** — I have no production
access. Confirm with `php artisan tinker --execute="echo config('queue.default');"`
on the prod host, plus `select count(*) from jobs`.
**Compounding:** `app/Http/Controllers/Api/HealthCheckController.php` checks only
database and cache. A dead or mis-pointed worker is invisible to `/api/health`.

### S3 — Backups default to the same host as the database

**Where.** `config/backup.php:34-36` — `$backupDisks = $backupS3Enabled ? ['local','s3'] : ['local'];`
with `.env.example` setting `BACKUP_S3_ENABLED=false`.
**Why.** With the default, the only copy of the national record lives on the same
volume as the database it protects — host loss loses both. The schedule and
failure alerts are correctly wired (`routes/console.php:21,28`; mail alerts on
`BackupHasFailed`), so this is one env var, not a rebuild. **Unconfirmed in prod:**
check `BACKUP_S3_ENABLED` in the prod `.env` and that a recent archive exists on the
s3 disk. Note also that backup failure alerts go by **email**, and Finder mail
deliverability has a known history — a silent backup failure could stay silent.

### S4 — Client secrets are bcrypt, not Argon2id, and an unsalted SHA-256 is still written for new clients

**Where.**
- No `config/hashing.php` exists → driver is Laravel's default. Verified:
  `config('hashing.driver')` = `bcrypt`, `Hash::make('x')` → `$2y$12$…`. So the
  column literally named `client_secret_argon` holds **bcrypt** hashes.
- `app/Http/Controllers/MedicalId/DeveloperPortalController.php:191` and `:314` write
  `'client_secret' => hash('sha256', $clientSecret)` for **brand-new** clients and on
  every rotation — not a legacy row, a fresh one.
- `app/Http/Middleware/VerifyIntegrationClient.php:95` and
  `app/Http/Controllers/Api/V1/Connect/AuthController.php:71` still **accept** that
  unsalted SHA-256 as valid credentials.

**Why.** A single unsalted SHA-256 of a partner's client secret is trivially
brute-forceable from a database read, and the "legacy fallback" is being freshly
populated rather than drained. Ranked SERIOUS rather than BLOCKER only because
`integration_clients` = **0 rows** — no live secret exists yet, so this is fixable
before it is exploitable. (Partner onboarding is in flight; see *In flight*.)

### S5 — Two independent document-verification systems, different tables, different token formats

**Where.**
- Live: `app/Services/Patient/DocumentVerificationService.php` → table
  `document_verifications` (**107 rows**), token `doc_{lookupId}_{secret}`, used by
  `app/Http/Controllers/MedicalId/DocumentVerifyController.php:8`.
- Orphan: `app/Services/Documents/DocumentVerificationService.php` → tables
  `document_verification_tokens` (**9 rows**), `document_verification_events` (0),
  token `vdt_…`. `grep -rn "Documents\\DocumentVerificationService" app/ routes/ tests/`
  returns **nothing** — no caller anywhere.
**Why.** Nine verification tokens exist that no verifier will ever accept. A
sealed clinical document whose QR resolves to "not found" reads to a clinician as a
forgery. Delete one system or make the verifier check both, deliberately.

### S6 — Six other duplicate class names, resolved only by import order

`find app -name '*.php' -printf '%f\n' | sort | uniq -d`:
`CarePlanService`, `DocumentVerificationService`, `NotificationEvent`,
`PaymentMethod`, `ProviderPerformanceService`, `WaitlistService` — each existing in
two namespaces. `NotificationEvent` (`app/Models/` vs `app/Modules/Notifications/Models/`)
is a known past defect. Importing the wrong one writes to the wrong table and fails
silently. Worth a deliberate pass before launch, not after.

### S7 — Patient enumeration by name prefix exists in code (currently unreachable)

**Where.** `app/Modules/Search/Services/GlobalSearchService.php:51`
```php
Patient::whereRaw('LOWER(health_id) LIKE ?', [$like])
  ->orWhereRaw('LOWER(first_name) LIKE ?', [$like])
  ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
```
**Why it is not a BLOCKER.** The only caller,
`app/Http/Controllers/MedicalId/StaffPortalController.php:266-273`, hardcodes
`'include_sensitive' => false`, and `patientResults()` runs only when that is true.
So it is dead today. It is one boolean away from becoming a national patient
name-search — remove it rather than rely on the flag.

### S8 — The staff dashboard "waiting" tile is structurally always zero

**Where.** `app/Http/Controllers/MedicalId/StaffPortalController.php:30` —
`QueueTicket::whereIn('status', ['waiting','called','service_started'])`.
**Evidence.** The only writer of `queue_tickets` anywhere in the tree is
`database/seeders/DemoClinicalRecordsSeeder.php:272-273`. No controller, service,
job or command creates one. `queue_tickets` = 0 rows; the nine queue tables
(`queues`, `queue_stations`, `queue_transfers`, `queue_priority_rules`,
`queue_status_histories`, `queue_display_settings`, `facility_queues`, …) are all
empty and `QueueStation` is referenced by nothing outside its own model.
**Why.** A dashboard number that can never be non-zero teaches staff to distrust
the dashboard. Either hide the tile or ship the queue writer.

### S9 — Patient-facing PDF documents contain hardcoded English

**Where.**
- `app/Services/Documents/DocumentDataAssembler.php` — 0 `__()` calls, 49 English
  literals (`'Not recorded'`, `'Pending documentation'`, `'Per prescription'`, …)
- `app/Services/Documents/DocumentOnDemandAssembler.php` — 0 `__()` calls, 32
  literals (`'As per clinical assessment'`, `'None documented'`, `'Secondary diagnosis'`, …)
- `app/Services/Patient/SurveyService.php:14-35` — 0 `__()` calls; **all** survey
  question text is hardcoded English (confirmed as previously reported)
- `app/Services/Tenancy/TenantOnboardingService.php`, `app/Services/Fhir/FhirCodeValidator.php` — smaller instances

**Why.** `scripts/i18n-audit.php` passes because it only compares the two lang
trees — it cannot see a string that never entered them. These strings appear on
official documents rendered for francophone patients and on the satisfaction survey
every patient sees. Bilingual EN/FR is a regulatory requirement here, not a polish
item.

---

## MINOR

- **M1 — USSD is fully absent from the routing table.** `app/Http/Controllers/Api/Ussd/UssdController.php`
  exists, `config/services.php:97` holds `africastalking.ussd_callback_secret`,
  `config/data_retention.php:15` has a purge policy for `ussd_sessions` — and there
  is no route (`grep -ri ussd routes/` → only a comment in `routes/api.php:11`).
  Feature-phone access matters in Cameroon; note it as deliberately out of scope or
  wire it, but the half-configured state is a trap.
- **M2 — `consent.grant` middleware is registered and applied to zero routes.**
  `bootstrap/app.php:102` aliases `RequireConsentGrant`; it appears on none of the
  706 routes. Enforcement genuinely happens in-controller via
  `App\Support\ClinicalRelationshipGate` — **this is not a hole** — but the root
  `CLAUDE.md` claim "`RequireConsentGrant` gates every patient endpoint" is false and
  will mislead the next reader. Fix the doc, delete the alias or the class.
- **M3 — `Api\V1\AppointmentController` falls back to a body-supplied `facility_id`.**
  `app/Http/Controllers/Api/V1/AppointmentController.php:145,181,215` —
  `$effectiveFacilityId = $facilityId ?? $validated['facility_id'];` is precisely the
  prohibited fallback. Harmless **today** because the controller has no route. Do not
  wire it without removing the `??`.
- **M4 — The appointment waitlist / backfill feature is unreachable.**
  `WaitlistService` is called only from that same unrouted `Api\V1\AppointmentController`
  (`:128 addToWaitlist`, `:167 triggerBackfill`). `waitlist_entries` = 0.
- **M5 — `admin.roles.index` and `admin.roles.users` have no nav link.** Role
  administration is reachable by URL only, against the project's own routing rule.
  (`roles` = 82 rows, `role_permission` is populated — this is live config.) Nav
  coverage elsewhere is good: only ~32 of 227 named GET routes are unlinked, and the
  rest are legitimately flow-internal (sitemaps, OTP/MFA steps, invite acceptance).
- **M6 — Six V1 admin API controllers exist with no routes:** `AccessControlController`,
  `AdminGovernanceController`, `AdminPlatformController`, `ConnectAdminController`,
  `PartnerGovernanceController`, `PlatformAdminController`. Most of their function is
  duplicated by the routed Blade portal (feature flags, modules, maintenance,
  incidents, developer clients, emergency-access review at `admin/security/emergency-access`).
  The two that are **not** duplicated are B1 and B2 above.
- **M7 — `BridgeSyncController` still advertises `POST /v1/bridge/sync` in its
  docblock and is declared in no route file.** Previously reported; it is currently
  being edited by another agent (see *In flight*).

---

## Empty but correct — do not "fix" these

These tables have 0 rows because nobody has used the feature yet, not because
nothing can write them. The write path exists and is routed.

| Table | Why empty is correct |
|---|---|
| `facility_catalogue_items` | `MedicalId\FacilityCatalogueController` is routed with create/update (`:170`, `:227`) behind `capability:service_catalogue`. No facility has published a catalogue yet. |
| `data_export_requests`, `correction_requests` | Correct *as counts* — no patient has filed one. The **approval** side is broken (B1/B2); the create side is fine. |
| `medicine_reservations`, `medicine_reservation_requests` | Reachable writer exists; reservations are new. |
| `provider_availabilities` | Written by a routed path; coverage is genuinely thin (a known launch constraint, not a code defect). |
| `record_export_requests`, `official_documents.pdf_path` | By design — patient PDFs render at download time and are never stored. `record_export_requests` is a superseded model; the live path is `DataExportRequest`. |
| `account_closure_requests` | Writer exists; `opescare:execute-account-deletions` is scheduled daily at 02:30. |
| `security_incidents` (10 rows) | Actively written from 4 call sites. Working. |
| `jobs`, `failed_jobs` | 0 locally is expected — no worker has run in this checkout. Says nothing about prod. |
| `integration_clients` | 0 is correct pre-launch; onboarding is in flight. |

Empty **and broken** (nothing can ever write them through the app): `queue_tickets`
and the eight sibling queue tables (S8); `waitlist_entries` (M4); `ussd_sessions`
(M1); `document_verification_events` (S5). Roughly 440 further tables are inherited
platform schema with no model reference outside `app/Models` — dormant, harmless,
and out of scope for launch.

---

## In flight — reported as state, not as defects

Three agents were editing while this audit ran. Findings in these areas are
deliberately excluded or flagged as provisional:

1. **`app/Console/Commands/ImportLoincAxes.php`** (untracked, new) plus the
   terminology stack. `clinical_code_dictionary` = 49,307 rows and
   `code_system_mappings` = 1. Not assessed.
2. **FHIR ingest layer** — untracked `app/Services/Fhir/`, `app/Support/Fhir/`,
   `app/Models/FhirIngestEvent.php`, `PartnerObservation`/`PartnerImmunization`/
   `PartnerDiagnosticReport`, `app/Traits/AppendOnlyPartnerRecord.php`,
   `app/Exceptions/FhirIngestRefusal.php`, migration
   `2026_09_08_160000_create_fhir_partner_ingest_tables.php`. There is no
   `routes/fhir.php` in the tree yet, so the ingest surface is not routed at the
   moment this audit ran — expected mid-flight, not reported as a gap.
   `app/Http/Controllers/Api/V1/Bridge/BridgeSyncController.php` and
   `app/Http/Middleware/VerifyBearerToken.php` are modified; M7 may already be
   resolved by the time you read this.
3. **Partner onboarding desk** — untracked
   `app/Http/Controllers/MedicalId/PartnerOnboardingController.php`,
   `resources/views/portals/admin/partners/`, `lang/{en,fr}/partners.php`, and
   modifications to `routes/web.php` and `resources/views/portals/admin/_admin_nav.blade.php`.
   S4 (client-secret hashing) sits squarely in this area and should be handed to
   that agent rather than fixed independently.

Also modified by others and excluded: `app/Enums/VitalProvenance.php`,
`lang/{en,fr}/patient_writes.php`.

---

## What I could NOT check, and why

- **Anything about production.** No SSH was used, by instruction. So: whether prod
  `QUEUE_CONNECTION` is `redis` (S2), whether `BACKUP_S3_ENABLED=true` and a recent
  archive exists (S3), whether Sentry's DSN is populated, whether the scheduler and
  `opescare-horizon` are actually running, whether outbound mail delivers, and
  whether the prod `APP_DEBUG` is off. Each has an exact check named in its finding.
- **The test suite.** Not run — three agents were working and the suite is slow and
  contends on the database. So I cannot tell you the suite is green, only that
  every route target resolves and every lang file passes the audit.
- **`php artisan view:cache`.** Not run: views are being edited by another agent, so
  a failure would not have been attributable.
- **Runtime behaviour of any screen.** No browser session. Findings like S1 were
  confirmed by executing the offending query read-only, not by loading the page.
- **Cross-facility responses (404 vs 403).** Not exercised end-to-end. What I can
  say from reading: `MobileProviderFacilityController::switch` deliberately returns
  **403 `FACILITY_NOT_ASSIGNED`** for a withdrawn facility rather than 404, with a
  written rationale (avoid confirming a uuid exists) — a defensible choice, but it
  is the opposite of the "cross-facility returns 404" invariant in the platform
  brief. Worth one deliberate decision rather than two conflicting conventions.
  **Unconfirmed** for the other patient-scoped surfaces.
- **Clinical immutability across the board.** Verified for immunisations
  (`entered_in_error` / `not_done` states, `MobileDoctorImmunizationController:502,596,755`).
  Not verified exhaustively for notes, vitals and results — an amend-vs-overwrite
  sweep is a separate exercise.
- **Mobile drift is a lower bound.** The diff below was computed by matching
  `'/mobile/...'` literals in the Expo app against 97 backend endpoint groups;
  template-built paths can hide a client that exists.

## Mobile drift (Expo app at `C:\laragon\www\opescare-finder-mobile`, HEAD `496d868`)

Backend surfaces that exist and have **no** client reference in the app:

| Backend | Endpoints | Note |
|---|---|---|
| `api/mobile/subscriptions*` | 4 | `subscription_plans` = 8 rows, priced; no patient screen picks one. Only reference in the app is an unrelated push-`subscriptions` string. |
| `api/mobile/blood-desk/*` | 6 | Blood-bank staff role has no app surface at all. |
| `api/mobile/preconsult/*` | 3 | Shipped in `749c897` (today); client presumably still to come. |

Everything else in the 97 mobile endpoint groups has a matching client module under
`lib/api/`.
