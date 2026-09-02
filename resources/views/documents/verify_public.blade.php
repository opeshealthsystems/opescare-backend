<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpesCare — Document Authenticity Verification</title>
    <meta name="theme-color" content="#090D16">
    @include('partials.favicons')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #090D16;
            color: #E2E8F0;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
            box-sizing: border-box;
        }

        /* Ambient background glow */
        .ambient-glow {
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(13, 242, 201, 0.08) 0%, rgba(9, 13, 22, 0) 70%);
            top: 10%;
            left: 20%;
            pointer-events: none;
            z-index: 1;
        }

        .ambient-glow-2 {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(15, 76, 129, 0.12) 0%, rgba(9, 13, 22, 0) 70%);
            bottom: 10%;
            right: 15%;
            pointer-events: none;
            z-index: 1;
        }

        .verification-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 520px;
            padding: 6mm;
            box-sizing: border-box;
        }

        /* Glassmorphism main card panel */
        .elite-card {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 8mm;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .elite-card:hover {
            border-color: rgba(13, 242, 201, 0.2);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 6mm;
        }

        .brand-logo-ring {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0F4C81 0%, #0DF2C9 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(13, 242, 201, 0.25);
            margin-bottom: 3mm;
        }

        .brand-logo-icon {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -1px;
        }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #FFFFFF;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .brand-subtitle {
            font-size: 11px;
            color: #64748B;
            margin: 1mm 0 0 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Big Status Section */
        .status-hero {
            border-radius: 12px;
            padding: 5mm;
            text-align: center;
            margin-bottom: 6mm;
            border-width: 1px;
            border-style: solid;
        }

        .status-hero-valid {
            background-color: rgba(16, 185, 129, 0.06);
            border-color: rgba(16, 185, 129, 0.2);
            color: #10B981;
        }

        .status-hero-warning {
            background-color: rgba(245, 158, 11, 0.06);
            border-color: rgba(245, 158, 11, 0.2);
            color: #F59E0B;
        }

        .status-hero-danger {
            background-color: rgba(239, 68, 68, 0.06);
            border-color: rgba(239, 68, 68, 0.2);
            color: #EF4444;
        }

        .status-icon {
            font-size: 28px;
            margin-bottom: 2mm;
            display: block;
        }

        .status-title {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 1mm 0;
            text-transform: uppercase;
        }

        .status-desc {
            font-size: 11.5px;
            color: #94A3B8;
            margin: 0;
            line-height: 1.4;
        }

        /* Detail Rows */
        .details-panel {
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            padding: 4mm;
            margin-bottom: 6mm;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2.5mm 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 11px;
            color: #64748B;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 12px;
            color: #F1F5F9;
            font-weight: 600;
        }

        .value-code {
            font-family: monospace;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 0.8mm 1.8mm;
            border-radius: 4px;
            font-size: 11px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* Action Buttons */
        .btn-elite {
            display: block;
            width: 100%;
            text-align: center;
            background: linear-gradient(135deg, #0F4C81 0%, #0F766E 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
            padding: 3mm 4mm;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            box-sizing: border-box;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
            box-shadow: 0 4px 12px rgba(15, 76, 129, 0.2);
        }

        .btn-elite:hover {
            box-shadow: 0 6px 16px rgba(13, 242, 201, 0.25);
            transform: translateY(-0.5mm);
        }

        .security-banner {
            text-align: center;
            font-size: 10px;
            color: #475569;
            margin-top: 6mm;
            line-height: 1.4;
        }

        @media (max-width: 600px) {
            body {
                align-items: flex-start;
                padding: 6mm 0;
            }
            .verification-wrapper { padding: 4mm; }
            .elite-card { padding: 5mm; }
            .brand-title { font-size: 18px; }
            .ambient-glow, .ambient-glow-2 { display: none; }
            .detail-row { flex-wrap: wrap; gap: 2mm; }
            .detail-value { text-align: right; }
        }
    </style>
</head>
<body>

<div class="ambient-glow"></div>
<div class="ambient-glow-2"></div>

<div class="verification-wrapper">
    <div class="elite-card">
        <!-- Brand Header -->
        <div class="brand-header">
            <div class="brand-logo-ring">
                <span class="brand-logo-icon">OC</span>
            </div>
            <h1 class="brand-title">OpesCare</h1>
            <p class="brand-subtitle">Document Verification Center</p>
        </div>

        @if($status === 'valid')
            <!-- Valid Status -->
            <div class="status-hero status-hero-valid">
                <span class="status-icon">🛡️</span>
                <h2 class="status-title">Authentic & Valid</h2>
                <p class="status-desc">This document is authentic and currently valid.</p>
            </div>
        @elseif($status === 'revoked')
            <!-- Revoked Status -->
            <div class="status-hero status-hero-danger">
                <span class="status-icon">⚠️</span>
                <h2 class="status-title">Document Revoked</h2>
                <p class="status-desc">This document was issued but has been revoked. Do not rely on it.</p>
            </div>
        @elseif($status === 'cancelled')
            <!-- Cancelled Status -->
            <div class="status-hero status-hero-danger">
                <span class="status-icon">🚫</span>
                <h2 class="status-title">Document Cancelled</h2>
                <p class="status-desc">This document was cancelled and is not valid for use.</p>
            </div>
        @elseif($status === 'entered_in_error')
            <!-- Entered in Error -->
            <div class="status-hero status-hero-danger">
                <span class="status-icon">❌</span>
                <h2 class="status-title">Entered in Error</h2>
                <p class="status-desc">This document was entered in error and should not be relied on.</p>
            </div>
        @elseif($status === 'superseded' || $status === 'amended')
            <!-- Superseded Status -->
            <div class="status-hero status-hero-warning">
                <span class="status-icon">🔄</span>
                <h2 class="status-title">Amended & Superseded</h2>
                <p class="status-desc">This document is authentic, but a newer amended version exists.</p>
            </div>
        @elseif($status === 'expired')
            <!-- Expired Status -->
            <div class="status-hero status-hero-warning">
                <span class="status-icon">⏳</span>
                <h2 class="status-title">Document Expired</h2>
                <p class="status-desc">This document is authentic but has expired.</p>
            </div>
        @else
            <!-- Not Found / Failure -->
            <div class="status-hero status-hero-danger">
                <span class="status-icon">❓</span>
                <h2 class="status-title">Verification Failed</h2>
                <p class="status-desc">This document could not be verified. Check the code and try again.</p>
            </div>
        @endif

        <!-- Details Panel -->
        @if($document)
            <div class="details-panel">
                <div class="detail-row">
                    <span class="detail-label">Doc Type</span>
                    <span class="detail-value" style="color: #0DF2C9;">{{ $document->document_type }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Doc Number</span>
                    <span class="detail-value value-code">{{ $document->document_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Verification Code</span>
                    <span class="detail-value value-code">{{ $document->verification_code }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issuing Facility</span>
                    <span class="detail-value">{{ $facility_name ?? 'OpesCare Partner Clinic' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issued At</span>
                    <span class="detail-value">{{ $document->issued_at ? $document->issued_at->format('d M Y, H:i') : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Version</span>
                    <span class="detail-value">v{{ $document->version }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Patient Ref</span>
                    <span class="detail-value">
                        <!-- Masked Patient Initials for Privacy Compliance -->
                        @if($patient_name)
                            {{ substr($patient_name, 0, 1) }}***{{ substr($patient_name, -1) }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>

            <!-- Login to View Details Button -->
            <a href="/login" class="btn-elite">
                Sign In to View Full Records
            </a>
        @else
            <div class="details-panel" style="text-align: center; padding: 6mm; color: #64748B;">
                No associated document metadata could be retrieved for this verification token.
            </div>
        @endif

        <!-- Security Disclaimer -->
        <div class="security-banner">
            Secure Cryptographic Document Integrity Verified by OpesCare.<br>
            All verification access is monitored under standard data governance protocol.
        </div>
    </div>
</div>

</body>
</html>
