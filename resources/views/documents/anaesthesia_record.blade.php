@extends('documents.base')

@section('title', 'ANAESTHESIA RECORD')

@section('subtitle', 'Anaesthetist\'s Intraoperative Record — ANS')

@section('content')
<style>
    .ans-accent { color: #0F4C81; }
    .ans-header-strip {
        background: linear-gradient(135deg, #0F4C81 0%, #1a6fbb 100%);
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .ans-header-strip .strip-title { font-size: 13px; font-weight: 700; margin-bottom: 1mm; }
    .ans-header-strip .strip-sub { font-size: 9.5px; opacity: 0.88; }
    .ans-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .badge-navy { background: #0F4C81; color: #fff; }
    .badge-green { background: #D1FAE5; color: #065F46; }
    .badge-red { background: #FEE2E2; color: #991B1B; }
    .badge-amber { background: #FEF3C7; color: #92400E; }
    .badge-slate { background: #F1F5F9; color: #334155; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3mm; margin-bottom: 5mm; }
    .info-card {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        overflow: hidden;
    }
    .info-card .ic-head {
        background: #EFF6FF;
        color: #0F4C81;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 2mm 3.5mm;
        border-bottom: 1px solid #CBD5E1;
    }
    .info-card .ic-body { padding: 3mm 3.5mm; }
    .kv { display: flex; justify-content: space-between; margin-bottom: 1mm; font-size: 10px; }
    .kv .k { color: #64748B; }
    .kv .v { color: #0F172A; font-weight: 600; }
    .section-label {
        font-size: 10px;
        font-weight: 700;
        color: #0F4C81;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-left: 3px solid #0F4C81;
        padding-left: 2.5mm;
        margin-bottom: 2.5mm;
    }
    .vitals-pill {
        display: inline-block;
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 9999px;
        padding: 1mm 3mm;
        font-size: 9.5px;
        font-weight: 600;
        color: #1D4ED8;
        margin: 0.5mm;
    }
    .risk-list { list-style: none; padding: 0; margin: 0; }
    .risk-list li { padding: 1mm 0; font-size: 10px; border-bottom: 1px solid #F1F5F9; }
    .risk-list li::before { content: "• "; color: #DC2626; font-weight: 700; }
    .pre-med-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .pre-med-table th { background: #F8FAFC; color: #475569; font-weight: 600; text-align: left; padding: 2mm 3mm; border-bottom: 2px solid #E2E8F0; font-size: 9px; text-transform: uppercase; }
    .pre-med-table td { padding: 2mm 3mm; border-bottom: 1px solid #F1F5F9; }
    .intraop-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    .intraop-table th { background: #0F4C81; color: #fff; font-weight: 600; text-align: center; padding: 2mm 2.5mm; font-size: 9px; text-transform: uppercase; }
    .intraop-table td { padding: 2mm 2.5mm; border-bottom: 1px solid #E2E8F0; text-align: center; }
    .intraop-table tr:nth-child(even) td { background: #F8FAFC; }
    .fluids-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3mm; margin-bottom: 5mm; }
    .fluid-box {
        border-radius: 6px;
        padding: 3mm;
        text-align: center;
    }
    .fluid-box .fb-val { font-size: 18px; font-weight: 800; margin-bottom: 1mm; }
    .fluid-box .fb-lbl { font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.4px; }
    .fb-blue { background: #EFF6FF; color: #1D4ED8; }
    .fb-red { background: #FEF2F2; color: #DC2626; }
    .fb-yellow { background: #FEFCE8; color: #CA8A04; }
    .recovery-box {
        background: #F0FDF4;
        border: 1px solid #86EFAC;
        border-radius: 6px;
        padding: 3.5mm;
        margin-bottom: 4mm;
        font-size: 10.5px;
        color: #14532D;
    }
    .duration-box {
        display: flex;
        align-items: center;
        gap: 3mm;
        background: #EFF6FF;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 4mm;
    }
    .duration-box .dur-item { text-align: center; }
    .duration-box .dur-val { font-size: 15px; font-weight: 800; color: #0F4C81; }
    .duration-box .dur-lbl { font-size: 8.5px; color: #64748B; text-transform: uppercase; }
    .duration-box .dur-arrow { font-size: 18px; color: #94A3B8; }
    .sig-area {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4mm;
        margin-top: 5mm;
    }
    .sig-box {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 3mm;
        text-align: center;
    }
    .sig-line { border-top: 1px solid #94A3B8; margin-top: 8mm; padding-top: 1mm; font-size: 9px; color: #64748B; }
</style>

{{-- 1. Header strip --}}
<div class="ans-header-strip">
    <div>
        <div class="strip-title">{{ $payload['procedure_name'] }}</div>
        <div class="strip-sub">Theatre: {{ $payload['theatre'] }} &nbsp;|&nbsp; Surgeon: {{ $payload['surgeon'] }} &nbsp;|&nbsp; Date: {{ $payload['surgery_date'] }}</div>
    </div>
    <div style="text-align:right;">
        <span class="ans-badge badge-amber" style="margin-bottom:2mm; display:inline-block;">{{ $payload['asa_grade'] }}</span><br>
        <span style="font-size:9px; opacity:0.85;">{{ $payload['anaesthesia_type'] }}</span>
    </div>
</div>

{{-- 2. Anaesthetist credentials | Pre-op vitals --}}
<div class="two-col">
    <div class="info-card">
        <div class="ic-head">Anaesthetist Details</div>
        <div class="ic-body">
            <div class="kv"><span class="k">Name</span><span class="v">{{ $payload['anaesthetist'] }}</span></div>
            <div class="kv"><span class="k">Registration No</span><span class="v">{{ $payload['anaesthetist_reg'] }}</span></div>
            <div class="kv"><span class="k">Pre-op Assessment</span><span class="v">{{ $payload['pre_op_assessment_date'] }}</span></div>
            <div class="kv"><span class="k">Fasting Status</span><span class="v">{{ $payload['fasting_status'] }}</span></div>
        </div>
    </div>
    <div class="info-card">
        <div class="ic-head">Pre-operative Vitals</div>
        <div class="ic-body">
            @php $pv = $payload['pre_op_vitals'] ?? []; @endphp
            <div class="kv"><span class="k">BP</span><span class="v">{{ $pv['bp'] ?? '—' }}</span></div>
            <div class="kv"><span class="k">Pulse</span><span class="v">{{ $pv['pulse'] ?? '—' }} bpm</span></div>
            <div class="kv"><span class="k">SpO2</span><span class="v">{{ $pv['spo2'] ?? '—' }}%</span></div>
            <div class="kv"><span class="k">Temp</span><span class="v">{{ $pv['temp'] ?? '—' }} °C</span></div>
            <div class="kv"><span class="k">Weight</span><span class="v">{{ $pv['weight_kg'] ?? '—' }} kg</span></div>
            <div class="kv"><span class="k">Height</span><span class="v">{{ $pv['height_cm'] ?? '—' }} cm</span></div>
        </div>
    </div>
</div>

{{-- 3. Airway Assessment --}}
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Airway Assessment</div>
    <div class="card-body">
        @php $aw = $payload['airway_assessment'] ?? []; @endphp
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:3mm; margin-bottom:3mm;">
            <div style="text-align:center;">
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Mallampati</div>
                <div style="font-size:13px; font-weight:800; color:#0F4C81;">{{ $aw['mallampati'] ?? '—' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Mouth Opening</div>
                <div style="font-size:11px; font-weight:700; color:#0F172A;">{{ $aw['mouth_opening'] ?? '—' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Neck Mobility</div>
                <div style="font-size:11px; font-weight:700; color:#0F172A;">{{ $aw['neck_mobility'] ?? '—' }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Thyromental Dist.</div>
                <div style="font-size:11px; font-weight:700; color:#0F172A;">{{ $aw['thyromental_distance'] ?? '—' }}</div>
            </div>
        </div>
        <div>
            <span style="font-size:9.5px; color:#64748B; margin-right:2mm;">Predicted Difficult Airway:</span>
            @if(!empty($aw['predicted_difficulty']))
                <span class="ans-badge badge-red">YES — Difficult Airway Anticipated</span>
            @else
                <span class="ans-badge badge-green">No Difficulty Predicted</span>
            @endif
        </div>
    </div>
</div>

{{-- 4. Relevant history --}}
@if(!empty($payload['relevant_history']))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Relevant Medical History</div>
    <div class="card-body">
        <ul class="risk-list">
            @foreach($payload['relevant_history'] as $h)
                <li>{{ $h }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- 5. Pre-medications --}}
@if(!empty($payload['pre_medications']))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Pre-medications</div>
    <div class="card-body">
        <table class="pre-med-table">
            <thead>
                <tr>
                    <th>Drug</th><th>Dose</th><th>Route</th><th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['pre_medications'] as $pm)
                <tr>
                    <td>{{ $pm['drug'] ?? '—' }}</td>
                    <td>{{ $pm['dose'] ?? '—' }}</td>
                    <td>{{ $pm['route'] ?? '—' }}</td>
                    <td>{{ $pm['time'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 6. Induction | Maintenance | Muscle Relaxants --}}
<div class="three-col">
    <div class="info-card">
        <div class="ic-head">Induction Agents</div>
        <div class="ic-body">
            @foreach($payload['induction_agents'] ?? [] as $ia)
            <div style="border-bottom:1px solid #F1F5F9; padding:1.5mm 0; font-size:10px;">
                <strong>{{ $ia['drug'] ?? '—' }}</strong> {{ $ia['dose'] ?? '' }} <span style="color:#64748B;">{{ $ia['route'] ?? '' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="info-card">
        <div class="ic-head">Maintenance Agents</div>
        <div class="ic-body">
            @foreach($payload['maintenance_agents'] ?? [] as $ma)
            <div style="border-bottom:1px solid #F1F5F9; padding:1.5mm 0; font-size:10px;">
                <strong>{{ $ma['agent'] ?? '—' }}</strong> <span style="color:#64748B;">{{ $ma['concentration'] ?? '' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="info-card">
        <div class="ic-head">Muscle Relaxants</div>
        <div class="ic-body">
            @foreach($payload['muscle_relaxants'] ?? [] as $mr)
            <div style="border-bottom:1px solid #F1F5F9; padding:1.5mm 0; font-size:10px;">
                <strong>{{ $mr['drug'] ?? '—' }}</strong> {{ $mr['dose'] ?? '' }} <span style="color:#64748B;">@ {{ $mr['time'] ?? '' }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- 7. Intubation details --}}
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Intubation / Airway Management</div>
    <div class="card-body">
        @php $intub = $payload['intubation'] ?? []; @endphp
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:3mm; align-items:center;">
            <div>
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Method</div>
                <div style="font-size:11px; font-weight:700; color:#0F4C81;">{{ $intub['method'] ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Attempts</div>
                @php $att = intval($intub['attempts'] ?? 1); @endphp
                <span class="ans-badge {{ $att === 1 ? 'badge-green' : 'badge-amber' }}">{{ $att }} {{ $att === 1 ? 'Attempt' : 'Attempts' }}</span>
            </div>
            <div>
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Cormack-Lehane</div>
                <div style="font-size:11px; font-weight:700;">{{ $intub['cormack_lehane'] ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:8.5px; color:#64748B; margin-bottom:1mm;">Confirmed By</div>
                <div style="font-size:9.5px; color:#0F172A;">{{ $intub['confirmed_by'] ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- 8. Intraoperative Vitals --}}
@if(!empty($payload['intraop_vitals']))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Intraoperative Vitals Chart</div>
    <div class="card-body" style="padding:2mm;">
        <table class="intraop-table">
            <thead>
                <tr>
                    <th>Time</th><th>BP (mmHg)</th><th>HR (bpm)</th><th>SpO2 (%)</th><th>EtCO2 (mmHg)</th><th>Agent (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['intraop_vitals'] as $vit)
                <tr>
                    <td>{{ $vit['time'] ?? '—' }}</td>
                    <td>{{ $vit['bp'] ?? '—' }}</td>
                    <td>{{ $vit['hr'] ?? '—' }}</td>
                    <td>{{ $vit['spo2'] ?? '—' }}</td>
                    <td>{{ $vit['etco2'] ?? '—' }}</td>
                    <td>{{ $vit['agent_percent'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 9. Fluids & Outputs --}}
<div style="margin-bottom:5mm;">
    <div class="section-label">Fluids &amp; Outputs</div>
    <div class="fluids-grid">
        @foreach($payload['fluids_given'] ?? [] as $fl)
        <div class="fluid-box fb-blue">
            <div class="fb-val">{{ $fl['volume_ml'] ?? '—' }} <span style="font-size:11px;">mL</span></div>
            <div class="fb-lbl">{{ $fl['fluid'] ?? '—' }}</div>
        </div>
        @endforeach
        <div class="fluid-box fb-red">
            <div class="fb-val">{{ $payload['blood_loss_ml'] ?? '—' }} <span style="font-size:11px;">mL</span></div>
            <div class="fb-lbl">Estimated Blood Loss</div>
        </div>
        <div class="fluid-box fb-yellow">
            <div class="fb-val">{{ $payload['urine_output_ml'] ?? '—' }} <span style="font-size:11px;">mL</span></div>
            <div class="fb-lbl">Urine Output</div>
        </div>
    </div>
</div>

{{-- 10. Reversal + Duration --}}
<div class="two-col">
    <div class="info-card">
        <div class="ic-head">Reversal Agents</div>
        <div class="ic-body">
            @foreach($payload['reversal_agents'] ?? [] as $ra)
            <div style="border-bottom:1px solid #F1F5F9; padding:1.5mm 0; font-size:10px;">
                <strong>{{ $ra['drug'] ?? '—' }}</strong> &nbsp; {{ $ra['dose'] ?? '' }}
            </div>
            @endforeach
        </div>
    </div>
    <div>
        <div class="section-label">Anaesthesia Duration</div>
        <div class="duration-box">
            <div class="dur-item"><div class="dur-val">{{ $payload['anaesthesia_start'] ?? '—' }}</div><div class="dur-lbl">Start</div></div>
            <div class="dur-arrow">→</div>
            <div class="dur-item"><div class="dur-val">{{ $payload['anaesthesia_end'] ?? '—' }}</div><div class="dur-lbl">End</div></div>
        </div>
    </div>
</div>

{{-- 11. Recovery handover + Complications --}}
<div class="section-label" style="margin-top:3mm;">Recovery &amp; Complications</div>
<div class="recovery-box">
    <strong>Recovery Handover:</strong> {{ $payload['recovery_handover'] ?? '—' }}
</div>
<div style="display:flex; align-items:center; gap:3mm; margin-bottom:5mm;">
    <span style="font-size:10px; color:#64748B;">Complications:</span>
    @if(strtolower($payload['complications'] ?? 'none') === 'none' || empty($payload['complications']))
        <span class="ans-badge badge-green">None Reported</span>
    @else
        <span class="ans-badge badge-red">{{ $payload['complications'] }}</span>
    @endif
</div>

{{-- Signature --}}
<div class="sig-area">
    <div class="sig-box">
        <div style="font-size:9px; color:#64748B; margin-bottom:8mm;">Anaesthetist Signature</div>
        <div class="sig-line">{{ $payload['anaesthetist'] ?? '' }} &nbsp;|&nbsp; Reg: {{ $payload['anaesthetist_reg'] ?? '' }}</div>
    </div>
    <div class="sig-box">
        <div style="font-size:9px; color:#64748B; margin-bottom:8mm;">Reviewed / Countersigned</div>
        <div class="sig-line">Date: ___________________</div>
    </div>
</div>
@endsection
