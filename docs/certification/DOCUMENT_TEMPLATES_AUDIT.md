# Document templates audit — OpesCare Finder

Date: 2026-10-18. Read-only audit. No code, views, lang, tests or database touched.

**Method.** Source reading plus `php artisan route:list` (read-only). **No PDF was rendered.**
Every patient-document render calls `DocumentVerificationService::issue()`, which writes a
`document_verifications` row before rendering, and the record export also writes the file to
disk. Rendering would have written to the dev database, which the brief forbids. So every print
quality verdict below comes from the source and is marked **[source]**.

---

## 1. Expected set

Scope follows CLAUDE.md. Included: the four finder services, Health ID, clinical records,
consent, clinician portal, one-off payments, and DHIS2 aggregate reporting. Excluded: insurance
claims, subscription billing, inventory operations and outbound FHIR/HL7.

Norm references are given as "where known". **[U]** means uncertain or not verified against
the source text of the norm.

| # | Document | Why it is expected |
|---|---|---|
| E1 | Health ID card (wallet, CR80) | Core identity credential of the product |
| E2 | Health ID enrolment attestation (A4 proof of registration) | Commonly asked by schools and employers. Product need, not a norm **[U]** |
| E3 | Prescription (ordonnance) | Legal instrument. Cameroon pharmacy practice requires the prescriber's identity and registration number (ONMC), a date, the patient's identity, the drug, its dose and the duration, plus a signature. WHO *Guide to Good Prescribing* lists the same elements **[U: exact Cameroon text]** |
| E4 | Dispensing record / medicine label for the patient | WHO good dispensing practice: the label carries the name, dose, frequency, duration and the pharmacy |
| E5 | Laboratory request form (bon d'examen) | A request travels with the patient between facilities |
| E6 | Laboratory result report | ISO 15189 §7.4.1.6 report content: units, reference intervals, flags, specimen, collection and report times, the authoriser |
| E7 | Imaging request | Same reason as E5 |
| E8 | Imaging (radiology) report | Radiology practice: indication, technique, findings, impression, signing radiologist |
| E9 | Consultation / encounter report (compte rendu) | Continuity of care. Encounters are live in the app |
| E10 | Referral letter and counter-referral (fiche de référence / contre-référence) | The MINSANTE health pyramid runs on referral and counter-referral slips **[U: official form layout]**. Referrals are live in the app |
| E11 | Discharge summary (compte rendu d'hospitalisation) | Hospital standard practice. Admissions exist in the app |
| E12 | Medical certificate (certificat médical) | High-demand civil and administrative document |
| E13 | Sick note / work-stoppage certificate (arrêt de travail) | Labour-code requirement for sick leave **[U: exact article]** |
| E14 | Immunisation record (carnet de vaccination, EPI/PEV) | WHO/EPI home-based record. Note: the yellow-fever ICVP is a WHO/IHR form that only designated centres issue, so Finder should not claim to issue it |
| E15 | Vitals / growth chart printout | Monitoring continuity. Paediatric growth charts are out of scope while there are no paediatric bands |
| E16 | Medical record export (patient summary) | Patient right of access. Cameroon personal-data law 2024 **[U: law no.]** |
| E17 | Health Passport (printable) | The product defines a 30-page passport |
| E18 | Emergency profile / international patient summary card | Allergies, conditions, medicines and blood group. Follows the IPS concept (ISO 27269) **[U: whether wanted]** |
| E19 | Appointment confirmation slip | The booking service |
| E20 | Invoice (facture) | One-off payments are live. Amounts in XAF |
| E21 | Payment receipt (reçu) | One-off payments are live. MoMo/OM references |
| E22 | Blood request / blood product issue slip | The blood finder service and the `BloodRequest` model. National blood transfusion practice uses request forms **[U]** |
| E23 | Consent record (grant / revocation, telemedicine consent) | Consent plumbing is live. The patient should be able to hold proof |
| E24 | Access / disclosure log for the patient | Who saw my record. Data-protection right **[U]** |
| E25 | Pharmacy operational reports (stock, expiry, valuation, dispensing) | Facility administration |
| E26 | Controlled-substance register | Narcotics register obligation for pharmacies **[U: MINSANTE form]** |
| E27 | Facility KPI / DHIS2 aggregate report | Admin reporting to MINSANTE (in scope per CLAUDE.md) |

**Expected: 27.**

---

## 2. Existing set

Abbreviations: "Secure" means the template extends `pdf/layouts/secure`. "QR" means the sheet
carries a `/verify/doc/{token}` QR, or another verification QR where named.

| # | Template | Route(s) | Who can generate it | Secure | QR | EN/FR | Kind |
|---|---|---|---|---|---|---|---|
| X1 | `resources/views/portals/patient/health-id-card-pdf.blade.php` | `GET patient/health-id-card.pdf` | The patient (web). Mobile `GET api/mobile/health-id-card` returns data, not this PDF **[unverified]** | No, own CR80 design | Yes, card QR to `/verify/qr` | Yes (`family.card_*`) | dompdf PDF, 90×54 mm |
| X2 | `resources/views/documents/prescription.blade.php` | `GET patient/documents/prescription/{record}.pdf`; `GET api/mobile/documents/{type}/download` | Patient, or a guardian acting for them | Yes | Yes | Yes | dompdf PDF, A4 |
| X3 | `resources/views/portals/prescriber/print.blade.php` | `GET facility/prescriptions/{id}/print` (`PrescriptionPrintController@show`) | Prescriber or pharmacy staff, linked from the prescriber and pharmacy desks | No, standalone HTML | Yes, inline SVG QR to `/verify/rx/{token}` | Yes (`prescriber.*`) | **Printed web page** (browser print, `@page A4 12mm`) |
| X4 | `resources/views/documents/laboratory-report.blade.php` | `patient/documents/laboratory_report/{id}.pdf`; `api/mobile/labs/{id}/report/download`; lab desk `portals.lab_manager.report` | Patient, guardian, lab staff | Yes | Yes | Yes | dompdf PDF |
| X5 | `resources/views/documents/imaging-report.blade.php` | `patient/documents/imaging_report/{id}.pdf`; mobile documents download | Patient, guardian | Yes | Yes | Yes | dompdf PDF |
| X6 | `resources/views/documents/discharge-summary.blade.php` | `patient/documents/discharge_summary/{id}.pdf`; mobile | Patient, guardian | Yes | Yes | Yes | dompdf PDF |
| X7 | `resources/views/documents/immunisation-record.blade.php` | `patient/documents/immunisation_record.pdf`; mobile | Patient, guardian | Yes | Yes | Yes | dompdf PDF |
| X8 | `resources/views/documents/certificate.blade.php` | `patient/documents/{id}/download` (official document fallback) | Patient. Renders an existing `official_documents` row | Yes | Yes, plus a printed verification code | Yes; the payload keys print as raw `ucfirst` English | dompdf PDF |
| X9 | `resources/views/exports/medical-record.blade.php` | `POST api/mobile/medical-records/export/pdf` (**mobile only, no web route**) | Patient (mobile) | Yes | Yes | Yes (`export.*`) | dompdf PDF, **also stored on disk** (see note) |
| X10 | `resources/views/portals/pharmacy/reports/pdf.blade.php` | `GET pharmacy/reports/{report}/pdf` (+ `/csv`) | Pharmacy staff | Yes | **No**: controller passes `verifyQr => null` | Yes | dompdf PDF + CSV |
| X11 | `resources/views/portals/pharmacy/reports/controlled_register_pdf.blade.php` | `GET pharmacy/controlled/register?format=pdf\|csv` | Pharmacy staff | Yes | **No** | Yes | dompdf PDF + CSV |
| X12 | `resources/views/portals/billing/receipt.blade.php` | `GET facility/billing/receipts/{id}` | Billing / cashier staff | No | No | Yes (`billing_desk.*`) | **Printed web page** (`data-print`, `od-clinic.css` print rule hides the chrome) |
| X13 | `resources/views/pdf/medical-record-summary.blade.php` via `MedicalRecordPdfService` | **None**: no caller in `app/` or `routes/` | Nobody | No | No | **No**, hardcoded English, prints `CNAMGS No.` | Dead dompdf template |

CSV-only exports (administrative, not printable documents):

| CSV | Route | Who |
|---|---|---|
| KPI export | `admin/kpi/export.csv` | Admin |
| Security compliance export | `POST admin/security/compliance-export` | Admin |
| Facility admin reports | `facility/team/overview/reports/export` | Facility admin |
| Pharmacy dispensing export | `pharmacy/dispensing/export` | Pharmacy |
| Public-health / DHIS2 report | `PublicHealth\ExportService::exportCsv` (writes to the private disk) | Admin |
| Governance data export | `api/mobile/data-exports/{id}/download` | Patient after approval. Format not verified |

Print-button pages that are just the screen printed (no print layout of their own):
`portals/pharmacy/prescriptions/show` (`data-print`), `portals/lab/show` (links to the X4 PDF).
`verify/rx` and `verify/document` have print CSS but are verification pages, not documents.

**Notes.**
- **Record export contradicts the "never stored" rule.** `MedicalRecordExportService` calls
  `Storage::put()` for every PDF, under the `exportDir` on the configured disk, "for
  audit/debugging". The other patient documents (`PatientDocumentService`) are rendered at
  download time and not stored, as the memory note says.
- **Certificates have no issuing surface.** `DocumentIssuanceService` is only used by the
  prescription and radiology services, and no web desk issues a `medical_certificate` or a
  sick note. X8 can only re-print rows that something else created (seeders or the API)
  **[unverified: seeders not checked]**.
- **The Health Passport has no print or PDF output.** `routes/mobile_health_passport.php`
  serves JSON pages only.

---

## 3. Gap table

| # | Expected | Existing | Status |
|---|---|---|---|
| E1 | Health ID card | X1 | Exists |
| E2 | Enrolment attestation | — | **Missing** |
| E3 | Prescription | X2 (patient PDF) + X3 (clinic sheet) | Exists. Two divergent templates, see §5 |
| E4 | Dispensing label / record | Section inside X2 only (date and pharmacy) | **Missing** |
| E5 | Lab request | — | **Missing** |
| E6 | Lab report | X4 | Exists (content gaps) |
| E7 | Imaging request | — | **Missing** |
| E8 | Imaging report | X5 | Exists |
| E9 | Consultation report | — | **Missing** |
| E10 | Referral / counter-referral | — | **Missing** |
| E11 | Discharge summary | X6 | Exists (thin) |
| E12 | Medical certificate | X8 (re-print only, no issuing flow) | **Partial** |
| E13 | Sick note | — | **Missing** |
| E14 | Immunisation record | X7 | Exists |
| E15 | Vitals printout | Section in X9 only | **Partial** |
| E16 | Record export | X9 (mobile only) | Exists |
| E17 | Health Passport print | — (JSON only) | **Missing** |
| E18 | Emergency summary card | — | **Missing** |
| E19 | Appointment slip | — | **Missing** |
| E20 | Invoice | — (no print or PDF found for `portals/billing/show`) **[verify]** | **Missing** |
| E21 | Payment receipt | X12 (printed web page) | Exists (no PDF, no QR) |
| E22 | Blood request slip | — | **Missing** |
| E23 | Consent record | — | **Missing** |
| E24 | Access log for the patient | — | **Missing** |
| E25 | Pharmacy reports | X10 | Exists |
| E26 | Controlled register | X11 | Exists |
| E27 | KPI / DHIS2 report | CSV exports | Exists (CSV only) |

**Counts: expected 27, exist 12 (+2 partial), left 13 missing + 2 partial to complete = 15.**
X13 is dead code and should be deleted or wired.

---

## 4. Specifications

I searched `docs/` (including `docs/superpowers/specs`, the audits and the launch plan) for
document or template specs.

- **No document has a written specification.** The only design specs are
  `2026-10-13-pharmacy-dashboard-complete-design.md`,
  `2026-10-13-diagnostic-centre-dashboard-design.md` and `2026-10-18-desk-design-system.md`.
  These are dashboard and desk specs. They may mention exports in passing, but none defines the
  fields of a printed document **[skimmed by grep, not read in full]**.
- The de facto specs are the docblocks at the top of each template, plus the
  `pdf/layouts/secure` header comment. Those cover the X3 prescription sheet best (issuer mark,
  watermark, licence numbers, print discipline).
- Have a spec: **0 of 12**. Docblock rationale only: X1–X12.

---

## 5. Content check

| Doc | Prints | Missing against the norm |
|---|---|---|
| **X2 Prescription (patient PDF)** | Name, Health ID, DOB, reference, prescriber **name**, prescribed and expiry dates, facility, status, items (drug, dose, route, frequency, duration, quantity), dispensed date and pharmacy | **Prescriber registration/licence number**, prescriber signature, facility licence and address, patient **sex, age, weight**, allergies, drug **strength / INN** as separate fields (only free-text `drug_name`; the item has no strength column), instructions and `notes`, route printed raw (not enum-labelled as in X3), `drug_code` |
| **X3 Prescription (clinic sheet)** | Facility name and licence, ministry code, reference, patient name, Health ID, DOB, sex, status, issued and expiry dates, prescriber name, credential and licence, items, notes, verify QR, signature block, void/expired banner | Patient **weight and age**, allergies, separate strength / INN. Facility address and phone **[unverified]**. Also: the **two prescription templates disagree** (X3 has the licence, the signature and sex; X2 does not) |
| **X4 Lab report** | Name, Health ID, DOB, test, ordered and reported dates, facility, results (analyte, value, unit, reference range, flag), advice | **Specimen type, collection time** (`collected_at` exists on `LabOrder` but is not printed), **validated/verified by** (`verified_by` exists on both order and result, not printed), sex and age (reference ranges depend on them), ordering clinician, LOINC code, result comments (`notes`). **Bug:** `clinical_indication` prints under the heading "Interpretation" (`document.lab_interpretation`). It is the request indication, not an interpretation |
| **X5 Imaging report** | Identity, modality, body part, accession, study date, reporting radiologist, finalised time, version, facility, amendment notice, indication, findings, impression, recommendation | Radiologist registration number, technique / contrast, referring clinician, sex and age. Otherwise good |
| **X6 Discharge summary** | Identity, admission and discharge dates, length of stay, admission reason, discharge reason and destination | **Diagnoses (ICD-10), procedures, medicines on discharge, follow-up plan, pending results, discharging clinician**, ward and facility (no facility row at all), allergies. **Too thin to be a clinical discharge summary** |
| **X7 Immunisation record** | Identity, then per dose: vaccine, dose number, date, lot, status. Completeness disclaimer | Facility and administering person per dose, next due date, antigen / vaccine code, route and site, sex |
| **X8 Certificate** | Title, holder, Health ID, DOB, number, issue and expiry dates, facility, state, scalar payload, verify code | Issuing clinician name and registration number, signature, the medical statement itself in a fixed structure (payload keys print as raw `ucfirst(snake_case)`, **not translated**), purpose and recipient |
| **X9 Record export** | Identity (name, Health ID, DOB, sex, blood group, phone), allergies, diagnoses, medications, vitals, labs, immunisations | Encounters, referrals, imaging, admissions, conditions vs diagnoses, emergency contact. Acceptable as a summary |
| **X10/X11 Pharmacy reports** | Facility, window or period, generated time, tabular rows, valuation total in XAF | The person who generated the report, a page total, a pharmacist signature line for the register **[U: regulatory need]**, a verification QR (deliberately null) |
| **X12 Receipt** | Facility, receipt number, issue time, payer name and Health ID, invoice number, method, payment reference, cashier, amount, balance (XAF) | Facility address, tax/NIU number **[U]**, itemised lines (or a link to the invoice), verification QR, "OpesCare" brand text hardcoded (minor) |
| **X1 Health ID card** | Name, Health ID, DOB, sex, blood group, verified/provisional pill, QR, issue date | Emergency contact **[design choice]**, expiry. Fine for a card |

---

## 6. Print quality [source]

Every verdict here is judged from source. No PDF was rendered.

- **Secure sheet (X2, X4–X11):** A4 portrait. `@page` margins 33/16/30/16 mm, with the fixed
  header and footer offset to the page edge so they repeat on every page. DejaVu Sans with
  subsetting. Tables only. `table.data tr { page-break-inside: avoid }`. The footer shows
  "Page N" (**no "of M"** total). Likely to print cleanly. Concerns:
  - The body font is **9.5px** (about 7pt) and the table header 7.5px. The footer micro-text is
    **5–6.8px**, below comfortable legibility on cheap printers.
  - The full-bleed background JPEG and the dark header band (`#0E1729` fill) use a lot of toner
    and will **grey out on mono printers and photocopies**. The X3 sheet avoids exactly this.
  - Table `<thead>` repetition across pages in dompdf **[not verified]**.
- **X3 prescription print sheet:** the best print engineering in the repo. `@page A4 12mm`, ink
  only (no fills), repeated headers, unbroken rows, end-of-list rule, watermark. It depends on the
  browser, so output varies by browser and print settings **[source]**.
- **X1 card:** fixed 90×54 mm page, `@page margin 0`. Font sizes of **3.9–4.6px** in the
  labels and footer are about 3pt, which is **unreadable when printed**. QR size not checked.
- **X12 receipt:** screen layout printed after `@media print` hides the chrome. There is no
  `@page` size, so the receipt prints at the browser default on A4 or Letter. Acceptable, not
  formatted.
- **X13:** no `@page` rule, off-brand colour `#1a56db`, English only. Dead code.

---

## Counts

**Expected 27 / exist 12 (+2 partial) / left 15** (13 missing, 2 partial).

## Top 10 priorities

1. **Prescription parity.** Bring X2 up to X3: prescriber licence (ONMC) number, signature
   block, facility licence, patient sex, age and weight, allergies. Or make one template serve
   both. Add strength / INN fields to `prescription_items`.
2. **Lab report fixes.** Print `collected_at`, specimen and `verified_by` (validator). Fix the
   mislabelled "Interpretation" (`clinical_indication`). Add sex and age.
3. **Medical certificate + sick note:** an issuing desk (clinician, registration number,
   structured statement, translated fields) on top of the existing X8 render.
4. **Referral / counter-referral letter** as a secure-sheet PDF (the referral data is live).
5. **Consultation / encounter report PDF.**
6. **Discharge summary enrichment:** diagnoses, medicines on discharge, follow-up, discharging
   clinician, facility.
7. **Invoice PDF + receipt as a secure-sheet PDF with a verification QR** (payments are live,
   XAF).
8. **Fix record export storage.** Stop `Storage::put` in `MedicalRecordExportService` to honour
   "never stored", and add a web route for the export.
9. **Health Passport / emergency summary PDF** (data already assembled by
   `HealthPassportBuilder`).
10. **Print legibility pass:** raise sheet and card minimum font sizes, add "Page N of M",
    offer a toner-light (mono) variant of the secure sheet. Delete or replace the dead
    `pdf/medical-record-summary`. Write one spec per document under `docs/`.

Later: appointment slip, blood request slip, consent record, patient access log, lab and imaging
request forms, dispensing label.
