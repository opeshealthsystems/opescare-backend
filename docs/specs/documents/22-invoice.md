# 22 — Invoice (facture)

Status: **built**. `GET /facility/billing/{id}/invoice.pdf` → `documents.new.invoice`. Button on the billing desk invoice page.

**Purpose.** Statement of what is owed for care at this facility. **Never a gate on care** (`CareIsNeverGatedOnPaymentTest` stays green; the document only reads).

**Basis.** Commercial invoice practice: issuer, number, date, lines, totals, currency. OHADA accounting **[U]**. Amounts XAF, whole francs (FCFA, exponent 0), thousands separated by a space.

**Fields.** `invoices`: invoice_number, status, issued_at, subtotal_amount, discount_amount, insurance_covered_amount, patient_responsibility_amount, paid_amount, refunded_amount, balance_amount, voided_at / void_reason (prints the VOID watermark). `invoice_items`: service_code, description, quantity, unit_price, discount_amount, line_total_amount. Facility block; patient name + Health ID.

**Who.** `capability:billing`; invoice.facility_id = desk facility (else 404). No clinical consent gate: the content is the facility's own billing.

**Security.** QR + reference + seal; watermark when voided.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
