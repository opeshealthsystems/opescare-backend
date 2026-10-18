# 04 — Discharge summary (compte rendu d'hospitalisation)

Template `resources/views/documents/discharge-summary.blade.php`; data assembled by
`PatientDocumentService::dischargeData()`. Route `GET patient/documents/discharge_summary/{id}.pdf`.
Only a discharged admission renders.

**Purpose.** Continuity of care after an inpatient stay. **Audience.** Patient, next clinician.

**Basis.** Common hospital discharge-summary content (diagnoses, procedures, medicines on
discharge, follow-up, responsible clinician) — e.g. UK PRSB eDischarge headings, HAS
"lettre de liaison" in francophone practice **[U: no MINSANTE template verified]**.

## Rule: never invent
Every section is read from a real row. A section with no data prints
"Nothing recorded for this stay." The medicines section is titled
**"Medicines prescribed during the stay — as prescribed; not a reconciled list"** because the
record has no reconciled discharge-medication list.

| Section | Source |
|---|---|
| Identity, sex, allergies | `patients.*`, `allergy_records.substance` |
| Facility | `admissions.facility_id` → `secure-facility` partial |
| Ward | `admissions.bed_id` → `beds.ward_id` → `wards.name` |
| Admitted / discharged / length | `admissions.admitted_at`, `.discharged_at` |
| Reason for admission | `admissions.admission_reason` |
| Diagnoses (code system + code, display, primary, status) | `diagnoses` on `admissions.visit_id`; when the admission has no visit, diagnoses recorded during the stay window |
| Medicines | `prescriptions` (+items) on the visit or prescribed within the stay; voided/cancelled/entered-in-error excluded |
| Discharge reason / destination | `admissions.discharge_reason`, `.discharge_destination`, fallback `discharge_plans.discharge_disposition` |
| Follow-up, medication notes, patient advice | `discharge_plans.follow_up_plan`, `.medication_reconciliation_notes`, `.patient_education_notes` |
| Discharging clinician | `admissions.attending_physician_id`, fallback `discharge_plans.approved_by`, fallback `admissions.admitted_by` → `ClinicianCredential` |

Not available in the schema (not printed): procedures, pending results.
Security/print/EN-FR: as 00 (`document.dis_*`, `doc_ex_dis.*`).
