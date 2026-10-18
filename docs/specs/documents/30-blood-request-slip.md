# 30 — Blood request slip

Status: **built**. `GET /facility/blood-requests/{id}/slip.pdf` → `documents.new.blood_request`. Button on the blood request desk detail page.

**Purpose.** Slip for the blood bank: what was asked for and what was decided. It is **not** a crossmatch or compatibility record — the schema holds no crossmatch data, and the slip says so.

**Basis.** National blood-transfusion practice request forms **[U]**.

**Fields.** `blood_requests`: reference, blood_group, component_type, urgency, status (enums `->value`), quantity, needed_by, patient_note, facility_note, confirmed_at, decided_at, fulfilled_at, created_at; careFacility.facility_name; patient name / Health ID when linked. contact_phone not printed.

**Who.** `capability:blood_inventory`; request belongs to a directory listing claimed by the desk facility (else 404).

**Security.** QR + reference + seal when a patient is linked (verification rows are per patient); a request without a patient prints its own `reference` only.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
