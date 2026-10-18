# 03 — Imaging (radiology) report

Template `resources/views/documents/imaging-report.blade.php`. Route
`GET patient/documents/imaging_report/{id}.pdf` and mobile download. Only the current,
distributed version renders.

**Basis.** Radiology reporting practice (ACR Practice Parameter for Communication of Diagnostic
Imaging Findings; RCR *Standards for the reporting and interpretation of imaging
investigations*): demographics, referrer, examination, clinical information, findings,
impression, signing radiologist **[U: no Cameroon-specific norm found]**.

| Field | Source |
|---|---|
| Patient identity, sex, age at study | `patients.*`, `radiology_reports.study_date` |
| Referring clinician | `radiology_reports.ordered_by` → user, fallback `imaging_orders.ordered_by`, fallback `imaging_orders.referring_physician` |
| Modality, body part, accession, study date | `radiology_reports.modality`, `.body_part`; `imaging_orders.accession_number`; `.study_date` |
| Radiologist, registration no. | `radiology_reports.reported_by` → `ClinicianCredential` |
| Finalised, version, amendment | `.finalized_at`, `.version`, `.amended_at`, `.amendment_reason` |
| Indication, findings, impression, recommendation | text as signed, unsummarised |
| Signature block | radiologist name, profession, registration no. |

Technique / contrast: **no column exists**; not printed (not invented).
Security, print, EN/FR: as 00 (`imaging_report.*`, `doc_ex_img.*`).
