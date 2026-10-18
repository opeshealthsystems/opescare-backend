# 24 — Health Passport (printable)

Status: **built**. `GET /patient/health-passport.pdf` → `documents.new.passport`. Button on the patient Documents page.

**Purpose.** The 30-page passport, on paper.

**Basis.** Product definition (`HealthPassportBuilder` HP-2.1); IPS concept (ISO 27269) **[U]**.

**Fields.** Exactly `HealthPassportBuilder::summary()` + `page()` for every page; sections rendered generically: `list` (label/value), `table` (columns/rows, pills as text), `callout` (body). Pages the builder returns empty print their own empty state. Nothing added, nothing inferred. Cover / back-cover pages are skipped (the sheet header is the cover).

**Layout.** A4; each passport page starts a new sheet page with its number and title.

**Who.** The patient, or a guardian in guardian context (`guardian.context` → `viewing_patient`). Audited as `patient_document_generated`. Throttle `portal.qr`.

**Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
