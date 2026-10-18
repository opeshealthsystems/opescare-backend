# 05 — Immunisation record (carnet de vaccination)

Template `resources/views/documents/immunisation-record.blade.php`. Route
`GET patient/documents/immunisation_record.pdf`; mobile download.

**Purpose.** Every dose recorded on the platform, for school, employer or clinic.

**Basis.** WHO/EPI home-based record content (vaccine, dose, date, batch, provider) — WHO
*Practical guide for the design, use and promotion of home-based records in immunization
programmes* (2015). Cameroon PEV **[U: national card layout]**. The yellow-fever ICVP (IHR
Annex 6) is issued only by designated centres: the sheet states it is **not** the ICVP.

| Column | Source |
|---|---|
| Vaccine (+ code) | `immunization_records.vaccine_name`, `.vaccine_code` |
| Dose number | `.dose_number` |
| Date given | `.administered_at` |
| Route / site | `.route` (enum-labelled), `.site` |
| Batch | `.lot_number` |
| Given by / where | `.administered_by_id` → user; `.facility_id` → facility |
| Status | `.status` |

Header: identity incl. sex. Completeness disclaimer kept. Next-due date: no column — not printed.
Security/print/EN-FR: as 00 (`document.imm_*`, `doc_ex_imm.*`).
