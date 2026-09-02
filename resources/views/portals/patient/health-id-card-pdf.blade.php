<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OpesCare Health ID — {{ $patient->health_id }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    /* CR80 card: 85.6mm × 54mm — DomPDF page set to 90×54mm in controller */
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        background: #fff;
        width: 255.12pt;   /* 90mm */
        height: 153.07pt;  /* 54mm */
        overflow: hidden;
        font-size: 6pt;
        color: #0F172A;
    }

    /* ── Card outer container ── */
    .card {
        width: 100%;
        height: 100%;
        display: block;
        position: relative;
        background: linear-gradient(135deg, #0F2744 0%, #0F4C81 55%, #1565C0 100%);
        border-radius: 4pt;
        overflow: hidden;
        padding: 0;
    }

    /* Decorative arc in the top-right corner */
    .card-arc {
        position: absolute;
        top: -30pt;
        right: -30pt;
        width: 80pt;
        height: 80pt;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .card-arc-2 {
        position: absolute;
        top: -15pt;
        right: -15pt;
        width: 55pt;
        height: 55pt;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }

    /* ── Header strip ── */
    .card-header {
        display: block;
        padding: 6pt 8pt 4pt;
        border-bottom: 0.5pt solid rgba(255,255,255,0.15);
    }
    .brand-row {
        display: block;
    }
    .brand-name {
        font-size: 8pt;
        font-weight: bold;
        color: #FFFFFF;
        letter-spacing: 0.05em;
    }
    .brand-sub {
        font-size: 5pt;
        color: rgba(255,255,255,0.65);
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-top: 1pt;
    }

    /* ── Body ── */
    .card-body {
        display: block;
        padding: 5pt 8pt 5pt 8pt;
    }

    /* Patient name */
    .patient-name {
        font-size: 9.5pt;
        font-weight: bold;
        color: #FFFFFF;
        letter-spacing: 0.02em;
        margin-bottom: 2pt;
        text-transform: uppercase;
    }

    /* Meta row */
    .meta-row {
        display: block;
        margin-bottom: 5pt;
    }
    .meta-item {
        display: inline-block;
        margin-right: 8pt;
    }
    .meta-label {
        font-size: 4.5pt;
        color: rgba(255,255,255,0.55);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        display: block;
    }
    .meta-value {
        font-size: 6pt;
        color: rgba(255,255,255,0.9);
        font-weight: bold;
        display: block;
    }

    /* Health ID number */
    .health-id-block {
        display: block;
        background: rgba(0,0,0,0.25);
        border-radius: 2.5pt;
        padding: 3pt 5pt;
        margin-bottom: 4pt;
        border: 0.5pt solid rgba(255,255,255,0.2);
    }
    .health-id-label-sm {
        font-size: 4pt;
        color: rgba(255,255,255,0.55);
        text-transform: uppercase;
        letter-spacing: 0.12em;
        display: block;
        margin-bottom: 1pt;
    }
    .health-id-value {
        font-size: 10pt;
        font-weight: bold;
        color: #FFFFFF;
        letter-spacing: 0.18em;
        display: block;
        font-family: 'Courier New', 'DejaVu Sans Mono', monospace;
    }

    /* Bottom row: QR + status + issued */
    .card-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 4pt 8pt 5pt;
        display: block;
        background: rgba(0,0,0,0.18);
        border-top: 0.5pt solid rgba(255,255,255,0.1);
    }
    .footer-left {
        display: inline-block;
        vertical-align: top;
        width: 72%;
    }
    .footer-right {
        display: inline-block;
        vertical-align: top;
        width: 26%;
        text-align: right;
    }

    .status-badge {
        display: inline-block;
        background: #22C55E;
        color: #fff;
        border-radius: 99pt;
        padding: 1pt 4pt;
        font-size: 4.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 2pt;
    }
    .issued-text {
        font-size: 4.5pt;
        color: rgba(255,255,255,0.5);
        display: block;
    }
    .country-text {
        font-size: 4.5pt;
        color: rgba(255,255,255,0.5);
        display: block;
        margin-top: 1pt;
    }
    .disclaimer-text {
        font-size: 3.5pt;
        color: rgba(255,255,255,0.35);
        display: block;
        margin-top: 2pt;
        line-height: 1.3;
    }

    /* QR code image */
    .qr-img {
        width: 32pt;
        height: 32pt;
        background: #fff;
        border-radius: 2pt;
        padding: 1pt;
    }
    .qr-label {
        font-size: 3.5pt;
        color: rgba(255,255,255,0.45);
        text-align: center;
        display: block;
        margin-top: 1pt;
    }

    /* Blood group badge */
    .blood-badge {
        display: inline-block;
        background: #DC2626;
        color: #fff;
        border-radius: 2pt;
        padding: 1pt 3pt;
        font-size: 5.5pt;
        font-weight: bold;
        margin-left: 4pt;
        vertical-align: middle;
    }
</style>
</head>
<body>
<div class="card">
    <div class="card-arc"></div>
    <div class="card-arc-2"></div>

    <!-- Header -->
    <div class="card-header">
        <div class="brand-row">
            <span class="brand-name">OpesCare</span>
            &nbsp;
            <span class="brand-sub">{{ __('public.portal.id_card_digital_health') }} &bull; Cameroon</span>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body">
        <div class="patient-name">
            {{ strtoupper(trim($patient->first_name . ' ' . ($patient->middle_name ? $patient->middle_name . ' ' : '') . $patient->last_name)) }}
            @if($patient->blood_group)
                <span class="blood-badge">{{ $patient->blood_group }}</span>
            @endif
        </div>

        <div class="meta-row">
            @if($patient->date_of_birth)
            <span class="meta-item">
                <span class="meta-label">{{ __('public.portal.id_card_dob') }}</span>
                <span class="meta-value">{{ $patient->date_of_birth->format('d M Y') }}</span>
            </span>
            @endif
            @if($patient->sex)
            <span class="meta-item">
                <span class="meta-label">{{ __('public.portal.id_card_sex') }}</span>
                <span class="meta-value">@enum($patient->sex)</span>
            </span>
            @endif
            <span class="meta-item">
                <span class="meta-label">{{ __('public.portal.id_card_issued') }}</span>
                <span class="meta-value">{{ $issuedAt }}</span>
            </span>
        </div>

        <div class="health-id-block">
            <span class="health-id-label-sm">{{ __('public.portal.id_card_health_id_num') }}</span>
            <span class="health-id-value">{{ $patient->health_id }}</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer">
        <div class="footer-left">
            <span class="status-badge">
                {{ strtoupper((string) ($patient->verification_status->value ?? $patient->verification_status ?? 'Active')) }}
            </span>
            <span class="country-text">
                {{ __('public.portal.id_card_country') }}: {{ $patient->country_code ?? 'CM' }} &bull; {{ __('public.portal.id_card_minsante') }}
            </span>
            <span class="disclaimer-text">
                {{ __('public.portal.id_card_disclaimer') }}
            </span>
        </div>
        <div class="footer-right">
            @if($qrDataUri)
                <img src="{{ $qrDataUri }}" class="qr-img" alt="{{ __('public.portal.id_card_health_id_num') }}">
                <span class="qr-label">{{ __('public.portal.id_card_scan_verify') }}</span>
            @endif
        </div>
    </div>
</div>
</body>
</html>
