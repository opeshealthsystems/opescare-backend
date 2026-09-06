{{--
    Printable Health ID card — CR80 wallet size, rendered by dompdf.

    The controller sets the paper to 90 × 54 mm ([0, 0, 255.12, 153.07] pt).
    dompdf rules apply: absolute positioning and tables only (no flex/grid),
    DejaVu Sans is the only bundled font, images are local files under
    public/ (the chroot), never URLs. Brand palette mirrors the portal and
    the OpesCare Secure sheet: brand blue #0F4C81, navy ink #0E1729, cream
    #EADFC8 / #FDF8F0.

    Variables: $patient, $qrDataUri (SVG data URI or null), $issuedAt (Carbon).
--}}
@php
    $seal       = public_path('images/health-id/sheet-seal.png');
    $holderName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $verification = $patient->verification_status instanceof \BackedEnum ? $patient->verification_status->value : ($patient->verification_status ?? null);
    $identity   = $patient->identity_status instanceof \BackedEnum ? $patient->identity_status->value : ($patient->identity_status ?? null);
    $isVerified = in_array($verification, ['verified', 'verified_by_facility', 'facility_verified'], true) || $identity === 'verified';
    $sexLabel   = match ($patient->sex) {
        'male'   => __('family.card_sex_male'),
        'female' => __('family.card_sex_female'),
        default  => '—',
    };
    $issued = ($issuedAt ?? now());
    $issued = $issued instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($issued) : \Illuminate\Support\Carbon::parse($issued);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('family.card_title') }} — {{ $holderName }}</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; width: 90mm; height: 54mm; font-family: "DejaVu Sans", sans-serif; color: #0E1729; background: #FFFFFF; font-size: 6.5px; line-height: 1.3; }

        /* Every box is placed with `top`, never `bottom`: dompdf resolves a
           bottom-anchored absolute box against the flow and pushed the footer
           onto a second page. 53.8mm keeps the card itself inside one page. */
        .card { position: relative; width: 90mm; height: 53.8mm; overflow: hidden; background: #FDF8F0; page-break-inside: avoid; }

        /* Header band */
        .band { position: absolute; top: 0; left: 0; width: 90mm; height: 11mm; background: #0E1729; }
        .band .rule { position: absolute; left: 0; bottom: 0; width: 90mm; height: 0.7mm; background: #0F4C81; }
        .band .rule-cream { position: absolute; left: 0; bottom: 0.7mm; width: 90mm; height: 0.25mm; background: #EADFC8; }
        .brand { position: absolute; top: 2.4mm; left: 4mm; }
        .brand .word { font-size: 10px; font-weight: bold; color: #FFFFFF; letter-spacing: .3px; line-height: 1.1; }
        .brand .kicker { font-size: 4.6px; color: #EADFC8; text-transform: uppercase; letter-spacing: 1.2px; margin-top: 1px; }
        .doc-type { position: absolute; top: 3mm; right: 4mm; text-align: right; }
        .doc-type .type { font-size: 8px; font-weight: bold; color: #FFFFFF; letter-spacing: .6px; text-transform: uppercase; }
        .doc-type .sub { font-size: 4.4px; color: #C9D3E3; margin-top: 1px; letter-spacing: .4px; }

        /* Seal (faded watermark in the empty space between the details and the QR) */
        .seal { position: absolute; left: 46mm; top: 32.5mm; width: 14mm; height: 14mm; opacity: .18; }
        .seal img { width: 14mm; height: 14mm; }

        /* Body */
        .name { position: absolute; top: 13mm; left: 4mm; width: 56mm; font-size: 9.5px; font-weight: bold; color: #0E1729; letter-spacing: .1px; white-space: nowrap; overflow: hidden; }
        .hid-label { position: absolute; top: 18.2mm; left: 4mm; font-size: 4.4px; color: #5B6B85; text-transform: uppercase; letter-spacing: 1px; }
        .hid { position: absolute; top: 20.4mm; left: 4mm; font-family: "DejaVu Sans Mono", monospace; font-size: 9px; font-weight: bold; color: #0F4C81; letter-spacing: 1px; white-space: nowrap; }

        table.kv { position: absolute; top: 27mm; left: 4mm; width: 56mm; border-collapse: collapse; }
        table.kv td { padding: 0.9mm 0; vertical-align: top; border-bottom: 0.2mm solid #E3E9F1; }
        table.kv td.k { width: 19mm; font-size: 4.6px; color: #5B6B85; text-transform: uppercase; letter-spacing: .7px; padding-top: 1.2mm; }
        table.kv td.v { font-size: 6.8px; color: #0E1729; font-weight: bold; }

        .pill { position: absolute; top: 13.6mm; right: 4mm; display: inline-block; padding: 0.5mm 1.8mm; border-radius: 3mm; font-size: 4.6px; text-transform: uppercase; letter-spacing: .6px; white-space: nowrap; }
        .pill.ok   { background: #E3F2E9; color: #146C43; border: 0.2mm solid #B9DFC9; }
        .pill.prov { background: #FFF4DC; color: #8A5A00; border: 0.2mm solid #F1D9A2; }

        /* QR */
        .qr { position: absolute; top: 18.5mm; right: 4mm; width: 22mm; text-align: center; }
        .qr .frame { width: 22mm; height: 22mm; background: #FFFFFF; border: 0.3mm solid #0F4C81; }
        .qr .frame img { width: 22mm; height: 22mm; display: block; }
        .qr .cap { font-size: 4.2px; letter-spacing: .8px; text-transform: uppercase; color: #0F4C81; font-weight: bold; margin-top: 0.8mm; white-space: nowrap; }

        /* Footer */
        .foot { position: absolute; left: 0; top: 47.8mm; width: 90mm; height: 6mm; background: #0E1729; }
        .foot .rule { position: absolute; left: 0; top: 0; width: 90mm; height: 0.4mm; background: #0F4C81; }
        .foot table { position: absolute; top: 1.1mm; left: 4mm; width: 82mm; border-collapse: collapse; }
        .foot td { padding: 0; vertical-align: top; font-size: 3.9px; color: #C9D3E3; line-height: 1.25; }
        .foot td.issued { text-align: right; white-space: nowrap; color: #FFFFFF; font-size: 4.2px; width: 22mm; }

        /* Corner markers */
        .corner { position: absolute; width: 3.5mm; height: 3.5mm; }
        .corner.tl { top: 12mm; left: 1.5mm; border-top: 0.3mm solid #0F4C81; border-left: 0.3mm solid #0F4C81; }
        .corner.br { top: 43.3mm; left: 85mm; border-bottom: 0.3mm solid #0F4C81; border-right: 0.3mm solid #0F4C81; }
    </style>
</head>
<body>
<div class="card">
    <div class="band">
        <div class="brand">
            <div class="word">OpesCare</div>
            <div class="kicker">Secure</div>
        </div>
        <div class="doc-type">
            <div class="type">{{ __('family.card_title') }}</div>
            <div class="sub">{{ $patient->country_code ?? 'CM' }}</div>
        </div>
        <div class="rule-cream"></div>
        <div class="rule"></div>
    </div>

    @if(file_exists($seal))
    <div class="seal"><img src="{{ $seal }}" alt=""></div>
    @endif

    <div class="corner tl"></div>
    <div class="corner br"></div>

    <div class="name">{{ $holderName }}</div>
    <span class="pill {{ $isVerified ? 'ok' : 'prov' }}">{{ $isVerified ? __('family.card_status_verified') : __('family.card_status_provisional') }}</span>

    <div class="hid-label">{{ __('family.card_title') }}</div>
    <div class="hid">{{ $patient->health_id }}</div>

    <table class="kv">
        <tr>
            <td class="k">{{ __('family.card_dob') }}</td>
            <td class="v">{{ $patient->date_of_birth?->translatedFormat('d M Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">{{ __('family.card_sex') }}</td>
            <td class="v">{{ $sexLabel }}</td>
        </tr>
        <tr>
            <td class="k">{{ __('family.card_blood') }}</td>
            <td class="v">{{ $patient->blood_group ?? '—' }}</td>
        </tr>
    </table>

    <div class="qr">
        <div class="frame">
            @if($qrDataUri)
                <img src="{{ $qrDataUri }}" alt="">
            @endif
        </div>
        <div class="cap">{{ __('family.card_verify') }}</div>
    </div>

    <div class="foot">
        <div class="rule"></div>
        <table>
            <tr>
                <td>{{ __('family.card_footer') }}</td>
                <td class="issued">{{ __('family.card_issued') }} {{ $issued->translatedFormat('d M Y') }}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
