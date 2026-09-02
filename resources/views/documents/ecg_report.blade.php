@extends('documents.base')

@section('title', 'ECG Report')

@section('subtitle', 'ECG — 12-Lead Electrocardiography')

@section('content')
@php
    $rate      = isset($payload['rate_bpm'])          ? (int)$payload['rate_bpm']          : null;
    $pr        = isset($payload['pr_interval_ms'])     ? (int)$payload['pr_interval_ms']     : null;
    $qrs       = isset($payload['qrs_duration_ms'])    ? (int)$payload['qrs_duration_ms']    : null;
    $qt        = isset($payload['qt_interval_ms'])     ? (int)$payload['qt_interval_ms']     : null;
    $qtc       = isset($payload['qtc_interval_ms'])    ? (int)$payload['qtc_interval_ms']    : null;
    $axis      = isset($payload['qrs_axis_degrees'])   ? (int)$payload['qrs_axis_degrees']   : null;
    $stChanges = $payload['st_changes'] ?? [];
    $critical  = $payload['critical_finding'] ?? false;
    $bbb       = $payload['bundle_branch_block'] ?? 'None';
    $lvh       = $payload['lvh_criteria'] ?? false;
    $delta     = $payload['delta_wave'] ?? false;
    $pathQ     = $payload['pathological_q_waves'] ?? null;

    // Flagging helpers
    $rateFlag = '';
    if ($rate !== null) {
        if ($rate < 60)      $rateFlag = 'LOW';
        elseif ($rate > 100) $rateFlag = 'HIGH';
        else                 $rateFlag = 'NORMAL';
    }
    $prFlag = '';
    if ($pr !== null) {
        if ($pr < 120)       $prFlag = 'SHORT';
        elseif ($pr > 200)   $prFlag = 'PROLONGED';
        else                 $prFlag = 'NORMAL';
    }
    $qrsFlag = '';
    if ($qrs !== null) {
        if ($qrs > 120)      $qrsFlag = 'WIDE';
        else                 $qrsFlag = 'NORMAL';
    }
    $qtcFlag = '';
    if ($qtc !== null) {
        if ($qtc > 500)      $qtcFlag = 'CRITICAL';
        elseif ($qtc > 450)  $qtcFlag = 'PROLONGED';
        else                 $qtcFlag = 'NORMAL';
    }
    $axisFlag = '';
    if ($axis !== null) {
        if ($axis < -30)      $axisFlag = 'LAD';
        elseif ($axis > 90)   $axisFlag = 'RAD';
        else                  $axisFlag = 'NORMAL';
    }

    $flagColors = [
        'NORMAL'    => 'background:#D1FAE5;color:#065F46',
        'LOW'       => 'background:#FEF3C7;color:#92400E',
        'HIGH'      => 'background:#FEE2E2;color:#991B1B',
        'SHORT'     => 'background:#FEF3C7;color:#92400E',
        'PROLONGED' => 'background:#FEE2E2;color:#991B1B',
        'CRITICAL'  => 'background:#7F1D1D;color:#FEE2E2',
        'WIDE'      => 'background:#FEE2E2;color:#991B1B',
        'LAD'       => 'background:#FEF3C7;color:#92400E',
        'RAD'       => 'background:#FEF3C7;color:#92400E',
        ''          => 'background:#F1F5F9;color:#334155',
    ];

    $qualityColors = [
        'Good'                          => 'background:#D1FAE5;color:#065F46',
        'Adequate — some artifact'      => 'background:#FEF3C7;color:#92400E',
        'Poor — repeat recommended'     => 'background:#FEE2E2;color:#991B1B',
    ];
    $quality = $payload['technical_quality'] ?? '';
    $qualityStyle = $qualityColors[$quality] ?? 'background:#F1F5F9;color:#334155';

    $stChangeColors = [
        'Elevation'  => 'background:#FEE2E2;color:#991B1B',
        'Depression' => 'background:#FEF3C7;color:#92400E',
        'No change'  => 'background:#F1F5F9;color:#334155',
    ];
@endphp
<style>
    .ecg-header-strip {
        background: linear-gradient(135deg, #DC2626 0%, #EF4444 100%);
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .ecg-header-strip .strip-title { font-size: 13px; font-weight: 700; margin-bottom: 1mm; }
    .ecg-header-strip .strip-sub   { font-size: 9.5px; opacity: 0.88; }
    .ecg-badge {
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
        color: #DC2626;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1.5px solid #FECACA;
        padding-bottom: 1mm;
        margin: 4mm 0 3mm;
    }
    .tech-strip {
        display: flex;
        gap: 6mm;
        align-items: center;
        background: #FFF1F2;
        border: 1px solid #FECACA;
        border-radius: 6px;
        padding: 2.5mm 4mm;
        margin-bottom: 4mm;
        flex-wrap: wrap;
        font-size: 10px;
    }
    .tech-strip .tl { color: #991B1B; font-weight: 700; font-size: 9px; text-transform: uppercase; }
    .tech-strip .tv { color: #0F172A; font-weight: 600; }
    .meas-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 4mm; }
    .meas-table th {
        background: #FFF1F2;
        color: #991B1B;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 2mm 3mm;
        border: 1px solid #FECACA;
        text-align: left;
    }
    .meas-table td {
        padding: 2mm 3mm;
        border: 1px solid #E2E8F0;
        vertical-align: middle;
    }
    .meas-table tr:nth-child(even) td { background: #FFF8F8; }
    .flag-badge {
        display: inline-block;
        padding: 0.8mm 2.5mm;
        border-radius: 9999px;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .info-card {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4mm;
    }
    .info-card .ic-head {
        background: #FFF1F2;
        color: #991B1B;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 2mm 3.5mm;
        border-bottom: 1px solid #FECACA;
    }
    .info-card .ic-body { padding: 3mm 3.5mm; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 4mm; }
    .special-findings { display: flex; flex-wrap: wrap; gap: 2mm; margin-bottom: 4mm; }
    .critical-banner {
        background: #7F1D1D;
        color: #FEE2E2;
        border-radius: 6px;
        padding: 3mm 5mm;
        margin-bottom: 4mm;
        font-size: 11px;
        font-weight: 700;
    }
    .impression-box {
        border: 2px solid #DC2626;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 4mm;
        background: #FFF8F8;
    }
    .impression-box .imp-label {
        font-size: 10px;
        font-weight: 700;
        color: #DC2626;
        text-transform: uppercase;
        margin-bottom: 2mm;
    }
    .impression-box .imp-text {
        font-size: 11px;
        color: #0F172A;
        line-height: 1.6;
    }
    .data-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 3mm; }
    .data-table th {
        background: #FFF1F2;
        color: #991B1B;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2mm 2.5mm;
        border: 1px solid #FECACA;
        text-align: left;
    }
    .data-table td {
        padding: 1.8mm 2.5mm;
        border: 1px solid #E2E8F0;
    }
    .data-table tr:nth-child(even) td { background: #FFF8F8; }
    .sig-area {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 3mm 4mm;
        text-align: center;
        font-size: 9.5px;
        color: #64748B;
        margin-top: 4mm;
    }
    .sig-area .sig-name { font-weight: 700; color: #0F172A; font-size: 11px; margin-top: 1mm; }
    .sig-area .sig-reg  { font-size: 9px; color: #64748B; }
    .signature-line { border-top: 1px solid #94A3B8; margin: 5mm 2mm 1.5mm; }
    .optional-box {
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 2.5mm 4mm;
        margin-bottom: 3mm;
        font-size: 10px;
    }
    .optional-label { font-size: 9px; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 1mm; }
</style>

{{-- RED HEADER --}}
<div class="ecg-header-strip">
    <div>
        <div class="strip-title">12-LEAD ELECTROCARDIOGRAPHY REPORT</div>
        <div class="strip-sub">
            Date: {{ $payload['ecg_date'] ?? 'N/A' }} &nbsp;|&nbsp;
            Time: {{ $payload['ecg_time'] ?? 'N/A' }} &nbsp;|&nbsp;
            Indication: {{ $payload['indication'] ?? 'N/A' }}
        </div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:9px;opacity:0.85;">Technician</div>
        <div style="font-size:10px;font-weight:600;">{{ $payload['technician'] ?? 'N/A' }}</div>
    </div>
</div>

{{-- TECHNICAL PARAMETERS --}}
<div class="tech-strip">
    <span><span class="tl">Rate:</span> <span class="tv">{{ $payload['rate_bpm'] ?? 'N/A' }} bpm</span></span>
    <span><span class="tl">Paper Speed:</span> <span class="tv">{{ $payload['paper_speed'] ?? '25 mm/s' }}</span></span>
    <span><span class="tl">Gain:</span> <span class="tv">{{ $payload['gain'] ?? '10 mm/mV' }}</span></span>
    <span><span class="tl">Quality:</span>
        <span class="flag-badge" style="{{ $qualityStyle }}">{{ $quality ?: 'N/A' }}</span>
    </span>
</div>

{{-- MEASUREMENTS TABLE --}}
<div class="section-label">Measurements</div>
<table class="meas-table">
    <thead>
        <tr><th>Parameter</th><th>Value</th><th>Normal Range</th><th>Flag</th></tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight:600;">Heart Rate</td>
            <td>{{ $rate !== null ? $rate . ' bpm' : 'N/A' }}</td>
            <td style="color:#64748B;">60–100 bpm</td>
            <td>@if($rateFlag)<span class="flag-badge" style="{{ $flagColors[$rateFlag] ?? '' }}">{{ $rateFlag }}</span>@endif</td>
        </tr>
        <tr>
            <td style="font-weight:600;">Rhythm</td>
            <td colspan="2">{{ $payload['rhythm'] ?? 'N/A' }}</td>
            <td></td>
        </tr>
        <tr>
            <td style="font-weight:600;">PR Interval</td>
            <td>{{ $pr !== null ? $pr . ' ms' : 'N/A' }}</td>
            <td style="color:#64748B;">120–200 ms</td>
            <td>@if($prFlag)<span class="flag-badge" style="{{ $flagColors[$prFlag] ?? '' }}">{{ $prFlag }}</span>@endif</td>
        </tr>
        <tr>
            <td style="font-weight:600;">QRS Duration</td>
            <td>{{ $qrs !== null ? $qrs . ' ms' : 'N/A' }}</td>
            <td style="color:#64748B;">&lt; 120 ms</td>
            <td>@if($qrsFlag)<span class="flag-badge" style="{{ $flagColors[$qrsFlag] ?? '' }}">{{ $qrsFlag }}</span>@endif</td>
        </tr>
        <tr>
            <td style="font-weight:600;">QT Interval</td>
            <td>{{ $qt !== null ? $qt . ' ms' : 'N/A' }}</td>
            <td style="color:#64748B;">350–440 ms</td>
            <td></td>
        </tr>
        <tr>
            <td style="font-weight:600;">QTc Interval</td>
            <td>{{ $qtc !== null ? $qtc . ' ms' : 'N/A' }}</td>
            <td style="color:#64748B;">&lt; 450 ms</td>
            <td>@if($qtcFlag)<span class="flag-badge" style="{{ $flagColors[$qtcFlag] ?? '' }}">{{ $qtcFlag }}</span>@endif</td>
        </tr>
        <tr>
            <td style="font-weight:600;">QRS Axis</td>
            <td>{{ $axis !== null ? $axis . '°' : 'N/A' }}</td>
            <td style="color:#64748B;">-30° to +90°</td>
            <td>@if($axisFlag)<span class="flag-badge" style="{{ $flagColors[$axisFlag] ?? '' }}">{{ $axisFlag }}</span>@endif</td>
        </tr>
    </tbody>
</table>

{{-- WAVE ANALYSIS --}}
<div class="section-label">Wave Analysis</div>
<div class="two-col">
    <div class="info-card">
        <div class="ic-head">P Wave</div>
        <div class="ic-body" style="font-size:10px;">{{ $payload['p_wave'] ?? 'N/A' }}</div>
    </div>
    <div class="info-card">
        <div class="ic-head">QRS Morphology</div>
        <div class="ic-body" style="font-size:10px;">{{ $payload['qrs_morphology'] ?? 'N/A' }}</div>
    </div>
</div>
<div class="two-col">
    <div class="info-card">
        <div class="ic-head">T Wave</div>
        <div class="ic-body" style="font-size:10px;">{{ $payload['t_wave'] ?? 'N/A' }}</div>
    </div>
    <div class="info-card">
        <div class="ic-head">ST Changes</div>
        <div class="ic-body">
            @if(count($stChanges) > 0)
            <table class="data-table">
                <thead><tr><th>Leads</th><th>Change</th><th>mm</th><th>Description</th></tr></thead>
                <tbody>
                    @foreach($stChanges as $st)
                    <tr>
                        <td style="font-weight:600;">{{ $st['leads'] ?? '' }}</td>
                        <td>
                            @php $ch = $st['change'] ?? ''; @endphp
                            <span class="flag-badge" style="{{ $stChangeColors[$ch] ?? 'background:#F1F5F9;color:#334155' }}">{{ $ch }}</span>
                        </td>
                        <td>{{ $st['mm'] ?? '—' }}</td>
                        <td style="color:#64748B;font-style:italic;">{{ $st['description'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p style="font-size:10px;color:#64748B;margin:0;">No significant ST changes.</p>
            @endif
        </div>
    </div>
</div>

{{-- SPECIAL FINDINGS --}}
<div class="section-label">Special Findings</div>
<div class="special-findings">
    <span class="ecg-badge" style="{{ $lvh ? 'background:#FEE2E2;color:#991B1B' : 'background:#D1FAE5;color:#065F46' }}">
        LVH: {{ $lvh ? 'Present' : 'Absent' }}
    </span>
    @if($lvh && $payload['lvh_detail'])
    <span class="ecg-badge" style="background:#FFF1F2;color:#991B1B;">{{ $payload['lvh_detail'] }}</span>
    @endif
    <span class="ecg-badge" style="{{ ($bbb && $bbb !== 'None') ? 'background:#FEE2E2;color:#991B1B' : 'background:#D1FAE5;color:#065F46' }}">
        BBB: {{ ($bbb && $bbb !== 'None') ? $bbb : 'None' }}
    </span>
    <span class="ecg-badge" style="{{ $delta ? 'background:#FEF3C7;color:#92400E' : 'background:#D1FAE5;color:#065F46' }}">
        Delta Wave: {{ $delta ? 'Present' : 'Absent' }}
    </span>
    <span class="ecg-badge" style="{{ $pathQ ? 'background:#FEE2E2;color:#991B1B' : 'background:#D1FAE5;color:#065F46' }}">
        Path. Q Waves: {{ $pathQ ?: 'None' }}
    </span>
</div>

{{-- CRITICAL FINDING --}}
@if($critical)
<div class="critical-banner">
    ⚠ CRITICAL FINDING — IMMEDIATE ATTENTION REQUIRED<br>
    <span style="font-weight:400;font-size:10.5px;">{{ $payload['critical_finding_detail'] ?? '' }}</span>
</div>
@endif

{{-- IMPRESSION --}}
<div class="section-label">Impression</div>
<div class="impression-box">
    <div class="imp-label">Clinical Interpretation</div>
    <div class="imp-text">{{ $payload['impression'] ?? 'No impression recorded.' }}</div>
</div>

{{-- COMPARISON --}}
@if(!empty($payload['comparison']))
<div class="optional-box">
    <div class="optional-label">Comparison with Previous ECG</div>
    {{ $payload['comparison'] }}
</div>
@endif

{{-- RECOMMENDATION --}}
@if(!empty($payload['recommendation']))
<div class="optional-box">
    <div class="optional-label">Recommendation</div>
    {{ $payload['recommendation'] }}
</div>
@endif

{{-- CARDIOLOGIST SIGNATURE --}}
<div class="sig-area">
    <div style="font-size:9px;text-transform:uppercase;color:#64748B;">Reporting Cardiologist</div>
    <div class="sig-name">{{ $payload['cardiologist'] ?? 'N/A' }}</div>
    <div class="sig-reg">Reg No: {{ $payload['cardiologist_reg'] ?? 'N/A' }}</div>
    <div class="signature-line"></div>
    <div style="font-size:8.5px;color:#94A3B8;">Signature &amp; Stamp</div>
</div>
@endsection
