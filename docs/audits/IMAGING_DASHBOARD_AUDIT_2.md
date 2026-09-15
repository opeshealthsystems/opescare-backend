# Imaging centre dashboard — audit 2 (2026-09-15)

Scope: `imaging_center` (32 listings in `care_facilities`). Logins
`demo.imaging.admin` (facility_admin) and `demo.imaging.tech` (labtech) at
"Centre d'Imagerie Demo Bonanjo". Yardstick: `docs/DASHBOARD_SCOPE.md`.
Radiology reporting, PACS, DICOM storage/viewing and modality worklists are OUT
of scope and were not built. Prod checked GET-only on finder.opescare.cloud.

## PLAN (ranked) and status

| # | Finding | Owner | Status |
|---|---|---|---|
| I-1 | Patients cannot look for an imaging exam. Imaging studies ARE in the dictionary (LOINC / RSNA Playbook, `common_reason` "Imaging: CT / MRI / Ultrasound / X-ray…", about 7,300 rows locally; ICHI also holds imaging procedures). `LabCatalogueProjector` sends every LOINC catalogue item, so an imaging centre's exams do go into `lab_test_availability`. But the only public page over those rows is the **lab-test** finder (droplet icon, "laboratory has published this test"). No page lists imaging exams under their own name. | this pass | **FIXED (needs route line)** — `/network/imaging-finder` reads the same projected rows through the same provenance gates, filtered to imaging LOINC codes. No new schema. |
| I-2 | Wrong label on imaging exams: an imaging centre's "Échographie abdominale" appears on `/network/lab-test-finder` as a laboratory test, and the lab finder's empty-state link points at laboratories. | lab owner (LabTestSearchService / lab finder view) | OPEN — suggest the lab finder excludes `common_reason LIKE 'Imaging:%'` codes, or shows them under a separate heading. Not changed here. |
| I-3 | ICHI imaging procedures in the catalogue are never published anywhere. The projector only takes LOINC and `FacilityCatalogueController::publish()` returns early for other systems. The centre sees them on its list but patients never do. | owner decision | OPEN — either tell imaging centres to pick the LOINC (Playbook) code, or extend projection to ICHI (that needs a target table, i.e. new schema, so it is planned only). |
| I-4 | No imaging role. The tech is a `labtech`, so the sidebar shows Laboratory dashboard, Patient requests, Laboratory desk (ordered/collected/validated worklist), **Sample collection** and **Blood Bank / Blood requests**. None of these fit an imaging centre, and the page says "Laboratory worklist" at the imaging centre. It does not lie (queues are empty), but it is the wrong tool. | owner decision + lead (seeder/capabilities/sidebar) | OPEN — diff below. |
| I-5 | An imaging request has no way in. `imaging_orders` exists (1 row locally) and is only *read* by the mobile doctor chart (`/orders?type=imaging`) and the results inbox. Nothing in the web or API code creates an `ImagingOrder`, and no facility desk lists them. | owner decision | OPEN — in scope ("receive, accept, fulfil, close"). Plan: an imaging request desk over `imaging_orders` filtered by the portal-context `facility_id`. The existing table is enough, so no schema change. Not built this pass because it needs the capability/sidebar/route shared files. |
| I-6 | A report or result has no way back to the patient. `RadiologyReportService` (draft/finalize/distribute) is never called. There is no staff upload that attaches a PDF report to the patient's documents. The Passport `RadiologyPage` reads reports that nothing writes, so it is honestly empty. | owner decision | OPEN — in scope: attach a PDF report to documents. Out of scope: writing the report and any viewer. |
| I-7 | The admin overview (`/facility/team/overview`) shows **Laboratory** (open orders / turnaround) and **Beds** ("no wards on record") tiles, plus Departments / Wards links. | inherited (HC-4 / C-5) | OPEN — the empty states are honest. Add `imaging_center` to the type list C-5 hides them for. |
| I-8 | Prod catalogue for the demo centre holds 0 items, and the local dev DB has no imaging demo tenant. A centre publishes nothing until it adds items AND its tenant is linked to a listing (`care_facilities.facility_id`). | inherited (two facility registers) | OPEN — linking is an admin decision. |

## Every link and reachable page (prod, GET, 2026-09-15)

| Login | Page | Status |
|---|---|---|
| admin | login → `/admin` (facility-admin portal) | 200 |
| admin | /facility/team/overview (+ /departments, /invoices, /reports, /wards), /facility/catalogue, /facility/catalogue/browse?q=ultrasound (dictionary returns Ultrasound terms), /facility/queue, /facility/settings, /facility/team, /notifications, /professional/profile, /professional/facilities, /help | all 200 |
| admin | /facility/lab | 302 → /admin (no lab capability; correct) |
| tech | login → `/facility` | 200 |
| tech | /facility/lab (+ ?status=ordered/collected/in_progress/resulted/validated), /facility/lab/dashboard, /facility/lab/requests, /facility/lab/collection, /facility/inventory/blood, /facility/blood-requests, /facility/messages, /facility/search, /notifications | all 200 (lab-shaped, see I-4) |
| tech | /facility/team/overview, /facility/catalogue | 403 (correct: tech cannot edit prices) |
| public | /network/lab-test-finder, /network/imaging-finder | 404 on prod (lab finder not deployed yet; imaging finder is new) |

No dead links. No URL-only pages among the sidebar targets. The only
URL-only page is the new `/network/imaging-finder`, until the lead adds the
route and a nav link (below).

## Workflow end to end

1. **Publish:** admin → Our services → browse dictionary → add an imaging LOINC code with a whole-franc price. For LOINC this works and projects (I-1). For ICHI it does not publish (I-3).
2. **Find:** patient → `/network/imaging-finder?q=échographie` → centre, price in FCFA, call / directions / profile (this pass).
3. **Request arrives:** there is no path (I-5). In practice this means a phone call or a walk-in through the queue.
4. **Result reaches the patient:** there is no path (I-6).

## Other checklist items

- `facility_id` from input: none in the new code. The finder is public and reads published rows only. Catalogue writes use the portal context (inherited, already audited).
- Cross-facility / provenance: the public finder shows only real-source + `available` + `listing_status = active` rows, and a test covers seeded, retired and pending listings. No patient data is involved.
- Unbounded lists: the search is capped at 200 rows; the page renders the first 30 (same as the lab finder).
- Hard-coded English: none in new files. `lang/{en,fr}/imaging_finder.php` are 1:1 and the i18n audit passes.
- Lying empty states: the new page says "no imaging centre has **published** this exam", never "unavailable".
- Honest limit of I-1: an imaging code first imported by the lab sweep keeps its lab `common_reason` and is not recognised as imaging. Nothing is guessed from titles.

## IN / OUT of scope

- IN: a public imaging-exam finder (being findable); an imaging request desk (being reachable); a PDF report attached to patient documents; the role/sidebar fit.
- OUT (report, never build): radiology reporting templates, PACS, DICOM storage/viewing, modality worklists (DICOM MWL), equipment maintenance.

## Removals for the owner (not done)

- The labtech sidebar entries Sample collection and Blood Bank / Blood requests at an imaging centre. Hide them for `imaging_center`, or keep them.

## Files changed (this pass)

- `app/Services/Imaging/ImagingExamSearch.php` (new)
- `app/Http/Controllers/Imaging/ImagingExamFinderController.php` (new)
- `routes/imaging_finder.php` (new; not loaded until the lead adds the line below)
- `resources/views/public/imaging/finder.blade.php` (new)
- `lang/en/imaging_finder.php`, `lang/fr/imaging_finder.php` (new)
- `tests/Feature/Imaging/ImagingExamFinderTest.php` (new, 3 tests; it loads the route file itself)

## Shared-file changes for the lead

1. Load the route. Either `Route::middleware('web')->group(base_path('routes/imaging_finder.php'));` in AppServiceProvider next to `routes/marketing.php`, **or** paste this into `routes/marketing.php` after the lab-test finder:
   ```php
   Route::get('/network/imaging-finder', \App\Http\Controllers\Imaging\ImagingExamFinderController::class)
       ->name('public.network.imaging-finder');
   ```
   If pasted, delete `routes/imaging_finder.php` so the name is not registered twice.
2. Add a nav link wherever `public.network.lab-test-finder` is linked (finder header / network menu), using `__('imaging_finder.title')`.
3. Owner decision, the imaging role (I-4). In `database/seeders/RolesSeeder.php` ROLES, after `lab_scientist`:
   ```php
   ['radiographer',         'laboratory',              'labtech',              'Radiographer / Sonographer'],
   ```
   In `config/staff_capabilities.php`, add `'radiographer'` to `identify_patient`. Do **not** add it to `$lab` (that grants the lab desk). It also needs its own dashboard profile/sidebar once I-5 exists. Until then, re-pointing `demo.imaging.tech` in `DemoFacilityTypesSeeder` would give that account nothing to do.
