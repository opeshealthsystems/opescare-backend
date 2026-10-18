# 32 — Patient access log

Status: **built**. `GET /patient/access-log.pdf` → `documents.new.access_log`. Button on the patient Access logs page.

**Purpose.** Who looked at my record.

**Basis.** Data-protection right of access **[U]**.

**Fields.** `access_logs` for the patient, newest 500: created_at, actor (users.name when the actor is a user, else the actor type), facility → name, purpose, data_category, access_type, emergency_access. ip_address and user_agent are **not printed**.

**Who.** Patient / guardian. **Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
