@extends('documents.base')

@section('title')
    ICU Daily Flowsheet
@endsection

@section('subtitle')
    Critical Care Monitoring Record — ICU | {{ $payload['icu_day'] ?? '' }}
@endsection

@section('content')
<style>
    .icu-top-banner {
        background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%);
        border: 2px solid #FCA5A5;
        border-left: 6px solid #DC2626;
        border-radius: 0 8px 8px 0;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .icu-day-val { font-size: 22px; font-weight: 900; color: #DC2626; }
    .icu-top-meta { font-size: 9.5px; color: #475569; }
    .icu-top-meta strong { color: #0F172A; }
    .icu-score-boxes { display: flex; gap: 4mm; }
    .icu-score-box {
        background: #fff;
        border: 1.5px solid #FCA5A5;
        border-radius: 6px;
        padding: 2mm 4mm;
        text-align: center;
        min-width: 22mm;
    }
    .icu-score-label { font-size: 7.5px; font-weight: 700; text-transform: uppercase; color: #64748B; letter-spacing: 0.3px; }
    .icu-score-value { font-size: 18px; font-weight: 900; color: #DC2626; margin-top: 0.5mm; }
    .icu-score-sub   { font-size: 7.5px; color: #94A3B8; margin-top: 0.3mm; }
    .mortality-badge {
        background: #DC2626;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 1mm 2.5mm;
        border-radius: 4px;
        margin-top: 1mm;
        display: inline-block;
    }

    .organ-strip {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 2mm;
        margin-bottom: 5mm;
    }
    .organ-box {
        border-radius: 6px;
        padding: 2.5mm;
        text-align: center;
        border: 1px solid;
    }
    .organ-label { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; opacity: 0.75; margin-bottom: 1mm; }
    .organ-value { font-size: 9px; font-weight: 700; }
    .organ-full     { background: #FEF2F2; border-color: #FCA5A5; color: #7F1D1D; }
    .organ-partial  { background: #FFFBEB; border-color: #FCD34D; color: #92400E; }
    .organ-none     { background: #ECFDF5; border-color: #6EE7B7; color: #065F46; }

    .hourly-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8px;
    }
    .hourly-table th {
        background: #1E293B;
        color: #fff;
        padding: 1.5mm;
        border: 1px solid #334155;
        text-align: center;
        font-size: 7.5px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .hourly-table td {
        padding: 1.5mm;
        border: 1px solid #E2E8F0;
        text-align: center;
        font-size: 8px;
        white-space: nowrap;
    }
    .hourly-table tr:nth-child(even) td { background: #F8FAFC; }
    .crit-high { background: #FEE2E2 !important; color: #DC2626; font-weight: 800; }
    .crit-low  { background: #EFF6FF !important; color: #1E3A5F; font-weight: 800; }

    .fluid-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 5mm;
    }
    .fluid-in-out {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }
    .fluid-in  { background: #F0F9FF; border-right: 1px solid #BAE6FD; padding: 3mm; }
    .fluid-out { background: #FEF2F2; padding: 3mm; }
    .fluid-col-title { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2mm; }
    .fluid-in .fluid-col-title  { color: #0369A1; }
    .fluid-out .fluid-col-title { color: #DC2626; }
    .fluid-row { display: flex; justify-content: space-between; font-size: 9.5px; padding: 1mm 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .fluid-row:last-child { border-bottom: none; }
    .fluid-key { color: #64748B; }
    .fluid-val { font-weight: 700; color: #0F172A; }
    .fluid-balance-row {
        background: #1E293B;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2.5mm 4mm;
        font-size: 10px;
        font-weight: 700;
    }
    .net-positive { color: #FBBF24; }
    .net-negative { color: #60A5FA; }
    .net-neutral  { color: #6EE7B7; }

    .vent-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 2mm;
        padding: 3mm;
        background: #F8FAFC;
        border-radius: 4px;
        margin-top: 2mm;
    }
    .vent-item { text-align: center; }
    .vent-label { font-size: 7.5px; color: #64748B; text-transform: uppercase; font-weight: 600; }
    .vent-value { font-size: 11px; font-weight: 700; color: #0F172A; margin-top: 0.5mm; }

    .pressor-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .pressor-table th { background: #FEF2F2; color: #7F1D1D; padding: 2mm; border: 1px solid #FCA5A5; font-size: 8.5px; text-transform: uppercase; font-weight: 700; text-align: left; }
    .pressor-table td { padding: 2mm; border: 1px solid #E2E8F0; }

    .lines-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .lines-table th { background: #F8FAFC; color: #475569; padding: 2mm; border: 1px solid #E2E8F0; font-size: 8.5px; text-transform: uppercase; font-weight: 600; text-align: left; }
    .lines-table td { padding: 2mm; border: 1px solid #E2E8F0; }
    .line-flag { background: #FEE2E2; color: #DC2626; font-size: 7.5px; font-weight: 700; padding: 0.5mm 1.5mm; border-radius: 3px; }

    .micro-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .micro-table th { background: #F8FAFC; color: #475569; padding: 2mm; border: 1px solid #E2E8F0; font-size: 8.5px; text-transform: uppercase; font-weight: 600; text-align: left; }
    .micro-table td { padding: 2mm; border: 1px solid #E2E8F0; }

    .icu-sig-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8mm;
        margin-top: 5mm;
    }
    .icu-sig-box { border-top: 1px solid #94A3B8; padding-top: 2mm; }
    .icu-sig-label { font-size: 8px; text-transform: uppercase; color: #94A3B8; margin-bottom: 5mm; }
    .icu-sig-name  { font-weight: 700; color: #0F172A; font-size: 10px; }
</style>

{{-- ICU TOP BANNER --}}
@php $fb = $payload['fluid_balance'] ?? []; @endphp
<div class="icu-top-banner">
    <div>
        <div class="icu-day-val">{{ $payload['icu_day'] ?? '—' }}</div>
        <div class="icu-top-meta">
            <div><strong>Admitted:</strong> {{ $payload['icu_admission_date'] ?? '—' }} &nbsp;|&nbsp; <strong>Bed:</strong> {{ $payload['bed_number'] ?? '—' }}</div>
            <div><strong>Diagnosis:</strong> {{ $payload['primary_diagnosis'] ?? '—' }}</div>
        </div>
    </div>
    <div class="icu-score-boxes">
        <div class="icu-score-box">
            <div class="icu-score-label">APACHE II</div>
            <div class="icu-score-value">{{ $payload['apache_ii_score'] ?? '—' }}</div>
            <div class="mortality-badge">{{ $payload['predicted_mortality_pct'] ?? '—' }}% mortality</div>
        </div>
        <div class="icu-score-box">
            <div class="icu-score-label">SOFA</div>
            <div class="icu-score-value">{{ $payload['sofa_score'] ?? '—' }}</div>
            <div class="icu-score-sub">Organ Failure Score</div>
        </div>
    </div>
</div>

{{-- ORGAN SUPPORT STRIP --}}
@php
    $rs = $payload['respiratory_support'] ?? [];
    $cs = $payload['cardiovascular_support'] ?? [];
    $ren = $payload['renal_support'] ?? null;
    $sed = $payload['sedation'] ?? [];
    $nut = $payload['nutrition'] ?? [];

    $respClass = ($rs['mode'] ?? '') === 'Spontaneous' ? 'organ-none' : (($rs['mode'] ?? '') === 'High-Flow O2' ? 'organ-partial' : 'organ-full');
    $cardClass  = !empty($cs) ? 'organ-full' : 'organ-none';
    $renClass   = $ren ? 'organ-full' : 'organ-none';
    $sedClass   = !empty($sed['drug'] ?? '') ? 'organ-partial' : 'organ-none';
    $nutClass   = ($nut['route'] ?? '') === 'Nil' ? 'organ-full' : (($nut['route'] ?? '') === 'Parenteral' ? 'organ-partial' : 'organ-none');
@endphp
<div class="organ-strip">
    <div class="organ-box {{ $respClass }}">
        <div class="organ-label">Respiratory</div>
        <div class="organ-value">{{ $rs['mode'] ?? 'Spontaneous' }}</div>
    </div>
    <div class="organ-box {{ $cardClass }}">
        <div class="organ-label">Cardiovascular</div>
        <div class="organ-value">{{ !empty($cs) ? count($cs) . ' vasoactive agent(s)' : 'None' }}</div>
    </div>
    <div class="organ-box {{ $renClass }}">
        <div class="organ-label">Renal</div>
        <div class="organ-value">{{ $ren ?? 'No RRT' }}</div>
    </div>
    <div class="organ-box {{ $sedClass }}">
        <div class="organ-label">Sedation</div>
        <div class="organ-value">{{ $sed['drug'] ?? 'None' }} {{ !empty($sed['score']) ? '('.$sed['score'].')' : '' }}</div>
    </div>
    <div class="organ-box {{ $nutClass }}">
        <div class="organ-label">Nutrition</div>
        <div class="organ-value">{{ $nut['route'] ?? '—' }}</div>
    </div>
</div>

{{-- HOURLY OBSERVATIONS --}}
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header" style="background:#1E293B; color:#fff;">24-Hour Hourly Observations</div>
    <div class="card-body" style="padding:0; overflow-x:auto;">
        <table class="hourly-table">
            <thead>
                <tr>
                    <th>Hour</th>
                    <th>SBP</th>
                    <th>DBP</th>
                    <th>HR</th>
                    <th>SpO2%</th>
                    <th>Temp°C</th>
                    <th>RR</th>
                    <th>GCS</th>
                    <th>UO ml</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['hourly_observations'] ?? [] as $obs)
                @php
                    $sbp = (int)($obs['bp_sys'] ?? 0);
                    $hr  = (int)($obs['hr'] ?? 0);
                    $spo = (float)($obs['spo2'] ?? 100);
                    $tmp = (float)($obs['temp'] ?? 37);
                    $sbpClass = ($sbp > 0 && ($sbp < 90 || $sbp > 180)) ? 'crit-high' : '';
                    $hrClass  = ($hr > 0  && ($hr  < 50 || $hr  > 130)) ? 'crit-high' : '';
                    $spoClass = ($spo > 0 && $spo < 92) ? 'crit-low' : '';
                    $tmpClass = ($tmp > 0 && ($tmp < 36 || $tmp > 38.5)) ? 'crit-high' : '';
                @endphp
                <tr>
                    <td><strong>{{ $obs['hour'] ?? '—' }}</strong></td>
                    <td class="{{ $sbpClass }}">{{ $obs['bp_sys'] ?? '—' }}</td>
                    <td>{{ $obs['bp_dia'] ?? '—' }}</td>
                    <td class="{{ $hrClass }}">{{ $obs['hr'] ?? '—' }}</td>
                    <td class="{{ $spoClass }}">{{ $obs['spo2'] ?? '—' }}</td>
                    <td class="{{ $tmpClass }}">{{ $obs['temp'] ?? '—' }}</td>
                    <td>{{ $obs['rr'] ?? '—' }}</td>
                    <td>{{ $obs['gcs'] ?? '—' }}</td>
                    <td>{{ $obs['urine_ml'] ?? '—' }}</td>
                    <td style="text-align:left; max-width:20mm; font-size:7.5px;">{{ $obs['notes'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- FLUID BALANCE --}}
<div class="fluid-card">
    <div style="background:#1E293B; color:#fff; padding:2mm 4mm; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Fluid Balance</div>
    <div class="fluid-in-out">
        <div class="fluid-in">
            <div class="fluid-col-title">&#8593; INPUT</div>
            <div class="fluid-row"><span class="fluid-key">IV Fluids</span><span class="fluid-val">{{ $fb['iv_fluids_ml'] ?? '—' }} ml</span></div>
            <div class="fluid-row"><span class="fluid-key">Oral</span><span class="fluid-val">{{ $fb['oral_ml'] ?? '—' }} ml</span></div>
            <div class="fluid-row"><span class="fluid-key">Blood Products</span><span class="fluid-val">{{ $fb['blood_products_ml'] ?? '—' }} ml</span></div>
        </div>
        <div class="fluid-out">
            <div class="fluid-col-title">&#8595; OUTPUT</div>
            <div class="fluid-row"><span class="fluid-key">Urine</span><span class="fluid-val">{{ $fb['urine_ml'] ?? '—' }} ml</span></div>
            <div class="fluid-row"><span class="fluid-key">Drains</span><span class="fluid-val">{{ $fb['drains_ml'] ?? '—' }} ml</span></div>
            <div class="fluid-row"><span class="fluid-key">Other Losses</span><span class="fluid-val">{{ $fb['other_losses_ml'] ?? '—' }} ml</span></div>
        </div>
    </div>
    @php
        $net = (int)($fb['net_balance_ml'] ?? 0);
        $cum = (int)($fb['cumulative_balance_ml'] ?? 0);
        $balClass = $net > 1000 ? 'net-positive' : ($net < 0 ? 'net-negative' : 'net-neutral');
    @endphp
    <div class="fluid-balance-row">
        <span>Net Balance (24h): <span class="{{ $balClass }}">{{ $net >= 0 ? '+' : '' }}{{ $net }} ml</span></span>
        <span>Cumulative: <span class="{{ $balClass }}">{{ $cum >= 0 ? '+' : '' }}{{ $cum }} ml</span></span>
    </div>
</div>

{{-- VASOPRESSORS --}}
@if(!empty($cs))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header" style="background:#FEF2F2; color:#7F1D1D;">Vasopressors / Inotropes</div>
    <div class="card-body" style="padding:0;">
        <table class="pressor-table">
            <thead>
                <tr>
                    <th>Drug</th>
                    <th>Dose (mcg/kg/min)</th>
                    <th>Rate (ml/hr)</th>
                    <th>Started</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cs as $vaso)
                <tr>
                    <td style="font-weight:700;">{{ $vaso['drug'] ?? '—' }}</td>
                    <td style="text-align:center;">{{ $vaso['dose_mcg_kg_min'] ?? '—' }}</td>
                    <td style="text-align:center;">{{ $vaso['rate_ml_hr'] ?? '—' }}</td>
                    <td>{{ $vaso['started'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- VENTILATOR SETTINGS --}}
@if(!empty($rs['mode']) && $rs['mode'] !== 'Spontaneous')
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Ventilator Settings — {{ $rs['mode'] ?? '—' }}</div>
    <div class="card-body" style="padding:3mm;">
        <div class="vent-grid">
            <div class="vent-item"><div class="vent-label">FiO2 %</div><div class="vent-value">{{ $rs['fio2_pct'] ?? '—' }}</div></div>
            <div class="vent-item"><div class="vent-label">PEEP (cmH2O)</div><div class="vent-value">{{ $rs['peep_cmh2o'] ?? '—' }}</div></div>
            <div class="vent-item"><div class="vent-label">TV (ml)</div><div class="vent-value">{{ $rs['tidal_volume_ml'] ?? '—' }}</div></div>
            <div class="vent-item"><div class="vent-label">RR Set</div><div class="vent-value">{{ $rs['rr_set'] ?? '—' }}</div></div>
            <div class="vent-item"><div class="vent-label">PIP (cmH2O)</div><div class="vent-value">{{ $rs['pip_cmh2o'] ?? '—' }}</div></div>
            <div class="vent-item"><div class="vent-label">SpO2</div><div class="vent-value">{{ $rs['spo2'] ?? '—' }}</div></div>
        </div>
        @if(!empty($rs['intubation_date']))
        <div style="font-size:8.5px; color:#64748B; margin-top:2mm;">Intubation Date: <strong>{{ $rs['intubation_date'] }}</strong></div>
        @endif
    </div>
</div>
@endif

{{-- LINES & DEVICES --}}
@php $lines = $payload['lines_devices'] ?? []; @endphp
@if(!empty($lines))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Lines &amp; Devices</div>
    <div class="card-body" style="padding:0;">
        <table class="lines-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Site</th>
                    <th>Date Inserted</th>
                    <th>Duration</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                @php
                    $insertedDate = $line['date_inserted'] ?? null;
                    $daysIn = 0;
                    if ($insertedDate) {
                        try {
                            $daysIn = (int)\Carbon\Carbon::parse($insertedDate)->diffInDays(\Carbon\Carbon::now());
                        } catch (\Exception $e) {
                            $daysIn = 0;
                        }
                    }
                @endphp
                <tr>
                    <td style="font-weight:700;">{{ $line['device'] ?? '—' }}</td>
                    <td>{{ $line['site'] ?? '—' }}</td>
                    <td>{{ $insertedDate ?? '—' }}</td>
                    <td>
                        {{ $daysIn > 0 ? $daysIn . ' day(s)' : '—' }}
                        @if($daysIn > 5)
                            <span class="line-flag">&#9888; &gt;5d</span>
                        @endif
                    </td>
                    <td>{{ $line['condition'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- MICROBIOLOGY --}}
@php $micro = $payload['microbiology'] ?? []; @endphp
@if(!empty($micro))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Microbiology Results</div>
    <div class="card-body" style="padding:0;">
        <table class="micro-table">
            <thead>
                <tr>
                    <th>Sample</th>
                    <th>Date</th>
                    <th>Result</th>
                    <th>Organism</th>
                    <th>Sensitivity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($micro as $m)
                <tr>
                    <td style="font-weight:600;">{{ $m['sample'] ?? '—' }}</td>
                    <td>{{ $m['date'] ?? '—' }}</td>
                    <td>{{ $m['result'] ?? '—' }}</td>
                    <td>{{ $m['organism'] ?? '—' }}</td>
                    <td style="font-size:8.5px;">{{ $m['sensitivity'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- NUTRITION --}}
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Nutrition Assessment</div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:3mm;">
            <div style="text-align:center;">
                <div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">Route</div>
                <div style="font-weight:700; color:#0F172A; margin-top:0.5mm;">{{ $nut['route'] ?? '—' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">Feed</div>
                <div style="font-weight:700; color:#0F172A; margin-top:0.5mm; font-size:9.5px;">{{ $nut['feed_name'] ?? '—' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">Rate ml/hr</div>
                <div style="font-weight:700; color:#0F172A; margin-top:0.5mm;">{{ $nut['rate_ml_hr'] ?? '—' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">Kcal Target</div>
                <div style="font-weight:700; color:#0F172A; margin-top:0.5mm;">{{ $nut['calories_target'] ?? '—' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:8px; color:#64748B; text-transform:uppercase; font-weight:600;">Kcal Achieved</div>
                <div style="font-weight:700; color:#0F172A; margin-top:0.5mm;">{{ $nut['calories_achieved'] ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- NURSING ASSESSMENT --}}
@if(!empty($payload['nursing_assessment']))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Nursing Assessment</div>
    <div class="card-body">
        <p style="margin:0; font-size:10.5px; color:#334155; line-height:1.7;">{{ $payload['nursing_assessment'] }}</p>
    </div>
</div>
@endif

{{-- ICU SIGNATURES --}}
<div class="icu-sig-row">
    <div class="icu-sig-box">
        <div class="icu-sig-label">ICU Physician</div>
        <div class="icu-sig-name">{{ $payload['icu_physician'] ?? $issuer_name }}</div>
    </div>
    <div class="icu-sig-box">
        <div class="icu-sig-label">ICU Nurse</div>
        <div class="icu-sig-name">{{ $payload['icu_nurse'] ?? '—' }}</div>
    </div>
</div>
@endsection
