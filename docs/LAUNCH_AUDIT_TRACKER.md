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
| S5 | Two document-verification systems; 9 orphan tokens no verifier accepts | **live** | `1e16428` | yes | Audit was wrong that the second system was orphaned: `DocumentIssuanceService` still mints `vdt_` tokens on every issue. Renamed to `OfficialDocumentTokenService`; the live verifier now answers BOTH token families (valid/withdrawn/expired/unknown). 9 rows untouched |
| S6 | Six duplicate class names across namespaces | **live** | `1e16428` | yes | 4 orphans deleted (evidence per pair in agent report), 2 renamed (`AppointmentWaitlistService`, `OfficialDocumentTokenService` — different tables, not merged). `NoDuplicateClassBasenamesTest` guards it. Leftover: `Reports\ProviderPerformanceService` and `DocumentVerificationEvent` model have no caller |
| S7 | Patient name-prefix enumeration in `GlobalSearchService` (dead, one flag from live) | **live** | `1e16428` | yes | `patientResults()` and the flag deleted outright; test proves a search issues no query against `patients`. `StaffPortalController` still passes the now-inert `include_sensitive` key (queue agent owns that file) |
| S8 | Staff dashboard "waiting" tile structurally always zero | **live** | `1e16428` | yes | Queue desk built at `/facility/queue` (9 routes, `queue` capability, 12 sidebars): check-in by Health ID or booking, call/start/complete/no-show/cancel, append-only history, SQL ranking by triage priority then wait, measured median wait. Tile now counts today's active tickets and links to the desk. Stations/transfers/priority rules/display deliberately unbuilt |
| S9 | Patient-facing PDFs hardcode English (`DocumentDataAssembler` 49, `DocumentOnDemandAssembler` 32, `SurveyService`) | **live** (latent) | `1e16428` | yes | 0→73 / 0→70 / 0→4 `__()` calls; clinical French sourced from published forms (HAS Braden, WHO growth) or `display_fr`, Morse items deliberately left English. **Finding:** nothing in Finder calls these assemblers, and 3 schema mismatches meant they could never have run. Fix is real but latent; a tokeniser test blocks new literals |

## Minor

| # | Finding | Status | Notes |
|---|---|---|---|
| M1 | USSD controller + config, no route | open | Decide whether USSD ships in v1 |
| M2 | `consent.grant` middleware registered, applied to zero routes | open | Either apply or remove; a registered-but-unused gate misleads readers |
| M3 | `Api\V1\AppointmentController` falls back to body `facility_id` | open | Harmless only because unrouted. Remove the fallback before anyone routes it |
| M4 | Appointment waitlist / `BackfillWaitlistJob` unreachable | open | S6 dedupe done (`AppointmentWaitlistService`); still unrouted — next Phase 3 slice |
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
| LOINC `specimen`/`scale`/`units` NULL on 19,292 rows | **live** | `8792211` | yes | 19,069 / 19,292 filled from HL7 tx.fhir.org (LOINC 2.82). 223 left NULL: the server publishes no SYSTEM part for them; not guessed |
| Facility catalogue could not show procedures/services/vaccines (`assignable()` vs `offerable()`) | **live** | `749c897` | yes | 46,768 offerable vs 37,034 assignable |
| Triage queue sorted by arrival, not urgency | **live** | `749c897` | yes | Sorted in SQL by derived priority; both tests mutation-checked |

## Caught by the gate before shipping (2026-09-14, batch 4)

- The lab agent's `MedicalId/LabDeskController` duplicated the basename of the pre-existing `Api/Mobile/Lab/LabDeskController`; `NoDuplicateClassBasenamesTest` (added this morning) failed the gate. Renamed to `LabDeskWebController`, matching its service.
- My blood-notification fix cast a missing tenant to `''`; Notify then ran a uuid comparison against it, Postgres raised 22P02, and inside `RefreshDatabase`'s per-test transaction that poisoned every later query (25P02) — six mobile blood tests became 500s. `rescue()` swallows the exception, not the aborted transaction. Now: no tenant → nothing queried.

## Owner actions outstanding

1. **Backup keys** — follow `/admin/cc/backups`. Until then every backup is on the same disk as the database. **Also:** the S3 driver (`league/flysystem-aws-s3-v3`) is NOT installed — only suggested in composer.lock; a backup attempt on 2026-09-05 failed on it (prod log). Step 0 on that page now says so.
2. **ICHI licence** — confirm WHO CC BY-ND 3.0 IGO terms permit redistribution to partners before ICHI data leaves the platform.
3. **Null MX record at Hostinger** — blocks all outbound email, including the notifications the new queue worker now delivers.
4. **`data_steward` tier** — its sidebar cannot link MPI/terminology/data-import desks because the role is not platform tier and would 403. Decide: promote the three data-quality roles to `RequirePlatformAdmin::PLATFORM_ROLES`, or build facility-scoped equivalents.
5. **16 seeded `dashboard_profiles` no role uses** (`appointment_coordinator`, `queue_manager`, `cashier`, `billing_officer`, `finance_manager`, `department_manager`, `public_health_officer`, `public_health_admin`, `disease_surveillance`, `support_agent`, `support_manager`, `implementation_lead`, `data_import`, `partner_admin`, `partner_review`, `country_regional_admin`) — seeder cleanup; harmless today.
6. **Set `OPESCARE_SUPPORT_EMAIL` on production** — unset today, and the old fallback pointed every email footer at the no-reply mailbox. Footers now omit the "write to" sentence until a real address is set.
7. **Two billing roles are unseeded** — `refund_approver` and `wallet_ops` are named in `config/staff_capabilities.php` but absent from `RolesSeeder` and from production `roles`. Until seeded, refund sign-off is held by `finance_manager` only. Decide whether these roles exist at all.
8. **Five terminology defects** need a human decision each on `/admin/terminology/gaps` (`L01XX14`, `10^9/L`/`x10^9/L`, `IM`/`intramuscular`, `Td`/`TD`, four group-level ATC codes).

## Dashboards (from DASHBOARD_COMPLETENESS_AUDIT.md, 2026-09-14)

| # | Item | Size | Status | Commit | Live |
|---|---|---|---|---|---|
| D-B1 | 10 roles bounced by `EnsurePortalAccess::PORTAL_ROLES` (dead dashboards) | S | **live** | see git | yes — 4 human roles refiled, 3 removed-module portals land on `portal.unavailable`, device roles stay denied on purpose |
| D-B2 | Route the finished patient-search screen (`identify_patient`, 30+ roles) | S | **live** | `e9856a8`+`505d31e` | yes — Routed at `/facility/search`, linked in 16 human sidebars (not device_kiosk/lite/demo_access). **Audit mislabel corrected:** it is a general staff search — facilities, documents, medicines, lab tests, partners, messages; the patient branch was deleted in S7 — so no `identify_patient` gate. That lookup already lives on the `/facility` landing |
| D-B3 | Dead `PatientPortalController` methods with missing views | S | partial (live) | `1e16428` | `PharmacyPortalController@inventory` (unrouted alias of `stock()`) removed. `PatientPortalController` `referrals()`/`surveys()`/`reportLostCard()` still pending — need views + routes in `routes/web.php`, which the queue agent holds |
| D-B4 | Lab desk — `lab_results` capability gates nothing | L | **live** | `a46baae` | `/facility/lab` 8 routes: worklist (STAT→urgent→routine, oldest first), result entry, correction as a NEW row (`[corrects:uuid]`, original byte-identical), validation = order `verified` with self-validation refused, critical → `CriticalValueAlert`. Flags via `ResultFlagResolver` on `reference_ranges` (the one authority patient + clinician share). 9 tests |
| D-B5 | Sample-collection worklist | M | **live** | `a46baae` | `/facility/lab/collection` (`sample_collection`); `document_specimen_events` deliberately NOT written — it FKs `official_documents`, a document trail not an order trail |
| D-B6 | Pharmacy stock operations (348 facilities, one screen) | M | **live** | `a46baae` | `/pharmacy/stock/*` 12 routes (GET/POST only): batches + expiry, append-only movements ledger (model refuses update/delete), reorder rules → below-minimum, goods receipt (supplier/PO → batch + movement, one transaction). Expired lots never reach the patient finder; unknown expiry stays unknown. Reuses the existing supply-chain tables (all had 0 rows) + `pharmacy_stock` capability; one additive migration (`inventory_items.medicine_id`). Left out: PO creation, supplier edit, recall UI. 8 tests |
| D-B7 | Guard `portals.listing.edit` behind an approved claim | S | **live** | `e9856a8`+`505d31e` | yes — `@if(approvedListingFor)` → "My facility listing", else → "Claim your listing" (`portals.listing.claims`), in pharmacy_manager + lab_manager |
| D-B8 | Patient chart — `clinical_chart` gates nothing (largest gap) | L | **live** | `a46baae` | `/facility/patients/{id}`, 9 tabs, GET only, exact Health ID. Gate is the existing `ClinicalRelationshipGate` (patient-centred, 5 bases; consent switch → preview scope); no basis → 404 + `denied` access row; every tab open access-logged; provenance badge on every vital; NO band words for under-16/unknown DOB; DOB never shown; `fhir_partner_*` deliberately not read (open clinical decision). 10 tests |
| D-B9 | Blood-request creation on web (13 empty desks) | M | **live** | hotfix | yes — `/facility/blood-requests/new` + POST via the one `BloodRequestService` writer, `blood_inventory` gate, session facility only, 8 tests. **Found:** `Notify::toFacilityStaff` was given `care_facilities.id`, which matches staff on `facilities.id` — zero recipients, no event ever, web or mobile. Fixed to the tenant bridge `care_facilities.facility_id`; the incomplete test is now a real assertion |
| D-B10 | Billing / cashier desk | L | **live** | `a46baae` | `/facility/billing` 16 routes (`billing`; refund sign-off `refund_approve`, new capability): invoices from the facility catalogue or an explicit integer XAF (never guessed), payments (cash needs the cashier's own open session; MoMo/Orange via the existing `PaymentChargeService`), overpayment refused, void keeps the row, refund = request → approve by a DIFFERENT person, session close records variance as-is. `BillingIsolationGuardTest` holds care↔billing separation both ways; all three care-never-gated tests pass. Left out: payment plans (two competing tables), PDF receipt (HTML only). `refund_approver`/`wallet_ops` roles are named in the capability map but NOT seeded. 14 tests. `StaffRoleMatrixTest::SURFACES` now registers triage/queue/lab/collection/chart/billing — it immediately caught `records_officer` holding `billing` with no link (fixed) |
| D-B11 | Link compliance/security/privacy sidebars to their built desks | S | **live** | `1e16428` | yes — 4 sidebars rewritten (security_officer 5 links, emergency_access_reviewer 3, compliance_audit 4, privacy_officer 5). `data_steward` NOT done: not platform tier, the desks would 403 — needs a decision |
| D-B12 | Link 12 orphan routes; fix duplicate developer links | S | **live** | `1e16428` | yes — admin nav: 3 security children + directory governance; patient: notifications + Refer & Earn; developer: webhook deliveries; dup links fixed in `c51d586`. Not done: `portals.listing.claims` (pharmacy/lab sidebars — queue agent territory) |
| D-B13 | Delete orphan `demo_access` sidebar; bijection test | S | **live** | `1e16428` | yes — `demo_access` KEPT — it is a seeded profile. `SidebarProfilesAreCompleteTest` guards role→partial (the silent-blank case). Found: 16 seeded `dashboard_profiles` no role uses — seeder cleanup, not dashboards |
| D-B14 | `@capability` guard on `multi_facility_admin` team link | S | **live** | `1e16428` | yes — wrapped in `@capability('facility_team')` |
| D-B15 | Insurance + NGO: build or land on `portal.unavailable` | decision | **resolved by D-B1** | `c51d586` | yes — insurance, lite and healthorg roles now land on `portal.unavailable` explicitly instead of `/login`. Building those modules remains out of v1 scope (frozen by decision, see V1 launch scope) |

## Email templates (Phase 4, 2026-09-14)

| Item | Status | Commit | Live | Evidence |
|---|---|---|---|---|
| One branded email design system for all 10 senders (3 mailables + 7 notifications), multipart HTML+text, EN/FR by recipient locale | **live** | `e9856a8` | yes | `#0F4C81` brand bar + wordmark, VML button, light-only colour scheme; `TransactionalEmailDesignTest` renders every sender ×2 locales through the array transport (23 tests, 914 assertions); previews in `storage/app/private/email-previews/` |
| `OpesCareNotificationMail` was TEXT-ONLY (no HTML body at all) | **live** | `e9856a8` | yes | now the shared layout, multipart |
| Off-brand `emails/base.blade.php` (emerald/sky on slate) | **live** | `e9856a8` | yes | adapter onto the new layout; old otp/reset/notification views deleted |
| Hardcoded English in all 7 `toMail()` bodies | **live** | `e9856a8` | yes | `lang/{en,fr}/mail.php`; `RecipientLocale` = stored `preferred_language` → `HasLocalePreference` → explicit → app; break-glass raised from an EN session lands in FR (tested) |
| Broken links: `/portals/patient/health-id` (no such page), `/portals/patient/logs` | **live** | `e9856a8` | yes | all links `route()`; test asserts no `/portals/` and all `https://` |
| PHI in subjects (Health ID in emergency alert; child's name in family event) | **live** | `e9856a8` | yes | subjects scrubbed; test asserts no Health-ID pattern / diagnosis / result / medication |
| **Found on review — migrated notifications sent with NO recipient** | **live** | `e9856a8` | yes | MailChannel sends a returned Mailable as-is; every `notify()` threw `An email must have a "To"`. Agent tests missed it (they used `Mail::to()`). `ComposedMail::forNotifiable()` + `NotificationsReachTheirRecipientTest` through the REAL channel; 5 family/invite tests that failed now pass |
| **Found on review — footer told patients to write to the no-reply mailbox** | **live** | `e9856a8` | yes | `support_email` fell back to `MAIL_FROM_ADDRESS` (`noreply@opescare.cloud` on prod, support unset). Fallback removed in config + 3 templates; sentence omitted until `OPESCARE_SUPPORT_EMAIL` is set (owner action #6) |
| Not verifiable here | — | — | — | Outlook/Gmail rendering (no client available); logo image only resolves after deploy; `OpesCareNotificationMail` callers still pass English bodies (chrome bilingual, body not) |

## Phase plan

- **Phase 1** (in-flight work) — done.
- **Phase 2** (audit blockers + ops) — done except S3, which is `owner`.
- **Phase 3** — queue system (S8), S5–S7, S9 — agents dispatched 2026-09-14. M4 waitlist after S6 dedupe.
- **Phase 4** — email templates DONE (pending gate). Remaining dashboards: D-B4 lab desk (L), D-B5 sample collection (M), D-B6 pharmacy stock ops (M), D-B8 patient chart (L), D-B9 blood-request creation (M), D-B10 billing (L), D-B3 rest (S), M4 waitlist, then final deploy.
