@extends('documents.base')

@section('content')
@php
    $accentColor = '#0F4C81';
    $accentLight = '#EFF6FF';
    $accentMid   = '#BFDBFE';
    $documentCode  = 'OPD';
    $documentTitle = 'Outpatient Consultation Summary';
@endphp

<style>
    /* ── OPD-specific overrides ───────────────────────────────────── */
    .opd-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 7px 12px;
        border-radius: 4px 4px 0 0;
        margin-bottom: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .opd-header-strip h1 {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin: 0;
        text-transform: uppercase;
    }
    .opd-header-strip .opd-sub {
        font-size: 9px;
        opacity: 0.85;
        margin-top: 2px;
    }
    .opd-header-strip .opd-date {
        font-size: 11px;
        font-weight: 700;
        text-align: right;
    }
    .opd-header-strip .opd-date span {
        font-size: 9px;
        font-weight: 400;
        opacity: 0.8;
        display: block;
    }

    .section-card {
        border: 1px solid #E5E7EB;
        border-radius: 4px;
        margin-bottom: 7px;
        overflow: hidden;
    }
    .section-card-title {
        background: #F3F4F6;
        padding: 3px 10px;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }
    .section-card-body {
        padding: 7px 10px;
        font-size: 10px;
        color: #1F2937;
        line-height: 1.5;
    }

    .badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }
    .badge-navy   { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .badge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gray   { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }
    .badge-new    { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-followup { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    .badge-emergency { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }

    /* Vitals strip */
    .vitals-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        padding: 7px 10px;
        background: {{ $accentLight }};
        border-bottom: 1px solid {{ $accentMid }};
    }
    .vital-pill {
        background: #FFFFFF;
        border: 1px solid {{ $accentMid }};
        border-radius: 20px;
        padding: 3px 9px;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 52px;
    }
    .vital-pill .vp-label {
        font-size: 7.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: {{ $accentColor }};
        letter-spacing: 0.3px;
        line-height: 1;
    }
    .vital-pill .vp-value {
        font-size: 10.5px;
        font-weight: 700;
        color: #1F2937;
        line-height: 1.2;
    }
    .vital-pill .vp-unit {
        font-size: 7.5px;
        color: #6B7280;
        line-height: 1;
    }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px; }

    /* Diagnosis table */
    .diag-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .diag-table th {
        background: #F9FAFB;
        padding: 3px 8px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }
    .diag-table td {
        padding: 4px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: middle;
    }
    .diag-table tr:last-child td { border-bottom: none; }

    /* Investigations table */
    .inv-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .inv-table th {
        background: #F9FAFB;
        padding: 3px 8px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }
    .inv-table td {
        padding: 4px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: middle;
    }
    .inv-table tr:last-child td { border-bottom: none; }

    /* Instructions list */
    .instructions-list { margin: 0; padding-left: 16px; }
    .instructions-list li {
        font-size: 10px;
        margin-bottom: 3px;
        color: #1F2937;
    }

    /* Next Review box */
    .next-review-box {
        background: {{ $accentColor }};
        color: #FFFFFF;
        border-radius: 4px;
        padding: 8px 12px;
        margin-bottom: 7px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .next-review-box .nr-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        opacity: 0.75;
        white-space: nowrap;
    }
    .next-review-box .nr-value {
        font-size: 11.5px;
        font-weight: 700;
    }

    /* Chief complaint banner */
    .complaint-banner {
        background: #FFFBEB;
        border-left: 3px solid {{ $accentColor }};
        padding: 6px 10px;
        margin-bottom: 7px;
        border-radius: 0 4px 4px 0;
    }
    .complaint-banner .cb-label {
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: #6B7280;
        margin-bottom: 2px;
    }
    .complaint-banner .cb-text {
        font-size: 12px;
        font-weight: 700;
        color: #1F2937;
    }

    .inline-badges { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }
</style>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- OPD HEADER STRIP                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="opd-header-strip">
    <div>
        <h1>Outpatient Consultation Summary</h1>
        <div class="opd-sub">{{ $facility_name }} &nbsp;|&nbsp; {{ $payload['clinic'] }}</div>
    </div>
    <div class="opd-date">
        {{ $payload['visit_date'] }}
        <span>Visit Date</span>
    </div>
</div>

{{-- Visit type badge bar --}}
<div style="background:{{ $accentLight }}; border:1px solid {{ $accentMid }}; border-top:none; border-radius:0 0 4px 4px; padding:5px 12px; display:flex; gap:8px; align-items:center; margin-bottom:8px;">
    @php
        $vtClass = match($payload['visit_type']) {
            'New Patient'       => 'badge-new',
            'Follow-up'         => 'badge-followup',
            'Emergency Walk-In' => 'badge-emergency',
            default             => 'badge-gray',
        };
    @endphp
    <span class="badge {{ $vtClass }}">{{ $payload['visit_type'] }}</span>
    <span style="font-size:9px; color:#6B7280;">{{ $payload['clinic'] }}</span>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- CHIEF COMPLAINT                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="complaint-banner">
    <div class="cb-label">Chief Complaint</div>
    <div class="cb-text">{{ $payload['chief_complaint'] }}</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- VITALS STRIP                                                       --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php $vitals = $payload['vitals']; @endphp
<div class="section-card" style="margin-bottom:7px;">
    <div class="section-card-title">Vital Signs</div>
    <div class="vitals-strip">
        @if(!empty($vitals['bp']))
        <div class="vital-pill">
            <div class="vp-label">BP</div>
            <div class="vp-value">{{ $vitals['bp'] }}</div>
            <div class="vp-unit">mmHg</div>
        </div>
        @endif
        @if(!empty($vitals['pulse']))
        <div class="vital-pill">
            <div class="vp-label">Pulse</div>
            <div class="vp-value">{{ $vitals['pulse'] }}</div>
            <div class="vp-unit">bpm</div>
        </div>
        @endif
        @if(!empty($vitals['temp']))
        <div class="vital-pill">
            <div class="vp-label">Temp</div>
            <div class="vp-value">{{ $vitals['temp'] }}</div>
            <div class="vp-unit">°C</div>
        </div>
        @endif
        @if(!empty($vitals['weight_kg']))
        <div class="vital-pill">
            <div class="vp-label">Weight</div>
            <div class="vp-value">{{ $vitals['weight_kg'] }}</div>
            <div class="vp-unit">kg</div>
        </div>
        @endif
        @if(!empty($vitals['height_cm']))
        <div class="vital-pill">
            <div class="vp-label">Height</div>
            <div class="vp-value">{{ $vitals['height_cm'] }}</div>
            <div class="vp-unit">cm</div>
        </div>
        @endif
        @if(!empty($vitals['spo2']))
        <div class="vital-pill">
            <div class="vp-label">SpO2</div>
            <div class="vp-value">{{ $vitals['spo2'] }}</div>
            <div class="vp-unit">%</div>
        </div>
        @endif
        @if(!empty($vitals['bmi']))
        <div class="vital-pill">
            <div class="vp-label">BMI</div>
            <div class="vp-value">{{ $vitals['bmi'] }}</div>
            <div class="vp-unit">kg/m²</div>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- HISTORY + EXAMINATION (two columns)                               --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">History Summary</div>
        <div class="section-card-body">{{ $payload['history_summary'] }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Examination Findings</div>
        <div class="section-card-body">{{ $payload['examination_findings'] }}</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- WORKING DIAGNOSIS                                                  --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="section-card" style="margin-top:6px;">
    <div class="section-card-title">Working Diagnosis</div>
    <div class="section-card-body" style="padding:0;">
        <table class="diag-table">
            <thead>
                <tr>
                    <th style="width:16%;">Type</th>
                    <th>Diagnosis</th>
                    <th style="width:18%;">ICD-10 Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['working_diagnosis'] as $diag)
                <tr>
                    <td>
                        <span class="badge {{ $diag['type'] === 'Primary' ? 'badge-navy' : 'badge-gray' }}">
                            {{ $diag['type'] }}
                        </span>
                    </td>
                    <td style="font-weight:{{ $diag['type'] === 'Primary' ? '600' : '400' }};">
                        {{ $diag['diagnosis'] }}
                    </td>
                    <td style="font-family:monospace; font-size:9.5px; color:#374151;">
                        {{ $diag['icd10'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- INVESTIGATIONS ORDERED                                             --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if(!empty($payload['investigations_ordered']))
<div class="section-card">
    <div class="section-card-title">Investigations Ordered</div>
    <div class="section-card-body" style="padding:0;">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Test</th>
                    <th style="width:22%;">Urgency</th>
                    <th style="width:35%;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['investigations_ordered'] as $inv)
                @php
                    $urgClass = match(strtolower($inv['urgency'] ?? '')) {
                        'urgent', 'stat', 'emergency' => 'badge-red',
                        'routine'                      => 'badge-gray',
                        default                        => 'badge-amber',
                    };
                @endphp
                <tr>
                    <td style="font-weight:500;">{{ $inv['test'] }}</td>
                    <td><span class="badge {{ $urgClass }}">{{ $inv['urgency'] }}</span></td>
                    <td style="color:#6B7280; font-size:9.5px;">{{ $inv['notes'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MANAGEMENT NOTES                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="section-card">
    <div class="section-card-title">Management Notes</div>
    <div class="section-card-body">{{ $payload['management_notes'] }}</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- INSTRUCTIONS TO PATIENT                                            --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if(!empty($payload['instructions_to_patient']))
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Instructions to Patient
    </div>
    <div class="section-card-body">
        <ol class="instructions-list">
            @foreach($payload['instructions_to_patient'] as $instr)
            <li>{{ $instr }}</li>
            @endforeach
        </ol>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- NEXT REVIEW BOX                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="next-review-box">
    <div class="nr-label">Next Review</div>
    <div class="nr-value">{{ $payload['next_review'] }}</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- PRESCRIPTION + REFERRAL BADGES                                     --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="inline-badges" style="margin-bottom:4px;">
    @if($payload['prescription_issued'])
    <span class="badge badge-green" style="font-size:10px; padding:4px 10px;">
        Prescription Issued
        @if(!empty($payload['prescription_number']))
        &nbsp;—&nbsp; Rx# {{ $payload['prescription_number'] }}
        @endif
    </span>
    @else
    <span class="badge badge-gray" style="font-size:10px; padding:4px 10px;">No Prescription Issued</span>
    @endif

    @if($payload['referral_issued'])
    <span class="badge badge-amber" style="font-size:10px; padding:4px 10px;">
        Referral Issued
        @if(!empty($payload['referral_to']))
        &nbsp;→&nbsp; {{ $payload['referral_to'] }}
        @endif
    </span>
    @endif
</div>
@endsection
