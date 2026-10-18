# 27 — Dispensing label

Status: **built**. `GET /pharmacy/prescriptions/{id}/labels.pdf` → `documents.new.labels`. Button on the pharmacy prescription detail page.

**Purpose.** Label for each dispensed medicine pack.

**Basis.** WHO good dispensing practice: patient name, medicine and strength, dose, frequency, duration, quantity, date, pharmacy **[U: MINSANTE label rule]**.

**Fields.** `prescription_items` with `dispensed_at` set: displayName(), dose, frequency, route, duration_days, quantity, dispensed_at; `prescriptions.reference`; facility name; patient name + Health ID. Undispensed items get no label.

**Layout.** **Not on the secure sheet** — 100 × 50 mm landscape, one label per page, plain black-on-white for label printers, no background art, no QR (a pack label is not a verifiable document; no verification row is issued). View `documents/new/dispensing-label`.

**Who.** `capability:dispense`; prescription through `PharmacyDeskService::find()` for the desk facility (refusal → 404/403). Nothing dispensed → 404.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
