# 29 — Imaging request form

Status: **built**. `GET /facility/imaging/orders/{id}/request.pdf` → `documents.new.imaging_request`. Button on the imaging order detail page.

**Purpose / basis.** Radiology request: indication, modality, region, urgency, referring physician — the justification principle (IAEA / WHO radiation protection) **[U: national form]**.

**Fields.** `imaging_orders`: accession_number, modality (`modalityLabel()`), body_part, clinical_indication, urgency, status (enum `->value`), referring_physician, ordered_by → users.name, ordered_at, scheduled_at, notes. Pregnancy status and contrast allergy are not columns — blank lines for the radiographer, not invented.

**Who.** `capability:imaging`; order via `ImagingDeskService::find(facility, id)` (else 404).

**Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
