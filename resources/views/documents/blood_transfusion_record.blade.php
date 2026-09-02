@extends('documents.base')

@section('title')
    {{ $language === 'fr' ? 'Fiche de Transfusion Sanguine' : 'Blood Transfusion Record' }}
@endsection

@section('subtitle')
    {{ $language === 'fr' ? 'Document médico-légal obligatoire — BTR' : 'Mandatory Medico-Legal Transfusion Record — BTR' }}
@endsection

@section('content')
<style>
    .btr-urgent-banner {
        background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%);
        color: #FFFFFF;
        border-radius: 8px;
        padding: 3.5mm 6mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .btr-urgent-banner h3 {
        margin: 0;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }
    .btr-urgent-banner p {
        margin: 1mm 0 0 0;
        font-size: 9.5px;
        opacity: 0.85;
    }
    .btr-blood-group-badge {
        background: rgba(255,255,255,0.15);
        border: 2px solid rgba(255,255,255,0.5);
        border-radius: 50%;
        width: 18mm;
        height: 18mm;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-size: 7.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        line-height: 1.3;
    }
    .btr-blood-group-badge span {
        font-size: 15px;
        font-weight: 900;
        letter-spacing: 0;
    }
    .btr-info-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 3mm;
        margin-bottom: 5mm;
    }
    .btr-info-cell {
        border: 1.5px solid #FECACA;
        border-radius: 6px;
        background: #FFF5F5;
        padding: 2.5mm 3mm;
    }
    .btr-info-cell label {
        display: block;
        font-size: 8px;
        color: #DC2626;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 0.5mm;
    }
    .btr-info-cell span {
        font-size: 11px;
        font-weight: 700;
        color: #7F1D1D;
    }
    .btr-section-title {
        background: #DC2626;
        color: #FFFFFF;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2mm 4mm;
        border-radius: 4px 4px 0 0;
    }
    .btr-section-body {
        border: 1.5px solid #FECACA;
        border-top: none;
        border-radius: 0 0 4px 4px;
        padding: 3.5mm;
        margin-bottom: 5mm;
    }
    .btr-consent-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2mm;
    }
    .btr-consent-item label {
        display: block;
        font-size: 8.5px;
        color: #64748B;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.5mm;
    }
    .btr-consent-item span {
        font-size: 11px;
        font-weight: 600;
        color: #0F172A;
    }
    .btr-checklist-item {
        display: flex;
        align-items: center;
        padding: 2mm 0;
        border-bottom: 1px dashed #E2E8F0;
        font-size: 10.5px;
    }
    .btr-checklist-item:last-child {
        border-bottom: none;
    }
    .btr-check-icon {
        width: 5mm;
        height: 5mm;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 900;
        margin-right: 3mm;
        flex-shrink: 0;
    }
    .btr-check-pass {
        background: #DCFCE7;
        color: #15803D;
        border: 1.5px solid #86EFAC;
    }
    .btr-check-fail {
        background: #FEE2E2;
        color: #DC2626;
        border: 1.5px solid #FCA5A5;
    }
    .btr-safety-note {
        background: #FEF3C7;
        border: 1.5px solid #FCD34D;
        border-radius: 5px;
        padding: 2mm 3mm;
        margin-top: 2mm;
        font-size: 9px;
        color: #92400E;
        font-weight: 600;
    }
    .btr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .btr-table th {
        background: #DC2626;
        color: #FFFFFF;
        padding: 2mm 2.5mm;
        text-align: left;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        border: 1px solid #B91C1C;
    }
    .btr-table td {
        padding: 2.5mm;
        border: 1px solid #E2E8F0;
        color: #0F172A;
        vertical-align: middle;
    }
    .btr-table tr:nth-child(even) td {
        background: #FFF5F5;
    }
    .btr-compat-pass {
        background: #DCFCE7;
        color: #15803D;
        border-radius: 9999px;
        padding: 0.5mm 2mm;
        font-size: 8.5px;
        font-weight: 700;
        display: inline-block;
    }
    .btr-compat-fail {
        background: #FEE2E2;
        color: #DC2626;
        border-radius: 9999px;
        padding: 0.5mm 2mm;
        font-size: 8.5px;
        font-weight: 700;
        display: inline-block;
    }
    .btr-hb-comparison {
        display: flex;
        align-items: center;
        gap: 4mm;
        background: #FFF5F5;
        border: 1.5px solid #FECACA;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .btr-hb-box {
        text-align: center;
        flex: 1;
    }
    .btr-hb-box label {
        display: block;
        font-size: 8px;
        color: #DC2626;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 1mm;
    }
    .btr-hb-box span {
        font-size: 18px;
        font-weight: 900;
        color: #7F1D1D;
    }
    .btr-hb-arrow {
        font-size: 20px;
        color: #DC2626;
        font-weight: 700;
    }
    .btr-adverse-box {
        background: #FEE2E2;
        border: 2px solid #DC2626;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .btr-adverse-box h4 {
        margin: 0 0 1.5mm 0;
        font-size: 10px;
        color: #7F1D1D;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .btr-outcome-badge {
        text-align: center;
        margin: 4mm 0;
    }
    .btr-outcome-inner {
        display: inline-block;
        padding: 3mm 8mm;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .outcome-ok {
        background: #DCFCE7;
        color: #14532D;
        border: 2px solid #86EFAC;
    }
    .outcome-reaction {
        background: #FEE2E2;
        color: #7F1D1D;
        border: 2px solid #FCA5A5;
    }
    .btr-sig-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6mm;
        margin-top: 4mm;
    }
    .btr-sig-box {
        border: 1px solid #FECACA;
        border-radius: 6px;
        padding: 3mm;
        background: #FFF5F5;
    }
    .btr-sig-box label {
        display: block;
        font-size: 8.5px;
        color: #DC2626;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.3px;
        margin-bottom: 1mm;
    }
    .btr-sig-line {
        border-bottom: 1.5px solid #94A3B8;
        height: 10mm;
        margin-bottom: 1.5mm;
    }
    .btr-sig-name {
        font-size: 10px;
        font-weight: 700;
        color: #0F172A;
    }
    .btr-sig-role {
        font-size: 9px;
        color: #64748B;
    }
</style>

{{-- 1. Red urgent header --}}
<div class="btr-urgent-banner">
    <div>
        <h3>&#128997; Blood Transfusion Record</h3>
        <p>Date: {{ $payload['transfusion_date'] }} &nbsp;|&nbsp; Indication: {{ $payload['indication'] }}</p>
    </div>
    <div class="btr-blood-group-badge">
        <div>Blood Group</div>
        <span>{{ $payload['patient_blood_group'] }}</span>
    </div>
</div>

{{-- 2. Indication + Hb pre/post --}}
<div class="btr-hb-comparison">
    <div class="btr-hb-box">
        <label>Hb Pre-Transfusion</label>
        <span>{{ $payload['patient_hb_pre'] }}</span>
    </div>
    <div class="btr-hb-arrow">&#8594;</div>
    <div class="btr-hb-box">
        <label>Hb Post-Transfusion</label>
        <span>{{ $payload['patient_hb_post'] ?? '—' }}</span>
    </div>
    <div style="flex:2;padding-left:4mm;border-left:2px solid #FECACA;">
        <div style="font-size:8.5px;color:#DC2626;font-weight:700;text-transform:uppercase;margin-bottom:1mm;">Indication for Transfusion</div>
        <div style="font-size:10px;color:#7F1D1D;font-weight:600;">{{ $payload['indication'] }}</div>
    </div>
</div>

{{-- 3. Consent card --}}
<div class="btr-section-title">Consent / Consentement</div>
<div class="btr-section-body">
    <div class="btr-consent-grid">
        <div class="btr-consent-item">
            <label>Consent Obtained</label>
            <span>
                @if($payload['consent_obtained'])
                    <span style="color:#15803D;font-weight:700;">&#10003; Yes</span>
                @else
                    <span style="color:#DC2626;font-weight:700;">&#10007; No</span>
                @endif
            </span>
        </div>
        <div class="btr-consent-item">
            <label>Consented By</label>
            <span>{{ $payload['consent_by'] }}</span>
        </div>
        <div class="btr-consent-item">
            <label>Consent Time</label>
            <span>{{ $payload['consent_time'] }}</span>
        </div>
    </div>
</div>

{{-- 4. Pre-Transfusion Safety Checklist --}}
<div class="btr-section-title">Pre-Transfusion Safety Checklist / Vérifications Pré-Transfusionnelles</div>
<div class="btr-section-body">
    @php $checks = $payload['pre_transfusion_check']; @endphp
    @php $checkItems = [
        'patient_id_verified'      => 'Patient identity verified against wristband',
        'blood_group_checked'      => 'Blood group confirmed matches patient record',
        'unit_label_checked'       => 'Blood unit label verified',
        'expiry_checked'           => 'Expiry date of unit checked',
        'compatibility_confirmed'  => 'Compatibility test result confirmed',
        'iv_access_verified'       => 'IV access verified and patent',
    ]; @endphp
    @foreach($checkItems as $key => $label)
    <div class="btr-checklist-item">
        <span class="btr-check-icon {{ $checks[$key] ? 'btr-check-pass' : 'btr-check-fail' }}">
            {{ $checks[$key] ? '✓' : '✗' }}
        </span>
        <span style="flex:1;">{{ $label }}</span>
    </div>
    @endforeach
    <div style="display:flex;justify-content:space-between;margin-top:2mm;font-size:9.5px;">
        <span style="color:#475569;">Checked by: <strong>{{ $checks['checked_by'] }}</strong></span>
    </div>
    <div class="btr-safety-note">
        &#9888; SAFETY NOTICE: All 6 checks must be completed and confirmed by two staff members before transfusion commences. Failure to complete this checklist is a serious adverse event risk.
    </div>
</div>

{{-- 5. Blood Units Administered --}}
<div class="btr-section-title">Blood Units Administered / Unités Transfusées</div>
<div class="btr-section-body" style="padding:0;">
    <table class="btr-table">
        <thead>
            <tr>
                <th>Unit ID</th>
                <th>Group</th>
                <th>Component</th>
                <th style="text-align:right;">Vol (mL)</th>
                <th>Compat. Test</th>
                <th>Start</th>
                <th>End</th>
                <th style="text-align:right;">Rate mL/hr</th>
                <th>Given By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['blood_units'] as $unit)
            <tr>
                <td style="font-family:monospace;font-size:9px;">{{ $unit['unit_id'] }}</td>
                <td style="font-weight:700;color:#DC2626;">{{ $unit['blood_group'] }}</td>
                <td>{{ $unit['component'] }}</td>
                <td style="text-align:right;font-weight:600;">{{ $unit['volume_ml'] }}</td>
                <td>
                    <span class="{{ $unit['result'] === 'Compatible' ? 'btr-compat-pass' : 'btr-compat-fail' }}">
                        {{ $unit['result'] }}
                    </span>
                </td>
                <td style="font-family:monospace;font-size:9px;">{{ $unit['start_time'] }}</td>
                <td style="font-family:monospace;font-size:9px;">{{ $unit['end_time'] }}</td>
                <td style="text-align:right;">{{ $unit['rate_ml_hr'] }}</td>
                <td style="font-size:9px;">{{ $unit['administered_by'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- 6. Monitoring observations --}}
<div class="btr-section-title">Transfusion Monitoring / Surveillance Transfusionnelle</div>
<div class="btr-section-body" style="padding:0;">
    <table class="btr-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>BP (mmHg)</th>
                <th>Pulse</th>
                <th>Temp (°C)</th>
                <th>SpO2 (%)</th>
                <th>Urticaria</th>
                <th>Other Reactions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payload['monitoring_observations'] as $obs)
            <tr>
                <td style="font-family:monospace;font-size:9.5px;font-weight:600;">{{ $obs['time'] }}</td>
                <td>{{ $obs['bp'] }}</td>
                <td>{{ $obs['pulse'] }}</td>
                <td>{{ $obs['temp'] }}</td>
                <td style="{{ (is_numeric($obs['spo2']) && (float)$obs['spo2'] < 94) ? 'color:#DC2626;font-weight:700;' : '' }}">
                    {{ $obs['spo2'] }}
                </td>
                <td>{{ $obs['urticaria'] ?? '—' }}</td>
                <td style="font-size:9px;color:#475569;">{{ $obs['other_reactions'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- 7. Adverse Reactions --}}
@if(!empty($payload['adverse_reactions']))
<div class="btr-adverse-box">
    <h4>&#9888; Adverse Reactions Recorded / Réactions Indésirables</h4>
    @foreach($payload['adverse_reactions'] as $rxn)
        <div style="font-size:10px;color:#7F1D1D;font-weight:600;margin-bottom:0.5mm;">&#8226; {{ $rxn }}</div>
    @endforeach
</div>
@endif

{{-- 8. Outcome badge --}}
<div class="btr-outcome-badge">
    @php
        $outcomeOk = str_contains(strtolower($payload['outcome']), 'without adverse');
    @endphp
    <div class="btr-outcome-inner {{ $outcomeOk ? 'outcome-ok' : 'outcome-reaction' }}">
        @if($outcomeOk)&#10003; @else&#10007; @endif
        {{ $payload['outcome'] }}
    </div>
</div>

{{-- 9. Dual Signatures --}}
<div class="btr-sig-grid">
    <div class="btr-sig-box">
        <label>Transfusion Nurse / Infirmière Transfusionniste</label>
        <div class="btr-sig-line"></div>
        <div class="btr-sig-name">{{ $payload['transfusion_nurse'] }}</div>
        <div class="btr-sig-role">Registered Nurse — Transfusion Unit</div>
        <div style="font-size:8.5px;color:#94A3B8;margin-top:1mm;">Date: {{ $payload['transfusion_date'] }}</div>
    </div>
    <div class="btr-sig-box">
        <label>Supervising Physician / Médecin Superviseur</label>
        <div class="btr-sig-line"></div>
        <div class="btr-sig-name">{{ $payload['supervising_physician'] }}</div>
        <div class="btr-sig-role">{{ $issuer_role }}</div>
        <div style="font-size:8.5px;color:#94A3B8;margin-top:1mm;">Date: {{ $payload['transfusion_date'] }}</div>
    </div>
</div>
@endsection
