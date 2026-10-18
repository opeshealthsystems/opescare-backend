# 28 — Laboratory request form (bon d'examen)

Status: **built**. `GET /facility/lab/orders/{id}/request.pdf` → `documents.new.lab_request`. Button on the lab order detail page.

**Purpose.** The request that travels with the specimen / patient.

**Basis.** ISO 15189:2022 §7.2 (pre-examination): patient identification, requester identity and contact, examination requested, clinical information, primary sample type, collection date/time, urgency.

**Fields.** `lab_orders`: test_name, test_code, urgency, status, clinical_indication, notes, ordered_at, collected_at, ordered_by → users.name; facility; patient name, Health ID, DOB, sex. **Sample type is not a column** — printed as a blank line to fill at collection (not invented).

**Who.** `capability:clinical_chart,lab_results`; order.facility_id = desk facility (else 404). The order is the facility's own episode (FACILITY_EPISODE basis).

**Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
