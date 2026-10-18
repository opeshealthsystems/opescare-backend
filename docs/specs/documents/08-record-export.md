# 08 — Medical record export

Template `resources/views/exports/medical-record.blade.php`; service
`App\Services\Patient\MedicalRecordExportService::render()` returns `[bytes, filename]`.

| Route | Who |
|---|---|
| `POST api/mobile/medical-records/export/pdf` (base64 JSON) | Patient (app) |
| `GET patient/record-export.pdf` → `portals.patient.record-export` (**new**) | Patient / guardian (web), throttled `portal.qr`, audited `patient_document_generated` |

Nav: "Export my record (PDF)" in the patient-family sidebar (`_patient_records`) and a card on
the documents page.

**Never stored.** `Storage::put()` was removed. The only persisted trace is the
`document_verifications` row (reference, sha256 of the exact bytes, issuer) and the audit row.
`cleanupExports()` remains only to purge files written before this change.

**Basis.** Patient right of access to their data — Cameroon Law No. 2024/017 on personal data
protection **[U: number]**.

Content: identity (name, Health ID, DOB, sex, blood group, phone), allergies, active diagnoses,
open prescriptions, last vitals, recent labs, immunisations. Filename uses Health ID and
reference, never the name. Security/print: as 00.
