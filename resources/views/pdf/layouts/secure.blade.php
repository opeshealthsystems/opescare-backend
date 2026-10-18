{{--
    OpesCare Secure document sheet — the master background every generated
    document sits on (record exports today; prescriptions, lab reports,
    certificates, referrals and radiology reports as they gain a PDF).

    Rendered by dompdf, so the rules differ from the web portal:
      * `position: fixed` elements repeat on every page — that is how the
        background art, the header band and the footer are laid down once
        and appear on each sheet.
      * Images are local files under public/ (dompdf's chroot is base_path();
        remote loading is off), never URLs.
      * DejaVu Sans is the only bundled font family, so the sheet uses it
        for everything and leans on weight, size and spacing for hierarchy.
      * No flexbox / grid — tables only.

    Interface (documented in docs/specs/documents/00-secure-sheet.md — keep
    it backward compatible, other documents extend this sheet):

    Slots:  @section('document_title')     the document type ("Medical Record")
            @section('content')            the body
            @section('head')               optional extra <style>

    Variables expected from the caller:
            $patient      (App\Models\Patient)        — holder shown in the header
            $issuedAt     (Carbon)                    — defaults to now()
            $verification (DocumentVerification|null) — reference in the header and under the QR
            $verifyQr     (string|null)               — data URI of the QR that resolves to /verify/doc/{token}
            $sheetTone    ('standard'|'light')        — 'light' = toner-light print variant (no fills)
            $watermark    (string|null)               — diagonal word on every page (VOID, EXPIRED…)

    Legibility floor: body >= 8pt (10.7px at 96 dpi), footer >= 7pt (9.4px).
--}}
@php
    $issuedAt     = $issuedAt ?? now();
    $verification = $verification ?? null;
    $verifyQr     = $verifyQr ?? null;
    $sheetTone    = ($sheetTone ?? 'standard') === 'light' ? 'light' : 'standard';
    $watermark    = $watermark ?? null;
    $patient      = $patient ?? null;
    $pageStamp    = $pageStamp ?? false;
    $sheet      = public_path('images/health-id/document-sheet-a4.jpg');
    $band       = public_path('images/health-id/sheet-band.jpg');
    $seal       = public_path('images/health-id/sheet-seal.png');
    $shield     = public_path('images/health-id/sheet-shield.png');
    $holderName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $isVerified = ($patient->verification_status ?? null) instanceof \App\Enums\VerificationStatus
        && in_array($patient->verification_status, [
            \App\Enums\VerificationStatus::Verified,
            \App\Enums\VerificationStatus::VerifiedByFacility,
            \App\Enums\VerificationStatus::FacilityVerified,
        ], true);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>@yield('document_title') — {{ $holderName }}</title>
    <style>
        /* Real page margins: dompdf starts EVERY page's flow inside them, so a
           long table never runs under the header. The fixed art is offset by
           the negative of these margins to reach the physical page edge. */
        @page { margin: 33mm 16mm 30mm 16mm; }

        /* Brand tokens, mirroring the portal: brand blue #0F4C81, navy ink #0E1729, cream paper #FDF8F0 / #EADFC8. */
        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1A2338;
            background: #FFFFFF;
        }

        /* ---- Sheet art, repeated on every page ---------------------------- */
        .sheet-bg { position: fixed; top: -33mm; left: -16mm; width: 210mm; height: 297mm; z-index: -3; }
        .sheet-bg img { width: 210mm; height: 297mm; }

        .sheet-head { position: fixed; top: -33mm; left: -16mm; width: 210mm; height: 26mm; background: #0E1729; z-index: -2; }
        .sheet-head .band { position: absolute; top: 0; left: 0; width: 210mm; height: 26mm; opacity: .22; }
        .sheet-head .band img { width: 210mm; height: 26mm; }
        .sheet-head .rule { position: absolute; left: 0; bottom: 0; width: 210mm; height: 1.2mm; background: #0F4C81; }
        .sheet-head .rule-cream { position: absolute; left: 0; bottom: 1.2mm; width: 210mm; height: .4mm; background: #EADFC8; }

        .head-table { position: absolute; top: 4.5mm; left: 14mm; width: 182mm; border-collapse: collapse; }
        .head-table td { vertical-align: middle; padding: 0; }
        .brand-word   { font-size: 15px; font-weight: bold; color: #FFFFFF; letter-spacing: .3px; line-height: 1.1; }
        .brand-kicker { font-size: 9.4px; color: #EADFC8; text-transform: uppercase; letter-spacing: 1.6px; margin-top: 1.5px; }
        .head-doc { text-align: right; }
        .head-doc .type { font-size: 12.5px; font-weight: bold; color: #FFFFFF; line-height: 1.15; }
        .head-doc .meta { font-size: 9.4px; color: #C9D3E3; margin-top: 2px; white-space: nowrap; }
        .head-doc .meta b { color: #FFFFFF; font-weight: normal; }
        .head-seal img { width: 17mm; height: 17mm; }

        .sheet-foot { position: fixed; bottom: -30mm; left: -16mm; width: 210mm; height: 25mm; z-index: -2; }
        .foot-micro {
            position: absolute; top: 0; left: 0; width: 210mm; height: 3.2mm;
            background: #0E1729; color: #8FA5C6;
            font-size: 6px; letter-spacing: 1.1px; text-transform: uppercase;
            line-height: 3.2mm; text-align: center; overflow: hidden; white-space: nowrap;
        }
        .foot-table { position: absolute; top: 4.5mm; left: 14mm; width: 182mm; border-collapse: collapse; }
        .foot-table td { vertical-align: top; padding: 0; font-size: 9.4px; color: #5B6B85; line-height: 1.35; }
        .foot-table .shield img { width: 6mm; height: 6mm; }
        .foot-table .page { text-align: right; white-space: nowrap; color: #0F4C81; font-weight: bold; font-size: 9.4px; }
        .foot-table .page .n:after { content: counter(page); }
        .foot-table .page .m:after { content: counter(pages); }
        .foot-table .qr { text-align: center; vertical-align: top; }
        .foot-table .qr img { width: 15mm; height: 15mm; display: block; margin: 0 auto; }
        .foot-table .qr .cap { font-size: 7.5px; letter-spacing: .8px; text-transform: uppercase; color: #0F4C81; font-weight: bold; margin-top: 1px; white-space: nowrap; }
        .foot-table .qr .ref { font-family: "DejaVu Sans Mono", monospace; font-size: 8px; color: #5B6B85; white-space: nowrap; }

        /* Corner markers, drawn in CSS so they stay crisp at any print size. */
        .corner { position: fixed; width: 7mm; height: 7mm; z-index: -1; }
        .corner.tl { top: -3mm;    left: -8mm;  border-top: .5mm solid #0F4C81;    border-left: .5mm solid #0F4C81; }
        .corner.tr { top: -3mm;    right: -8mm; border-top: .5mm solid #0F4C81;    border-right: .5mm solid #0F4C81; }
        .corner.bl { bottom: -1mm; left: -8mm;  border-bottom: .5mm solid #0F4C81; border-left: .5mm solid #0F4C81; }
        .corner.br { bottom: -1mm; right: -8mm; border-bottom: .5mm solid #0F4C81; border-right: .5mm solid #0F4C81; }

        /* ---- Body ---------------------------------------------------------- */
        .content { padding: 0; }

        h1 { font-size: 19px; color: #0E1729; margin: 0 0 1px; letter-spacing: -.2px; }
        .subtitle { font-size: 10.7px; color: #5B6B85; text-transform: uppercase; letter-spacing: 1.4px; margin: 0 0 8px; }

        h2 {
            font-size: 12px; color: #0F4C81; text-transform: uppercase; letter-spacing: 1.2px;
            margin: 14px 0 4px; padding: 0 0 3px; border-bottom: .6px solid #0F4C81;
        }
        h2 .hint { float: right; font-size: 9.4px; color: #5B6B85; letter-spacing: .6px; font-weight: normal; padding-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.data tr { page-break-inside: avoid; }
        table.data th {
            background: #E7EEF6; color: #0E1729; text-align: left;
            padding: 4px 6px; font-size: 10.7px; text-transform: uppercase; letter-spacing: .6px;
            border-bottom: .5px solid #C5D3E4;
        }
        table.data td { padding: 4px 6px; border-bottom: .4px solid #DCE4EE; vertical-align: top; }
        table.data tr:nth-child(even) td { background: rgba(253, 248, 240, .65); }
        table.data td.num { text-align: right; white-space: nowrap; }
        table.data td.group { background: #F3F6FA; color: #0E1729; font-size: 10.7px; padding: 5px 6px; }
        table.data td.group .ref { font-family: "DejaVu Sans Mono", monospace; color: #0F4C81; letter-spacing: .6px; }
        .muted { color: #8A97AD; }

        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 3px 6px 3px 0; vertical-align: top; border-bottom: .4px solid #E3E9F1; }
        .kv td.k { width: 36mm; color: #5B6B85; font-size: 10.7px; text-transform: uppercase; letter-spacing: .7px; padding-top: 4px; }
        .kv td.v { font-size: 11px; color: #0E1729; }
        .kv td.v.id { font-family: "DejaVu Sans Mono", monospace; letter-spacing: 1.2px; font-weight: bold; color: #0F4C81; }

        .pill { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 10px; text-transform: uppercase; letter-spacing: .6px; white-space: nowrap; }
        .pill.ok   { background: #E3F2E9; color: #146C43; }
        .pill.warn { background: #FCEFD6; color: #8A5A00; }
        .pill.bad  { background: #FBE3E3; color: #9B1C1C; }
        .pill.info { background: #E7EEF6; color: #0F4C81; }
        .pill.mute { background: #EEF1F5; color: #5B6B85; }

        .empty { color: #8A97AD; font-style: italic; padding: 2px 0 0; }
        .note { font-size: 10.7px; margin: 8px 0 0; }
        table.data thead { display: table-header-group; }
        .box { border: .6px solid #C5D3E4; padding: 6px 8px; margin-top: 4px; }

        /* Signature / stamp block (pdf/layouts/secure-signature). */
        .sig { width: 100%; border-collapse: collapse; margin-top: 14px; page-break-inside: avoid; }
        .sig td { vertical-align: bottom; padding: 0 8px 0 0; font-size: 10.7px; }
        .sig .line { border-bottom: .6px solid #0E1729; height: 16mm; }
        .sig .cap { color: #5B6B85; font-size: 9.4px; text-transform: uppercase; letter-spacing: .6px; padding-top: 2px; }

        /* Diagonal watermark (VOID, EXPIRED, REVOKED, COPY). */
        .watermark {
            position: fixed; top: 95mm; left: -10mm; width: 200mm; text-align: center;
            font-size: 64px; font-weight: bold; letter-spacing: 8px; color: #9B1C1C; opacity: .12;
            transform: rotate(-30deg); z-index: -1; text-transform: uppercase;
        }

        /* ---- Toner-light variant: ink only, no fills, no background art ---- */
        body.tone-light .sheet-head { background: #FFFFFF; border-bottom: .8mm solid #0F4C81; }
        body.tone-light .brand-word, body.tone-light .head-doc .type { color: #0F4C81; }
        body.tone-light .brand-kicker, body.tone-light .head-doc .meta { color: #1A2338; }
        body.tone-light .head-doc .meta b { color: #0E1729; font-weight: bold; }
        body.tone-light .foot-micro { background: #FFFFFF; color: #5B6B85; border-top: .3mm solid #0F4C81; }
        body.tone-light table.data th { background: #FFFFFF; border-bottom: .8px solid #0E1729; }
        body.tone-light table.data tr:nth-child(even) td { background: #FFFFFF; }
        body.tone-light table.data td.group { background: #FFFFFF; border-bottom: .6px solid #0E1729; }
        body.tone-light .pill { background: #FFFFFF; border: .5px solid #5B6B85; color: #0E1729; }
    </style>
    @yield('head')
</head>
<body class="tone-{{ $sheetTone }}">

@if($sheetTone === 'standard')
<div class="sheet-bg"><img src="{{ $sheet }}" alt=""></div>
@endif

@if($watermark)
<div class="watermark">{{ $watermark }}</div>
@endif

<div class="sheet-head">
    @if($sheetTone === 'standard')
    <div class="band"><img src="{{ $band }}" alt=""></div>
    <div class="rule-cream"></div>
    <div class="rule"></div>
    @endif
    <table class="head-table">
        <tr>
            <td class="head-seal" style="width: 19mm;"><img src="{{ $seal }}" alt=""></td>
            <td style="width: 52mm;">
                <div class="brand-word">{{ __('export.brand') }}</div>
                <div class="brand-kicker">{{ __('export.secure_kicker') }}</div>
            </td>
            <td class="head-doc">
                <div class="type">@yield('document_title')</div>
                <div class="meta">
                    @if($verification)
                        {{ __('export.reference') }} <b>{{ $verification->reference }}</b> &nbsp;·&nbsp;
                    @endif
                    {{ __('export.issued') }} <b>{{ $issuedAt->translatedFormat('d M Y, H:i') }}</b>
                    @if($holderName !== '')
                        &nbsp;·&nbsp; {{ __('export.holder') }} <b>{{ $holderName }}</b>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="corner tl"></div><div class="corner tr"></div><div class="corner bl"></div><div class="corner br"></div>

<div class="sheet-foot">
    <div class="foot-micro">{{ __('export.microtext') }}</div>
    <table class="foot-table">
        <tr>
            @if($isVerified)
                <td class="shield" style="width: 8mm;"><img src="{{ $shield }}" alt=""></td>
            @endif
            <td>
                {{ __('export.footer_confidential') }}<br>
                {{ __('export.footer_verify') }}
            </td>
            {{-- "Page N of M" is stamped on the canvas by App\Support\SecureSheet
                 (dompdf prints 0 for CSS counter(pages) in fixed boxes); a caller
                 rendering the layout directly still gets "Page N" from CSS. --}}
            <td class="page" style="width: 30mm;">@unless($pageStamp ?? false){{ __('export.page') }} <span class="n"></span>@endunless</td>
            @if($verifyQr)
                <td class="qr" style="width: 26mm;">
                    <img src="{{ $verifyQr }}" alt="">
                    <div class="cap">{{ __('export.scan_to_verify') }}</div>
                    @if($verification)<div class="ref">{{ $verification->reference }}</div>@endif
                </td>
            @endif
        </tr>
    </table>
</div>

<div class="content">
    @yield('content')
</div>

</body>
</html>
