# Laboratory dashboard — audit 2 (2026-09-15)

Scope: a STANDALONE laboratory ("Laboratoire Demo Akwa") — logins
`demo.lab.manager`, `demo.lab.scientist`, `demo.lab.collection`, plus
`demo.labtech` at the hospital. Yardstick: `docs/DASHBOARD_SCOPE.md`.
Follows `docs/LAB_DASHBOARD_AUDIT.md` (L-1, L-2, L-4, L-5, L-7 already built).
Prod (finder.opescare.cloud) checked GET-only with a cookie jar after form
login; local checks against `opescare_finder` (migrated first — it was missing
`2026_09_14_140000_add_publication_columns_to_lab_test_availability`).

## PLAN (ranked) and status

| # | Item | In scope? | Status |
|---|---|---|---|
| 1 | **Critical values can never be closed.** Nothing in `app/` sets `critical_value_alerts.acknowledged = true` (`CriticalValueService::acknowledgeAlert` has no caller; the clinician inbox acknowledge writes `notification_events`, not the alert). The dashboard tile "not yet acknowledged" can only grow, and its hint claimed the clinician closes it from the inbox — a lying hint. For a patient-requested order `ordered_by` is the lab user, so no clinician exists at all. | IN — safety | **FIXED**: lab call-back record (named person + note), one-way latch, facility-scoped, `POST /facility/lab/alerts/{id}/callback`; hint strings corrected |
| 2 | **No patient-facing web page searches lab tests.** `GET /api/v1/care-map/labs/test-search` exists; `/network/lab-test-finder` was 404 on prod; no `care_map` view used it. | IN | **FIXED**: `/network/lab-test-finder` over the same `LabTestSearchService` (same provenance gates) |
| 3 | **The manager cannot see what the finder publishes** (published count, last published, unlinked). Only the catalogue page hinted at it. | IN | **FIXED**: "On the public lab-test finder" panel on `/facility/lab/dashboard` read from `lab_test_availability` itself |
| 4 | **Lab with no orders yet** sees a grid of zeros indistinguishable from a quiet day. | IN | **FIXED**: first-run panel naming the three ways work arrives |
| 5 | Lab roles land on `/facility` = generic staff dashboard (Today's Appointments 0 / In Queue 0 / Pending Referrals 0 / Open Invoices 0 — verified on prod as demo.lab.manager). Lying empty KPIs for a lab. | IN | **Shared-file change for the lead** (StaffPortalController is the hospital auditor's) |
| 6 | `lab_validator` is not in `FacilityStaffInvite::INVITABLE_ROLES` — a lab manager cannot invite the second signer the validation step needs (lab_scientist / labtech / sample_collection / lab_manager are invitable). | IN | **Shared-file change for the lead** (owner decision: validator is a signing role) |
| 7 | Lab-test finder page has no nav link in `layouts/finder.blade.php` (medicine + blood finders do). Reachable today only from the lab dashboard's "View the public finder" button. | IN | **Shared-file change for the lead** |
| 8 | Orders from OTHER facilities' clinicians never reach a standalone lab: `MobileDoctorLabOrderController::store` writes `facility_id` = the clinician's own facility; no performing-lab field. | OUT — schema decision (was L-3) | open, owner |
| 9 | Clinician lab-test picker (`MobileDoctorLabOrderController::catalogue`) reads `lab_test_availability` without `reportedByRealSource()` — seeded rows offered to clinicians. | IN, not mine | listed for the lead |
| 10 | API disclaimer hard-coded English (`CareMapController::searchTests`, "Some tests may require…"). | IN, not mine | listed for the lead |
| 11 | Lab roles hold `blood_inventory` (`config/staff_capabilities.php:110`), so a standalone lab's sidebar shows Blood Bank + Blood Requests (both 200). The prior audit said they render nothing — that was wrong. | Owner decision | listed |
| 12 | Reagent stock (L-8), instrument interfaces, QC runs, reagent lots | OUT — DASHBOARD_SCOPE "is NOT for" | — |

## Every link and reachable page

Prod, logged in (all GET; POSTs verified locally by tests):

| Page | lab_manager | lab_scientist | labtech (hospital) | sample_collection | View on disk |
|---|---|---|---|---|---|
| `/facility` (generic) | 200 — hospital KPIs (item 5) | 200 | 200 | 200 | portals/staff/index |
| `/facility/search` | 200 | 200 | 200 | 200 | portals/staff/search |
| `/facility/lab/dashboard` | 200 | 200 | 200 | not linked | portals/lab/dashboard |
| `/facility/lab/requests` | 200 | 200 | 200 | not linked | portals/lab/requests |
| `/facility/lab` (+ `orders/{id}`) | 200 | 200 | 200 | not linked | portals/lab/index, show |
| `/facility/lab/collection` | 200 | 200 | 200 | 200 | portals/lab/collection |
| `/facility/inventory/blood`, `/facility/blood-requests` | 200 | 200 | 200 | not linked | (item 11) |
| `/facility/messages` | 200 | 200 | 200 | 200 | — |
| `/facility/catalogue` | 200 | not linked | not linked | not linked | FacilityCatalogueController |
| `/facility/settings`, `/facility/team`, `/facility/team/overview*` | 200 | not linked | not linked | not linked | — |
| `/portals/listing/claims` | 200 | — | — | — | care_map/listing |
| `/network/lab-test-finder` | **404 before this pass** | | | | care_map/labs/test_finder (new) |

**No sidebar link 403s** for any of the four roles (every sidebar href fetched on
prod). No URL-only route in `routes/lab.php` / `routes/lab_manager.php`: GETs
are linked from the sidebar or the dashboard/worklist; POSTs are forms; the
report is a button on the order page. New callback POST is a form on the
dashboard; the new public page is linked from the dashboard.

## Order lifecycle, end to end

| Step | Exists? | Evidence |
|---|---|---|
| Patient request → approve / decline (patient told) | yes | `LabDeskService::approveRequest/declineRequest`, `/facility/lab/requests` |
| Clinician at ANOTHER facility → this lab | **no** | item 8 |
| Clinician at THIS facility → worklist | yes (app only) | `MobileDoctorLabOrderController::store` |
| Collection | yes | `/facility/lab/collection` |
| Sample rejection (reason, final, before results) | yes | `LabManagerDeskService::reject`; `LabManagerDeskTest` |
| Result entry, derived flag, append-only correction | yes | `LabDeskWebService::writeRow/correct` |
| Critical value raised | yes | `LabDeskWebService::raiseAlert` |
| Critical value closed | **no → fixed** | item 1 |
| Release → patient bell/push + guardians + ordering clinician | yes | `LabDeskService::notifyReleased`. For patient-requested orders the "ordering clinician" is the approving lab user (no clinician exists) — noted |
| Second-person validation; self-validation refused | yes | `LabDeskWebService::validate` (`self_validation` 409) |
| Printable report (Secure sheet, QR) | yes | `GET /facility/lab/orders/{id}/report` |
| Catalogue → public finder | yes (projector) — **not visible to the manager → fixed** | item 3 |

## Other checklist items

- **Back-links:** dashboard/requests breadcrumb → worklist; order page → worklist. OK.
- **Lying empty states:** the critical hint (fixed); first-run zeros (fixed); `/facility` generic KPIs (item 5).
- **Hard-coded English:** API disclaimer (item 10); `'Specimen: '` in stored `notes` (deliberate, prior audit). New page/panel strings all in `lang/{en,fr}/lab_manager.php`; i18n audit 1:1 OK.
- **`facility_id` from input:** none. New callback resolves the alert through `lab_results → lab_orders.facility_id = PortalContextService::facilityId()`; another lab's alert 404 (tested).
- **Cross-facility leaks (local tests):** alert of another lab → 404; seeded/unlinked finder rows not shown (tested).
- **Unbounded lists:** worklist/collection paginated; requests `MAX_ROWS`; dashboard critical list `limit(20)`; finder service `MAX_ROWS = 300`, page renders first 30. OK.
- **Payment:** nothing added reads billing.

## Files changed (this pass)

- `app/Services/Lab/LabManagerDeskService.php` — `hasAnyWork()`, `publication()`, `findCriticalAlert()`, `recordCriticalCallback()`, `CALLBACK_TAG` (additive)
- `app/Http/Controllers/MedicalId/LabManagerDeskController.php` — dashboard passes `publication`, `hasAnyWork`, `canCatalogue`; new `criticalCallback()`
- `routes/lab_manager.php` — `POST /facility/lab/alerts/{id}/callback` (`portals.lab_manager.critical_callback`, `capability:lab_results`)
- `resources/views/portals/lab/dashboard.blade.php` — first-run panel, publication panel, call-back form per alert, validation-error flash
- `app/Http/Controllers/CareMap/LabTestFinderController.php` — NEW (invokable)
- `resources/views/care_map/labs/test_finder.blade.php` — NEW
- `routes/marketing.php` — `GET /network/lab-test-finder` → `public.network.lab-test-finder`
- `lang/en/lab_manager.php`, `lang/fr/lab_manager.php` — new keys; `kpi_hint_critical` and `critical_subtitle` reworded (were untrue)
- `tests/Feature/Lab/LabDashboardAudit2Test.php` — NEW, 6 tests
