@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Lettre de Préautorisation' : 'Pre-Authorisation Request Letter' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Demande officielle d\'autorisation — PAL' : 'Official Insurance Pre-Authorisation — PAL' }}
@endsection

@section('content')
<style>
    .pal-accent { color: #0369A1; }

    .pal-letter-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 5mm;
        padding-bottom: 4mm;
        border-bottom: 2px solid #BAE6FD;
    }
    .pal-letter-address-block { font-size: 10px; line-height: 1.7; color: #1E293B; }
    .pal-letter-address-block .addr-label { font-size: 9px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; }
    .pal-letter-address-block .addr-name  { font-weight: 700; font-size: 11px; color: #0369A1; }
    .pal-letter-meta { text-align: right; font-size: 10px; line-height: 1.8; color: #374151; }
    .pal-letter-meta .meta-date { font-size: 11px; font-weight: 600; color: #1E293B; }
    .pal-letter-meta .meta-ref  { font-weight: 700; color: #0369A1; font-size: 11px; }

    .pal-urgency-badge {
        display: inline-block;
        padding: 1.5mm 4mm;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 2mm;
    }
    .urgency-routine   { background: #DBEAFE; color: #1D4ED8; border: 1.5px solid #93C5FD; }
    .urgency-urgent    { background: #FEF3C7; color: #B45309; border: 1.5px solid #FCD34D; }
    .urgency-emergency { background: #FEE2E2; color: #B91C1C; border: 1.5px solid #FCA5A5; }

    .pal-subject-bar {
        background: linear-gradient(135deg, #0369A1 0%, #075985 100%);
        color: #FFFFFF;
        padding: 3mm 4mm;
        border-radius: 5px;
        margin-bottom: 5mm;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .pal-subject-bar span { opacity: 0.75; font-weight: 400; margin-right: 2mm; }

    .pal-policy-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .pal-policy-cell {
        background: #F0F9FF;
        border: 1px solid #BAE6FD;
        border-radius: 5px;
        padding: 3mm;
    }
    .pal-policy-cell .pc-label {
        font-size: 8.5px;
        font-weight: 600;
        text-transform: uppercase;
        color: #0369A1;
        letter-spacing: 0.5px;
        margin-bottom: 0.5mm;
    }
    .pal-policy-cell .pc-value {
        font-size: 11px;
        font-weight: 700;
        color: #0F172A;
    }

    .pal-body-text {
        font-size: 10.5px;
        line-height: 1.75;
        color: #1E293B;
        margin-bottom: 4mm;
        text-align: justify;
    }
    .pal-salutation {
        font-size: 11px;
        font-weight: 600;
        color: #1E293B;
        margin-bottom: 3mm;
    }

    .pal-section-header {
        background: #0369A1;
        color: #FFFFFF;
        padding: 2.5mm 4mm;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 4px 4px 0 0;
        margin-top: 4mm;
    }

    .pal-procedure-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #BAE6FD;
        border-top: none;
        margin-bottom: 4mm;
    }
    .pal-procedure-table th {
        background: #E0F2FE;
        color: #0369A1;
        font-weight: 700;
        font-size: 9px;
        text-transform: uppercase;
        padding: 2.5mm 3mm;
        border-bottom: 2px solid #BAE6FD;
        text-align: left;
    }
    .pal-procedure-table td {
        padding: 2.5mm 3mm;
        font-size: 10px;
        border-bottom: 1px solid #E0F2FE;
        color: #1E293B;
    }
    .pal-procedure-table tr:last-child td { border-bottom: none; }
    .pal-procedure-table .code-cell {
        font-family: monospace;
        font-weight: 700;
        color: #0369A1;
        font-size: 10px;
        background: #F0F9FF;
        border-radius: 3px;
        padding: 1mm 2mm;
    }

    .pal-justification-card {
        background: #F0F9FF;
        border: 1px solid #BAE6FD;
        border-top: none;
        padding: 3.5mm 4mm;
        font-size: 10.5px;
        line-height: 1.7;
        color: #1E293B;
        font-style: italic;
        margin-bottom: 4mm;
    }

    .pal-cost-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #BAE6FD;
        border-top: none;
        margin-bottom: 4mm;
    }
    .pal-cost-table th {
        background: #E0F2FE;
        color: #0369A1;
        font-weight: 700;
        font-size: 9px;
        text-transform: uppercase;
        padding: 2.5mm 3mm;
        border-bottom: 2px solid #BAE6FD;
        text-align: left;
    }
    .pal-cost-table td {
        padding: 2.5mm 3mm;
        font-size: 10px;
        border-bottom: 1px solid #E0F2FE;
    }
    .pal-cost-table .amount-cell { text-align: right; font-weight: 600; color: #1E293B; font-family: monospace; }
    .pal-cost-table .subtotal-row td { background: #F0F9FF; font-weight: 700; border-top: 2px solid #BAE6FD; }
    .pal-cost-table .coverage-row td { background: #DCFCE7; color: #166534; font-weight: 700; }
    .pal-cost-table .patient-row td  { background: #FEF3C7; color: #92400E; font-weight: 800; font-size: 11px; }

    .pal-coverage-visual {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3mm;
        margin-bottom: 4mm;
    }
    .pal-coverage-box-blue {
        background: linear-gradient(135deg, #0369A1 0%, #075985 100%);
        color: #FFFFFF;
        border-radius: 6px;
        padding: 4mm;
        text-align: center;
    }
    .pal-coverage-box-gray {
        background: #F1F5F9;
        color: #374151;
        border: 2px solid #CBD5E1;
        border-radius: 6px;
        padding: 4mm;
        text-align: center;
    }
    .cov-pct { font-size: 24px; font-weight: 900; line-height: 1; margin-bottom: 1mm; }
    .cov-label { font-size: 8.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85; margin-bottom: 1mm; }
    .cov-amount { font-size: 12px; font-weight: 700; }

    .pal-docs-list { border: 1px solid #BAE6FD; border-top: none; padding: 0; margin-bottom: 4mm; }
    .pal-doc-item {
        display: flex;
        align-items: center;
        gap: 3mm;
        padding: 2mm 4mm;
        border-bottom: 1px solid #E0F2FE;
        font-size: 10px;
        color: #1E293B;
    }
    .pal-doc-item:last-child { border-bottom: none; }
    .doc-check { color: #0369A1; font-weight: 700; font-size: 12px; }

    .pal-pending-box {
        border: 2.5px dashed #0369A1;
        border-radius: 6px;
        padding: 6mm;
        text-align: center;
        margin-bottom: 5mm;
        position: relative;
        overflow: hidden;
    }
    .pal-pending-watermark {
        font-size: 32px;
        font-weight: 900;
        color: rgba(3, 105, 161, 0.06);
        text-transform: uppercase;
        letter-spacing: 4px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        white-space: nowrap;
        pointer-events: none;
    }
    .pal-pending-label {
        font-size: 14px;
        font-weight: 800;
        color: #0369A1;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 2mm;
    }
    .pal-pending-meta { font-size: 9px; color: #64748B; }

    .pal-closing-block {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 5mm;
        margin-top: 4mm;
    }
    .pal-provider-sig {
        border-top: 1.5px solid #94A3B8;
        padding-top: 2mm;
        font-size: 9.5px;
        color: #374151;
    }
    .pal-facility-stamp {
        border: 2px dashed #CBD5E1;
        border-radius: 50%;
        width: 25mm;
        height: 25mm;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 7.5px;
        color: #94A3B8;
        text-align: center;
        margin-left: auto;
    }
</style>

{{-- LETTER HEADER --}}
<div class="pal-letter-header">
    <div class="pal-letter-address-block">
        <div class="addr-label">{{ $language === 'fr' ? 'À / To:' : 'To:' }}</div>
        <div class="addr-name">{{ $payload['insurer_name'] ?? 'Insurance Provider' }}</div>
        <div>{{ $payload['insurer_address'] ?? '' }}</div>
        <div>{{ $language === 'fr' ? 'Att: Service des Préautorisations' : 'Attn: Pre-Authorisation Department' }}</div>
        <div style="margin-top: 2mm; color: #64748B;">
            {{ $language === 'fr' ? 'Tél:' : 'Tel:' }} {{ $payload['insurer_contact'] ?? 'N/A' }}
        </div>
        <div style="margin-top: 3mm;">
            <div class="addr-label">{{ $language === 'fr' ? 'De / From:' : 'From:' }}</div>
            <strong>{{ $facility_name }}</strong><br>
            Tel: +237 233-421-000
        </div>
    </div>
    <div class="pal-letter-meta">
        <div class="meta-date">{{ $payload['request_date'] ?? $issued_at }}</div>
        <div style="margin-top: 2mm;">
            <div class="meta-ref">Ref: PREAUTH-{{ $payload['preauth_number'] ?? $document_number }}</div>
        </div>
        <div style="margin-top: 3mm;">
            @php
                $urgency = strtolower($payload['urgency'] ?? 'routine');
                $urgencyClass = match($urgency) { 'urgent' => 'urgency-urgent', 'emergency' => 'urgency-emergency', default => 'urgency-routine' };
            @endphp
            <span class="pal-urgency-badge {{ $urgencyClass }}">{{ strtoupper($urgency) }}</span>
        </div>
        @if(!empty($payload['response_required_by']))
        <div style="font-size: 9px; color: #B45309; font-weight: 600; margin-top: 1mm;">
            {{ $language === 'fr' ? 'Réponse requise avant :' : 'Response required by:' }}<br>
            <span style="font-size: 11px; color: #92400E;">{{ $payload['response_required_by'] }}</span>
        </div>
        @endif
    </div>
</div>

{{-- Subject Bar --}}
<div class="pal-subject-bar">
    <span>{{ $language === 'fr' ? 'Objet:' : 'Subject:' }}</span>
    {{ $language === 'fr' ? 'DEMANDE DE PRÉAUTORISATION —' : 'REQUEST FOR PRE-AUTHORISATION —' }}
    {{ strtoupper($payload['proposed_procedure'] ?? 'Proposed Medical Procedure') }}
</div>

{{-- Policy Information --}}
<div class="pal-policy-grid">
    <div class="pal-policy-cell">
        <div class="pc-label">{{ $language === 'fr' ? 'Titulaire de la Police' : 'Policy Holder' }}</div>
        <div class="pc-value">{{ $payload['policy_holder'] ?? $patient_name }}</div>
    </div>
    <div class="pal-policy-cell">
        <div class="pc-label">{{ $language === 'fr' ? 'Numéro de Police' : 'Policy Number' }}</div>
        <div class="pc-value" style="font-family: monospace;">{{ $payload['policy_number'] ?? 'N/A' }}</div>
    </div>
    <div class="pal-policy-cell">
        <div class="pc-label">{{ $language === 'fr' ? 'Employeur' : 'Employer of Record' }}</div>
        <div class="pc-value">{{ $payload['employer_of_record'] ?? 'N/A' }}</div>
    </div>
    <div class="pal-policy-cell">
        <div class="pc-label">{{ $language === 'fr' ? 'Patient / Bénéficiaire' : 'Patient / Beneficiary' }}</div>
        <div class="pc-value">{{ $patient_name }}</div>
    </div>
</div>

{{-- Salutation + Opening --}}
<div class="pal-salutation">
    {{ $language === 'fr' ? 'Madame, Monsieur,' : 'Dear Pre-Authorisation Team,' }}
</div>
<div class="pal-body-text">
    {{ $language === 'fr'
        ? 'Nous vous adressons la présente au nom de notre patient(e) ' . $patient_name . ', titulaire de la police susmentionnée, afin de solliciter une préautorisation pour la procédure médicale proposée ci-après. Nous vous prions de bien vouloir examiner ce dossier avec la priorité correspondant au niveau d\'urgence indiqué.'
        : 'We are writing on behalf of our patient ' . $patient_name . ', the above-named policy holder, to request pre-authorisation for the following proposed medical procedure. We respectfully request that this matter be considered with the priority corresponding to the indicated urgency level.' }}
</div>

{{-- Procedure Details --}}
@if(!empty($payload['procedure_codes']))
<div class="pal-section-header">
    {{ $language === 'fr' ? 'DÉTAILS DE LA PROCÉDURE / PROCEDURE DETAILS' : 'PROCEDURE DETAILS — CPT / ICD CODES' }}
</div>
<table class="pal-procedure-table">
    <thead>
        <tr>
            <th>{{ $language === 'fr' ? 'CODE CPT/CIM' : 'CPT/ICD CODE' }}</th>
            <th>{{ $language === 'fr' ? 'DESCRIPTION DE LA PROCÉDURE' : 'PROCEDURE DESCRIPTION' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payload['procedure_codes'] as $pc)
        <tr>
            <td><span class="code-cell">{{ $pc['code'] ?? '' }}</span></td>
            <td>{{ $pc['description'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Clinical Justification --}}
@if(!empty($payload['clinical_justification']))
<div class="pal-section-header">
    {{ $language === 'fr' ? 'JUSTIFICATION CLINIQUE / CLINICAL JUSTIFICATION' : 'CLINICAL JUSTIFICATION' }}
</div>
<div class="pal-justification-card">
    {{ $payload['clinical_justification'] }}
</div>
@endif

{{-- Cost Breakdown --}}
@if(!empty($payload['cost_breakdown']))
<div class="pal-section-header">
    {{ $language === 'fr' ? 'VENTILATION DES COÛTS / COST BREAKDOWN' : 'ESTIMATED COST BREAKDOWN' }}
</div>
<table class="pal-cost-table">
    <thead>
        <tr>
            <th>{{ $language === 'fr' ? 'ÉLÉMENT' : 'ITEM' }}</th>
            <th style="text-align:right;">{{ $language === 'fr' ? 'COÛT ESTIMÉ (XAF)' : 'ESTIMATED COST (XAF)' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payload['cost_breakdown'] as $line)
        <tr>
            <td>{{ $line['item'] ?? '' }}</td>
            <td class="amount-cell">{{ number_format($line['amount'] ?? 0) }}</td>
        </tr>
        @endforeach
        <tr class="subtotal-row">
            <td>{{ $language === 'fr' ? 'SOUS-TOTAL' : 'SUBTOTAL' }}</td>
            <td class="amount-cell">{{ number_format($payload['subtotal'] ?? 0) }} XAF</td>
        </tr>
        <tr class="coverage-row">
            <td>{{ $language === 'fr' ? 'Couverture CNPS/Assurance' : 'CNPS/Insurance Coverage' }} ({{ $payload['cnps_coverage_percent'] ?? 0 }}%)</td>
            <td class="amount-cell">− {{ number_format($payload['insurer_coverage_amount'] ?? 0) }} XAF</td>
        </tr>
        <tr class="patient-row">
            <td>{{ $language === 'fr' ? 'PART DU PATIENT' : 'PATIENT RESPONSIBILITY' }}</td>
            <td class="amount-cell">{{ number_format($payload['patient_responsibility'] ?? 0) }} XAF</td>
        </tr>
    </tbody>
</table>
@endif

{{-- Coverage Visual --}}
@php
    $coveragePct = $payload['cnps_coverage_percent'] ?? 0;
    $patientPct  = 100 - $coveragePct;
@endphp
<div class="pal-coverage-visual">
    <div class="pal-coverage-box-blue">
        <div class="cov-pct">{{ $coveragePct }}%</div>
        <div class="cov-label">{{ $language === 'fr' ? 'Couverture Assurance' : 'Insurance Coverage' }}</div>
        <div class="cov-amount">{{ number_format($payload['insurer_coverage_amount'] ?? 0) }} XAF</div>
    </div>
    <div class="pal-coverage-box-gray">
        <div class="cov-pct" style="color: #92400E;">{{ $patientPct }}%</div>
        <div class="cov-label">{{ $language === 'fr' ? 'Part du Patient' : 'Patient Responsibility' }}</div>
        <div class="cov-amount" style="color: #78350F;">{{ number_format($payload['patient_responsibility'] ?? 0) }} XAF</div>
    </div>
</div>

{{-- Supporting Documents --}}
@if(!empty($payload['supporting_documents']))
<div class="pal-section-header">
    {{ $language === 'fr' ? 'PIÈCES JOINTES / DOCUMENTS ENCLOSED' : 'SUPPORTING DOCUMENTS ENCLOSED' }}
</div>
<div class="pal-docs-list">
    @foreach($payload['supporting_documents'] as $doc)
    <div class="pal-doc-item">
        <span class="doc-check">✓</span>
        <span>{{ $doc }}</span>
    </div>
    @endforeach
</div>
@endif

{{-- Request paragraph --}}
<div class="pal-body-text">
    {{ $language === 'fr'
        ? 'Nous sollicitons respectueusement que la préautorisation soit accordée pour la procédure susmentionnée. Compte tenu du niveau d\'urgence indiqué (' . strtoupper($payload['urgency'] ?? 'routine') . '), nous vous prions de bien vouloir nous faire parvenir votre réponse avant le ' . ($payload['response_required_by'] ?? 'dès que possible') . '. Nous restons disponibles pour tout renseignement complémentaire que vous pourriez souhaiter.'
        : 'We respectfully request that pre-authorisation be granted for the above procedure. We note the urgency level (' . strtoupper($payload['urgency'] ?? 'ROUTINE') . ') and kindly request a response by ' . ($payload['response_required_by'] ?? 'the earliest opportunity') . '. We remain available to provide any additional clinical information you may require.' }}
</div>

{{-- Authorisation Pending Box --}}
<div class="pal-pending-box">
    <div class="pal-pending-watermark">AUTHORISATION PENDING</div>
    <div class="pal-pending-label">
        {{ $language === 'fr' ? '⏳ AUTORISATION EN ATTENTE' : '⏳ AUTHORISATION PENDING' }}
    </div>
    <div class="pal-pending-meta">
        {{ $language === 'fr'
            ? 'Ce document est une demande officielle. La préautorisation n\'est pas encore accordée.'
            : 'This document is a formal request. Pre-authorisation has not yet been granted.' }}
    </div>
</div>

{{-- Closing --}}
<div class="pal-body-text">
    {{ $language === 'fr'
        ? 'Dans l\'attente d\'une réponse favorable, nous vous prions d\'agréer, Madame, Monsieur, l\'expression de nos salutations distinguées.'
        : 'Yours faithfully,' }}
</div>

<div class="pal-closing-block">
    <div>
        <div style="font-size: 10px; font-weight: 600; margin-bottom: 8mm;">{{ $issuer_name }}</div>
        <div class="pal-provider-sig">
            <div>{{ $issuer_role }}</div>
            <div>{{ $facility_name }}</div>
            <div>License: {{ $facility_license ?? 'N/A' }}</div>
        </div>
    </div>
    <div style="display: flex; align-items: center; justify-content: flex-end;">
        <div class="pal-facility-stamp">
            OFFICIAL<br>FACILITY<br>STAMP
        </div>
    </div>
</div>
@endsection
