<!DOCTYPE html>
<html lang="{{ $language ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            color: #0F172A;
            background-color: #FFFFFF;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
        }

        .page-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 16mm 14mm;
            position: relative;
            box-sizing: border-box;
            background: #FFFFFF;
        }

        /* Status Watermarks */
        .watermark-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            overflow: hidden;
            z-index: 10;
        }

        .watermark-text {
            font-size: 72px;
            font-weight: 800;
            color: rgba(239, 68, 68, 0.08);
            transform: rotate(-45deg);
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 4px;
        }

        /* Headers and Branding */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0F4C81;
            padding-bottom: 6mm;
            margin-bottom: 6mm;
        }

        .facility-info {
            display: flex;
            align-items: center;
        }

        .facility-logo {
            width: 12mm;
            height: 12mm;
            background-color: #0F4C81;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 16px;
            margin-right: 4mm;
        }

        .facility-details h2 {
            margin: 0 0 1mm 0;
            font-size: 14px;
            color: #0F4C81;
            font-weight: 700;
        }

        .facility-details p {
            margin: 0;
            font-size: 9.5px;
            color: #475569;
        }

        .verification-header-block {
            text-align: right;
        }

        .verification-badge {
            display: inline-block;
            padding: 1mm 2.5mm;
            font-size: 8.5px;
            font-weight: 600;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2mm;
        }

        .badge-issued { background-color: #E6F7F5; color: #0F766E; }
        .badge-amended { background-color: #FEF3C7; color: #D97706; }
        .badge-draft { background-color: #F1F5F9; color: #475569; }
        .badge-revoked { background-color: #FEE2E2; color: #B91C1C; }

        /* Meta Blocks */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6mm;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 4mm;
            margin-bottom: 6mm;
        }

        .meta-section h3 {
            margin: 0 0 2mm 0;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 1mm;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        .meta-label {
            color: #64748B;
            font-weight: 500;
        }

        .meta-value {
            color: #0F172A;
            font-weight: 600;
        }

        /* Document Title */
        .doc-title-container {
            text-align: center;
            margin-bottom: 6mm;
        }

        .doc-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0F4C81;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-subtitle {
            margin: 1mm 0 0 0;
            font-size: 11px;
            color: #475569;
            font-style: italic;
        }

        /* Content Blocks */
        .content-card {
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            margin-bottom: 6mm;
            overflow: hidden;
        }

        .card-header {
            background-color: #E8F2FA;
            color: #0F4C81;
            font-weight: 600;
            font-size: 11px;
            padding: 2.5mm 4mm;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 4mm;
        }

        /* Custom Clinical Tables */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2mm;
        }

        .doc-table th {
            background-color: #F8FAFC;
            color: #475569;
            font-weight: 600;
            text-align: left;
            padding: 2.5mm 3mm;
            border-bottom: 2px solid #E2E8F0;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .doc-table td {
            padding: 3mm;
            border-bottom: 1px solid #E2E8F0;
            font-size: 10.5px;
        }

        /* Verification QR & Code block */
        .verification-footer-block {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #E2E8F0;
            padding-top: 6mm;
            margin-top: 8mm;
        }

        .verification-left {
            display: flex;
            align-items: center;
        }

        .qr-placeholder {
            margin-right: 4mm;
        }

        .verification-instructions h4 {
            margin: 0 0 1mm 0;
            font-size: 10.5px;
            color: #0F4C81;
            font-weight: 600;
        }

        .verification-instructions p {
            margin: 0 0 2mm 0;
            font-size: 9px;
            color: #64748B;
            max-width: 120mm;
        }

        .verification-code-badge {
            background-color: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
            padding: 1.5mm 3mm;
            font-family: monospace;
            font-size: 10px;
            font-weight: 700;
            color: #0F172A;
            display: inline-block;
        }

        /* Disclaimer & Page Numbers */
        .doc-footer {
            border-top: 1px solid #E2E8F0;
            padding-top: 4mm;
            margin-top: 6mm;
            display: flex;
            justify-content: space-between;
            font-size: 8.5px;
            color: #94A3B8;
        }

        .footer-disclaimer {
            max-width: 140mm;
        }
    </style>
</head>
<body>

<div class="page-container">
    <!-- Status Watermark -->
    @if(in_array($status, ['draft', 'revoked', 'cancelled', 'entered_in_error']))
        <div class="watermark-container">
            <div class="watermark-text">
                @if($status === 'draft')
                    DRAFT — NOT VALID
                @elseif($status === 'revoked')
                    REVOKED
                @elseif($status === 'cancelled')
                    CANCELLED
                @elseif($status === 'entered_in_error')
                    ENTERED IN ERROR
                @endif
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="doc-header">
        <div class="facility-info">
            <div class="facility-logo">OC</div>
            <div class="facility-details">
                <h2>{{ $facility_name ?? 'OpesCare Partner General Hospital' }}</h2>
                @if($facility_license)
                <p>License No: {{ $facility_license }} | Tel: +237 600-000-000</p>
                @else
                <p>Tel: +237 600-000-000</p>
                @endif
                <p>Address: Bonanjo, Douala, Cameroon</p>
            </div>
        </div>
        <div class="verification-header-block">
            <div class="verification-badge badge-{{ $status }}">
                {{ $status }}
            </div>
            <div style="font-size: 9px; color: #64748B;">Version {{ $version ?? '1.0' }}</div>
        </div>
    </div>

    <!-- Title -->
    <div class="doc-title-container">
        <h1 class="doc-title">@yield('title')</h1>
        <p class="doc-subtitle">@yield('subtitle')</p>
    </div>

    <!-- Meta Details -->
    <div class="meta-grid">
        <div class="meta-section">
            <h3>{{ $language === 'fr' ? 'INFORMATIONS DU PATIENT' : 'PATIENT INFORMATION' }}</h3>
            <div class="meta-row">
                <span class="meta-label">Name / Nom:</span>
                <span class="meta-value">{{ $patient_name }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Health ID / ID Santé:</span>
                <span class="meta-value">{{ $health_id ?? 'N/A' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Sex / Sexe:</span>
                <span class="meta-value">{{ $patient_sex ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="meta-section">
            <h3>{{ $language === 'fr' ? 'DÉTAILS DU DOCUMENT' : 'DOCUMENT METADATA' }}</h3>
            <div class="meta-row">
                <span class="meta-label">Doc No:</span>
                <span class="meta-value">{{ $document_number }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Issued / Émis le:</span>
                <span class="meta-value">{{ $issued_at }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Issuer / Émetteur:</span>
                <span class="meta-value">{{ $issuer_name }} ({{ $issuer_role }})</span>
            </div>
        </div>
    </div>

    <!-- Main Content Body -->
    @yield('content')

    <!-- Verification Footer Section -->
    <div class="verification-footer-block">
        <div class="verification-left">
            <div class="qr-placeholder">
                {!! $qr_svg !!}
            </div>
            <div class="verification-instructions">
                <h4>{{ $language === 'fr' ? 'Scanner pour vérifier' : 'Scan to Verify Authenticity' }}</h4>
                <p>
                    {{ $language === 'fr' 
                        ? 'Ce document peut être vérifié via OpesCare. Scannez le code de vérification ou saisissez le numéro de vérification sur la page officielle de vérification.'
                        : 'This document can be verified through OpesCare. Scan the verification code or enter the verification number at the official verification page.' }}
                </p>
                <div class="verification-code-badge">
                    {{ $verification_code }}
                </div>
            </div>
        </div>
        <div class="signature-block" style="text-align: right; font-size: 9px; color: #475569;">
            <div style="font-weight: 600; margin-bottom: 8mm;">
                {{ $language === 'fr' ? 'Signature Autorisée' : 'Authorized Validation Signature' }}
            </div>
            <div style="border-top: 1px solid #94A3B8; padding-top: 1mm; width: 45mm; display: inline-block;">
                {{ $issuer_name }}
            </div>
        </div>
    </div>

    <!-- Disclaimer / Page details -->
    <div class="doc-footer">
        <div class="footer-disclaimer">
            {{ $language === 'fr'
                ? 'Confidentialité : Ce document contient des informations de santé confidentielles protégées par les règles de gouvernance OpesCare.'
                : 'Privacy Notice: This document contains secure clinical health information protected by OpesCare governance frameworks.' }}
        </div>
        <div>Page 1 of 1</div>
    </div>
</div>

</body>
</html>
