# Dental practice dashboard — audit 2 (2026-09-15)

Scope: a DENTAL PRACTICE (40 `care_facilities` rows typed `dental` locally; many more
"Cabinet Dentaire …" listings are typed `clinic`) using the Finder to be findable and run
its day. Demo tenant "Cabinet Dentaire Demo Bastos" (`facilities.type = dental`, seeded by
`DemoFacilityTypesSeeder`; not present in the local dev DB — prod only). Logins
demo.dental.{admin,doctor}@opescare.test. Yardstick: docs/DASHBOARD_SCOPE.md. Prod checked
GET-only (form login, cookie jar); fixes tested locally on `opescare_test_t5`.
Findings that apply to every facility are in CLINIC_DASHBOARD_AUDIT_2 / HOSPITAL_DASHBOARD_AUDIT_2
and are listed once under "Inherited".

## Plan (ranked)

| # | Item | Owner | Status |
|---|---|---|---|
| D-1 | Public search could not find a dentist: `CareMapSearchService` used case-sensitive `LIKE` on Postgres and had no type vocabulary. Prod: `query=dentist` / `dentiste` → 0 listings, `dentaire` → 1, `Dentaire` → 25 | dental auditor | **FIXED** (case-insensitive name/city + EN/FR type synonyms → `facility_type=dental`) |
| D-2 | Prices a practice publishes in `/facility/catalogue` (ICHI procedures, HL7 services) reached **no public page** — only LOINC lab tests are projected (lab finder). The catalogue form tells the admin "a price is information for patients" | dental auditor | **FIXED** (new "Published prices" card on `/care-map/facility/{id}` for linked, active listings; non-LOINC, active rows only, whole-franc FCFA, bounded 100) |
| D-3 | Doctor sidebar at a dental practice shows **Blood inventory** and **Blood requests** (both 200) — irrelevant for a practice that holds no blood | lead (sidebars/doctor.blade.php) | OPEN — shared-file change below |
| D-4 | Staff "book appointment" (`portals/staff/appointments_create`) offers general/follow_up/specialist/lab/pharmacy/emergency — **no `dental`**, although the patient booking form and `public.appt_type_dental` already have it | staff-desk owner (view rendered by StaffPortalController) | OPEN — shared-file change below |
| D-5 | Mobile directory `GET /api/mobile/facilities?q=` searches name/description/city only — "dentiste" does not match `facility_type=dental` (the `type=dental` filter itself works) | mobile owner (MobileFacilityController) | OPEN — shared-file change below |
| D-6 | No `dentist` role (RolesSeeder / config/staff_capabilities.php have none); a dental surgeon logs in as `doctor` and inherits triage + blood desk | owner decision | OPEN — see below |
| D-7 | Many "Cabinet Dentaire …" listings are typed `clinic` on prod (the `Dentaire` search shows clinic/hospital chips), so the `dental` chip under-counts | directory data review | OPEN — data, not code; never auto-retype |
| D-8 | Institutional dictionary: no ICHI term for crown, denture, orthodontics, whitening by name (`KAE.MK.AC Restoration of tooth` covers fillings/crowns by meaning). 17 ICHI `KAE.*` codes locally, **0 with a French title** | institutional data | REPORTED — codes not invented |
| D-9 | Catalogue browse without a system filter ranks ATC/HL7/ICD-10 first, so "dental" / "extraction" page 1 shows no procedure; with `system=ICHI` all appear | catalogue owner | OPEN (UX: a "Procedures" quick filter) |

## Prod status (GET, 2026-09-15)

| Login | Page | Status |
|---|---|---|
| dental.admin | /facility → 302 /admin; /admin, /facility/team/overview (+departments, invoices, reports, **wards**), /facility/settings, /facility/team, /facility/catalogue, /facility/catalogue/browse, /facility/queue, /professional/facilities, /notifications | 200 |
| dental.admin | /facility/appointments, /facility/prescriptions | 302 → /admin (not linked; inherited C-6) |
| dental.doctor | /facility, /facility/patients, /facility/triage, /facility/appointments (+create, +waitlist), /facility/messages, /facility/prescriptions (+new), /facility/queue, /facility/search, /facility/blood-requests, /facility/inventory/blood | 200 |
| dental.doctor | /facility/lab, /facility/catalogue | 403 (correct: no capability, not linked) |
| public | /care-map?facility_type=dental | 200, chip "Dental Clinic" present |

Prod still shows **Wards & beds** in the admin sidebar: the local type gate in
`partials/sidebars/facility_admin.blade.php` (hospital/maternity/health_center only) is not
deployed yet. Locally correct for `type=dental`.

## Dental-specific checks
- **Catalogue with whole-franc prices:** works. ICHI `procedure` rows are `offerable()`; found on prod browse: KAE.AE.AC examination, KAE.JE.AC / KAE.JK.AC extraction, KAE.JG.AC scaling/polishing, KAE.ML.AC root canal, KAE.MK.AC restoration, KAE.DN.AC implant, KAE.BA.BA dental X-ray; HL7 service-type 88 General Dental, 313 Dental Hygiene; SNOMED specialties (408444009 general dental practice, orthodontics, endodontics…). Local dental-matching counts: ICHI 26, ICD-10 98, SNOMED 11, LOINC 11, HL7 5. `price_xaf` integer.
- **Findable:** `dental` is a data-driven chip on `/care-map` and a `type=` filter on the mobile API. Free text was broken (D-1 fixed for web + API v1; D-5 mobile open).
- **Appointment types:** free string (`required|string|max:100`) everywhere; patient portal list is a hard-coded array including `dental`; staff create view lacks it (D-4).
- **Admin landing:** team overview shows a "Laboratory" block and "Beds … no wards on record, so no occupancy is shown" — honest empty states, but hospital-shaped (inherited C-5).
- **Doctor sidebar:** queue, triage, patients (consent-gated chart), appointments, waitlist, messages, prescriptions (analgesics/antibiotics — in scope), search; blood links irrelevant (D-3).
- **Public profile:** services (`care_facility_services`, edited in settings `_services` — other agent), hours, availability; **no photo** — `logo_path` / `cover_image_path` exist on CareFacility but the profile never renders them (inherited, all types).

## Security / data
- Fixes read no patient row. `FacilityPriceListService` scopes by the listing's own `facility_id` (route listing, never input); unlinked or inactive listing → empty; another facility's catalogue never shown (tested).
- Search synonyms are whole-query exact matches; public directory data only, not patient search.
- Unbounded: directory search loads all matches then paginates in PHP (inherited, CareMapController). Price list bounded to 100.

## IN / OUT of scope
- IN: catalogue + prices, public findability, appointments, queue, prescriptions, team, invoices.
- OUT (DASHBOARD_SCOPE "not"): dental charting/odontogram, treatment plans, radiograph storage/PACS. Not built, not proposed.

## Inherited (listed once)
C-3 no web referral desk; C-4 no prescription notification; C-5 hospital-shaped admin overview; C-6 silent 302 on role refusal; C-7 `?: 'English'` fallbacks after `__()` (also in staff appointments_create and doctor sidebar); photo not rendered on public profile; directory search not SQL-paginated.

## Owner decisions
- **D-6 `dentist` role.** Not added. Additive diff if wanted:
  - `database/seeders/RolesSeeder.php`: a `dentist` row mirroring `doctor` (`dashboard_profile_key` => `doctor`).
  - `config/staff_capabilities.php`: `'dentist' => ['clinical_chart', 'queue', 'schedule', 'prescribe', 'messaging']` (no `triage`, no `blood_inventory`).
  - `DemoFacilityTypesSeeder`: `'demo.dental.doctor@opescare.test' => ['dentist', 'Dental Doctor']`; mirror in the app TS capability map.
- **D-7** retyping dental-named `clinic` listings: human review only.

## Shared-file changes for the lead (verbatim)

D-3 `resources/views/partials/sidebars/doctor.blade.php` — wrap the existing blood inventory + blood requests links:
```blade
@php($__dFid = app(\App\Services\Portal\PortalContextService::class)->facilityId())
@php($__dType = $__dFid ? \App\Models\Facility::withoutGlobalScopes()->whereKey($__dFid)->value('type') : null)
@if(!in_array($__dType, ['dental', 'eye_clinic', 'laboratory', 'imaging_center', 'pharmacy'], true))
    {{-- existing @capability('blood_inventory') … blood requests link … @endcapability unchanged --}}
@endif
```

D-4 `resources/views/portals/staff/appointments_create.blade.php` — after the `specialist` option:
```blade
<option value="dental" {{ old('appointment_type') === 'dental' ? 'selected' : '' }}>
    {{ __('public.appt_type_dental') }}
</option>
```

D-5 `app/Http/Controllers/Api/Mobile/MobileFacilityController.php`, inside the `q` closure after the city clause:
```php
if ($synonymType = \App\Modules\CareMap\Services\CareMapSearchService::typeForQuery($q)) {
    $sub->orWhere('facility_type', $synonymType);
}
```

## Files changed (this audit)
- app/Modules/CareMap/Services/CareMapSearchService.php (D-1)
- app/Modules/CareMap/Services/FacilityPriceListService.php (new, D-2)
- resources/views/care_map/profile.blade.php (D-2 card)
- lang/en/finder_directory.php, lang/fr/finder_directory.php (+4 keys each)
- tests/Feature/Dental/DentalFinderTest.php (4 tests, 17 assertions)

## Removals for the owner
None.
