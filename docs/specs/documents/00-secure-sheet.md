# 00 — OpesCare Secure sheet (shared PDF layout)

Owner: existing-documents track. Status: live, 2026-10-18.
Files: `resources/views/pdf/layouts/secure.blade.php` (layout),
`resources/views/pdf/layouts/secure-signature.blade.php` (signature block partial),
`resources/views/pdf/layouts/secure-facility.blade.php` (issuing-facility block partial).
Lang: `lang/{en,fr}/export.php` (header/footer strings, pre-existing) and
`lang/{en,fr}/doc_ex_sheet.php` (strings added by this pass).

Every patient-facing PDF and every facility PDF report extends this sheet. **Extend it,
do not edit it.** Anything you need that is not here: ask the owner, or add your own
partial outside `pdf/layouts/secure*`.

## Public interface (stable, backward compatible)

### Sections
| Section | Required | What it is |
|---|---|---|
| `document_title` | yes | Document type, printed in the header band and the `<title>` |
| `content` | yes | Body. Tables only (dompdf: no flex/grid) |
| `head` | no | Extra `<style>` for this document. Do not override `@page` |

### Variables (all optional unless stated)
| Variable | Type | Default | Effect |
|---|---|---|---|
| `$patient` | `App\Models\Patient\|null` | null | Holder name in header; shield in footer when verified. Null for facility reports |
| `$issuedAt` | `Carbon` | `now()` | "Issued" stamp in header |
| `$verification` | `DocumentVerification\|null` | null | Document number (`reference`, e.g. `OC-DOC-XXXX-XXXX`) in header and under QR |
| `$verifyQr` | `string\|null` (data URI PNG) | null | QR in the footer, must resolve to `/verify/doc/{token}` |
| `$sheetTone` | `'standard'\|'light'` | `'standard'` | **New.** `light` = toner-light: no background art, no dark band, ink-only header (brand-blue rule + text), white footer. Use for mono printers / photocopies |
| `$pageStamp` | `bool` | `false` | **New.** Set by `SecureSheet`; leaves the page cell for the canvas stamp |
| `$watermark` | `string\|null` | null | **New.** Diagonal translucent word on every page (e.g. `__('doc_ex_sheet.watermark_void')`). Use for void / expired / revoked / copy |

Callers that pass none of the new variables get the same sheet as before (only the
legibility changes below apply).

### Partials (include from `content`)
- `@include('pdf.layouts.secure-signature', ['name' => ?string, 'role' => ?string, 'registration' => ?string, 'date' => ?Carbon, 'label' => ?string])`
  — signature / stamp box. Prints a blank line for a wet signature. Never invents a name:
  null prints the "not recorded" dash.
- `@include('pdf.layouts.secure-facility', ['facility' => Facility|CareFacility|null])`
  — issuing facility: name, licence / registration number, address, phone, whichever
  columns exist and are filled.

### CSS classes available in `content`
`h1`, `.subtitle`, `h2` (+ `.hint`), `table.data` (+ `td.num`, `td.group`), `table.kv`
(`td.k`, `td.v`, `td.v.id`), `.pill.ok|warn|bad|info|mute`, `.muted`, `.empty`, `.note`,
`.sig` (signature block), `.box` (bordered panel).

## Security features
- **Verify QR** → `/verify/doc/{token}`; token `doc_{lookup}_{secret}`, secret stored hashed.
  Issue the row with `DocumentVerificationService::issue()` BEFORE render, then
  `seal($row, $bytes)` stores the sha256 of the exact bytes handed over.
- **Document number** = `$verification->reference`, printed in header and under the QR.
- **Microtext** line across the footer (`export.microtext`) and corner markers.
- **Watermark** (`$watermark`) for void/expired/revoked states.
- **No PII in URLs**: the QR carries only the opaque token. Filenames carry the Health ID
  and reference, never the name.
- **Never stored**: render on demand, stream with `Cache-Control: no-store`.

## Verifier labels
`/verify/doc/{token}` names the document type from, in order: `verify.doc_type_{type}`,
`doc_ex_sheet.type.{type}`, `doc_new_types.{type}` (a flat lang file the new-documents track may
create), else "OpesCare document". Facility documents (no patient) use
`DocumentVerificationService::issueForFacility($facilityId, $type, $userId)`; the verifier then
shows no holder.

## Print rules
- A4 portrait, page margins 33/16/30/16 mm; header/footer fixed and repeated per page.
- Body text min **8pt** (10.7px at dompdf 96 dpi); footer min **7pt**; table header 8pt.
- Footer: **"Page N of M"**. dompdf prints 0 for CSS `counter(pages)` in fixed boxes, so
  render through **`App\Support\SecureSheet::render($view, $data)`** (returns bytes; sets
  paper/options and stamps "Page N of M" on the canvas). If you build the `Pdf` yourself, pass
  `'pageStamp' => true` in the view data and finish with `SecureSheet::output($pdf, $hasQr)`.
  Rendering the layout without either still prints "Page N" via CSS.
- `table.data` rows do not break across pages; `thead` repeats.
- Brand blue `#0F4C81` only accent. No emoji; no icon fonts (Lucide is web-only; on the
  sheet use the existing PNG seal/shield assets).
- Render options: `setOptions([... 'defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true, 'isFontSubsettingEnabled' => true, 'dpi' => 96], true)`
  — the trailing `true` is mandatory (merges; without it the chroot is lost and images vanish).
