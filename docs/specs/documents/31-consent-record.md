# 31 — Consent record

Status: **built**. `GET /patient/consent-record.pdf` → `documents.new.consents`. Button on the patient Documents page.

**Purpose.** The patient's proof of who they allowed to see their record, and when it ended.

**Basis.** Cameroon personal-data protection law 2024 **[U: law number]**; Finder consent plumbing.

**Fields.** `consent_grants`: facility → name, scope (array), status, authorizing_actor, created_at, expires_at. `consent_requests`: requesting facility, purpose, status, created_at, decided_at. Telemedicine consents (`telemedicine_consents`) are not included — follow-up.

**Who.** Patient / guardian. **Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
