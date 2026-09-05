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

    Slots:  @section('document_title')     the document type ("Medical Record")
            @section('document_reference') the reference shown in the header
            @section('content')            the body

    Variables expected from the caller:
            $patient  (App\Models\Patient) — holder shown in the header
            $issuedAt (Carbon)             — defaults to now()
--}}
@php
    $issuedAt   = $issuedAt ?? now();
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
        @page { margin: 33mm 16mm 27mm 16mm; }

        /* Brand tokens, mirroring the portal: brand blue #0F4C81, navy ink #0E1729, cream paper #FDF8F0 / #EADFC8. */
        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.5px;
            line-height: 1.45;
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
        .brand-kicker { font-size: 7px; color: #EADFC8; text-transform: uppercase; letter-spacing: 1.6px; margin-top: 1.5px; }
        .head-doc { text-align: right; }
        .head-doc .type { font-size: 12.5px; font-weight: bold; color: #FFFFFF; line-height: 1.15; }
        .head-doc .meta { font-size: 7.5px; color: #C9D3E3; margin-top: 2px; }
        .head-doc .meta b { color: #FFFFFF; font-weight: normal; }
        .head-seal img { width: 17mm; height: 17mm; }

        .sheet-foot { position: fixed; bottom: -27mm; left: -16mm; width: 210mm; height: 22mm; z-index: -2; }
        .foot-micro {
            position: absolute; top: 0; left: 0; width: 210mm; height: 3.2mm;
            background: #0E1729; color: #8FA5C6;
            font-size: 5px; letter-spacing: 1.1px; text-transform: uppercase;
            line-height: 3.2mm; text-align: center; overflow: hidden; white-space: nowrap;
        }
        .foot-table { position: absolute; top: 5mm; left: 14mm; width: 182mm; border-collapse: collapse; }
        .foot-table td { vertical-align: top; padding: 0; font-size: 6.8px; color: #5B6B85; line-height: 1.35; }
        .foot-table .shield img { width: 6mm; height: 6mm; }
        .foot-table .page { text-align: right; white-space: nowrap; color: #0F4C81; font-weight: bold; font-size: 7.5px; }
        .foot-table .page .n:after { content: counter(page); }

        /* Corner markers, drawn in CSS so they stay crisp at any print size. */
        .corner { position: fixed; width: 7mm; height: 7mm; z-index: -1; }
        .corner.tl { top: -3mm;    left: -8mm;  border-top: .5mm solid #0F4C81;    border-left: .5mm solid #0F4C81; }
        .corner.tr { top: -3mm;    right: -8mm; border-top: .5mm solid #0F4C81;    border-right: .5mm solid #0F4C81; }
        .corner.bl { bottom: -1mm; left: -8mm;  border-bottom: .5mm solid #0F4C81; border-left: .5mm solid #0F4C81; }
        .corner.br { bottom: -1mm; right: -8mm; border-bottom: .5mm solid #0F4C81; border-right: .5mm solid #0F4C81; }

        /* ---- Body ---------------------------------------------------------- */
        .content { padding: 0; }

        h1 { font-size: 17px; color: #0E1729; margin: 0 0 1px; letter-spacing: -.2px; }
        .subtitle { font-size: 8.5px; color: #5B6B85; text-transform: uppercase; letter-spacing: 1.4px; margin: 0 0 8px; }

        h2 {
            font-size: 10.5px; color: #0F4C81; text-transform: uppercase; letter-spacing: 1.2px;
            margin: 14px 0 4px; padding: 0 0 3px; border-bottom: .6px solid #0F4C81;
        }
        h2 .hint { float: right; font-size: 7px; color: #5B6B85; letter-spacing: .6px; font-weight: normal; padding-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.data tr { page-break-inside: avoid; }
        table.data th {
            background: #E7EEF6; color: #0E1729; text-align: left;
            padding: 4px 6px; font-size: 7.5px; text-transform: uppercase; letter-spacing: .6px;
            border-bottom: .5px solid #C5D3E4;
        }
        table.data td { padding: 4px 6px; border-bottom: .4px solid #DCE4EE; vertical-align: top; }
        table.data tr:nth-child(even) td { background: rgba(253, 248, 240, .65); }
        table.data td.num { text-align: right; white-space: nowrap; }
        table.data td.group { background: #F3F6FA; color: #0E1729; font-size: 8.5px; padding: 5px 6px; }
        table.data td.group .ref { font-family: "DejaVu Sans Mono", monospace; color: #0F4C81; letter-spacing: .6px; }
        .muted { color: #8A97AD; }

        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 3px 6px 3px 0; vertical-align: top; border-bottom: .4px solid #E3E9F1; }
        .kv td.k { width: 30mm; color: #5B6B85; font-size: 7.5px; text-transform: uppercase; letter-spacing: .7px; padding-top: 4px; }
        .kv td.v { font-size: 10px; color: #0E1729; }
        .kv td.v.id { font-family: "DejaVu Sans Mono", monospace; letter-spacing: 1.2px; font-weight: bold; color: #0F4C81; }

        .pill { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7.5px; text-transform: uppercase; letter-spacing: .6px; white-space: nowrap; }
        .pill.ok   { background: #E3F2E9; color: #146C43; }
        .pill.warn { background: #FCEFD6; color: #8A5A00; }
        .pill.bad  { background: #FBE3E3; color: #9B1C1C; }
        .pill.info { background: #E7EEF6; color: #0F4C81; }
        .pill.mute { background: #EEF1F5; color: #5B6B85; }

        .empty { color: #8A97AD; font-style: italic; padding: 2px 0 0; }
    </style>
    @yield('head')
</head>
<body>

<div class="sheet-bg"><img src="{{ $sheet }}" alt=""></div>

<div class="sheet-head">
    <div class="band"><img src="{{ $band }}" alt=""></div>
    <div class="rule-cream"></div>
    <div class="rule"></div>
    <table class="head-table">
        <tr>
            <td class="head-seal" style="width: 19mm;"><img src="{{ $seal }}" alt=""></td>
            <td style="width: 70mm;">
                <div class="brand-word">{{ __('export.brand') }}</div>
                <div class="brand-kicker">{{ __('export.secure_kicker') }}</div>
            </td>
            <td class="head-doc">
                <div class="type">@yield('document_title')</div>
                <div class="meta">
                    @hasSection('document_reference')
                        {{ __('export.reference') }} <b>@yield('document_reference')</b> &nbsp;·&nbsp;
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
            <td class="page" style="width: 24mm;">{{ __('export.page') }} <span class="n"></span></td>
        </tr>
    </table>
</div>

<div class="content">
    @yield('content')
</div>

</body>
</html>
