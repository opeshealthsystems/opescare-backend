# Patient FHIR R4 export: HL7 validator results

Endpoint: `POST /api/mobile/medical-records/export/fhir`
(`MedicalRecordExportController::exportFhir` -> `MedicalRecordExportService::generateFhirBundle`).

Validated 2026-10-18 with the official HL7 validator
(`validator_cli.jar` 7.0.2, `-version 4.0.1`, terminology server tx.fhir.org)
against a real Bundle from the dev database: seeded demo patient with
2 allergies, 2 ICD-10 diagnoses and 8 prescriptions (active, partially
dispensed, dispensed, voided).

## Result

| | Errors | Warnings | Notes |
|---|---|---|---|
| Before | 62 | 17 | 0 |
| After  | **0** | **0** | 1 ("All OK") |

Remaining warnings: none.

## What was wrong, and the fix

| Finding (before) | Count | Fix |
|---|---|---|
| Entries without `fullUrl`; relative `Patient/{id}` references that could not resolve | 26 + 13 | Every entry has `fullUrl` `urn:uuid:{id}`; every reference points at the patient's `urn:uuid` inside the bundle |
| `bdl-1`: `total` on a `collection` bundle | 1 | `total` removed (only valid for searchset/history) |
| `clinicalStatus` codings with no `system`; Condition status `confirmed` (a verification status, not a clinical one) | 8 | HL7 systems `allergyintolerance-clinical`, `condition-clinical`; `confirmed`/`chronic` map to clinical `active` + `verificationStatus` `confirmed` |
| MedicationRequest `status` used internal values (`voided`, `dispensed`, `partially_dispensed`) | 10 | Mapped to the R4 value set: active / completed / cancelled / stopped / entered-in-error |
| Empty `dosageInstruction.text` (`ele-1`) | 12 | Omitted when there is no dosage |
| `dom-6`: no narrative | 13 | Generated `text.div` built only from what the resource already states |

Data-honesty fixes (not flagged by the validator, but wrong):
- The medicine was the prescription's free-text `notes` (or the literal
  "Prescription"). Now one MedicationRequest per prescription line, medicine
  from `drug_name`/`strength`/`dosage_form`, dosage from the line,
  `groupIdentifier` = the RX reference, notes kept as `note`.
- `onsetDateTime` was filled with the record's `created_at`; we hold no onset
  date, so it is now `recordedDate`.
- Placeholders ("Unknown") are no longer emitted; a field we do not hold is
  omitted. An empty record exports the Patient alone.
- Diagnoses carry an ICD-10 coding (`http://hl7.org/fhir/sid/icd-10`), plus
  SNOMED CT (`http://snomed.info/sct`) when a SNOMED code is stored.
- Patient identifier system is now an absolute URI
  (`https://opescare.cloud/fhir/sid/health-id`).

## Not in the export (scope, not errors)
Vitals, lab results and immunizations are not exported as resources yet
(they were loaded but never emitted before this change either).

## Regression guard
`tests/Feature/Mobile/FhirExportStructureTest.php` asserts resourceType,
`type=collection`, no `total`, unique `fullUrl`s, every reference resolving
in-bundle, HL7 code-system URIs and absolute `system` values.
