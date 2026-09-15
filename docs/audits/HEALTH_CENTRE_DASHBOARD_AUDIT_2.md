# Health centre dashboard — audit 2 (2026-09-15)

Scope: a Cameroon *Centre de Santé Intégré* (434 in the directory) — nurse/midwife-led primary care:
immunisation days, ANC, malaria RDT, family planning, referral up to the district hospital.
Accounts (`demo1234`): `demo.healthcenter.admin` (facility_admin), `demo.healthcenter.nurse`, `demo.healthcenter.midwife`
at "Centre de Santé Demo Nkolbisson". Yardstick: `docs/DASHBOARD_SCOPE.md`. Findings already in
`CLINIC_DASHBOARD_AUDIT_2.md` are referenced by id, not re-reported.

## Plan (ranked)

| # | Finding | Owner | Status |
|---|---|---|---|
| HC-1 | **A centre could not publish its services where patients look.** The Finder profile (`care_map/profile`) lists `care_facility_services`; the only writer was the claim-based `/portals/listing` editor — not in the facility_admin sidebar and, on prod, "No listing to manage yet" for the demo admin (no approved claim) although the tenant IS linked. `/facility/catalogue` ("Our services") writes `facility_catalogue_items`, which the public profile never reads. "Vaccination / ANC / TDR paludisme" could not reach a patient. | health-centre auditor | **FIXED** — "Services" section on `/facility/settings` (linked listing; same audited `FacilityListingEditService`; category/status validated against the listing editor's lists); 5 tests |
| HC-2 | **No web immunisation desk.** The only clinician write path is the mobile API `routes/mobile_doctor_immunizations.php` (`capability:clinical_chart`, which includes nurse + midwife). The web chart immunisations tab is read-only. A centre on a desk PC on vaccination day cannot record a dose on the web. The API works for nurse/midwife with no doctor posted (test added). | lead (route file loaded from AppServiceProvider + sidebar links) + hospital auditor (chart tab link) | OPEN — shared-file change (design below) |
| HC-3 | **Referral up to the district hospital** has no web desk (C-3). The nurse landing shows a "Pending Referrals" tile with no page to act on. | lead | OPEN — C-3 applies, more acutely: referring up is a CSI's core act |
| HC-4 | Admin overview (`/facility/team/overview`) shows **Laboratory** (median turnaround) and **Beds** tiles, and Departments / Wards & beds sidebar links, for a `health_center` with neither. Empty states are honest ("no wards on record") — hospital-shaped, not lying. | hospital auditor (overview) + lead (sidebar) | OPEN — C-5 extended to `type=health_center` |
| HC-5 | Staff landing (`/facility`) "Purpose" select offers **Insurance** (module removed per scope). | hospital auditor (StaffPortalController view) | OPEN |
| HC-6 | Nurse/midwife sidebars show **Blood inventory** at a centre with no blood bank (200, empty state). | lead | OPEN — cosmetic; keep Blood requests (a centre may request), consider hiding inventory by type |
| HC-7 | `/portals/listing` is URL-only for facility_admin (linked for lab_manager / pharmacy_manager). HC-1 now covers services from settings. | lead | OWNER DECISION — link the claim editor or not |

## Prod status (GET only, 2026-09-15)

| Role | Page | Status |
|---|---|---|
| admin | /admin, /facility/team/overview (+ departments, invoices, reports, wards), /facility/settings, /facility/team, /facility/catalogue, /facility/queue | 200 |
| admin | /portals/listing | 200, "No listing to manage yet" (not in sidebar) |
| admin | /facility/patients, /facility/triage | 302 → /admin (not linked; C-6 applies) |
| nurse, midwife | /facility, /facility/search, /facility/patients, /facility/triage, /facility/triage/new, /facility/queue, /facility/appointments (+waitlist), /facility/messages, /facility/blood-requests, /facility/inventory/blood | 200 |
| nurse, midwife | /facility/prescriptions | 403 (no `prescribe`) |

Nurse and midwife sidebars are identical (both in `$nurses`); every link self-gates on its route capability — no dead or 403 links.
Catalogue on prod: honest empty state. Settings on prod: "Public listing linked, Self-reported", type Health Center, icon heart-pulse.

## Can a nurse-led centre run without a doctor?

| Act | Who | Where | Verdict |
|---|---|---|---|
| Queue / check-in | nurse, midwife, admin | `/facility/queue` | IN, works |
| Vitals at triage | nurse, midwife (`triage`) | `/facility/triage/new` | IN, works |
| Consent-scoped chart read | nurse, midwife (`clinical_chart`) | `/facility/patients` | IN, works |
| Immunisation onto the Health ID card | nurse, midwife (`clinical_chart`) | mobile API only | IN — web gap HC-2 |
| Prescription | doctors + supervisors only | 403 for nurse/midwife | **OWNER DECISION**: in CSIs the *infirmier chef* treats per national protocols (ACT after a positive RDT, iron/folate in ANC, FP methods). Granting `prescribe` (or a protocol-limited variant) to nurses/midwives is clinical governance, not a bug. |
| Referral up | nobody on web | API only | HC-3 |
| ANC visit record | — | — | OUT (obstetric clinical documentation) |
| Malaria RDT result by a nurse | lab desk only | — | OWNER DECISION (point-of-care result on triage vs lab order); lab QC is OUT |

## Patient-facing
- Hours: `/facility/settings` writes the linked listing's hours; the profile shows only reported hours. Works.
- Services: fixed by HC-1. Existing categories suffice (maternity = ANC, preventive = immunisation/FP, diagnostic = RDT); nothing invented.
- Vaccination *days* ("Tuesday mornings") have no structured field; service name/specialty text is the honest place today. Structured sessions = owner decision.

## Security / data (tested locally)
- New routes sit in the existing settings group (`capability:service_catalogue`, `facility.context`). Facility = session tenant; a body `facility_id` is ignored; another centre's service id → 404 and the row survives; nurse/midwife → 403; unlinked tenant → 404. All tested.
- Immunisation API: facility and administering clinician from the token only; void not delete (existing DoctorImmunizationTest + new nurse/midwife test).
- Services list is per listing (small; unpaginated like the listing editor).
- No hard-coded English added; EN/FR 1:1 (i18n audit passes).

## IN / OUT of scope
- IN: listing services + hours, queue, triage vitals, immunisation write, referrals out, team, catalogue.
- OUT: ANC/partograph documentation, DHIS2/PEV reporting, vaccine cold-chain/lot stock, wards/ADT.

## Shared-file changes for the lead
1. **HC-2 web immunisation desk**
   - `app/Providers/AppServiceProvider.php`, beside `Route::group([], base_path('routes/chart.php'));`:
     ```php
     Route::group([], base_path('routes/immunisation_desk.php'));
     ```
   - `routes/immunisation_desk.php`: `Route::middleware(['web','auth','mfa.verified','portal.access','capability:clinical_chart','facility.context'])` with
     `GET /facility/patients/{id}/immunizations/new` and `POST /facility/patients/{id}/immunizations` (exact Health ID; facility from PortalContextService; extract the validation / duplicate / future-date / not-done-reason rules of `MobileDoctorImmunizationController@store` into a service both call).
   - Links: chart immunisations tab (hospital auditor) and `partials/sidebars/{nurse,midwife}.blade.php` inside `@capability('clinical_chart')`.
2. **HC-4 / C-5**: hide Laboratory + Beds tiles and Departments / Wards links for `type` in {health_center, clinic, dispensary}, or keep with the honest empty state.
3. **HC-5**: remove `insurance` from the staff landing purpose select.

## Removals for the owner (not done)
- `FacilityListingEditService::removeService` (pre-existing, now also called from settings) hard-deletes a `care_facility_services` row, with an audit entry. Consider a retire flag like the catalogue.
