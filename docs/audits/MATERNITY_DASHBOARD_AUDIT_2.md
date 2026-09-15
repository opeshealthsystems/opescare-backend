# Maternity dashboard audit 2 — Maternité Demo Essos (2026-09-15)

Yardstick: `docs/DASHBOARD_SCOPE.md`. Logins: demo.maternity.admin (facility_admin), demo.maternity.midwife (midwife).
Findings common to every facility are in the HOSPITAL / HEALTH_CENTRE / CLINIC audit 2 files and are listed once under "Inherited".

## PLAN (ranked)
1. **FIXED — newborn cannot get a Health ID on the day of birth.** Web + mobile family registration validated `date_of_birth before:today` and the web date picker capped at yesterday. BCG / OPV-0 / HepB birth doses are given at birth, so the baby had no record to receive them until the next day. Now `before_or_equal:today` (future dates still refused), still through `PatientIdentityService`.
2. **NEEDS LEAD — a pregnant woman typing "maternité", "sage-femme", "accouchement" does not find a maternity** whose name lacks the word. `CareMapSearchService::TYPE_SYNONYMS` only carries `dental` (file owned elsewhere). Diff below. "maternity" (EN) already works via the `facility_type LIKE` clause.
3. **NEEDS LEAD (in progress elsewhere) — referral desk**: midwife sidebar already links `portals.referrals.index`; route file lives on the referral builder's branch; prod `/facility/referrals` = 404 for both logins until deployed. High-risk pregnancy referral therefore not usable on prod today.
4. **NEEDS LEAD — immunisation desk entry**: desk is per-patient (`/facility/patients/{id}/immunizations`), reached from the chart; no sidebar entry is required, but the chart must link it (owned by desk builder).
5. **OWNER DECISION — antenatal appointment type**: `appointment_type` is `required|string` free text; no maternity preset (e.g. "Consultation prénatale (CPN)", "Postnatal", "Vaccination"). Adding a preset list is a product call.
6. **OWNER DECISION — mobile app date picker** (separate repo) may also cap at yesterday; API now accepts today.

## Prod status (GET only, 2026-09-15)
| Login | Path | Status |
|---|---|---|
| midwife | /facility, /facility/triage, /facility/queue, /facility/appointments, /facility/appointments/waitlist, /facility/patients | 200 |
| midwife | /facility/catalogue | 403 (correct: service_catalogue is admin) |
| midwife | /facility/team/overview[/wards,/reports] | 302 → /facility (correct) |
| midwife | /facility/referrals | **404 — not deployed** |
| admin | /facility/team/overview, /wards, /reports, /facility/queue, /facility/catalogue | 200 |
| admin | /facility, /facility/triage, /facility/appointments, /facility/patients | 302 → /admin (inherited: admin is not a clinical desk) |
| admin | /facility/referrals | 404 |
Admin sidebar on prod: Dashboard, Facility overview, Invoices, Departments, **Wards & beds**, Team, Our services, Patient queue, My Profile.

## Maternity-specific workflow
- **Findable**: 9 `maternity` rows in local `care_facilities` (brief says 10). OSM normaliser maps `maternite`/`midwife`/`birthing_centre` → maternity. Search: see item 2.
- **Catalogue (dictionary, never invented)**: `clinical_code_dictionary` label hits — antenatal: ICD-10 19, HL7-service-type 1; delivery: ICD-10 105, ICHI 5, LOINC 3; caesarean: ICD-10 10, ICHI 1; newborn: ICD-10 157, LOINC 90; postnatal 1; postpartum 9. Publishable billable services: essentially the 1 HL7 antenatal service type + ~6 ICHI delivery/caesarean procedures; ICD-10 are diagnoses, not services. Delivery *packages* have no code — a gap in the dictionary, not code to write. Maternity demo catalogue rows: 0.
- **Book / queue antenatal visit**: appointments + waitlist + queue 200 for midwife; type is free text (item 5).
- **Vitals at triage**: `/facility/triage` 200. VitalBandResolver is adult-only (inherited; no obstetric/neonatal bands — correct not to invent).
- **Newborn birth doses**: requires a Health ID → guardian flow (item 1, fixed). A midwife cannot create a newborn Health ID at the desk; creation from the facility side is identity work, owner decision.
- **Mother adds newborn as dependent**: `/patient/family/add` → `FamilyController@store` → `PatientIdentityService::createPatientCandidate` + `FamilyLink` (active, self_registered). Mobile `POST /api/mobile/family` same. Records follow via ActAsDependent / guardian.context. Duplicate-identity guard sends to invite.
- **Wards register**: maternity is in the sidebar allow-list; occupancy register is in scope (ADT is not).

## OUT OF SCOPE (report only)
Partographs, antenatal/obstetric cards, delivery registers, admission/discharge of mothers, neonatal records, APGAR charting, DHIS2 maternal reporting.

## Inherited (see HOSPITAL/HEALTH_CENTRE/CLINIC audit 2)
Admin redirected away from clinical desks; adult-only vital bands; appointment types free text across facilities; referral + immunisation desks under construction.

## Tests
`tests/Feature/Maternity/MaternityNewbornAndFinderTest.php` — 5 tests (newborn today registers via identity service; future DOB refused; form max=today; mobile rule; "maternity" finds type — FR words marked incomplete until the lead diff lands). Run with FamilyChildRegistrationTest: 12 passed.

## Shared-file changes for the lead
`app/Modules/CareMap/Services/CareMapSearchService.php`, in `TYPE_SYNONYMS` add after the `dental` line:
```php
        'maternity' => ['maternity', 'maternities', 'maternity clinic', 'maternity home', 'midwife', 'midwives', 'birth centre', 'birthing centre', 'maternité', 'maternite', 'maternités', 'sage-femme', 'sage femme', 'sages-femmes', 'accouchement', 'accouchements', 'clinique maternité', 'centre mère-enfant', 'centre mere enfant'],
```
