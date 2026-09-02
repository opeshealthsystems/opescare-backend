@extends('documents.base')

@section('title', 'MEDICOLEGAL REPORT')

@section('subtitle', 'Police Medical / Court Report — MLR')

@section('content')
<style>
    .mlr-confidential-banner {
        background: #DC2626;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 2.5mm 5mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }
    .mlr-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .mbadge-red { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .mbadge-green { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
    .mbadge-amber { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .mbadge-slate { background: #F1F5F9; color: #334155; border: 1px solid #E2E8F0; }
    .mbadge-dark { background: #374151; color: #F9FAFB; }
    .mlr-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 5mm;
    }
    .mlc-head {
        background: #F8FAFC;
        color: #374151;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #E2E8F0;
    }
    .mlc-body { padding: 4mm; }
    .mlc-head-dark { background: #374151; color: #F9FAFB; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .kv { display: flex; justify-content: space-between; margin-bottom: 1.5mm; font-size: 10px; }
    .kv .k { color: #64748B; }
    .kv .v { color: #0F172A; font-weight: 600; }
    .injury-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .injury-table th { background: #374151; color: #fff; font-weight: 600; text-align: left; padding: 2.5mm 3mm; font-size: 9px; text-transform: uppercase; }
    .injury-table td { padding: 2.5mm 3mm; border-bottom: 1px solid #E2E8F0; }
    .injury-table tr:nth-child(even) td { background: #F8FAFC; }
    .injury-type {
        display: inline-block;
        padding: 0.5mm 2mm;
        border-radius: 4px;
        font-size: 8.5px;
        font-weight: 600;
        text-transform: capitalize;
    }
    .it-abrasion { background: #FEF3C7; color: #92400E; }
    .it-laceration { background: #FEE2E2; color: #991B1B; }
    .it-contusion { background: #EDE9FE; color: #5B21B6; }
    .it-fracture { background: #DBEAFE; color: #1E3A8A; }
    .it-burn { background: #FFEDD5; color: #9A3412; }
    .injury-age {
        display: inline-block;
        padding: 0.5mm 2mm;
        border-radius: 4px;
        font-size: 8.5px;
        font-weight: 600;
    }
    .ia-fresh { background: #FEF2F2; color: #DC2626; }
    .ia-recent { background: #FFFBEB; color: #D97706; }
    .ia-old { background: #F0FDF4; color: #16A34A; }
    .spec-list { list-style: none; padding: 0; margin: 0; }
    .spec-list li { padding: 1.5mm 0; font-size: 10px; border-bottom: 1px solid #F1F5F9; display: flex; align-items: center; gap: 2mm; }
    .spec-list li::before { content: "☑ "; color: #374151; font-size: 11px; }
    .inv-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .inv-table th { background: #F8FAFC; color: #475569; font-weight: 600; text-align: left; padding: 2mm 3mm; border-bottom: 2px solid #E2E8F0; font-size: 9px; text-transform: uppercase; }
    .inv-table td { padding: 2mm 3mm; border-bottom: 1px solid #F1F5F9; }
    .degree-row { display: flex; align-items: center; gap: 4mm; margin-bottom: 4mm; flex-wrap: wrap; }
    .opinion-box {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-left: 4px solid #374151;
        border-radius: 0 6px 6px 0;
        padding: 4mm;
        margin-bottom: 5mm;
        font-size: 10.5px;
        color: #0F172A;
        line-height: 1.6;
        font-style: italic;
    }
    .sensitivity-notice {
        background: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 4px;
        padding: 1.5mm 3mm;
        font-size: 8.5px;
        color: #991B1B;
        margin-bottom: 2mm;
        font-style: italic;
    }
    .examiner-block {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-bottom: 5mm;
    }
    .exam-sig-box {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 3mm;
        text-align: center;
    }
    .exam-sig-box .esb-lbl { font-size: 9px; color: #64748B; margin-bottom: 8mm; }
    .esb-line { border-top: 1px solid #94A3B8; padding-top: 1mm; font-size: 9px; color: #374151; }
    .stamp-box {
        border: 2px dashed #94A3B8;
        border-radius: 6px;
        min-height: 20mm;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94A3B8;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .legal-notice {
        background: #374151;
        color: #F9FAFB;
        border-radius: 6px;
        padding: 3.5mm 4mm;
        font-size: 9px;
        line-height: 1.5;
        margin-top: 5mm;
    }
    .legal-notice strong { color: #FCD34D; }
    .fitness-row { display: flex; align-items: center; gap: 3mm; margin-bottom: 4mm; flex-wrap: wrap; }
</style>

{{-- 1. Confidential banner --}}
<div class="mlr-confidential-banner">
    STRICTLY CONFIDENTIAL — FOR OFFICIAL USE ONLY — CONFIDENTIEL / USAGE OFFICIEL UNIQUEMENT
</div>

{{-- Report type + Authority + Police ref --}}
@php
    $reportType = $payload['report_type'] ?? '—';
    $reportTypeClass = match($reportType) {
        'Road Traffic Accident' => 'mbadge-amber',
        'Assault — Physical' => 'mbadge-red',
        'Sexual Violence' => 'mbadge-red',
        'Suspicious Death' => 'mbadge-dark',
        'Workplace Injury' => 'mbadge-slate',
        default => 'mbadge-slate',
    };
@endphp
<div class="mlr-card">
    <div class="mlc-head mlc-head-dark">Report Classification</div>
    <div class="mlc-body">
        <div class="kv"><span class="k">Report Type</span><span class="v"><span class="mlr-badge {{ $reportTypeClass }}">{{ $reportType }}</span></span></div>
        <div class="kv"><span class="k">Examination Date</span><span class="v">{{ $payload['examination_date'] ?? '—' }} at {{ $payload['examination_time'] ?? '—' }}</span></div>
        <div class="kv"><span class="k">Requesting Authority</span><span class="v">{{ $payload['requesting_authority'] ?? '—' }}</span></div>
        @if(!empty($payload['police_ref_number']))
        <div class="kv"><span class="k">Police Reference No.</span><span class="v" style="font-family:monospace;">{{ $payload['police_ref_number'] }}</span></div>
        @endif
    </div>
</div>

{{-- 3. History of Incident --}}
<div class="mlr-card">
    <div class="mlc-head">Alleged History — As Reported by Patient</div>
    <div class="mlc-body">
        <p style="margin:0; font-size:10px; line-height:1.6; color:#0F172A; font-style:italic;">{{ $payload['history_of_incident'] ?? '—' }}</p>
    </div>
</div>

{{-- 4. Examination Findings --}}
<div class="mlr-card">
    <div class="mlc-head">Examination Findings</div>
    <div class="mlc-body">
        <p style="margin:0; font-size:10px; line-height:1.6; color:#0F172A;">{{ $payload['examination_findings'] ?? '—' }}</p>
    </div>
</div>

{{-- 5. Injury Documentation table --}}
@if(!empty($payload['injuries']))
<div class="mlr-card">
    <div class="mlc-head">Injury Documentation</div>
    <div class="mlc-body" style="padding:2mm;">
        <table class="injury-table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Age</th>
                    <th>Size (cm)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['injuries'] as $inj)
                <tr>
                    <td>{{ $inj['location'] ?? '—' }}</td>
                    <td>{{ $inj['description'] ?? '—' }}</td>
                    <td>
                        @php $itype = strtolower($inj['type'] ?? 'other'); @endphp
                        <span class="injury-type it-{{ $itype }}">{{ $inj['type'] ?? '—' }}</span>
                    </td>
                    <td>
                        @php $iage = strtolower($inj['age'] ?? 'fresh'); @endphp
                        <span class="injury-age ia-{{ $iage }}">{{ $inj['age'] ?? '—' }}</span>
                    </td>
                    <td>{{ $inj['size_cm'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 6. Genital Examination (if present) --}}
@if(!empty($payload['genital_examination']))
<div class="mlr-card" style="border-color:#FECACA;">
    <div class="mlc-head" style="background:#FEF2F2; color:#991B1B; border-bottom-color:#FECACA;">
        Genital Examination
    </div>
    <div class="mlc-body">
        <div class="sensitivity-notice">
            Sensitive Content — Restricted Access — This section is subject to enhanced confidentiality protections.
        </div>
        <p style="margin:0; font-size:10px; line-height:1.6; color:#0F172A;">{{ $payload['genital_examination'] }}</p>
    </div>
</div>
@endif

{{-- 7. Specimens Collected --}}
<div class="two-col">
    <div class="mlr-card" style="margin-bottom:0;">
        <div class="mlc-head">Specimens Collected</div>
        <div class="mlc-body">
            @if(!empty($payload['specimens_collected']))
                <ul class="spec-list">
                    @foreach($payload['specimens_collected'] as $spec)
                        <li>{{ $spec }}</li>
                    @endforeach
                </ul>
            @else
                <p style="font-size:10px; color:#94A3B8; margin:0;">No specimens collected.</p>
            @endif
        </div>
    </div>

    {{-- 8. Investigations --}}
    <div class="mlr-card" style="margin-bottom:0;">
        <div class="mlc-head">Investigations</div>
        <div class="mlc-body" style="padding:2mm;">
            @if(!empty($payload['investigations']))
            <table class="inv-table">
                <thead><tr><th>Test</th><th>Result</th></tr></thead>
                <tbody>
                    @foreach($payload['investigations'] as $inv)
                    <tr>
                        <td>{{ $inv['test'] ?? '—' }}</td>
                        <td>{{ $inv['result'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
                <p style="font-size:10px; color:#94A3B8; margin:2mm;">None requested.</p>
            @endif
        </div>
    </div>
</div>
<div style="margin-bottom:5mm;"></div>

{{-- 9. Degree of Injury + Incapacity + Fitness --}}
@php
    $degree = $payload['degree_of_injury'] ?? 'Simple';
    $degreeClass = match($degree) {
        'Simple' => 'mbadge-green',
        'Grievous' => 'mbadge-amber',
        'Life-threatening' => 'mbadge-red',
        default => 'mbadge-slate',
    };
@endphp
<div class="degree-row">
    <span style="font-size:10px; color:#64748B;">Degree of Injury:</span>
    <span class="mlr-badge {{ $degreeClass }}" style="font-size:10px; padding:1.5mm 4mm;">{{ $degree }}</span>
    @if(!empty($payload['incapacity_days']))
        <span style="font-size:10px; color:#374151; margin-left:2mm;">
            Incapacity: <strong>{{ $payload['incapacity_days'] }} days</strong>
        </span>
    @endif
</div>

{{-- 10. Fitness for Interview --}}
<div class="fitness-row">
    <span style="font-size:10px; color:#64748B;">Fitness for Police Interview:</span>
    @if(!empty($payload['fitness_for_interview']))
        <span class="mlr-badge mbadge-green">Fit for Interview</span>
    @else
        <span class="mlr-badge mbadge-red">Not Fit for Interview</span>
    @endif
    @if(!empty($payload['fitness_notes']))
        <span style="font-size:9.5px; color:#374151; font-style:italic;">{{ $payload['fitness_notes'] }}</span>
    @endif
</div>

{{-- 11. Clinical Opinion --}}
<div style="font-size:10px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:0.5px; border-left:3px solid #374151; padding-left:2.5mm; margin-bottom:2.5mm;">
    Clinical Opinion for Court
</div>
<div class="opinion-box">{{ $payload['opinion'] ?? '—' }}</div>

{{-- 12. Examiner signature + stamp --}}
<div class="examiner-block">
    <div class="exam-sig-box">
        <div class="esb-lbl">Examining Medical Officer</div>
        <div class="esb-line">{{ $payload['examiner'] ?? '—' }} &nbsp;|&nbsp; Reg: {{ $payload['examiner_reg'] ?? '—' }}</div>
    </div>
    <div class="stamp-box">Official Stamp / Cachet Officiel</div>
</div>

{{-- 13. Legal Notice --}}
<div class="legal-notice">
    <strong>CONFIDENTIALITY NOTICE:</strong> This report is strictly confidential and must only be released to the requesting authority ({{ $payload['requesting_authority'] ?? 'authorised recipient' }}). Unauthorized disclosure, copying, or distribution of this document is an offence under <strong>Cameroon Law No. 2010/012</strong> on the protection of personal health information and applicable criminal procedure laws. This document is prepared solely for medico-legal purposes and constitutes expert medical evidence.
</div>
@endsection
