# 25 — Emergency summary card

Status: **built**. `GET /patient/emergency-summary.pdf` → `documents.new.emergency`. Button on the patient Documents page.

**Purpose.** One page a first responder can read: identity, allergies, conditions, medicines, emergency contact.

**Basis.** IPS minimal data set (ISO 27269) **[U]**; WHO emergency care.

**Fields.** `HealthPassportBuilder::page('emergency-profile')` sections verbatim; `patients.emergency_contact` (array: name / phone / relationship when recorded). Blood group is **not** a patient column and is not printed.

**Layout.** A4 sheet with a dashed cut-out wallet panel (name, Health ID, allergies) followed by the full sections. A4 because the secure sheet is A4-only; a true CR80 card is a follow-up.

**Who.** Patient / guardian. **Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
