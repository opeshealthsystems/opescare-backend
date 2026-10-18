# 26 — Appointment slip

Status: **built**. `GET /patient/appointments/{id}/slip.pdf` → `documents.new.appointment`. Button per appointment on the patient appointments page.

**Purpose.** Confirmation the patient brings to the front desk.

**Basis.** Product need.

**Fields.** `appointments`: scheduled_at, appointment_type, status, reason, facility (secure-facility block), provider_id → users.name, checked_in_at. Patient name + Health ID.

**Security.** Carries the sheet QR and reference (same renderer) so a desk can confirm it is genuine. Nothing more.

**Who.** Patient / guardian; appointment.patient_id must be the viewing patient (else 404).

**Print.** A4 (A5 would suit; the secure sheet is A4-only).

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
