@extends('documents.base')

@section('content')
{{-- ============================================================
     NARCOTIC / CONTROLLED SUBSTANCE PRESCRIPTION
     Slug: narcotic-prescription | Code: NRX | Color: #B45309
     ============================================================ --}}

<style>
    /* ── Amber/orange controlled-substance theme ── */
    :root {
        --nrx-amber:  #B45309;
        --nrx-amber2: #D97706;
        --nrx-warn:   #FEF3C7;
        --nrx-red:    #DC2626;
        --nrx-border: #92400E;
    }

    /* Tamper-evident diagonal watermark */
    .nrx-watermark-wrap {
        position: relative;
    }
    .nrx-watermark-wrap::before {
        content: "CONTROLLED SUBSTANCE • STUPÉFIANT • CONTROLLED SUBSTANCE • STUPÉFIANT • ";
        position: absolute;
        top: 50%;
        left: -10%;
        width: 120%;
        transform: rotate(-30deg) translateY(-50%);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 3px;
        color: rgba(180, 83, 9, 0.07);
        white-space: nowrap;
        pointer-events: none;
        z-index: 0;
        line-height: 2.4;
    }

    /* Top warning banner */
    .nrx-banner {
        background: var(--nrx-amber);
        color: #fff;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 6px 6px 0 0;
    }
    .nrx-banner-title {
        font-size: 15px;
        font-weight: 800;
        letter-spacing: 0.5px;
        line-height: 1.3;
    }
    .nrx-banner-subtitle {
        font-size: 11px;
        opacity: 0.9;
        margin-top: 2px;
        font-style: italic;
    }
    .nrx-serial-badge {
        background: #fff;
        color: var(--nrx-amber);
        border: 2px solid #92400E;
        border-radius: 5px;
        padding: 6px 14px;
        text-align: center;
        min-width: 180px;
    }
    .nrx-serial-badge .label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.75;
    }
    .nrx-serial-badge .value {
        font-size: 13px;
        font-weight: 800;
        font-family: monospace;
        margin-top: 2px;
    }

    /* Info strip */
    .nrx-info-strip {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0;
        border: 1.5px solid var(--nrx-border);
        border-top: none;
    }
    .nrx-info-cell {
        padding: 10px 16px;
        border-right: 1px solid var(--nrx-border);
    }
    .nrx-info-cell:last-child { border-right: none; }
    .nrx-info-cell .cell-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--nrx-amber);
        margin-bottom: 3px;
    }
    .nrx-info-cell .cell-value {
        font-size: 12px;
        font-weight: 600;
        color: #1a1a1a;
    }

    /* Section cards */
    .nrx-card {
        border: 1.5px solid #D97706;
        border-radius: 5px;
        margin: 14px 0;
        background: #fff;
        position: relative;
        z-index: 1;
    }
    .nrx-card-header {
        background: var(--nrx-warn);
        border-bottom: 1px solid #D97706;
        padding: 7px 14px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: var(--nrx-amber);
        border-radius: 3px 3px 0 0;
    }
    .nrx-card-body {
        padding: 12px 14px;
        font-size: 12px;
        color: #1f2937;
        line-height: 1.6;
    }

    /* Medications table */
    .nrx-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin: 0;
    }
    .nrx-table thead th {
        background: var(--nrx-amber);
        color: #fff;
        padding: 8px 7px;
        text-align: left;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-right: 1px solid rgba(255,255,255,0.2);
    }
    .nrx-table thead th:last-child { border-right: none; }
    .nrx-table tbody tr {
        border-bottom: 1px solid #FDE68A;
    }
    .nrx-table tbody tr:nth-child(even) {
        background: #FFFBEB;
    }
    .nrx-table tbody td {
        padding: 9px 7px;
        vertical-align: top;
        border-right: 1px solid #FDE68A;
    }
    .nrx-table tbody td:last-child { border-right: none; }

    .nrx-drug-name { font-weight: 700; color: #1a1a1a; font-size: 12px; }
    .nrx-generic   { font-size: 10px; color: #6b7280; font-style: italic; }
    .nrx-sched-badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 3px;
        font-size: 9.5px;
        font-weight: 800;
        background: #B45309;
        color: #fff;
        white-space: nowrap;
    }
    .nrx-qty-words {
        color: var(--nrx-red);
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        font-style: italic;
    }
    .nrx-no-sub {
        display: inline-block;
        border: 1.5px solid var(--nrx-red);
        color: var(--nrx-red);
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2px 5px;
        border-radius: 3px;
        margin-top: 4px;
        white-space: nowrap;
    }

    /* Stamps */
    .nrx-no-refill-stamp {
        display: inline-block;
        border: 3px solid var(--nrx-red);
        color: var(--nrx-red);
        font-size: 16px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 2px;
        padding: 8px 20px;
        border-radius: 6px;
        transform: rotate(-4deg);
        margin: 10px 0;
    }

    /* Allergy warning */
    .nrx-allergy {
        background: #FEF2F2;
        border: 1.5px solid #FCA5A5;
        border-radius: 5px;
        padding: 10px 14px;
        margin: 10px 0;
        font-size: 11px;
        color: #991B1B;
    }
    .nrx-allergy strong { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Legal box */
    .nrx-legal {
        background: #1c1917;
        color: #e7e5e4;
        border-radius: 5px;
        padding: 13px 16px;
        font-size: 10px;
        line-height: 1.7;
        margin: 14px 0;
    }
    .nrx-legal strong { color: #FCD34D; }

    /* Signature area */
    .nrx-sig-area {
        border: 1.5px solid var(--nrx-border);
        border-radius: 5px;
        padding: 14px 18px;
        display: flex;
        align-items: flex-end;
        gap: 40px;
        margin-top: 14px;
    }
    .nrx-sig-block { flex: 1; }
    .nrx-sig-line {
        border-bottom: 1.5px solid #374151;
        min-height: 44px;
        margin-bottom: 6px;
    }
    .nrx-sig-label {
        font-size: 10px;
        color: #6b7280;
        text-align: center;
        font-style: italic;
    }
    .nrx-sig-name  { font-size: 12px; font-weight: 700; color: #1a1a1a; }
    .nrx-sig-role  { font-size: 10px; color: var(--nrx-amber); }

    .nrx-stamp-area {
        width: 100px;
        height: 100px;
        border: 2px dashed #9CA3AF;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #9CA3AF;
        text-align: center;
        flex-shrink: 0;
    }

    .nrx-instructions {
        font-size: 10px;
        color: #4B5563;
        font-style: italic;
        margin-top: 3px;
    }
</style>

<div class="nrx-watermark-wrap">

    {{-- ── 1. TOP AMBER WARNING BANNER ── --}}
    <div class="nrx-banner">
        <div>
            <div class="nrx-banner-title">⚠ CONTROLLED SUBSTANCE PRESCRIPTION</div>
            <div class="nrx-banner-subtitle">ORDONNANCE DE STUPÉFIANT — NRX • {{ $document_number }}</div>
        </div>
        <div class="nrx-serial-badge">
            <div class="label">Narcotics Serial / N° Série</div>
            <div class="value">{{ $payload['narcotics_serial'] }}</div>
        </div>
    </div>

    {{-- ── 2. INFO STRIP ── --}}
    <div class="nrx-info-strip">
        <div class="nrx-info-cell">
            <div class="cell-label">Doctor Narcotics License / Licence Stupéfiants</div>
            <div class="cell-value">{{ $payload['doctor_narcotics_license'] }}</div>
        </div>
        <div class="nrx-info-cell">
            <div class="cell-label">Valid Until / Valable Jusqu'au</div>
            <div class="cell-value">{{ $payload['valid_until'] }}</div>
        </div>
        <div class="nrx-info-cell">
            <div class="cell-label">Dispensing Pharmacy / Pharmacie Désignée</div>
            <div class="cell-value">
                @if(!empty($payload['dispensing_pharmacy']))
                    {{ $payload['dispensing_pharmacy'] }}
                @else
                    <em style="color:#9CA3AF;">Any Licensed Pharmacy / Toute Pharmacie Agréée</em>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Patient + Prescriber strip ── --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0 0;">
        <div class="nrx-card" style="margin:0;">
            <div class="nrx-card-header">Patient</div>
            <div class="nrx-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;">
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Name</span><br><strong>{{ $patient_name }}</strong></div>
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Health ID</span><br><strong>{{ $health_id }}</strong></div>
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Sex</span><br>{{ $patient_sex }}</div>
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Date of Birth</span><br>{{ $patient_dob }}</div>
            </div>
        </div>
        <div class="nrx-card" style="margin:0;">
            <div class="nrx-card-header">Prescriber / Prescripteur</div>
            <div class="nrx-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;">
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Name</span><br><strong>{{ $issuer_name }}</strong></div>
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Role</span><br>{{ $issuer_role }}</div>
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Prescription Date</span><br>{{ $payload['prescription_date'] }}</div>
                <div><span style="font-size:9px;color:#9CA3AF;text-transform:uppercase;letter-spacing:.5px;">Facility</span><br>{{ $facility_name }}</div>
            </div>
        </div>
    </div>

    {{-- ── ALLERGY WARNINGS ── --}}
    @if(!empty($payload['allergy_warnings']))
    <div class="nrx-allergy">
        <strong>⚠ Known Allergies / Allergies Connues:</strong>&nbsp;{{ $payload['allergy_warnings'] }}
    </div>
    @endif

    {{-- ── 3. CLINICAL JUSTIFICATION ── --}}
    <div class="nrx-card">
        <div class="nrx-card-header">Clinical Justification / Justification Clinique</div>
        <div class="nrx-card-body">{{ $payload['clinical_justification'] }}</div>
    </div>

    {{-- ── 4. MEDICATIONS TABLE ── --}}
    <div class="nrx-card">
        <div class="nrx-card-header">Prescribed Controlled Substances / Médicaments Stupéfiants Prescrits</div>
        <div style="overflow-x:auto;">
            <table class="nrx-table">
                <thead>
                    <tr>
                        <th>Drug / Médicament</th>
                        <th>Schedule</th>
                        <th>Strength</th>
                        <th>QTY (No.)</th>
                        <th>QTY in Words / En Lettres</th>
                        <th>Route</th>
                        <th>Frequency</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['medications'] as $med)
                    <tr>
                        <td>
                            <div class="nrx-drug-name">{{ $med['name'] }}</div>
                            @if(!empty($med['generic_name']))
                                <div class="nrx-generic">{{ $med['generic_name'] }}</div>
                            @endif
                            <div class="nrx-no-sub">⊘ No Substitution / Non Substituable</div>
                            @if(!empty($med['instructions']))
                                <div class="nrx-instructions">{{ $med['instructions'] }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="nrx-sched-badge">{{ $med['schedule'] }}</span>
                        </td>
                        <td>{{ $med['strength'] }}<br><span style="font-size:10px;color:#6b7280;">{{ $med['form'] }}</span></td>
                        <td style="text-align:center;font-weight:700;font-size:13px;">{{ $med['quantity_numeric'] }}</td>
                        <td>
                            <span class="nrx-qty-words">{{ $med['quantity_words'] }}</span>
                        </td>
                        <td>{{ $med['route'] }}</td>
                        <td>{{ $med['frequency'] }}</td>
                        <td>{{ $med['duration'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── 5. NO REFILL STAMP ── --}}
    @if(!empty($payload['no_refill']))
    <div style="text-align:center;margin:6px 0;">
        <div class="nrx-no-refill-stamp">
            🚫 NO REFILL / NON RENOUVELABLE
        </div>
    </div>
    @endif

    {{-- ── 6. LEGAL NOTICE ── --}}
    <div class="nrx-legal">
        <strong>⚖ Legal Notice / Avis Légal</strong><br>
        This prescription is valid for <strong>10 days</strong> from the date of issue. The dispensing pharmacist must record the batch number, dispensing date, and countersign this prescription. Illegal to reproduce, alter, or reuse. Any person found forging, altering, or misusing this prescription is liable to prosecution under <strong>Cameroon Law No. 90/034 on Narcotic Drugs and Psychotropic Substances</strong> and related penal provisions. This document is subject to inspection by MINSANTE and law-enforcement authorities.
        <br><br>
        <em>Cette ordonnance est valable 10 jours à compter de la date d'émission. Le pharmacien dispensateur doit enregistrer le numéro de lot, la date de délivrance et contresigner. Toute falsification est passible de poursuites judiciaires conformément à la Loi n° 90/034 sur les stupéfiants et substances psychotropes.</em>
    </div>

    {{-- ── 7. SIGNATURE AREA ── --}}
    <div class="nrx-sig-area">
        <div class="nrx-sig-block">
            <div class="nrx-sig-line"></div>
            <div class="nrx-sig-name">{{ $issuer_name }}</div>
            <div class="nrx-sig-role">{{ $issuer_role }}</div>
            <div class="nrx-sig-label">Authorized Narcotics Prescriber / Prescripteur Autorisé<br>License: {{ $payload['doctor_narcotics_license'] }}</div>
        </div>
        <div class="nrx-sig-block">
            <div class="nrx-sig-line"></div>
            <div class="nrx-sig-label">Dispensing Pharmacist / Pharmacien Dispensateur<br>(Signature + Date + Batch No. / Cachet + Date + N° Lot)</div>
        </div>
        <div class="nrx-stamp-area">Official<br>Pharmacy<br>Stamp<br>Cachet</div>
    </div>

    {{-- QR + Verification strip (from base) --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;padding-top:10px;border-top:1px dashed #D97706;font-size:9px;color:#92400E;">
        <div>
            {!! $qr_svg !!}
        </div>
        <div style="text-align:right;">
            <div style="font-weight:700;font-size:10px;text-transform:uppercase;letter-spacing:1px;">Verification Code</div>
            <div style="font-family:monospace;font-size:13px;font-weight:800;letter-spacing:2px;">{{ $verification_code }}</div>
            <div style="margin-top:3px;">Issued: {{ $issued_at }} &bull; {{ $facility_name }}</div>
            <div>License: {{ $facility_license }}</div>
        </div>
    </div>

</div>
@endsection
