# 01 — Prescription (ordonnance)

Two renderings, **one source of facts** (`App\Services\Documents\PrescriptionSheetFacts`):

| Rendering | Template | Route | Who |
|---|---|---|---|
| Patient PDF (secure sheet) | `resources/views/documents/prescription.blade.php` | `GET patient/documents/prescription/{id}.pdf`; mobile `api/mobile/documents/{type}/download` | Patient, guardian |
| Clinic print sheet (browser print, A4) | `resources/views/portals/prescriber/print.blade.php` | `GET facility/prescriptions/{id}/print` (`capability:prescribe`) | Prescriber |

**Purpose.** The legal order a patient carries to a pharmacy. Records what was written; it is
not proof of supply.

**Audience.** Dispensing pharmacist (primary), patient, inspector.

**Basis.** WHO *Guide to Good Prescribing* (1994), ch. 7: prescriber name, address, phone,
date, generic name (INN), strength, dosage form, total amount, label instructions, prescriber
signature; patient name, address, age (and weight for children). Cameroon practice: prescriber
registered with the Ordre National des Médecins du Cameroun (ONMC), registration number on the
script **[U: exact MINSANTE/ONMC text not verified]**. Controlled substances: separate register
(see 09).

## Required fields and sources
| Field | Source |
|---|---|
| Facility name, licence no., MINSANTE code, address, phone | `facilities.name`, `facilities.license_number`; `facility_registry` (claimed row): `ministry_code`, `address`, `city`, `region`, `phone` |
| Prescriber name | `users.name` via `prescriptions.prescribed_by` |
| Prescriber profession + registration no. | `professional_licenses.profession`, `.license_number` (latest, via `staff_profiles.user_id`), fallback `staff_profiles.job_title` |
| Patient name, Health ID, DOB, sex | `patients.*` (DOB is encrypted cast) |
| Age | derived from DOB at `prescribed_at` |
| Weight | latest `triage_vital_signs.weight_kg`, printed **with its measurement date** |
| Allergies | `allergy_records.substance` (status not inactive/resolved/refuted/entered_in_error); none = "No known allergy recorded" |
| Reference, prescribed at, valid until, status | `prescriptions.reference`, `.prescribed_at`, `.expires_at`, `.status` |
| Per line: INN | `medicines.generic_name` (via `prescription_items.medicine_id`), fallback `drug_name`; brand shown under it when different |
| Strength / form | `prescription_items.strength`, `.dosage_form` (**new nullable columns**, migration `2026_10_18_130000`), fallback `medicines.strength`, `.form` |
| Dose, route (enum-labelled), frequency, duration, quantity | `prescription_items.dose`, `.route`, `.frequency`, `.duration_days`, `.quantity` |
| Instructions | `prescriptions.notes` |
| Dispensed date / pharmacy | `prescriptions.dispensed_at`, `.dispensing_pharmacy_name` |

Missing values print **"Not recorded"** — never invented.

## Layout
A4 portrait. Secure sheet: issuer block → patient block → order block → medicines table
(numbered, line count in the heading and an end-of-list line) → instructions → signature and
stamp block → legal notes. Clinic sheet: unchanged ink-only design plus age, weight, allergies
and INN / strength-form columns.

## Security
Patient PDF: `/verify/doc/{token}` QR, sealed sha256, OC-DOC reference, microtext, diagonal
**VOID / EXPIRED watermark** for voided, cancelled, amended, entered-in-error or lapsed scripts.
Clinic sheet: `/verify/rx/{token}` QR, reference watermark, void banner. No PII in URLs.

## EN/FR
`document.*`, `doc_ex_rx.*`, `doc_ex_sheet.*`, `prescriber.*`.

## Print rules
Body ≥ 8pt; toner-light variant available (`$sheetTone = 'light'`); rows never split.
