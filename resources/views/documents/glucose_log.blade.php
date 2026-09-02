@extends('documents.base')

@section('title', 'Blood Glucose Monitoring Log')

@section('subtitle', 'DGL — Diabetic Glucose Log')

@section('content')
@php
    $readings       = $payload['readings'] ?? [];
    $oralMeds       = $payload['oral_medications'] ?? [];
    $targets        = $payload['glucose_target'] ?? [];
    $diabetesType   = $payload['diabetes_type'] ?? '';
    $regimen        = $payload['treatment_regimen'] ?? '';
    $insulinRegimen = $payload['insulin_regimen'] ?? null;
    $hba1c          = $payload['hba1c'] ?? null;
    $meanFasting    = $payload['mean_fasting_glucose'] ?? null;
    $hypoCount      = $payload['hypoglycaemia_events_count'] ?? 0;
    $hyperCount     = $payload['hyperglycaemia_events_count'] ?? 0;

    $typeColors = [
        'Type 1'      => 'background:#EDE9FE;color:#5B21B6',
        'Type 2'      => 'background:#DBEAFE;color:#1E40AF',
        'Gestational' => 'background:#FCE7F3;color:#831843',
        'Secondary'   => 'background:#E5E7EB;color:#374151',
    ];
    $typeStyle = $typeColors[$diabetesType] ?? 'background:#F1F5F9;color:#334155';

    // Glucose colour coding helper
    $glucoseClass = function($val, $context = 'fasting') {
        if ($val === null || $val === '') return '';
        $v = (float) $val;
        if ($v < 4.0)  return 'gc-hypo';
        if ($context === 'fasting') {
            if ($v <= 7.0)  return 'gc-target';
            if ($v <= 10.0) return 'gc-pp';
        }
        if ($v <= 10.0) return 'gc-pp';
        if ($v <= 15.0) return 'gc-high';
        return 'gc-critical';
    };
@endphp
<style>
    .dgl-header-strip {
        background: linear-gradient(135deg, #7C3AED 0%, #8B5CF6 100%);
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .dgl-header-strip .strip-title { font-size: 13px; font-weight: 700; margin-bottom: 1mm; }
    .dgl-header-strip .strip-sub   { font-size: 9.5px; opacity: 0.88; }
    .dgl-badge {
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
        color: #7C3AED;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1.5px solid #DDD6FE;
        padding-bottom: 1mm;
        margin: 4mm 0 3mm;
    }
    .target-strip {
        display: flex;
        gap: 4mm;
        flex-wrap: wrap;
        align-items: center;
        background: #F5F3FF;
        border: 1px solid #DDD6FE;
        border-radius: 6px;
        padding: 2mm 4mm;
        margin-bottom: 4mm;
        font-size: 9.5px;
    }
    .target-strip .tl { color: #7C3AED; font-weight: 700; font-size: 9px; text-transform: uppercase; }
    .target-strip .tv { color: #0F172A; font-weight: 600; }
    .glucose-table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 4mm; }
    .glucose-table th {
        background: #F5F3FF;
        color: #6D28D9;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        padding: 2mm 1.5mm;
        border: 1px solid #DDD6FE;
        text-align: center;
        white-space: nowrap;
    }
    .glucose-table td {
        padding: 1.8mm 1.5mm;
        border: 1px solid #E2E8F0;
        text-align: center;
        vertical-align: middle;
    }
    .glucose-table .td-date { text-align: left; font-weight: 600; font-size: 9px; white-space: nowrap; }
    .glucose-table tr.hypo-row td { background: #FFF1F2 !important; }
    /* Glucose colour classes */
    .gc-hypo     { background: #FEE2E2; color: #991B1B; font-weight: 700; border-radius: 3px; padding: 0.5mm 1mm; display:inline-block; }
    .gc-target   { background: #D1FAE5; color: #065F46; font-weight: 600; border-radius: 3px; padding: 0.5mm 1mm; display:inline-block; }
    .gc-pp       { background: #FEF3C7; color: #92400E; font-weight: 600; border-radius: 3px; padding: 0.5mm 1mm; display:inline-block; }
    .gc-high     { background: #FED7AA; color: #7C2D12; font-weight: 700; border-radius: 3px; padding: 0.5mm 1mm; display:inline-block; }
    .gc-critical { background: #991B1B; color: #FEE2E2; font-weight: 700; border-radius: 3px; padding: 0.5mm 1mm; display:inline-block; }
    .gc-nil      { color: #CBD5E1; font-style: italic; font-size: 8px; }
    .summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3mm; margin-bottom: 4mm; }
    .stat-card {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 2.5mm 3mm;
        text-align: center;
    }
    .stat-card .st-label { font-size: 8.5px; color: #64748B; text-transform: uppercase; font-weight: 600; margin-bottom: 1mm; }
    .stat-card .st-value { font-size: 14px; font-weight: 700; color: #0F172A; }
    .hypo-protocol {
        background: #FFF1F2;
        border: 2px solid #DC2626;
        border-left: 5px solid #DC2626;
        border-radius: 6px;
        padding: 3mm 5mm;
        margin-bottom: 4mm;
        font-size: 10px;
    }
    .hypo-protocol .hp-title { font-weight: 700; color: #991B1B; margin-bottom: 1mm; }
    .info-card {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4mm;
    }
    .info-card .ic-head {
        background: #F5F3FF;
        color: #6D28D9;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2mm 3.5mm;
        border-bottom: 1px solid #DDD6FE;
    }
    .info-card .ic-body { padding: 3mm 3.5mm; font-size: 10px; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 4mm; }
    .footer-row {
        display: flex;
        gap: 6mm;
        flex-wrap: wrap;
        align-items: center;
        font-size: 10px;
        border-top: 1px solid #E2E8F0;
        padding-top: 3mm;
        margin-top: 2mm;
        color: #64748B;
    }
    .footer-row strong { color: #0F172A; }
</style>

{{-- HEADER --}}
<div class="dgl-header-strip">
    <div>
        <div class="strip-title">BLOOD GLUCOSE MONITORING LOG</div>
        <div class="strip-sub">
            Period: {{ $payload['monitoring_period'] ?? 'N/A' }} &nbsp;|&nbsp;
            Regimen: {{ $regimen }}
        </div>
    </div>
    <div style="text-align:right;">
        <span class="dgl-badge" style="{{ $typeStyle }}">{{ $diabetesType ?: 'N/A' }}</span>
    </div>
</div>

{{-- TARGETS + HBA1C --}}
<div class="target-strip">
    @if($hba1c)
    <span><span class="tl">HbA1c:</span> <span class="tv">{{ $hba1c }}</span></span>
    <span style="color:#DDD6FE;">|</span>
    @endif
    <span><span class="tl">Fasting Target:</span> <span class="tv">{{ $targets['fasting'] ?? '4.0–7.0 mmol/L' }}</span></span>
    <span><span class="tl">Post-Meal Target:</span> <span class="tv">{{ $targets['post_meal'] ?? '< 10.0 mmol/L' }}</span></span>
    <span><span class="tl">Bedtime Target:</span> <span class="tv">{{ $targets['bedtime'] ?? '6.0–8.0 mmol/L' }}</span></span>
</div>

{{-- REGIMEN --}}
@if($insulinRegimen || count($oralMeds) > 0)
<div class="two-col">
    @if($insulinRegimen)
    <div class="info-card">
        <div class="ic-head">Insulin Regimen</div>
        <div class="ic-body">{{ $insulinRegimen }}</div>
    </div>
    @endif
    @if(count($oralMeds) > 0)
    <div class="info-card">
        <div class="ic-head">Oral Medications</div>
        <div class="ic-body">
            @foreach($oralMeds as $om)
            <div style="margin-bottom:1mm;">
                <strong>{{ $om['drug'] ?? '' }}</strong>
                {{ $om['dose'] ?? '' }} — {{ $om['timing'] ?? '' }}
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

{{-- MAIN READINGS TABLE --}}
<div class="section-label">Daily Glucose Readings</div>
<table class="glucose-table">
    <thead>
        <tr>
            <th rowspan="2" style="text-align:left;vertical-align:bottom;">Date</th>
            <th colspan="2">Pre-Breakfast</th>
            <th>Post-B</th>
            <th colspan="2">Pre-Lunch</th>
            <th>Post-L</th>
            <th colspan="2">Pre-Dinner</th>
            <th>Post-D</th>
            <th colspan="2">Bedtime</th>
            <th rowspan="2" style="vertical-align:bottom;">Events</th>
        </tr>
        <tr>
            <th>BG</th><th>Ins.</th>
            <th>BG</th>
            <th>BG</th><th>Ins.</th>
            <th>BG</th>
            <th>BG</th><th>Ins.</th>
            <th>BG</th>
            <th>BG</th><th>Ins.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($readings as $row)
        @php
            $isHypo = $row['hypoglycaemic_event'] ?? false;
            $preBG  = $row['pre_breakfast']['glucose']  ?? null;
            $postBG = $row['post_breakfast']['glucose'] ?? null;
            $preLBG = $row['pre_lunch']['glucose']      ?? null;
            $postLBG= $row['post_lunch']['glucose']     ?? null;
            $preDBG = $row['pre_dinner']['glucose']     ?? null;
            $postDBG= $row['post_dinner']['glucose']    ?? null;
            $bedBG  = $row['bedtime']['glucose']        ?? null;
        @endphp
        <tr class="{{ $isHypo ? 'hypo-row' : '' }}">
            <td class="td-date">
                {{ $row['date'] ?? '' }}<br>
                <span style="font-size:8px;color:#64748B;font-weight:400;">{{ $row['day_label'] ?? '' }}</span>
            </td>
            <td>
                @if($preBG !== null)
                <span class="{{ $glucoseClass($preBG, 'fasting') }}">{{ $preBG }}</span>
                @else<span class="gc-nil">—</span>@endif
            </td>
            <td style="font-size:8.5px;">{{ $row['pre_breakfast']['insulin_dose'] ?? '—' }}</td>

            <td>
                @if($postBG !== null)
                <span class="{{ $glucoseClass($postBG, 'pp') }}">{{ $postBG }}</span>
                @else<span class="gc-nil">—</span>@endif
            </td>

            <td>
                @if($preLBG !== null)
                <span class="{{ $glucoseClass($preLBG, 'fasting') }}">{{ $preLBG }}</span>
                @else<span class="gc-nil">—</span>@endif
            </td>
            <td style="font-size:8.5px;">{{ $row['pre_lunch']['insulin_dose'] ?? '—' }}</td>

            <td>
                @if($postLBG !== null)
                <span class="{{ $glucoseClass($postLBG, 'pp') }}">{{ $postLBG }}</span>
                @else<span class="gc-nil">—</span>@endif
            </td>

            <td>
                @if($preDBG !== null)
                <span class="{{ $glucoseClass($preDBG, 'fasting') }}">{{ $preDBG }}</span>
                @else<span class="gc-nil">—</span>@endif
            </td>
            <td style="font-size:8.5px;">{{ $row['pre_dinner']['insulin_dose'] ?? '—' }}</td>

            <td>
                @if($postDBG !== null)
                <span class="{{ $glucoseClass($postDBG, 'pp') }}">{{ $postDBG }}</span>
                @else<span class="gc-nil">—</span>@endif
            </td>

            <td>
                @if($bedBG !== null)
                <span class="{{ $glucoseClass($bedBG, 'pp') }}">{{ $bedBG }}</span>
                @else<span class="gc-nil">—</span>@endif
            </td>
            <td style="font-size:8.5px;">{{ $row['bedtime']['insulin_dose'] ?? '—' }}</td>

            <td>
                @if($isHypo)
                <span class="dgl-badge" style="background:#FEE2E2;color:#991B1B;">HYPO</span>
                @if(!empty($row['hypo_details']))
                <br><span style="font-size:8px;color:#991B1B;">{{ $row['hypo_details'] }}</span>
                @endif
                @else
                <span style="color:#CBD5E1;font-size:8px;">—</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- COLOUR KEY --}}
<div style="display:flex;gap:3mm;flex-wrap:wrap;margin-bottom:4mm;font-size:8.5px;align-items:center;">
    <span style="font-weight:700;color:#64748B;">Key:</span>
    <span class="gc-hypo">&lt;4.0 — HYPO</span>
    <span class="gc-target">4.0–7.0 — Target (fasting)</span>
    <span class="gc-pp">7.1–10.0 — Acceptable (PP)</span>
    <span class="gc-high">10.1–15.0 — High</span>
    <span class="gc-critical">&gt;15.0 — Critical</span>
</div>

{{-- SUMMARY STATS --}}
<div class="section-label">Summary Statistics</div>
<div class="summary-grid">
    <div class="stat-card">
        <div class="st-label">Mean Fasting Glucose</div>
        <div class="st-value">{{ $meanFasting !== null ? $meanFasting . ' mmol/L' : 'N/A' }}</div>
    </div>
    <div class="stat-card" style="{{ $hypoCount > 0 ? 'border-color:#FECACA;background:#FFF1F2' : '' }}">
        <div class="st-label" style="{{ $hypoCount > 0 ? 'color:#991B1B' : '' }}">Hypoglycaemia Events</div>
        <div class="st-value" style="{{ $hypoCount > 0 ? 'color:#991B1B' : 'color:#065F46' }}">{{ $hypoCount }}</div>
    </div>
    <div class="stat-card" style="{{ $hyperCount > 0 ? 'border-color:#FDE68A' : '' }}">
        <div class="st-label">Hyperglycaemia Events</div>
        <div class="st-value" style="{{ $hyperCount > 0 ? 'color:#92400E' : 'color:#065F46' }}">{{ $hyperCount }}</div>
    </div>
</div>

{{-- HYPO PROTOCOL --}}
@if($hypoCount > 0)
<div class="hypo-protocol">
    <div class="hp-title">Hypoglycaemia Protocol</div>
    For BG &lt; 4.0 mmol/L — administer 15 g fast-acting carbohydrate (e.g. 150 mL fruit juice or 3–4 glucose tablets).
    Recheck blood glucose in 15 minutes. Repeat if still &lt; 4.0 mmol/L. Notify treating doctor if no improvement after 2 cycles.
</div>
@endif

{{-- FOOTER --}}
<div class="footer-row">
    <span>Treating Doctor: <strong>{{ $payload['treating_doctor'] ?? 'N/A' }}</strong></span>
    @if(!empty($payload['diabetic_nurse']))
    <span>Diabetic Nurse: <strong>{{ $payload['diabetic_nurse'] }}</strong></span>
    @endif
    <span>Period: <strong>{{ $payload['monitoring_period'] ?? 'N/A' }}</strong></span>
</div>
@endsection
