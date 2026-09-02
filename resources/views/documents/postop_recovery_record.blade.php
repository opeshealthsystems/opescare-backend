@extends('documents.base')

@section('title', 'Post-Operative Recovery Record')

@section('subtitle', 'POR — ' . ($payload['procedure_name'] ?? 'Procedure'))

@section('content')
@php
    $obs = $payload['observations'] ?? [];
    $fluids = $payload['fluids_in_recovery'] ?? [];
    $meds = $payload['medications_in_recovery'] ?? [];
    $drains = $payload['drain_output'] ?? [];
    $complications = $payload['complications'] ?? ['None'];
    $arrAldrete = $payload['aldrete_score_arrival'] ?? [];
    $disAldrete = $payload['aldrete_score_discharge'] ?? [];
    $dischargeCriteriaMet = $payload['discharge_criteria_met'] ?? false;

    $airwayColors = [
        'Spontaneous — LMA in situ' => ['bg' => '#ECFDF5', 'fg' => '#065F46'],
        'Intubated'                  => ['bg' => '#FEF3C7', 'fg' => '#92400E'],
        'Spontaneous — no airway'   => ['bg' => '#F0FDF4', 'fg' => '#166534'],
        'Facemask'                   => ['bg' => '#EFF6FF', 'fg' => '#1E40AF'],
    ];
    $consciousnessColors = [
        'Awake and oriented'       => ['bg' => '#ECFDF5', 'fg' => '#065F46'],
        'Responding to voice'      => ['bg' => '#FEF3C7', 'fg' => '#92400E'],
        'Responding to pain'       => ['bg' => '#FEE2E2', 'fg' => '#991B1B'],
        'Unresponsive'             => ['bg' => '#FEE2E2', 'fg' => '#7F1D1D'],
    ];

    $airway = $payload['airway_on_arrival'] ?? '';
    $consciousness = $payload['consciousness_on_arrival'] ?? '';
    $airwayStyle = isset($airwayColors[$airway])
        ? 'background:' . $airwayColors[$airway]['bg'] . ';color:' . $airwayColors[$airway]['fg']
        : 'background:#F1F5F9;color:#334155';
    $consciousnessStyle = isset($consciousnessColors[$consciousness])
        ? 'background:' . $consciousnessColors[$consciousness]['bg'] . ';color:' . $consciousnessColors[$consciousness]['fg']
        : 'background:#F1F5F9;color:#334155';

    $aldreteItems = ['activity', 'respiration', 'circulation', 'consciousness', 'oxygen'];
    $aldreteLabels = [
        'activity'      => 'Activity',
        'respiration'   => 'Respiration',
        'circulation'   => 'Circulation',
        'consciousness' => 'Consciousness',
        'oxygen'        => 'O₂ Saturation',
    ];

    $hasComplications = !(count($complications) === 1 && strtolower($complications[0]) === 'none');
@endphp
<style>
    .por-header-strip {
        background: linear-gradient(135deg, #0891B2 0%, #06B6D4 100%);
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .por-header-strip .strip-title { font-size: 13px; font-weight: 700; margin-bottom: 1mm; }
    .por-header-strip .strip-sub { font-size: 9.5px; opacity: 0.88; }
    .por-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .badge-cyan { background: #CFFAFE; color: #164E63; }
    .badge-green { background: #D1FAE5; color: #065F46; }
    .badge-red { background: #FEE2E2; color: #991B1B; }
    .badge-amber { background: #FEF3C7; color: #92400E; }
    .badge-slate { background: #F1F5F9; color: #334155; }
    .section-label {
        font-size: 10px;
        font-weight: 700;
        color: #0891B2;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1.5px solid #A5F3FC;
        padding-bottom: 1mm;
        margin: 4mm 0 3mm;
    }
    .info-card {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 4mm;
    }
    .info-card .ic-head {
        background: #ECFEFF;
        color: #0E7490;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 2mm 3.5mm;
        border-bottom: 1px solid #A5F3FC;
    }
    .info-card .ic-body { padding: 3mm 3.5mm; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 4mm; }
    .kv { display: flex; justify-content: space-between; margin-bottom: 1.2mm; font-size: 10px; }
    .kv .k { color: #64748B; }
    .kv .v { color: #0F172A; font-weight: 600; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 4mm; }
    .data-table th {
        background: #ECFEFF;
        color: #0E7490;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 2mm 2.5mm;
        border: 1px solid #A5F3FC;
        text-align: left;
    }
    .data-table td {
        padding: 1.8mm 2.5mm;
        border: 1px solid #E2E8F0;
        color: #0F172A;
        vertical-align: middle;
    }
    .data-table tr:nth-child(even) td { background: #F8FAFC; }
    .td-critical { background: #FEE2E2 !important; color: #991B1B; font-weight: 700; }
    .td-warn     { background: #FEF3C7 !important; color: #92400E; font-weight: 700; }
    .td-ok       { background: #ECFDF5 !important; color: #065F46; font-weight: 700; }
    .o2-strip {
        background: #F0FDFF;
        border: 1px solid #A5F3FC;
        border-radius: 6px;
        padding: 2.5mm 4mm;
        margin-bottom: 4mm;
        display: flex;
        gap: 8mm;
        align-items: center;
        font-size: 10px;
    }
    .o2-strip .o2-label { color: #0E7490; font-weight: 700; font-size: 9px; text-transform: uppercase; }
    .o2-strip .o2-val { color: #0F172A; font-weight: 600; }
    .aldrete-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .aldrete-table th {
        background: #ECFEFF;
        color: #0E7490;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2mm 3mm;
        border: 1px solid #A5F3FC;
        text-align: center;
    }
    .aldrete-table td {
        padding: 2mm 3mm;
        border: 1px solid #E2E8F0;
        text-align: center;
    }
    .aldrete-total {
        font-weight: 700;
        font-size: 11px;
    }
    .complication-alert {
        background: #FEE2E2;
        border: 1px solid #FECACA;
        border-left: 4px solid #DC2626;
        border-radius: 6px;
        padding: 2.5mm 4mm;
        margin-bottom: 4mm;
        font-size: 10px;
        color: #991B1B;
    }
    .discharge-badge-wrap {
        display: flex;
        align-items: center;
        gap: 4mm;
        margin-bottom: 4mm;
        flex-wrap: wrap;
    }
    .criteria-met-badge {
        display: inline-block;
        padding: 1.5mm 4mm;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.3px;
    }
    .criteria-met   { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .criteria-unmet { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
    .handover-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-top: 4mm;
    }
    .signature-box {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 3mm 4mm;
        text-align: center;
        font-size: 9.5px;
        color: #64748B;
    }
    .signature-box .sig-name { font-weight: 700; color: #0F172A; font-size: 10.5px; margin-top: 1mm; }
    .signature-line { border-top: 1px solid #94A3B8; margin: 5mm 2mm 1.5mm; }
</style>

{{-- CYAN HEADER --}}
<div class="por-header-strip">
    <div>
        <div class="strip-title">POST-OPERATIVE RECOVERY ROOM RECORD</div>
        <div class="strip-sub">
            Procedure: {{ $payload['procedure_name'] ?? 'N/A' }} &nbsp;|&nbsp;
            Arrival: {{ $payload['arrival_time'] ?? 'N/A' }} &nbsp;|&nbsp;
            Anaesthesia: {{ $payload['anaesthesia_type'] ?? 'N/A' }}
        </div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:10px;opacity:0.9;">Discharge from Recovery</div>
        <div style="font-size:11px;font-weight:700;">{{ $payload['discharge_from_recovery_time'] ?? 'Ongoing' }}</div>
    </div>
</div>

{{-- ARRIVAL STATUS --}}
<div class="section-label">Arrival Status</div>
<div style="display:flex;gap:4mm;margin-bottom:4mm;flex-wrap:wrap;">
    <div>
        <span style="font-size:9px;color:#64748B;text-transform:uppercase;font-weight:600;">Airway on Arrival</span><br>
        <span class="por-badge" style="{{ $airwayStyle }};margin-top:1mm;display:inline-block;">
            {{ $payload['airway_on_arrival'] ?? 'N/A' }}
        </span>
    </div>
    <div>
        <span style="font-size:9px;color:#64748B;text-transform:uppercase;font-weight:600;">Consciousness on Arrival</span><br>
        <span class="por-badge" style="{{ $consciousnessStyle }};margin-top:1mm;display:inline-block;">
            {{ $payload['consciousness_on_arrival'] ?? 'N/A' }}
        </span>
    </div>
</div>

{{-- O2 THERAPY --}}
<div class="o2-strip">
    <span class="o2-label">O₂ Therapy</span>
    <span><span class="o2-label">Method:</span> <span class="o2-val">{{ $payload['o2_therapy']['method'] ?? 'N/A' }}</span></span>
    <span><span class="o2-label">Flow Rate:</span> <span class="o2-val">{{ $payload['o2_therapy']['flow_rate'] ?? 'N/A' }}</span></span>
    <span><span class="o2-label">IV Access:</span> <span class="o2-val">{{ $payload['iv_access'] ?? 'N/A' }}</span></span>
</div>

{{-- OBSERVATIONS TABLE --}}
<div class="section-label">Recovery Observations</div>
@if(count($obs) > 0)
<table class="data-table">
    <thead>
        <tr>
            <th>Time</th><th>BP (mmHg)</th><th>HR (bpm)</th>
            <th>SpO₂ (%)</th><th>RR (/min)</th><th>Temp (°C)</th>
            <th>GCS</th><th>Pain (0–10)</th><th>Sedation</th>
            <th>N / V</th><th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($obs as $row)
        @php
            $spo2 = isset($row['spo2']) ? (int)$row['spo2'] : null;
            $hr   = isset($row['hr'])   ? (int)$row['hr']   : null;
            $pain = isset($row['pain_score']) ? (int)$row['pain_score'] : null;
            $spo2Class = ($spo2 !== null && $spo2 < 94) ? 'td-critical' : '';
            $hrClass   = ($hr !== null && ($hr > 110 || $hr < 50)) ? 'td-warn' : '';
            $painClass = ($pain !== null && $pain >= 7) ? 'td-warn' : (($pain !== null && $pain >= 4) ? '' : '');
            $nvText = implode('/', array_filter([
                ($row['nausea'] ?? false)   ? 'N' : '',
                ($row['vomiting'] ?? false) ? 'V' : '',
            ]));
            $nvText = $nvText ?: '—';
        @endphp
        <tr>
            <td style="font-weight:600;">{{ $row['time'] ?? '' }}</td>
            <td>{{ $row['bp'] ?? '—' }}</td>
            <td class="{{ $hrClass }}">{{ $row['hr'] ?? '—' }}</td>
            <td class="{{ $spo2Class }}">{{ $row['spo2'] ?? '—' }}</td>
            <td>{{ $row['rr'] ?? '—' }}</td>
            <td>{{ $row['temp'] ?? '—' }}</td>
            <td>{{ $row['gcs_total'] ?? '—' }}</td>
            <td class="{{ $painClass }}">{{ $row['pain_score'] ?? '—' }}</td>
            <td>{{ $row['sedation_score'] ?? '—' }}</td>
            <td>{{ $nvText }}</td>
            <td style="color:#64748B;font-style:italic;">{{ $row['notes'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#64748B;font-style:italic;font-size:10px;">No observations recorded.</p>
@endif

{{-- FLUIDS & MEDICATIONS --}}
<div class="two-col">
    <div>
        <div class="section-label">Fluids Given in Recovery</div>
        @if(count($fluids) > 0)
        <table class="data-table">
            <thead>
                <tr><th>Fluid</th><th>Volume (mL)</th><th>Time</th></tr>
            </thead>
            <tbody>
                @foreach($fluids as $f)
                <tr>
                    <td>{{ $f['fluid'] ?? '' }}</td>
                    <td style="text-align:right;">{{ $f['volume_ml'] ?? '' }}</td>
                    <td>{{ $f['time'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#64748B;font-style:italic;font-size:10px;">None administered.</p>
        @endif
    </div>
    <div>
        <div class="section-label">Medications Given in Recovery</div>
        @if(count($meds) > 0)
        <table class="data-table">
            <thead>
                <tr><th>Drug</th><th>Dose</th><th>Route</th><th>Time</th><th>Reason</th></tr>
            </thead>
            <tbody>
                @foreach($meds as $m)
                <tr>
                    <td style="font-weight:600;">{{ $m['drug'] ?? '' }}</td>
                    <td>{{ $m['dose'] ?? '' }}</td>
                    <td>{{ $m['route'] ?? '' }}</td>
                    <td>{{ $m['time'] ?? '' }}</td>
                    <td style="color:#64748B;font-style:italic;">{{ $m['reason'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#64748B;font-style:italic;font-size:10px;">None administered.</p>
        @endif
    </div>
</div>

{{-- WOUND, DRAIN, URINE --}}
<div class="section-label">Wound, Drain &amp; Output</div>
<div class="two-col">
    <div class="info-card">
        <div class="ic-head">Wound Condition</div>
        <div class="ic-body">
            <p style="margin:0;font-size:10px;color:#0F172A;">{{ $payload['wound_condition'] ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="info-card">
        <div class="ic-head">Output Summary</div>
        <div class="ic-body">
            <div class="kv"><span class="k">Blood Loss (Recovery)</span><span class="v">{{ isset($payload['blood_loss_recovery_ml']) ? $payload['blood_loss_recovery_ml'] . ' mL' : 'N/A' }}</span></div>
            <div class="kv"><span class="k">Urine Output</span><span class="v">{{ isset($payload['urine_output_recovery_ml']) ? $payload['urine_output_recovery_ml'] . ' mL' : 'N/A' }}</span></div>
            @if(count($drains) > 0)
                @foreach($drains as $d)
                <div class="kv"><span class="k">Drain — {{ $d['drain_site'] ?? '' }}</span><span class="v">{{ $d['volume_ml'] ?? '' }} mL</span></div>
                @endforeach
            @endif
        </div>
    </div>
</div>

{{-- COMPLICATIONS --}}
<div class="section-label">Complications</div>
@if($hasComplications)
<div class="complication-alert">
    <strong>Complications Noted:</strong>
    {{ implode(', ', $complications) }}
</div>
@else
<p style="font-size:10px;margin-bottom:4mm;"><span class="por-badge badge-green">No Complications</span></p>
@endif

{{-- ALDRETE SCORE --}}
<div class="section-label">Aldrete Score — Arrival vs. Discharge</div>
<table class="aldrete-table" style="margin-bottom:4mm;">
    <thead>
        <tr>
            <th style="text-align:left;">Criterion</th>
            <th>Arrival Score</th>
            <th>Discharge Score</th>
        </tr>
    </thead>
    <tbody>
        @foreach($aldreteItems as $item)
        <tr>
            <td style="text-align:left;font-weight:600;">{{ $aldreteLabels[$item] }}</td>
            <td>{{ $arrAldrete[$item] ?? '—' }}</td>
            <td>{{ $disAldrete[$item] ?? '—' }}</td>
        </tr>
        @endforeach
        <tr style="background:#F8FAFC;">
            <td style="text-align:left;font-weight:700;text-transform:uppercase;font-size:10px;">TOTAL</td>
            <td class="aldrete-total">{{ $arrAldrete['total'] ?? '—' }}</td>
            <td class="aldrete-total" style="color:{{ ($disAldrete['total'] ?? 0) >= 9 ? '#065F46' : '#991B1B' }};">
                {{ $disAldrete['total'] ?? '—' }}
            </td>
        </tr>
    </tbody>
</table>
<p style="font-size:9px;color:#64748B;margin-bottom:4mm;"><em>Discharge criterion: Aldrete total ≥ 9</em></p>

{{-- DISCHARGE --}}
<div class="discharge-badge-wrap">
    <span class="criteria-met-badge {{ $dischargeCriteriaMet ? 'criteria-met' : 'criteria-unmet' }}">
        Discharge Criteria: {{ $dischargeCriteriaMet ? 'MET' : 'NOT MET' }}
    </span>
    <span style="font-size:10px;color:#64748B;">Discharged to:</span>
    <span class="por-badge badge-cyan">{{ $payload['discharged_to'] ?? 'N/A' }}</span>
</div>

{{-- HANDOVER --}}
<div class="section-label">Handover Record</div>
<div class="handover-box">
    <div class="signature-box">
        <div style="color:#64748B;font-size:9px;text-transform:uppercase;">Handover</div>
        <div class="sig-name">{{ $payload['handover_nurse'] ?? 'N/A' }}</div>
        <div class="signature-line"></div>
        <div style="font-size:8.5px;color:#94A3B8;">Signature</div>
    </div>
    <div class="signature-box">
        <div style="color:#64748B;font-size:9px;text-transform:uppercase;">Recovery Nurse</div>
        <div class="sig-name">{{ $payload['recovery_nurse'] ?? 'N/A' }}</div>
        <div class="signature-line"></div>
        <div style="font-size:8.5px;color:#94A3B8;">Signature</div>
    </div>
</div>
@endsection
