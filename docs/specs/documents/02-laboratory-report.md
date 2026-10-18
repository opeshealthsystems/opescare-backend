# 02 — Laboratory report (compte rendu d'analyses)

Template `resources/views/documents/laboratory-report.blade.php`. Routes:
`GET patient/documents/laboratory_report/{id}.pdf`, mobile `api/mobile/labs/{id}/report/download`,
lab desk `portals.lab_manager.report`. Only released orders (`LabOrder::RELEASED_STATUSES` +
`reported`) render.

**Purpose.** The released result of a laboratory order. **Audience.** Patient, treating clinician.

**Basis.** ISO 15189:2022 §7.4.1.6 report content: patient identification, sex and age, the
requester, specimen type, collection date/time, report date/time, results with units and
biological reference intervals, flags, comments, and the identity of the person who
authorised the release **[U: clause numbering per 2022 edition]**.

## Fields and sources
| Field | Source |
|---|---|
| Patient name, Health ID, DOB, age (at collection), sex | `patients.*` |
| Test, test code | `lab_orders.test_name`, `.test_code` |
| Ordered by | `lab_orders.ordered_by` → `users.name` |
| Ordered / collected / reported | `lab_orders.ordered_at`, `.collected_at`, `.resulted_at` (date + time) |
| Specimen | LOINC dictionary `clinical_code_dictionary.specimen` for the order's LOINC code (`LabDeskWebService::dictionaryTerm`); no specimen column exists on the order. Absent → "Not recorded" |
| Validated by | `lab_orders.verified_by`, fallback first `lab_results.verified_by` |
| Laboratory | facility block (`secure-facility` partial) |
| Results: analyte (+LOINC), value, unit, reference range, flag | `lab_results.parameter_name`, `.loinc_code`, `.value`, `.unit`, `.reference_range`, `.flag` (the lab's own flag — never recomputed) |
| Result comments | `lab_results.notes` |
| Clinical indication | `lab_orders.clinical_indication` — **heading fixed**: it used to print as "Interpretation"; it is the reason for the test |
| Validation signature | `secure-signature` partial with the validator |

## Layout / security / print
A4, secure sheet, verify QR + sha256 seal + reference. EN/FR via `document.*`, `doc_ex_lab.*`.
Body ≥ 8pt.
