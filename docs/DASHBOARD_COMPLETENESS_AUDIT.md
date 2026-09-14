# Dashboard completeness audit

Read-only audit of all 44 sidebar partials against the route table, controllers,
views and `config/staff_capabilities.php`. 2026-09-14. Spot-checked by the lead
before use: B1 (roles absent from `PORTAL_ROLES`), B2 (search view 133 lines, no
route) and B9 (no blood-request create route) all confirmed against the code.

## Headline

The problem is **not** stub views. There are zero "coming soon"/TODO/lorem screens
under `resources/views/portals/**`, no sidebar link points at a missing route
(0/74 distinct route names missing), and both tabbed pages have controller-backed
tabs. What is built is built well. The problem is three other things:

1. **10 roles have a sidebar whose links their own middleware will not let them
   open** — `EnsurePortalAccess::PORTAL_ROLES` does not list the role under the
   prefix its sidebar targets, so it silently redirects, or bounces to `/login`.
2. **The sidebars are starved.** 42 profiles share ~20 destinations. `_admin_nav`
   has 32 links; the median non-admin dashboard has **5**.
3. **Whole capabilities have no screen.** `lab_results`, `sample_collection`,
   `billing`, `clinical_chart`, `identify_patient`, `request_access`,
   `emergency_access` are granted and gate nothing, because no web route exists.

## Summary by facility volume

| Profile | Roles | Links | Built | Broken | Empty-data | Missing |
|---|---|---|---|---|---|---|
| **Pharmacy — 348 facilities** | | | | | | |
| `pharmacist` | pharmacist, pharmacy_technician, dispensing_officer | 6 | 6 | 0 | 0 | dispensing history/report |
| `pharmacy_manager` | pharmacy_manager | 8 | 7 | 0 | 1 | `portals.listing.edit` dead-ends without an approved claim; no stock-report, supplier, PO or team screen |
| `medicine_stock` | medicine_stock | 3 | 3 | 0 | 0 | reorder rules, batches, adjustments, suppliers (tables exist) |
| **Hospital — 100** | | | | | | |
| `doctor` / `specialist_doctor` / `resident_doctor` / `medical_supervisor` | doctor, specialist, consultant, resident, medical_supervisor… | 7 | 6 | 0 | 1 | **No patient chart. No patient search.** Blood-request desk cannot be populated from web |
| `nurse` / `ward_nurse` / `triage_nurse` / `nurse_supervisor` / `midwife` | — | 6 | 5 | 0 | 1 | no chart, vitals, wards/beds, handover |
| `receptionist` | receptionist, front_desk, cashier, billing_officer, finance_manager, **finance** | 5 | 5 | 1 role | 1 | `finance` bounced to `/admin`; no billing/queue screen despite `billing` capability |
| `records_officer` | records_officer | 5 | 5 | 0 | 1 | no records/documents surface |
| `student_clinical` | student_doctor, student_nurse, intern | 3 | 3 | 0 | 0 | no supervised chart |
| `facility_admin` | facility_admin, clinic_admin, hospital_admin | 4 | 4 | 0 | 0 | no settings, hours, wards, reports |
| `facility_executive` | facility_ceo | 4 | 4 | 0 | 0 | no KPI/report screen (`kpi_dashboards`, `facility_reports` tables exist) |
| `multi_facility_admin` | branch_admin | 3 | 3 | 0 | 0 | no cross-facility roll-up |
| **Laboratory — 3 (but 100 hospitals run labs)** | | | | | | |
| `lab_manager` | lab_manager, lab_validator | 6 | 6 | 0 | 1 | **No lab screen.** `lab_orders` 23 rows, `lab_results` 42, no web UI |
| `labtech` | labtech, lab_scientist | 4 | 4 | 0 | 1 | same |
| `sample_collection` | sample_collection | 2 | 2 | 0 | 0 | capability has no route or view |
| **Insurance — 2** | `insurance_admin`, `insurance_reviewer` | 0 | — | — | — | empty by design (frozen); roles resolve to `/login` |
| **Platform / patient** | | | | | | |
| `super_admin` / `platform_owner` | — | 32 | 32 | 0 | 0 | complete — the model for the rest |
| `patient` | patient, emergency_contact | 16 | 16 | 0 | 0 | unlinked: notifications, prefs, refer |
| `guardian` / `caregiver` / `dependent_manager` | — | 14–15 | all | 0 | 0 | built |
| `compliance_audit` / `privacy_officer` / `security_officer` / `emergency_access_reviewer` / `data_steward` | — | 1 | 1 | 0 | 0 | hold platform tier; **no link** to the built desks they exist for |
| `developer` | developer, sandbox_developer, partner_technical, public_health_api | 5 | 5 | 2 roles | 0 | partner_technical → `/admin`; public_health_api → `/login` |
| `api_partner_admin` / `webhook_manager` | developer_org_admin, webhook_manager | 3 | 2 | 1 | 0 | "Dashboard" → `/admin` → developer redirect; two links point at the same route |
| `bridge_agent_admin` | bridge_admin | 1 | 0 | 1 | 0 | **dead** — role in no portal list |
| `device_kiosk` | kiosk, bridge_agent, facility_device, integration_device | 2 | 0 | 2 | 0 | **dead** |
| `lite` | lite_staff, lite_facility, lite_device, lite_offline_sync | 2 | 0 | 2 | 0 | **dead** — `lite` key exists, links point at `/facility` which excludes them |
| `outreach_mobile` | outreach_team, mobile_clinic_team | 2 | 1 | 1 | 0 | "Dashboard" → `/login` |
| `ngo_health_org` | ngo_admin, health_program_manager | 1 | 1 | 0 | 0 | no portal; one external link |
| `demo_access` | **none** | 2 | 2 | 0 | 0 | **orphan partial** |

## Broken links — the role cannot reach its own sidebar

| Sidebar | Role | Target | Result |
|---|---|---|---|
| `bridge_agent_admin` | bridge_admin | `/admin` | → `/login` |
| `device_kiosk` | kiosk, bridge_agent, facility_device, integration_device | `/facility` | → `/login` |
| `lite` | lite_staff, lite_facility, lite_device, lite_offline_sync | `/facility` | → `/login` |
| `outreach_mobile` | outreach_team, mobile_clinic_team | `/admin` | → `/login` |
| `developer` | public_health_api | `/developer` | → `/login` |
| `developer` | partner_technical | `/developer` | → `/admin` (silent, wrong sidebar) |
| `api_partner_admin` | developer_org_admin | `/admin` | → `/developer` (silent) |
| `webhook_manager` | webhook_manager | `/admin` | → `/developer` (silent) |
| `receptionist` | finance | `/facility` | → `/admin` (silent) |

## Empty-data links

| Link | On | Why |
|---|---|---|
| `portals.staff.blood_requests.index` | 13 clinical dashboards | `blood_requests` = 0 rows. Web has decide/ready/fulfil but **no create route**; only writer is the mobile API |
| `portals.listing.edit` | pharmacy_manager, lab_manager | requires an approved `FacilityClaim`; nearly all 348 pharmacies have none |
| `portals.facility.catalogue` | 4 dashboards | `facility_catalogue_items` = 0 — cold start, writer exists |

## Capabilities with no screen anywhere

| Capability | Roles | Data waiting |
|---|---|---|
| `lab_results` | labtech, lab_scientist, lab_manager, lab_validator | `lab_orders` 23, `lab_results` 42, `critical_value_alerts` |
| `sample_collection` | sample_collection | `document_specimen_events` |
| `billing` | cashier, billing_officer, finance_manager, finance, refund_approver, wallet_ops, front desk | `invoices`, `payments`, `receipts`, `cashier_sessions` |
| `clinical_chart` | all 14 clinical roles | `encounters` 18, `clinical_notes`, `vital_signs`, `diagnoses`, `allergy_records` |
| `identify_patient` | 30+ roles | `StaffPortalController@search` + a complete 133-line view — **unrouted** |
| `request_access` | clinical + pharmacy | `consent_requests` 8 rows, no web screen |
| `emergency_access` | doctors, nurses | review desk exists; no break-glass initiation screen |

## Built but unreachable

- `StaffPortalController@search` — complete view, no route. The only patient-identification surface in the product.
- `PatientPortalController@referrals`, `@surveys` — no route **and their views do not exist** (would 500).
- `PatientPortalController@reportLostCard`, `PharmacyPortalController@inventory` — unrouted; the latter duplicates `stock()`.
- Built routes no sidebar links: `portals.admin.security.{audit_explorer,emergency_access,incidents}`, `portals.admin.governance.log`, `portals.admin.terminology.log`, `admin.care-map.governance`, `portals.developer.webhook_deliveries`, `portals.listing.claims`, `portals.patient.{notifications,notifications.preferences,refer}`, `portals.facility.catalogue.browse`.
- Prefix drift: `portals/patient/refer`, `portals/listing/*`, `portals/guardian/*` bypass `EnsurePortalAccess` because `detectPortalPrefix()` tests `str_starts_with($path,'patient')`.
- `layouts/portal.blade.php` uses `@includeIf` — a missing sidebar key renders **blank**, not 500.
- `multi_facility_admin` links `portals.facility.team` with no `@capability` guard.

## Build list — priority order

**P0 — dashboards that do not work**
- **B1 (S)** Fix `EnsurePortalAccess::PORTAL_ROLES` for the 10 roles above. Point `api_partner_admin`/`webhook_manager` "Dashboard" at `portals.developer.dashboard` instead of `/admin`. Add a test: every profile's sidebar links are reachable by at least one role using it.
- **B2 (S)** Route `StaffPortalController@search` at `/facility/search` behind `capability:identify_patient`; link from all 16 `/facility` sidebars.
- **B3 (S)** `PatientPortalController` `referrals()`/`surveys()`/`reportLostCard()`: build views + routes, or delete. Delete `PharmacyPortalController@inventory`.

**P1 — laboratory has no lab**
- **B4 (L)** Lab desk at `/facility/lab` (`capability:lab_results`): worklist, result entry/validate, critical-value acknowledgement, reference ranges. Model on `TriageDeskController`.
- **B5 (M)** Sample-collection worklist at `/facility/lab/collection`.

**P2 — pharmacy depth (348 facilities)**
- **B6 (M)** Stock operations: reorder rules, batches/expiry, adjustments log, goods receipt, suppliers, POs (tables exist; `medicine_pharmacy_stocks` 22,197 rows, one screen).
- **B7 (S)** Guard `portals.listing.edit` behind an approved claim, or relabel "Claim your listing".

**P3 — clinical core**
- **B8 (L)** Patient chart at `/facility/patients/{id}` with tabs, `capability:clinical_chart` + `RequireConsentGrant`. The single largest gap.
- **B9 (M)** Blood-request creation on web (`GET /facility/blood-requests/new`, `POST`), calling the existing `BloodRequestService`.
- **B10 (L)** Billing/cashier desk (`capability:billing`).

**P4 — platform roles reaching what they own**
- **B11 (S)** Link the security/compliance/privacy/emergency-reviewer/data-steward sidebars to their existing desks. `data_steward` is not platform tier — decide before wiring.
- **B12 (S)** Link the 12 orphan routes; fix the duplicate "Webhooks"/"My Apps" links.
- **B13 (S)** Delete `demo_access.blade.php`; add a bijection test between `roles.dashboard_profile_key` and sidebar partials.
- **B14 (S)** `@capability('facility_team')` on `multi_facility_admin`.

**P5 — decide**
- **B15** Insurance (5 roles) and NGO: build the desks, or land those roles on `portal.unavailable` explicitly instead of `/login`.
