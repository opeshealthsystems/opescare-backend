# 20 — Referral letter / counter-referral (fiche de référence / contre-référence)

Status: **built**. Route `GET /facility/referrals/{id}/letter.pdf` → `documents.new.referral`. Button on the referral desk detail page.

**Purpose.** The paper that travels with the patient up (référence) and back down (contre-référence) the health pyramid. One document: the counter-referral part prints once the receiving facility has completed the case with feedback; until then it is an empty panel captioned "to be completed by the receiving facility".

**Audience.** Receiving clinician; the patient carries it; the referring clinician gets the counter-referral back.

**Basis.** MINSANTE health-pyramid referral / counter-referral practice **[U: official form layout not verified]**; WHO referral-system guidance (identity, reason, summary, urgency, sender, receiver, feedback).

**Fields (`referral_cases`).** urgency; status; reason; clinical_summary; receiving_specialty; receiving_provider_name; referringFacility / receivingFacility (`facilities`, via the secure-facility partial); referring_provider_id → `users.name`; created_at, accepted_at, rejected_at + rejection_reason, completed_at; feedback (counter-referral); expires_at. Patient: name, Health ID, DOB, sex.
Not in schema, therefore not printed: vitals at referral, transport mode, escort.

**Layout.** A4 portrait. Patient → from / to → referral (urgency, reason, summary, specialty) → referring signature → counter-referral panel.

**Who.** `capability:clinical_chart`; the desk facility must be a **party** (`ReferralDeskService::findForFacility`) — anyone else gets 404. Consent: `ClinicalRelationshipGate::evaluate()` must hold for that facility, else 403. Access-logged (`AccessLogService`, purpose `treatment:referral`, access_type `print`).

**Security.** Verify QR + document number + sealed sha256.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
