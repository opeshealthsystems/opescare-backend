# Diagnostic centre dashboard — audit 2 (2026-09-15)

Scope: `diagnostic_center` (15 listings). This is a combined laboratory and imaging
centre, often with a consulting doctor. Logins `demo.diagnostic.admin`
(facility_admin) and `demo.diagnostic.lab` (lab_manager) at "Centre de Diagnostic
Demo". Yardstick: `docs/DASHBOARD_SCOPE.md`. This audit covers only what is
specific to a COMBINED centre. Lab and imaging findings are listed once under
"Inherited" and were not re-investigated. Prod (finder.opescare.cloud) was checked
GET-only with a cookie jar after form login.

## PLAN (ranked) and status

| # | Finding | In scope? | Status |
|---|---|---|---|
| D-1 | **Imaging exams appear twice on the public finders, once as lab tests.** `LabTestSearchService::searchTests` (lab-test finder) has no imaging filter. A centre's "US Abdomen" (LOINC, `common_reason` "Imaging: …") shows on `/network/lab-test-finder` as a laboratory test AND on `/network/imaging-finder`. A combined centre is the worst case, because both kinds come from one tenant. Same issue as imaging I-2. | IN | **Shared-file change** (LabTestSearchService is lab-owned). Diff 1 below uses the new `ServiceMix` rule. |
| D-2 | **The lab manager dashboard counts imaging exams as lab work.** `LabManagerDeskService::publication()` counts every active LOINC catalogue item as `catalogue_tests` and every published portal row as `published`, imaging exams included. The "On the public lab-test finder" panel therefore overstates what the lab-test finder shows (and after D-1, the numbers would disagree with the finder itself). The worklist KPIs are correct: they read `lab_orders` only, and nothing creates `imaging_orders` (I-5). | IN | **Shared-file change** (lab-owned). Diff 2. |
| D-3 | **One catalogue feeds two finders, and nothing said which.** Both roles edit one list. A LOINC row goes to the lab-test finder or the imaging finder depending on the dictionary. An ICD-10/ICHI row goes to no finder at all. The screen gave no sign of this. | IN | **FIXED**. The catalogue shows a per-row badge ("Lab-test finder" / "Imaging finder") and, when the list holds imaging exams, a banner with catalogue and published counts for each finder plus links to both finders. Rule: `App\Services\DiagnosticCentre\ServiceMix` (dictionary `common_reason` prefix only, no title guessing). |
| D-4 | **Two roles answer for the catalogue, and the last save won silently.** `service_catalogue` = facility admins + `lab_manager`. `FacilityCatalogueController::update` clears every field it does not receive, and it had no concurrency check. If the admin changes the imaging price while the lab manager has the same row open, the manager's save wiped the admin's price and local name without any warning. | IN | **FIXED**. The edit form sends `seen_updated_at`. A save made against an older version is refused with a message and nothing is changed. A request without the field keeps the old behaviour. Limit: one-second granularity. There is no `updated_by` column, so "who last changed it" cannot be shown without schema (owner decision). |
| D-5 | **Settings also has two owners.** Both roles get 200 on `/facility/settings` (identity, location, hours, photo). `FacilitySettingsController` has no stale-edit check either. | IN | **Shared-file change** (FacilitySettingsController is owned). Same pattern as D-4, not diffed here; the owner of that file should apply it. |
| D-6 | **Lab manager landing at an imaging-heavy centre.** Login lands on `/facility` (generic "Staff Clinical Portal" KPIs: appointments/queue/referrals/invoices), the same as lab item 5. The lab dashboard itself only talks about lab work. For a centre whose volume is mostly imaging, neither page shows the imaging side, and no imaging desk exists (I-5). | IN | Plan only. (a) Lead lands lab roles on `/facility/lab/dashboard` (lab item 5). (b) After D-2, add a one-line "Imaging exams published: N — View the imaging finder" to the lab dashboard publication panel (diff 2 provides `imaging_published`). (c) A real imaging landing needs I-5 (imaging request desk). |
| D-7 | **A diagnostic package (bundle price) cannot be represented.** `facility_catalogue_items` is one row = one (system, code) with one `price_xaf`. The only "panels" available are standard LOINC panel codes (e.g. a CBC panel code, one price). A centre's own "Bilan prénatal" (NFS + glycémie + échographie obstétricale, 25 000 FCFA) has no code, no row, no bundle price, and cannot be found or booked as one thing. Inventing a code is forbidden. | IN (being findable, prices) | **Plan only, no schema built.** See "Package plan" below. |
| D-8 | **Results of a combined visit reach the patient as two unequal things.** Lab: each released lab order renders on demand as a Secure-sheet PDF (`PatientDocumentService::LABORATORY_REPORT`, one per order). Imaging: nothing (I-6). A patient who had a glucose test and an ultrasound in one visit gets one lab PDF and no imaging document. There is no visit-level grouping. | IN | Inherited I-6 for imaging. The combined-specific part (one visit, one bundle of documents) depends on D-7 and I-5/I-6; plan only. |
| D-9 | facility_admin at a diagnostic centre cannot open the lab desk (`/facility/lab*` 302 → `/admin`). | Owner decision | Correct role separation (no `lab_results` capability). Listed only: a small centre where the admin is also the biologist needs a second posting, not a wider admin role. |

### Package plan (D-7) — not built
1. New table `facility_catalogue_packages` (tenant `facility_id`, `local_name`, `price_xaf` integer nullable, `is_active`, retire-never-delete like the catalogue), plus `facility_catalogue_package_items` (package_id, catalogue item id). Members must be existing catalogue rows of the same tenant, so every part keeps a real code.
2. Catalogue screen gets a "Packages" tab (both roles, same `service_catalogue` capability, same stale-edit guard).
3. Public: on the facility profile and in both finders, "Included in package X — N FCFA" on each member row. No package code is published to FHIR/LOINC, because there is none.
4. Booking: a lab test request (`LabDeskService::approveRequest` path) naming a package creates one order per lab member. Imaging members wait on I-5.
5. Documents: group by the package's request id once D-8/I-6 exist.

## Every link and reachable page (prod, GET, 2026-09-15)

| Page | admin | lab_manager | View on disk |
|---|---|---|---|
| login lands on | `/admin` 200 | `/facility` 200 (generic KPIs, D-6) | — |
| `/facility/team/overview` (+ departments, invoices, reports, wards) | 200 | 200 | portals/facility overview |
| `/facility/catalogue`, `/facility/catalogue/browse?q=echographie` | 200 | 200 | portals/facility/catalogue/index, browse |
| `/facility/settings`, `/facility/team` | 200 | 200 | (D-5) |
| `/facility/queue` | 200 | 403 (not linked for the lab manager; correct) | — |
| `/facility/lab/dashboard`, `/facility/lab`, `/facility/lab/requests`, `/facility/lab/collection` | 302 → /admin (D-9) | 200 | portals/lab/* |
| `/facility/inventory/blood`, `/facility/blood-requests` | not linked | 200 (inherited lab item 11) | — |
| `/facility/messages`, `/facility/search`, `/portals/listing/claims` | — | 200 | — |
| `/notifications`, `/help`, `/professional/profile`, `/professional/facilities` | 200 | 200 | — |
| `/network/lab-test-finder`, `/network/imaging-finder` | 404 on prod (not deployed); both registered locally | | care_map/labs/test_finder, public/imaging/finder |

No sidebar link returned 403 or 404 for either login. The only 403 (`/facility/queue`
as lab manager) was a probe, not a link. Nothing new is URL-only: the catalogue
banner links to both finders, and the catalogue is in both sidebars.

## Workflow end to end (combined centre)
1. **Publish:** either role adds a lab LOINC and an imaging LOINC with whole-franc prices. Both project to `lab_test_availability`. The row badge now says which finder each reaches (D-3). ICHI imaging procedures reach neither finder (I-3).
2. **Find:** lab test → lab-test finder (correct). Imaging exam → imaging finder (correct) AND lab-test finder (D-1).
3. **Manage counts:** the lab dashboard overstates lab publication (D-2). The catalogue banner is correct.
4. **Request:** lab request desk works (inherited lab audit). Imaging has no way in (I-5). Packages: none (D-7).
5. **Result → patient:** lab PDF per order yes. Imaging no (I-6). No visit grouping (D-8).

## Other checklist items
- **`facility_id` from input:** none. `ServiceMix::forFacility` is called with `PortalContextService::facilityId()`. The stale-edit guard runs after `findForThisFacility` (cross-facility still 404, tested).
- **Cross-facility leak (local test):** a lab manager at another diagnostic centre gets 404 on an update of this centre's row, and the price is unchanged.
- **Lying empty states:** the new banner shows only when imaging exams exist, and counts come from the published rows themselves.
- **Hard-coded English:** none added. 7 new keys in `lang/{en,fr}/catalogue.php`; `scripts/i18n-audit.php` ALL FILES PASS.
- **Unbounded lists:** the catalogue stays paginated (50). ServiceMix runs 4 counts with no lists.
- **Payment:** nothing added reads a price to gate anything.
- **Icons:** Lucide `scan-line`, `flask-conical`.

## Inherited (listed once, not re-investigated)
- Lab audit 2: #5 lab roles land on generic `/facility` KPIs; #6 `lab_validator` not invitable; #7 lab-test finder has no header nav link; #8 other facilities' clinicians cannot order at this lab; #9 clinician picker ignores provenance; #10 API disclaimer in English; #11 Blood Bank/Requests in lab sidebar (owner).
- Imaging audit 2: I-2 (= D-1 here); I-3 ICHI imaging never published; I-4 no imaging role; I-5 no imaging request desk; I-6 no imaging report to patient documents; I-7 Laboratory/Beds tiles on admin overview (add `diagnostic_center` next to `imaging_center` in the C-5 type list); I-8 catalogue publishes only when the tenant is linked to a listing.

## OUT of scope
Radiology reporting, PACS/DICOM viewing, modality worklists, instrument interfaces, QC, reagent lots (DASHBOARD_SCOPE "is NOT for").

## Removals for the owner (not done)
None proposed beyond the inherited lab #11 / imaging I-4 sidebar entries.

## Files changed (this pass)
- `app/Services/DiagnosticCentre/ServiceMix.php` (new)
- `app/Http/Controllers/MedicalId/FacilityCatalogueController.php`: `index` passes `mix`; `update` has an optional `seen_updated_at` stale-edit guard (additive)
- `resources/views/portals/facility/catalogue/index.blade.php`: mix banner, per-row finder badge, hidden `seen_updated_at`
- `lang/en/catalogue.php`, `lang/fr/catalogue.php`: `mix_*` (6) + `flash_stale_edit`
- `tests/Feature/DiagnosticCentre/DiagnosticCentreAudit2Test.php` (new, 5 tests)

## Shared-file changes for the lead

**Diff 1 (D-1)** `app/Modules/CareMap/Services/LabTestSearchService.php`, in `searchTests()` after `->available()`:
```php
            // An imaging exam is published on the imaging finder, not here.
            ->whereNotIn('loinc_code', \App\Models\ClinicalCodeDictionary::query()
                ->where('system', 'LOINC')
                ->where('common_reason', 'like', \App\Services\Imaging\ImagingExamSearch::IMAGING_REASON_PREFIX . '%')
                ->select('code'))
```
The lab owner should add a test: an imaging LOINC row is absent from `/network/lab-test-finder` and present on `/network/imaging-finder`.

**Diff 2 (D-2)** `app/Services/Lab/LabManagerDeskService.php::publication()`: keep the existing keys, but make them lab-only and add the imaging count:
```php
        $mix = app(\App\Services\DiagnosticCentre\ServiceMix::class)->forFacility($facilityId);
        // ... in both return arrays:
        'catalogue_tests'   => $mix['lab_catalogue'],
        'published'         => $mix['lab_published'],   // linked branch only; unlinked stays 0
        'imaging_published' => $mix['imaging_published'],
```
Then in `resources/views/portals/lab/dashboard.blade.php`, when `imaging_published > 0`, show one line using a new `lab_manager` key (e.g. "Imaging exams published: :count") linking to `route('public.network.imaging-finder')`.

**D-5**: apply the D-4 `seen_updated_at` pattern to `FacilitySettingsController::updateIdentity/updateLocation/updateHours` (the tenant or listing `updated_at`).

**I-7 for diagnostic centres**: wherever the C-5 type list hides the Beds/Wards tiles for `imaging_center`, add `diagnostic_center`.
