@extends('documents.base')

@section('title', 'Pressure Ulcer Risk Assessment')

@section('subtitle', 'PUA — Braden Scale')

@section('content')
@php
    $braden = $payload['braden_scale'] ?? [];
    $total  = $braden['total_score'] ?? 0;
    $riskLevel = $payload['risk_level'] ?? '';
    $existingInjuries = $payload['existing_pressure_injuries'] ?? [];
    $interventions = $payload['interventions'] ?? [];
    $prevScore = $payload['previous_score'] ?? null;
    $occasion = $payload['assessment_occasion'] ?? '';

    // Risk level colour
    $riskStyle = 'background:#D1FAE5;color:#065F46;border-color:#6EE7B7';
    if (str_contains($riskLevel, 'Very High'))     $riskStyle = 'background:#7F1D1D;color:#FEE2E2;border-color:#991B1B';
    elseif (str_contains($riskLevel, 'High'))      $riskStyle = 'background:#FEE2E2;color:#7F1D1D;border-color:#FECACA';
    elseif (str_contains($riskLevel, 'Moderate'))  $riskStyle = 'background:#FED7AA;color:#7C2D12;border-color:#FB923C';
    elseif (str_contains($riskLevel, 'Mild'))      $riskStyle = 'background:#FEF3C7;color:#92400E;border-color:#FDE68A';

    $stageColors = [
        'Stage 1'      => 'background:#FEF3C7;color:#92400E',
        'Stage 2'      => 'background:#FED7AA;color:#7C2D12',
        'Stage 3'      => 'background:#FEE2E2;color:#991B1B',
        'Stage 4'      => 'background:#7F1D1D;color:#FEE2E2',
        'Unstageable'  => 'background:#E5E7EB;color:#374151',
        'Deep Tissue'  => 'background:#EDE9FE;color:#5B21B6',
    ];

    $bradenCriteria = [
        'sensory_perception' => 'Sensory Perception',
        'moisture'           => 'Moisture',
        'activity'           => 'Activity',
        'mobility'           => 'Mobility',
        'nutrition'          => 'Nutrition',
        'friction_shear'     => 'Friction / Shear',
    ];

    $bradenMax = [
        'sensory_perception' => 4,
        'moisture'           => 4,
        'activity'           => 4,
        'mobility'           => 4,
        'nutrition'          => 4,
        'friction_shear'     => 3,
    ];

    $ivChecklist = [
        'repositioning_2hourly'      => 'Repositioning every 2 hours',
        'pressure_relieving_mattress'=> 'Pressure-relieving mattress',
        'heel_protectors'            => 'Heel protectors applied',
        'skin_moisturiser'           => 'Skin moisturiser applied',
        'nutritional_support'        => 'Nutritional support initiated',
        'moisture_barrier_cream'     => 'Moisture barrier cream applied',
        'keep_skin_dry'              => 'Skin kept dry and clean',
        'pad_bony_prominences'       => 'Bony prominences padded',
        'avoid_massage_bony_areas'   => 'Avoid massage over bony areas',
        'wound_care_commenced'       => 'Wound care commenced',
        'tissue_viability_referral'  => 'Tissue viability nurse referral',
    ];

    $tissueReferral = $interventions['tissue_viability_referral'] ?? false;

    $occasionColors = [
        'Admission'       => 'background:#EFF6FF;color:#1E40AF',
        '72-hour review'  => 'background:#F3E8FF;color:#6B21A8',
        'Clinical change' => 'background:#FEF3C7;color:#92400E',
        'Weekly review'   => 'background:#F1F5F9;color:#334155',
    ];
    $occasionStyle = $occasionColors[$occasion] ?? 'background:#F1F5F9;color:#334155';
@endphp
<style>
    .pua-header-strip {
        background: linear-gradient(135deg, #B45309 0%, #D97706 100%);
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .pua-header-strip .strip-title { font-size: 13px; font-weight: 700; margin-bottom: 1mm; }
    .pua-header-strip .strip-sub   { font-size: 9.5px; opacity: 0.88; }
    .pua-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .section-label {
        font-size: 10px;
        font-weight: 700;
        color: #B45309;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1.5px solid #FDE68A;
        padding-bottom: 1mm;
        margin: 4mm 0 3mm;
    }
    .braden-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 4mm; }
    .braden-table th {
        background: #FFFBEB;
        color: #92400E;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2mm 3mm;
        border: 1px solid #FDE68A;
        text-align: left;
    }
    .braden-table td {
        padding: 2.5mm 3mm;
        border: 1px solid #E2E8F0;
        vertical-align: middle;
    }
    .braden-table tr:nth-child(even) td { background: #FFFBEB; }
    .score-dot {
        display: inline-block;
        width: 7mm; height: 7mm;
        border-radius: 50%;
        text-align: center;
        line-height: 7mm;
        font-size: 10px;
        font-weight: 700;
    }
    .score-low  { background: #FEE2E2; color: #991B1B; }
    .score-mid  { background: #FEF3C7; color: #92400E; }
    .score-high { background: #D1FAE5; color: #065F46; }
    .risk-badge-large {
        display: inline-block;
        padding: 2mm 6mm;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        border: 1.5px solid;
    }
    .risk-row {
        display: flex;
        align-items: center;
        gap: 5mm;
        margin-bottom: 4mm;
        flex-wrap: wrap;
    }
    .injury-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 3mm;
        background: #FFFBEB;
    }
    .injury-card .stage-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 1.5mm;
    }
    .iv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2mm 4mm; margin-bottom: 4mm; }
    .iv-item {
        display: flex;
        align-items: center;
        gap: 2mm;
        font-size: 10px;
        padding: 1.5mm 0;
    }
    .chk-yes {
        width: 4mm; height: 4mm;
        background: #D1FAE5;
        border: 1px solid #6EE7B7;
        border-radius: 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: #065F46;
        font-weight: 700;
        flex-shrink: 0;
    }
    .chk-no {
        width: 4mm; height: 4mm;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 2px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 8px;
        color: #DC2626;
        font-weight: 700;
        flex-shrink: 0;
    }
    .tissue-referral-badge {
        background: #EDE9FE;
        border: 1px solid #C4B5FD;
        border-radius: 6px;
        padding: 2.5mm 4mm;
        color: #5B21B6;
        font-size: 10px;
        font-weight: 700;
        margin-bottom: 4mm;
        display: inline-block;
    }
    .skin-box {
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 4mm;
        font-size: 10px;
        color: #0F172A;
        line-height: 1.6;
    }
    .skin-box .skin-label {
        font-size: 9px;
        font-weight: 700;
        color: #B45309;
        text-transform: uppercase;
        margin-bottom: 1.5mm;
    }
    .sig-box {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 3mm 4mm;
        text-align: center;
        font-size: 9.5px;
        color: #64748B;
        margin-top: 4mm;
    }
    .sig-box .sig-name { font-weight: 700; color: #0F172A; font-size: 10.5px; margin-top: 1mm; }
    .signature-line { border-top: 1px solid #94A3B8; margin: 5mm 2mm 1.5mm; }
    .info-strip {
        display: flex;
        gap: 6mm;
        align-items: center;
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 6px;
        padding: 2mm 4mm;
        margin-bottom: 4mm;
        font-size: 10px;
        flex-wrap: wrap;
    }
    .info-strip .il { color: #B45309; font-weight: 700; font-size: 9px; text-transform: uppercase; }
</style>

{{-- HEADER --}}
<div class="pua-header-strip">
    <div>
        <div class="strip-title">PRESSURE ULCER RISK ASSESSMENT (BRADEN SCALE)</div>
        <div class="strip-sub">
            Date: {{ $payload['assessment_date'] ?? 'N/A' }} &nbsp;|&nbsp;
            Time: {{ $payload['assessment_time'] ?? 'N/A' }}
        </div>
    </div>
    <div style="text-align:right;">
        <span class="pua-badge" style="{{ $occasionStyle }}">{{ $occasion ?: 'N/A' }}</span>
    </div>
</div>

{{-- BRADEN SCALE TABLE --}}
<div class="section-label">Braden Scale Scoring</div>
<table class="braden-table">
    <thead>
        <tr>
            <th style="width:28%;">Criterion</th>
            <th>Patient Status</th>
            <th style="width:18%;text-align:center;">Score (max {{ '' }})</th>
        </tr>
    </thead>
    <tbody>
        @foreach($bradenCriteria as $key => $label)
        @php
            $item = $braden[$key] ?? [];
            $score = $item['score'] ?? 0;
            $desc  = $item['description'] ?? 'N/A';
            $max   = $bradenMax[$key];
            $scoreClass = 'score-mid';
            if ($score === 1)                                 $scoreClass = 'score-low';
            elseif ($score >= $max || ($max === 3 && $score >= 2)) $scoreClass = 'score-high';
        @endphp
        <tr>
            <td style="font-weight:600;">{{ $label }} <span style="color:#94A3B8;font-weight:400;font-size:9px;">(max {{ $max }})</span></td>
            <td style="color:#374151;">{{ $desc }}</td>
            <td style="text-align:center;">
                <span class="score-dot {{ $scoreClass }}">{{ $score }}</span>
            </td>
        </tr>
        @endforeach
        <tr style="background:#FEF3C7 !important;">
            <td colspan="2" style="text-align:right;font-weight:700;font-size:11px;text-transform:uppercase;">TOTAL SCORE</td>
            <td style="text-align:center;font-size:15px;font-weight:700;">{{ $total }}</td>
        </tr>
    </tbody>
</table>

{{-- RISK LEVEL --}}
<div class="risk-row">
    <span class="risk-badge-large" style="{{ $riskStyle }}">{{ $riskLevel ?: 'N/A' }}</span>
    @if($prevScore !== null)
    <span style="font-size:10px;color:#64748B;">Previous Score: <strong>{{ $prevScore }}</strong></span>
    @endif
</div>

{{-- EXISTING PRESSURE INJURIES --}}
<div class="section-label">Existing Pressure Injuries</div>
@if(count($existingInjuries) > 0)
    @foreach($existingInjuries as $inj)
    <div class="injury-card">
        <div>
            <span class="stage-badge" style="{{ $stageColors[$inj['stage'] ?? ''] ?? 'background:#F1F5F9;color:#334155' }}">{{ $inj['stage'] ?? 'N/A' }}</span>
            &nbsp;
            <strong style="font-size:10.5px;">{{ $inj['location'] ?? 'N/A' }}</strong>
            @if(!empty($inj['size_cm']))
            &nbsp;<span style="font-size:9.5px;color:#64748B;">Size: {{ $inj['size_cm'] }} cm</span>
            @endif
        </div>
        @if(!empty($inj['description']))
        <p style="margin:1.5mm 0 0;font-size:10px;color:#374151;">{{ $inj['description'] }}</p>
        @endif
    </div>
    @endforeach
@else
<p style="font-size:10px;color:#64748B;font-style:italic;margin-bottom:4mm;">No existing pressure injuries documented.</p>
@endif

{{-- SKIN INSPECTION --}}
<div class="section-label">Skin Inspection Findings</div>
<div class="skin-box">
    <div class="skin-label">Inspection Notes</div>
    {{ $payload['skin_inspection_findings'] ?? 'N/A' }}
</div>

{{-- INTERVENTIONS --}}
<div class="section-label">Preventive Interventions</div>
<div class="iv-grid">
    @foreach($ivChecklist as $key => $label)
    @php $checked = $interventions[$key] ?? false; @endphp
    <div class="iv-item">
        <span class="{{ $checked ? 'chk-yes' : 'chk-no' }}">{{ $checked ? '✓' : '✗' }}</span>
        <span style="{{ $checked ? 'color:#0F172A' : 'color:#94A3B8' }}">{{ $label }}</span>
    </div>
    @endforeach
</div>

@if($tissueReferral)
<div class="tissue-referral-badge">Tissue Viability Nurse Referral — ACTIVATED</div>
@endif

{{-- ASSESSOR --}}
<div class="info-strip">
    <span><span class="il">Assessed By:</span> <strong style="margin-left:1mm;">{{ $payload['assessed_by'] ?? 'N/A' }}</strong></span>
    <span><span class="il">Date:</span> <strong style="margin-left:1mm;">{{ $payload['assessment_date'] ?? 'N/A' }}</strong></span>
</div>

{{-- SIGNATURE --}}
<div class="sig-box">
    <div style="font-size:9px;text-transform:uppercase;color:#64748B;">Assessing Nurse</div>
    <div class="sig-name">{{ $payload['assessed_by'] ?? 'N/A' }}</div>
    <div class="signature-line"></div>
    <div style="font-size:8.5px;color:#94A3B8;">Signature</div>
</div>
@endsection
