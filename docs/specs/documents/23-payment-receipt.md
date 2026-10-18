# 23 — Payment receipt (reçu)

Status: **built**. `GET /facility/billing/receipts/{id}/receipt.pdf` → `documents.new.receipt`. Button on the HTML receipt page and per receipt on the invoice page.

**Purpose.** Proof of one payment.

**Basis.** As the invoice; MTN MoMo / Orange Money references printed exactly as recorded.

**Fields.** `receipts`: receipt_number, amount, issued_at. `payments`: payment_reference, method, gateway, gateway_transaction_id, status, payer_name, confirmed_at, cashier → users.name. Invoice number and the invoice balance. Whole francs XAF. `payer_phone`, ip_address and device fields are **not printed**.

**Who.** `capability:billing`; the receipt's invoice belongs to the desk facility (else 404).

**Security.** QR + reference + seal.

## Common to every new document (20–32)
- Sheet: `@extends('pdf.layouts.secure')` (spec 00), sections `document_title` / `content` / `head`; partials `pdf.layouts.secure-facility`, `pdf.layouts.secure-signature`. The layout itself is not edited.
- Rendering: `App\Services\Documents\NewDocumentRenderer` — `DocumentVerificationService::issue()` before render, QR to `/verify/doc/{token}` (opaque token, no PII in any URL), `seal()` stores the sha256 of the exact bytes, streamed with `Cache-Control: no-store`. **Never written to disk.**
- Filenames: document kind + Health ID + reference, never the name.
- Lang: `lang/{en,fr}/doc_new_docs.php`. Never-invent: an empty column prints the "not recorded" dash; an empty section prints its empty line.
- Facility identity: `DeskFacilityContext::resolve()` (session posting) — never the request.
- Tests: `tests/Feature/Documents/New*`.
