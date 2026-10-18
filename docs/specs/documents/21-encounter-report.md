# 21 — Consultation / encounter report (compte rendu de consultation)

Status: **built**. Route `GET /facility/encounters/{id}/report.pdf` → `documents.new.encounter`. Link per encounter on the patient chart (`/facility/patients/{id}`).

**Purpose.** Continuity-of-care summary of one encounter, for the patient or the next clinician.

**Basis.** Standard clinical documentation practice; WHO continuity of care **[U: no Cameroon template known]**.

**Fields.** `encounters`: encounter_type, encounter_class, status, reason_for_encounter, started_at, ended_at, discharge_disposition, attending_provider_id → users.name, facility. Through `visit_id`: `diagnoses` (code_system, code, display_name, is_primary, status), `clinical_notes` (history_of_present_illness, examination_findings, treatment_plan, signed_at — **signed notes only**), `prescriptions` + items (drug_name, dose, frequency, route, duration_days; voided excluded), `lab_orders` (test_name, status), `imaging_orders` (modality, body_part, status).
Vitals are not printed: `vital_signs` hang off triage records, not the encounter. An encounter without a visit prints every visit-linked section empty.

**Layout.** A4 portrait: patient, encounter, findings, diagnoses, orders, prescriptions, attending signature.

**Who.** `capability:clinical_chart`; encounter.facility_id must equal the desk facility (else 404); `ClinicalRelationshipGate` must hold (else 403). Access-logged.

**Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
