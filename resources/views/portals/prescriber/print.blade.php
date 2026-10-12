{{--
    The printable prescription — an A4 sheet a patient carries to a pharmacy.

    Deliberately NOT extending layouts/portal: this document leaves the building.
    No sidebar, no nav — just the script, the issuer's mark, and a QR that takes
    whoever holds the paper to the live record.

    Two jobs beyond "show the data":

    1. LOOK ISSUED. A sheet with no issuer mark is a sheet anyone can retype in
       Word. So: the platform mark and wordmark, the facility's own name and
       contact block, and both licence numbers — printed as "not recorded" when
       they are missing, because a blank where a licence should be is itself
       information a pharmacist needs.

    2. BE HARD TO FORGE OR MISREAD. A diagonal watermark woven from this
       prescription's own reference (so a photocopied blank is useless), an
       end-of-list rule that closes the medicines table against added lines, a
       framed "verify before dispensing" block that says in words that the paper
       is not the authority, and a security notice that the sheet is void if
       altered and stops verifying if withdrawn.

    Print discipline — this lands on cheap shared clinic printers:
      - Colour appears ONLY as text and border ink. Never as a fill: browsers
        do not print backgrounds unless the user opts in, so anything carried by
        a background would silently vanish. Rules and type survive.
      - #0F4C81 rasterises to roughly a 29% charcoal in greyscale (8.8:1 on
        white), so every brand-coloured element is still legible on a mono
        laser and in a photocopy.
      - The watermark is SVG <text>, not a background-image, for the same
        reason, and it is position:fixed under print so it repeats on page 2+.
      - A long script repeats its column headings and never tears a row in half.

    CSS at-rules are written @@media / @@page — Blade compiles @@ to a literal @,
    which is the only way to be certain a directive-shaped token in a <style>
    block reaches the browser intact in this repo.
--}}
@php
    $rx  = $prescription;
    $ref = (string) ($rx->reference ?? '');

    $patientName = $patient
        ? trim(((string) ($patient->first_name ?? '')) . ' ' . ((string) ($patient->last_name ?? '')))
        : '';

    $prescriberName = trim((string) ($prescriber->name ?? ''));

    // Status label from the prescriber namespace, falling back to the shared
    // enum labeller — the same resolution the on-screen detail view uses, so
    // paper and screen never disagree about what a prescription is called.
    $label = function (?string $value): string {
        $value = (string) $value;
        if ($value === '') {
            return '—';
        }
        $key  = 'prescriber.st_' . $value;
        $text = __($key);

        return $text !== $key ? $text : \App\Support\Enums::label($value);
    };

    $facilityName = (string) ($facility->name ?? ($registry->name ?? ''));

    // Contact block: whatever the care-map registry row actually holds. Blank
    // fields are dropped rather than printed as em dashes.
    $contactLines = array_values(array_filter([
        trim(implode(', ', array_filter([
            (string) ($registry->address ?? ''),
            (string) ($registry->city ?? ''),
            (string) ($registry->region ?? ''),
        ]))),
        (string) ($registry->phone ?? ''),
        (string) ($registry->email ?? ''),
    ], fn ($line) => trim((string) $line) !== ''));

    // Licences are printed whether or not they exist. "Not recorded" is a
    // deliberate statement: a pharmacist reading a blank cannot tell whether
    // the licence is missing from the record or was trimmed off the sheet.
    $notRecorded      = __('prescriber.print_not_recorded');
    $facilityLicence  = trim((string) ($facility->license_number ?? ''));
    $prescriberLicence = trim((string) ($licenseNumber ?? ''));
    $ministryCode     = trim((string) ($registry->ministry_code ?? ''));

    $itemCount = is_countable($items) ? count($items) : 0;

    // The reference is the one string on the sheet that must never wrap: a
    // number split across two lines is a number a pharmacist can mistype. The
    // ref box is a fixed 62mm, so the type size steps down to whatever keeps
    // the whole token on one line (Courier advances at 0.6em + 0.5px tracking).
    $refLen = mb_strlen($ref);
    $refPt  = match (true) {
        $refLen <= 16 => '16pt',
        $refLen <= 21 => '12pt',
        $refLen <= 28 => '8.5pt',
        default       => '6.5pt',
    };

    // ── Watermark geometry ───────────────────────────────────────────────────
    // The tile is sized from the actual string so the pattern never clips it
    // (SVG <pattern> clips its content to the tile). Two staggered rows per
    // tile give the diagonal weave; the second row starts one text-width plus
    // a gap in, which keeps it inside the tile at every reference length.
    $wmText  = trim(($ref !== '' ? $ref . '  ·  ' : '') . mb_strtoupper(__('prescriber.print_brand')));
    $wmChar  = 9.6;   // Courier 12px advance (7.2) + 2.4px tracking
    $wmRun   = (int) ceil(mb_strlen($wmText) * $wmChar);
    $wmGap   = 64;
    $wmTile  = $wmRun * 2 + $wmGap * 2;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $ref !== '' ? __('prescriber.print_doc_title', ['reference' => $ref]) : __('prescriber.detail_title') }}</title>
<style>
    :root {
        --ink: #111;
        --rule: #444;
        --brand: #0F4C81;   /* ≈ 29% grey / 8.8:1 on white once printed mono */
        --wm: #DEE7F1;      /* the watermark, and nothing else */
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        background: #eef1f5;
        color: var(--ink);
        font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 11pt;
        line-height: 1.45;
    }

    /* ── On-screen controls; never printed ─────────────────────────────── */
    .toolbar {
        display: flex; gap: 10px; align-items: center;
        max-width: 210mm; margin: 16px auto 0; padding: 0 6mm;
    }
    .toolbar .spacer { flex: 1; }
    .tb-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 14px; border-radius: 6px; border: 1px solid #c7d0da;
        background: #fff; color: var(--ink); font: inherit; font-size: 10.5pt;
        text-decoration: none; cursor: pointer;
    }
    .tb-btn svg { width: 16px; height: 16px; }
    .tb-btn--primary { background: var(--brand); border-color: var(--brand); color: #fff; }

    /* ── The sheet ─────────────────────────────────────────────────────── */
    .sheet {
        position: relative; z-index: 0;   /* stacking context for the watermark */
        max-width: 210mm; min-height: 297mm; margin: 16px auto 40px;
        background: #fff; padding: 13mm 14mm 12mm;
        border-top: 4px solid var(--brand);
        box-shadow: 0 2px 14px rgba(15, 23, 42, .16);
    }

    /* ── Watermark ─────────────────────────────────────────────────────────
       z-index:-1 puts it in paint step 2 — above the sheet's own white,
       below every line of text. SVG <text>, so it is ink, not a background,
       and a printer will actually lay it down. */
    .watermark {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        z-index: -1; pointer-events: none;
    }
    .wm-t {
        font-family: "Courier New", Courier, monospace;
        font-size: 12px; letter-spacing: 2.4px; font-weight: 700;
        fill: var(--wm);
    }

    /* ── Letterhead ────────────────────────────────────────────────────── */
    .head { display: flex; gap: 10mm; align-items: flex-start; }
    .head__issuer { flex: 1; min-width: 0; }

    .brandmark { display: flex; align-items: center; gap: 7px; }
    .brandmark__logo { width: 9.5mm; height: 9.5mm; flex-shrink: 0; object-fit: contain; }
    .brandmark__name {
        font-size: 13pt; font-weight: 800; line-height: 1.05;
        color: var(--brand); letter-spacing: -.2px;
    }
    .brandmark__strap {
        font-size: 7.5pt; line-height: 1.25; letter-spacing: .3px;
        text-transform: uppercase; color: var(--rule);
    }

    .head__facility {
        margin: 5px 0 2px; font-size: 13pt; line-height: 1.2;
        font-weight: 700; letter-spacing: .1px;
    }
    .head__contact { font-size: 9pt; line-height: 1.45; }
    .head__contact div { word-break: break-word; }
    .head__ids { font-size: 8.5pt; line-height: 1.5; margin-top: 3px; }
    .head__ids .idlab {
        text-transform: uppercase; letter-spacing: .8px; font-size: 7.5pt;
        color: var(--rule);
    }
    .head__ids .idnone { font-style: italic; }

    .head__doc { width: 62mm; flex-shrink: 0; text-align: right; }
    .head__doc .kind {
        margin: 0; font-size: 12pt; font-weight: 800; color: var(--brand);
        text-transform: uppercase; letter-spacing: 2.5px;
    }
    .refbox {
        margin-top: 6px; border: 1.5px solid var(--brand);
        padding: 4px 8px 6px; text-align: right;
    }
    .refbox__label {
        font-size: 7.5pt; text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--brand); font-weight: 700;
    }
    .refbox__value {
        font-family: "Courier New", Courier, monospace;
        font-weight: 700; letter-spacing: .5px; line-height: 1.15;
        word-break: break-all;   /* backstop only; $refPt sizes it to fit */
    }

    .rule { border: 0; border-top: 2px solid var(--brand); margin: 7px 0 0; }

    /* ── The unmissable banner for a script that must not be dispensed ───
       Double-walled black: it must out-shout every brand-coloured stroke on
       the page, none of which is heavier than 2px. */
    .void-banner { margin: 10px 0 0; border: 3px solid var(--ink); padding: 3px; }
    .void-banner__inner {
        border: 1px solid var(--ink); padding: 7px 9px;
        display: flex; gap: 10px; align-items: flex-start;
    }
    .void-banner svg { width: 22px; height: 22px; flex-shrink: 0; margin-top: 1px; }
    .void-banner .vb-title {
        font-size: 13pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
        line-height: 1.2;
    }
    .void-banner .vb-detail { font-size: 9.5pt; }

    /* ── Facts ─────────────────────────────────────────────────────────── */
    .facts { display: flex; gap: 10mm; margin-top: 8px; }
    .facts__col { flex: 1; min-width: 0; }
    h2.section, .facts h2 {
        margin: 0 0 4px; font-size: 8.5pt; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--brand);
        border-bottom: 1px solid var(--brand); padding-bottom: 2px;
    }
    .kv { width: 100%; border-collapse: collapse; font-size: 10pt; }
    .kv td { padding: 2px 0; vertical-align: top; }
    .kv td:first-child { width: 42%; padding-right: 6px; }
    .kv .v { font-weight: 600; word-break: break-word; }
    .kv .sub { font-weight: 400; font-size: 9pt; }
    .mono { font-family: "Courier New", Courier, monospace; letter-spacing: .5px; }
    .none { font-style: italic; font-weight: 400; }

    /* ── Medicines ─────────────────────────────────────────────────────── */
    h2.section {
        margin: 9px 0 3px;
        display: flex; align-items: baseline; justify-content: space-between; gap: 8px;
    }
    h2.section .count { color: var(--ink); letter-spacing: .6px; }
    table.items { width: 100%; border-collapse: collapse; font-size: 10pt; }
    table.items thead th {
        text-align: left; font-size: 8.5pt; font-weight: 700;
        text-transform: uppercase; letter-spacing: .6px;
        padding: 4px 5px; border-bottom: 2px solid var(--brand);
        background: transparent;
    }
    table.items tbody td {
        padding: 4px 5px; border-bottom: 1px solid #bbb; vertical-align: top;
    }
    table.items tbody tr:last-child td { border-bottom: 1px solid var(--rule); }
    table.items .num { text-align: right; white-space: nowrap; }
    table.items .idx { width: 6mm; text-align: right; color: var(--rule); }
    table.items .drug { font-weight: 700; }

    /* Closes the list against a line added by hand after printing. */
    .items-end {
        margin-top: 2px; padding-top: 2px; border-top: 1.5px solid var(--ink);
        font-size: 8pt; text-transform: uppercase; letter-spacing: .9px;
        font-weight: 700; text-align: center;
    }

    .notes { margin-top: 8px; font-size: 10pt; }
    .notes .notes__label {
        font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--brand);
    }
    .notes .notes__body {
        border: 1px solid var(--rule); padding: 5px 7px; margin-top: 3px; min-height: 7mm;
        white-space: pre-wrap; word-break: break-word;
    }

    /* ── Verify + signature ────────────────────────────────────────────── */
    .close { display: flex; gap: 8mm; margin-top: 9px; align-items: stretch; }

    .verify { width: 70mm; flex-shrink: 0; border: 1.5px solid var(--brand); }
    .verify__head {
        display: flex; align-items: center; gap: 6px;
        border-bottom: 1.5px solid var(--brand); padding: 4px 7px;
        font-size: 8.5pt; font-weight: 800; text-transform: uppercase;
        letter-spacing: .9px; color: var(--brand); line-height: 1.2;
    }
    .verify__head svg { width: 15px; height: 15px; flex-shrink: 0; }
    .verify__body { padding: 6px 7px 7px; text-align: center; }
    .verify .qr { width: 28mm; height: 28mm; margin: 0 auto; }
    .verify .qr svg { width: 100%; height: 100%; display: block; }
    .verify .scan { font-size: 8.5pt; font-weight: 700; margin-top: 4px; line-height: 1.3; }
    .verify .url {
        font-family: "Courier New", Courier, monospace; font-size: 7pt;
        word-break: break-all; line-height: 1.3; margin-top: 2px;
    }
    .verify__note {
        margin: 5px 0 0; padding-top: 4px; border-top: 1px solid var(--rule);
        font-size: 7.5pt; line-height: 1.35; text-align: left;
    }
    .verify--none {
        border: 1px dashed var(--rule); padding: 8px; font-size: 8.5pt; text-align: left;
    }

    .sign { flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 6px; }
    .sign__repeat {
        border: 2px solid var(--ink); padding: 4px 7px; margin-bottom: 6px;
        font-size: 9.5pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
        line-height: 1.25;
    }
    .sign__line { border-bottom: 1px solid var(--ink); height: 16mm; }
    .sign__caption { font-size: 8.5pt; margin-top: 3px; }
    .sign__who { font-size: 10pt; font-weight: 700; }
    .sign__lic { font-size: 8.5pt; }

    /* ── Security notice ─────────────────────────────────────────────────
       It lives at the top of the signature column, which was otherwise 48mm
       of white space beside the taller verify box. Costs no page height and
       puts the terms directly above the hand that signs them. */
    .security {
        border: 1px solid var(--ink); padding: 5px 7px;
        display: flex; gap: 7px; align-items: flex-start;
        font-size: 8pt; line-height: 1.35;
    }
    .security svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
    .security__head {
        font-size: 8pt; font-weight: 800; text-transform: uppercase; letter-spacing: 1.1px;
    }

    .foot {
        margin-top: 8px; padding-top: 4px; border-top: 1px solid var(--rule);
        font-size: 7.5pt; line-height: 1.5;
        display: flex; gap: 8mm; align-items: flex-start;
    }
    .foot__legal { flex: 1; }
    .foot__stamp { text-align: right; white-space: nowrap; }

    /* ── Print ─────────────────────────────────────────────────────────── */
    @@page { size: A4; margin: 12mm; }

    @@media print {
        /* No fills, no shadows — a shaded block is a solid bar of toner on a
           shared clinic printer and photocopies to mud. Colour survives only
           as type and rules, which print regardless of the browser's
           "background graphics" setting. */
        * { background: transparent !important; box-shadow: none !important; }
        body { background: #fff; font-size: 10.5pt; }
        .no-print { display: none !important; }
        .sheet {
            max-width: none; min-height: 0; margin: 0;
            padding: 3mm 0 0; box-shadow: none;
        }
        /* Fixed, so the watermark is redrawn on page 2 and beyond instead of
           stopping where the first page ends. */
        .watermark { position: fixed; top: 0; left: 0; width: 100%; height: 100%; }
        /* A script that runs past one page keeps its column headings and never
           tears a medicine line in half. */
        table.items { page-break-inside: auto; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; break-inside: avoid; }
        .head, .facts, .void-banner, .items-end, .close, .foot {
            page-break-inside: avoid; break-inside: avoid;
        }
        a { text-decoration: none; }
    }

    @@media screen and (max-width: 820px) {
        .head, .facts, .close { flex-direction: column; gap: 6mm; }
        .head__doc { text-align: left; width: auto; }
        .refbox { text-align: left; }
        .verify { width: auto; }
    }
</style>
</head>
<body>

<div class="toolbar no-print">
    <button type="button" class="tb-btn tb-btn--primary" data-print>
        {{-- Lucide "printer", inlined: a print sheet must not depend on a CDN icon font. --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect width="12" height="8" x="6" y="14"></rect></svg>
        {{ __('prescriber.print_action') }}
    </button>
    <a class="tb-btn" href="{{ route('portals.prescriber.show', $rx->id) }}">
        {{-- Lucide "arrow-left" --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>
        {{ __('prescriber.back') }}
    </a>
    <div class="spacer"></div>
</div>

<div class="sheet">

    {{--
        The watermark carries THIS prescription's reference, so a sheet
        photocopied blank and refilled by hand still says whose script it was.
        Pure SVG: no image request, and text ink a printer will actually put
        on the page.
    --}}
    <svg class="watermark" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <defs>
            <pattern id="rx-watermark" patternUnits="userSpaceOnUse" width="{{ $wmTile }}" height="96" patternTransform="rotate(-32)">
                <text class="wm-t" x="0" y="28">{{ $wmText }}</text>
                <text class="wm-t" x="{{ $wmRun + $wmGap }}" y="76">{{ $wmText }}</text>
            </pattern>
        </defs>
        <rect x="0" y="0" width="100%" height="100%" fill="url(#rx-watermark)"></rect>
    </svg>

    {{-- Who issued it, under whose platform, and what this piece of paper is --}}
    <header class="head">
        <div class="head__issuer">
            <div class="brandmark">
                {{-- The platform mark the rest of the app uses (layouts/public, verify/qr). --}}
                <img class="brandmark__logo" src="{{ asset('brand/opescare-favicon.png') }}" alt="" width="44" height="44">
                <div>
                    <div class="brandmark__name">{{ __('prescriber.print_brand') }}</div>
                    <div class="brandmark__strap">{{ __('prescriber.print_platform_strap') }}</div>
                </div>
            </div>
            <h1 class="head__facility">{{ $facilityName !== '' ? $facilityName : __('prescriber.print_facility') }}</h1>
            <div class="head__contact">
                @foreach($contactLines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
            <div class="head__ids">
                <div>
                    <span class="idlab">{{ __('prescriber.print_facility_license') }}</span>
                    @if($facilityLicence !== '')
                        <span class="mono">{{ $facilityLicence }}</span>
                    @else
                        <span class="idnone">{{ $notRecorded }}</span>
                    @endif
                </div>
                @if($ministryCode !== '')
                <div>
                    <span class="idlab">{{ __('prescriber.print_ministry_code') }}</span>
                    <span class="mono">{{ $ministryCode }}</span>
                </div>
                @endif
            </div>
        </div>
        <div class="head__doc">
            <p class="kind">{{ __('prescriber.detail_title') }}</p>
            <div class="refbox">
                <div class="refbox__label">{{ __('prescriber.print_reference') }}</div>
                <div class="refbox__value" style="font-size: {{ $refPt }}">{{ $ref !== '' ? $ref : '—' }}</div>
            </div>
        </div>
    </header>
    <hr class="rule">

    {{--
        A terminal or lapsed script must be impossible to mistake for a live
        one. Double black border, all-caps, and it repeats beside the signature
        so a second page or a folded sheet still carries the warning.
    --}}
    @if($isNotValid || $isExpired)
    <div class="void-banner">
        <div class="void-banner__inner">
            {{-- Lucide "triangle-alert" --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
            <div>
                <div class="vb-title">
                    @if($isNotValid)
                        {{ __('prescriber.print_void_banner') }}
                    @else
                        {{ __('prescriber.print_expired_banner') }}
                    @endif
                </div>
                <div class="vb-detail">
                    @if($isNotValid)
                        {{ __('prescriber.print_void_detail', [
                            'status' => $label($rxStatus),
                            'date'   => $rx->voided_at?->translatedFormat('d M Y H:i') ?? ($rx->updated_at?->translatedFormat('d M Y H:i') ?? '—'),
                        ]) }}
                        @if(filled($rx->void_reason))
                            <div>{{ __('prescriber.void_reason') }}: {{ $rx->void_reason }}</div>
                        @endif
                    @else
                        {{ __('prescriber.print_expired_detail', [
                            'date' => $rx->expires_at?->translatedFormat('d M Y') ?? '—',
                        ]) }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Who it is for, and who ordered it --}}
    <div class="facts">
        <div class="facts__col">
            <h2>{{ __('prescriber.patient') }}</h2>
            <table class="kv">
                <tr>
                    <td>{{ __('prescriber.col_patient') }}</td>
                    <td class="v">{{ $patientName !== '' ? $patientName : '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.health_id') }}</td>
                    <td class="v mono">{{ $patient?->health_id ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.print_date_of_birth') }}</td>
                    <td class="v">{{ $patient?->date_of_birth?->translatedFormat('d M Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.print_sex') }}</td>
                    <td class="v">@if($patient && filled($patient->sex))@enum($patient->sex, 'sex')@else—@endif</td>
                </tr>
            </table>
        </div>
        <div class="facts__col">
            <h2>{{ __('prescriber.detail_title') }}</h2>
            <table class="kv">
                <tr>
                    <td>{{ __('prescriber.status') }}</td>
                    <td class="v">{{ $label($rxStatus) }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.issued_at') }}</td>
                    <td class="v">{{ $rx->prescribed_at?->translatedFormat('d M Y H:i') ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.expires_at') }}</td>
                    <td class="v">{{ $rx->expires_at?->translatedFormat('d M Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <td>{{ __('prescriber.col_prescriber') }}</td>
                    <td class="v">
                        {{ $prescriberName !== '' ? $prescriberName : '—' }}
                        @if(filled($credential))<div class="sub">{{ $credential }}</div>@endif
                        <div class="sub">
                            {{ __('prescriber.print_license') }}:
                            @if($prescriberLicence !== '')<span class="mono">{{ $prescriberLicence }}</span>@else<span class="none">{{ $notRecorded }}</span>@endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- The order itself. The count is stated twice — in the heading and on the
         closing rule — so an added line is arithmetic, not a judgement call. --}}
    <h2 class="section">
        <span>{{ __('prescriber.medicines') }}</span>
        <span class="count">{{ __('prescriber.print_items_count', ['count' => $itemCount]) }}</span>
    </h2>
    <table class="items">
        <thead>
            <tr>
                <th class="idx">#</th>
                <th>{{ __('prescriber.drug_name') }}</th>
                <th>{{ __('prescriber.dose') }}</th>
                <th>{{ __('prescriber.frequency') }}</th>
                <th>{{ __('prescriber.route') }}</th>
                <th class="num">{{ __('prescriber.duration_days') }}</th>
                <th class="num">{{ __('prescriber.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $i => $item)
            <tr>
                <td class="idx">{{ $i + 1 }}</td>
                <td class="drug">{{ $item->drug_name ?: '—' }}</td>
                <td>{{ $item->dose ?: '—' }}</td>
                <td>{{ $item->frequency ?: '—' }}</td>
                <td>{{ $item->route ? \App\Support\Enums::label($item->route, 'route') : '—' }}</td>
                <td class="num">{{ $item->duration_days !== null ? $item->duration_days : '—' }}</td>
                <td class="num">{{ $item->quantity !== null ? $item->quantity : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7">{{ __('prescriber.print_no_items') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="items-end">{{ __('prescriber.print_items_end', ['count' => $itemCount]) }}</div>

    @if(filled($rx->notes))
    <div class="notes">
        <div class="notes__label">{{ __('prescriber.notes') }}</div>
        <div class="notes__body">{{ $rx->notes }}</div>
    </div>
    @endif

    {{-- Scan-to-verify, and the hand that signed it --}}
    <div class="close">
        <div class="verify">
            <div class="verify__head">
                {{-- Lucide "scan-line" --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><path d="M7 12h10"></path></svg>
                {{ __('prescriber.print_verify_heading') }}
            </div>
            <div class="verify__body">
                @if($qrSvg && $verifyUrl)
                    <div class="qr">{!! $qrSvg !!}</div>
                    <div class="scan">{{ __('prescriber.print_scan_to_verify') }}</div>
                    <div class="url">{{ $verifyUrl }}</div>
                    <p class="verify__note">{{ __('prescriber.print_verify_body') }}</p>
                @else
                    <div class="verify--none">{{ __('prescriber.print_verify_unavailable') }}</div>
                    <p class="verify__note">{{ __('prescriber.print_verify_ref') }}</p>
                @endif
            </div>
        </div>
        <div class="sign">
            {{-- Void if altered, and paper is not proof — set directly above
                 the hand that signs, in space the verify box already paid for. --}}
            <div class="security">
                {{-- Lucide "shield-check" --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
                <div>
                    <div class="security__head">{{ __('prescriber.print_security_heading') }}</div>
                    <div>{{ __('prescriber.print_security_altered') }}</div>
                    <div>{{ __('prescriber.print_security_withdrawn') }}</div>
                </div>
            </div>
            <div class="sign__block">
                @if($isNotValid || $isExpired)
                <div class="sign__repeat">
                    @if($isNotValid){{ __('prescriber.print_void_banner') }}@else{{ __('prescriber.print_expired_banner') }}@endif
                </div>
                @endif
                <div class="sign__line"></div>
                <div class="sign__who">{{ $prescriberName !== '' ? $prescriberName : '—' }}@if(filled($credential)) — {{ $credential }}@endif</div>
                <div class="sign__lic">
                    {{ __('prescriber.print_license') }}:
                    @if($prescriberLicence !== '')<span class="mono">{{ $prescriberLicence }}</span>@else<span class="none">{{ $notRecorded }}</span>@endif
                </div>
                <div class="sign__caption">{{ __('prescriber.print_signature') }}</div>
            </div>
        </div>
    </div>

    <div class="foot">
        <div class="foot__legal">{{ __('prescriber.print_footer') }}</div>
        <div class="foot__stamp">
            <div class="mono">{{ $ref !== '' ? $ref : '—' }}</div>
            <div>{{ __('prescriber.print_generated', ['datetime' => now()->translatedFormat('d M Y H:i')]) }}</div>
        </div>
    </div>

</div>

</body>
</html>
