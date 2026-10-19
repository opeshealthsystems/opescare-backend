# Patient chart Summary: alignment with the patient app

Built 2026-10-19 from the approved design `docs/design/preview-2026-10-19/chart-merged.html`.
Route `portals.chart.show` (`/facility/patients/{id}`, tab `summary`), controller
`PatientChartController`, builder `App\Services\Chart\ChartSummaryBuilder`.

## The gate (unchanged, reused)

- Record-level: `ClinicalRelationshipGate` via `PatientChartAccess` (no basis = 404, logged `denied`).
- Scope: the patient's switch **"Full record behind consent"** (`require_consent_for_full_record`,
  default ON). ON + no grant to this facility = **preview**: identity, allergies, current medicines only.
  OFF, or an active `consent_grants` row for the active facility = **full**.
- The second switch (**emergency access**) gates break-glass, which this chart has no path to; it is
  shown, not used.
- Each summary section asks `PatientChartAccess::covers(<category>)`, the same call the tabs use.
- Every section shown writes its own `access_logs` row (`access_type=read`, facility = active facility,
  purpose `treatment:<basis>`). These rows are what `GET /api/mobile/access-logs` returns to the patient;
  new categories have patient-facing labels in `lang/{en,fr}/access_log.php` (`category.*`).

## Section map

| Section | Data source | Gate (access-log category) | What the patient sees in the app |
|---|---|---|---|
| Identity header | `patients` (age + band, never DOB) | `patient_identity` (always) | "Who you are" entry |
| Allergy pills + list | `allergy_records` | `allergies` (always) | "Allergies" entry |
| Current medicines | `prescriptions` active/partially_dispensed + items | `medications` (always) | "Medicines and prescriptions" entry |
| Consent pill / green note / panel | `consent_grants` active for this facility | shown on any holding verdict (it is the gate's own fact) | Their grant in the consent ledger |
| KPI tiles + 14-day trend | `triage_vital_signs` + legacy `vital_signs` (`facilityVitals`) — facility-measured only, no self-reported | `vitals` | "Vital signs" |
| Presenting complaint body map | `patient_pain_sites` unresolved, **active facility only** | `pain_sites` | "Where it hurts (pain sites)"; their own check-in sites via the pain-sites endpoint |
| Recent results | `lab_results` + `ResultFlagResolver` | `lab_results` | "Laboratory results" |
| Encounters timeline + care-across-facilities donut | `encounters` (all facilities, as the Encounters tab) | `encounters` | "Visits and consultations" |
| Problems | `problem_lists` | `problem_list` (was shown in preview before: fixed) | "Ongoing problems" |
| History (diagnoses) | `diagnoses` | `diagnoses` | "Diagnoses" |
| Referrals | `referral_cases` where this facility referred or received | `referrals` | "Referrals" |
| Documents + QR state | `official_documents` issued; `document_verifications` counts | `documents` | "Documents" |
| Immunisations | `immunization_records` (not entered_in_error) | `immunization` | "Immunizations" |
| Pre-consult answers | `preconsult_submissions` submitted, **active facility only** | `patient_reported_intake` | "Your pre-consult answers" |
| Care team panel | `care_team_members` active | `care_team` | "Your care team" |
| Family and emergency panel, guardian pill | `family_links` active (guardian side), `patients.emergency_contact` (never invented) | `family_links` | "Family and emergency contacts" |
| Access log panel | `access_logs` for this patient **at this facility only** | `access_log` | The same rows, in their access log |

## Decisions

- **Chart stays read-only** (routes/chart.php). Pain sites are written from: triage desk create form
  (source `triage`), triage record page add/resolve (source `clinician` for prescriber roles, else
  `triage`; capability `triage`, record facility-scoped), and patient check-in
  `POST /api/mobile/preconsult/appointments/{appointment}/pain-sites` (source `patient_checkin`,
  facility from the appointment, only while the pre-consult window is open). No web patient pre-consult
  exists, so the web check-in door is the mobile API only. The app UI is out of scope.
- **Cross-facility**: patient-reported material (pain sites, pre-consult) and the facility trail
  (referrals, access log) are limited to the active facility; portable clinical history crosses
  facilities exactly as the existing tabs do, so the donut discloses nothing the Encounters tab
  does not.
- **Grant scope**: the gate treats any active grant as full scope (existing behaviour); the grant's
  `scope` list is displayed, not enforced per section. Changing that is a gate change, not a view change.
- **Figure**: child (<13) vs adult by DOB; boy/girl, male/female by recorded sex. Unrecorded sex: male
  art at the age build, same stand-in as the app's `generic` profile, with a visible caption saying it is
  a stand-in. Unknown DOB: adult build, captioned. Back view: no art exists; the Back toggle is disabled
  ("Back view coming") and back regions are listed without a pin.
- **Heart**: same parts and timing as `LivingAnatomy.tsx` — base PNG, 24-frame current sheet (6x4) via
  CSS `steps()` at 833 ms per beat, cut-out heart at the meta.json box with the app's lub-dub keyframes.
  `prefers-reduced-motion` stops all three (static frame 0).
- **Allergy reaction**: `allergy_records` has no reaction column, so the pill shows substance + severity.
- **Actions**: no web lab-order or new-encounter route exists. Actions map to existing desks:
  Order imaging (`imaging`), Refer (`clinical_chart`), New visit (triage) (`triage`), each with the Health ID.
- **Health passport**: patient-only (mobile + patient PDF); no clinician view exists, so no card.
- **Red**: only pain pins and out-of-range result pills. Allergy pills use amber.

## Mobile API for the app team

`GET|POST /api/mobile/preconsult/appointments/{appointment}/pain-sites` (patient bearer; guardian
act-as works). POST body `{ "sites": [{ "region", "severity" 0-10, "onset_at"?, "trend"? better|same|worse,
"note"? }] }` (1-8). Regions: head, forehead, face, neck, chest, upper_abdomen, lower_abdomen, pelvis,
right_arm, left_arm, right_leg, left_leg, upper_back, lower_back (patient's left/right). 201 with
`PainSite[]`; 404 not your appointment; 409 window closed; 422 validation. Schema in `public/openapi.json`.
