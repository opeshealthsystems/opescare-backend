@extends('documents.base')

@section('title', 'Fall Risk Assessment')

@section('subtitle', 'FRA — Morse Fall Scale')

@section('content')
@php
    $morse = $payload['morse_scale'] ?? [];
    $total = $morse['total_score'] ?? 0;
    $riskLevel = $payload['risk_level'] ?? '';
    $prevScore = $payload['previous_score'] ?? null;
    $trend = $payload['score_trend'] ?? null;
    $interventions = $payload['interventions_implemented'] ?? [];
    $existing = $payload['existing_falls_precautions'] ?? [];
    $assessmentReason = $payload['assessment_reason'] ?? '';

    $riskStyle = 'background:#D1FAE5;color:#065F46';
    $riskBorderColor = '#6EE7B7';
    $isHighRisk = false;
    if (str_contains($riskLevel, 'High')) {
        $riskStyle = 'background:#FEE2E2;color:#991B1B';
        $riskBorderColor = '#FECACA';
        $isHighRisk = true;
    } elseif (str_contains($riskLevel, 'Medium')) {
        $riskStyle = 'background:#FEF3C7;color:#92400E';
        $riskBorderColor = '#FDE68A';
    }

    $reasonColors = [
        'Admission'        => 'background:#EFF6FF;color:#1E40AF',
        'Post-fall'        => 'background:#FEE2E2;color:#991B1B',
        'Clinical change'  => 'background:#FEF3C7;color:#92400E',
        'Routine review'   => 'background:#F1F5F9;color:#334155',
    ];
    $reasonStyle = $reasonColors[$assessmentReason] ?? 'background:#F1F5F9;color:#334155';

    $trendIcon = '';
    if ($trend === 'Improving')  $trendIcon = '↓ Improving';
    elseif ($trend === 'Stable') $trendIcon = '→ Stable';
    elseif ($trend === 'Worsening') $trendIcon = '↑ Worsening';

    $trendStyle = 'color:#64748B';
    if ($trend === 'Improving')  $trendStyle = 'color:#065F46;font-weight:700';
    elseif ($trend === 'Worsening') $trendStyle = 'color:#991B1B;font-weight:700';

    $morseRows = [
        ['key' => 'history_of_falls',    'label' => 'History of Falls',      'options' => ['No → 0', 'Yes → 25']],
        ['key' => 'secondary_diagnosis', 'label' => 'Secondary Diagnosis',   'options' => ['No → 0', 'Yes → 15']],
        ['key' => 'ambulatory_aid',      'label' => 'Ambulatory Aid',        'options' => ['None/Bedrest/WC → 0', 'Crutches/Cane/Walker → 15', 'Furniture → 30']],
        ['key' => 'iv_or_heplock',       'label' => 'IV / Heparin Lock',     'options' => ['No → 0', 'Yes → 20']],
        ['key' => 'gait',                'label' => 'Gait',                  'options' => ['Normal/Bedrest/Immobile → 0', 'Weak → 10', 'Impaired → 20']],
        ['key' => 'mental_status',       'label' => 'Mental Status',         'options' => ['Oriented to ability → 0', 'Forgets limitations → 15']],
    ];

    $ivChecklist = [
        'bed_lowest_position'    => 'Bed in lowest position',
        'call_bell_within_reach' => 'Call bell within reach',
        'non_slip_footwear'      => 'Non-slip footwear provided',
        'bed_rails_raised'       => 'Bed rails raised',
        'falls_risk_sign_placed' => 'Falls risk sign placed',
        'regular_toileting'      => 'Regular toileting schedule',
        'medication_review'      => 'Medication review completed',
        'physiotherapy_referral' => 'Physiotherapy referral',
        'patient_education'      => 'Patient / family education',
        'environment_cleared'    => 'Environment cleared of hazards',
        'night_light_provided'   => 'Night light provided',
    ];
@endphp
<style>
    .fra-header-strip {
        background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .fra-header-strip .strip-title { font-size: 13px; font-weight: 700; margin-bottom: 1mm; }
    .fra-header-strip .strip-sub   { font-size: 9.5px; opacity: 0.88; }
    .fra-badge {
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
    .morse-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 4mm; }
    .morse-table th {
        background: #FFFBEB;
        color: #B45309;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2mm 3mm;
        border: 1px solid #FDE68A;
        text-align: left;
    }
    .morse-table td {
        padding: 2.5mm 3mm;
        border: 1px solid #E2E8F0;
        vertical-align: middle;
    }
    .morse-table tr:nth-child(even) td { background: #FFFBEB; }
    .score-cell { font-weight: 700; text-align: center; font-size: 12px; }
    .total-row td { background: #FEF3C7 !important; font-weight: 700; }
    .risk-badge-large {
        display: inline-block;
        padding: 2mm 6mm;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        border: 1.5px solid;
    }
    .risk-summary-row {
        display: flex;
        align-items: center;
        gap: 5mm;
        margin-bottom: 4mm;
        flex-wrap: wrap;
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
    .high-risk-alert {
        background: #FEF2F2;
        border: 2px solid #DC2626;
        border-left: 5px solid #DC2626;
        border-radius: 6px;
        padding: 3mm 5mm;
        margin-bottom: 4mm;
        font-size: 10.5px;
        font-weight: 700;
        color: #991B1B;
    }
    .info-row {
        display: flex;
        gap: 6mm;
        align-items: center;
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 6px;
        padding: 2.5mm 4mm;
        margin-bottom: 4mm;
        font-size: 10px;
        flex-wrap: wrap;
    }
    .info-row .il { color: #B45309; font-weight: 700; font-size: 9px; text-transform: uppercase; }
    .info-row .iv { color: #0F172A; font-weight: 600; }
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
</style>

{{-- AMBER HEADER --}}
<div class="fra-header-strip">
    <div>
        <div class="strip-title">FALL RISK ASSESSMENT (MORSE FALL SCALE)</div>
        <div class="strip-sub">
            Date: {{ $payload['assessment_date'] ?? 'N/A' }} &nbsp;|&nbsp;
            Time: {{ $payload['assessment_time'] ?? 'N/A' }}
        </div>
    </div>
    <div style="text-align:right;">
        <span class="fra-badge" style="{{ $reasonStyle }}">{{ $assessmentReason ?: 'N/A' }}</span>
    </div>
</div>

{{-- MORSE SCALE TABLE --}}
<div class="section-label">Morse Fall Scale Scoring</div>
<table class="morse-table">
    <thead>
        <tr>
            <th style="width:30%;">Criterion</th>
            <th>Patient Response</th>
            <th style="width:15%;text-align:center;">Score</th>
        </tr>
    </thead>
    <tbody>
        @foreach($morseRows as $row)
        @php
            $item = $morse[$row['key']] ?? [];
            $val = $item['value'] ?? null;
            $score = $item['score'] ?? 0;

            if (is_bool($val)) {
                $displayVal = $val ? 'Yes' : 'No';
            } else {
                $displayVal = $val ?? 'N/A';
            }
        @endphp
        <tr>
            <td style="font-weight:600;">{{ $row['label'] }}</td>
            <td>{{ $displayVal }}</td>
            <td class="score-cell" style="{{ $score > 0 ? 'color:#DC2626' : 'color:#065F46' }}">{{ $score }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" style="text-align:right;font-size:11px;text-transform:uppercase;letter-spacing:0.4px;">TOTAL SCORE</td>
            <td class="score-cell" style="font-size:15px;">{{ $total }}</td>
        </tr>
    </tbody>
</table>

{{-- RISK LEVEL --}}
<div class="risk-summary-row">
    <span class="risk-badge-large" style="{{ $riskStyle }};border-color:{{ $riskBorderColor }};">
        {{ $riskLevel ?: 'N/A' }}
    </span>
    @if($prevScore !== null)
    <span style="font-size:10px;color:#64748B;">
        Previous Score: <strong>{{ $prevScore }}</strong>
        &nbsp;
        @if($trendIcon)
        <span style="{{ $trendStyle }}">{{ $trendIcon }}</span>
        @endif
    </span>
    @endif
</div>

{{-- EXISTING PRECAUTIONS --}}
@if(count($existing) > 0)
<div class="section-label">Existing Precautions in Place</div>
<div style="display:flex;flex-wrap:wrap;gap:2mm;margin-bottom:4mm;">
    @foreach($existing as $ep)
    <span class="fra-badge" style="background:#EFF6FF;color:#1E40AF;">{{ $ep }}</span>
    @endforeach
</div>
@endif

{{-- INTERVENTIONS CHECKLIST --}}
<div class="section-label">Interventions Implemented</div>
<div class="iv-grid">
    @foreach($ivChecklist as $key => $label)
    @php $checked = $interventions[$key] ?? false; @endphp
    <div class="iv-item">
        <span class="{{ $checked ? 'chk-yes' : 'chk-no' }}">{{ $checked ? '✓' : '✗' }}</span>
        <span style="{{ $checked ? 'color:#0F172A' : 'color:#94A3B8' }}">{{ $label }}</span>
    </div>
    @endforeach
</div>
@if(!empty($interventions['other']))
<div style="font-size:10px;color:#64748B;margin-bottom:4mm;"><strong>Other:</strong> {{ $interventions['other'] }}</div>
@endif

{{-- HIGH RISK ALERT --}}
@if($isHighRisk)
<div class="high-risk-alert">
    HIGH FALL RISK — Enhanced precautions required. Document in nursing care plan and communicate to all care team members.
</div>
@endif

{{-- NEXT ASSESSMENT --}}
<div class="info-row">
    <span><span class="il">Assessed By:</span> <span class="iv">{{ $payload['assessed_by'] ?? 'N/A' }}</span></span>
    <span><span class="il">Designation:</span> <span class="iv">{{ $payload['designation'] ?? 'N/A' }}</span></span>
    <span><span class="il">Next Assessment Due:</span> <span class="iv">{{ $payload['next_assessment_due'] ?? 'N/A' }}</span></span>
</div>

{{-- SIGNATURE --}}
<div class="sig-box">
    <div style="font-size:9px;text-transform:uppercase;color:#64748B;">Assessing Nurse</div>
    <div class="sig-name">{{ $payload['assessed_by'] ?? 'N/A' }}</div>
    <div style="font-size:9px;color:#94A3B8;">{{ $payload['designation'] ?? '' }}</div>
    <div class="signature-line"></div>
    <div style="font-size:8.5px;color:#94A3B8;">Signature</div>
</div>
@endsection
