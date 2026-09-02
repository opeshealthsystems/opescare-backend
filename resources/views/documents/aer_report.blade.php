@extends('documents.base')

@section('title', 'EMERGENCY DEPARTMENT REPORT')

@section('subtitle', 'A&amp;E Visit Summary — AER')

@section('content')
<style>
    .aer-header-strip {
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .aer-strip-bg { background: linear-gradient(135deg, #B91C1C 0%, #DC2626 100%); color: #fff; }
    .aer-strip-bg .strip-title { font-size: 14px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 1.5mm; }
    .aer-strip-bg .strip-sub { font-size: 9.5px; opacity: 0.88; }
    .triage-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 14mm;
        height: 14mm;
        border-radius: 50%;
        font-size: 14px;
        font-weight: 900;
        border: 3px solid rgba(255,255,255,0.5);
    }
    .t-p1 { background: #7F1D1D; color: #fff; }
    .t-p2 { background: #EA580C; color: #fff; }
    .t-p3 { background: #CA8A04; color: #fff; }
    .t-p4 { background: #16A34A; color: #fff; }
    .t-p5 { background: #1D4ED8; color: #fff; }
    .aer-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .abadge-red { background: #FEE2E2; color: #991B1B; }
    .abadge-orange { background: #FFEDD5; color: #9A3412; }
    .abadge-amber { background: #FEF3C7; color: #92400E; }
    .abadge-green { background: #D1FAE5; color: #065F46; }
    .abadge-blue { background: #DBEAFE; color: #1E3A8A; }
    .abadge-dark { background: #1F2937; color: #F9FAFB; }
    .abadge-slate { background: #F1F5F9; color: #334155; }
    .vitals-row {
        display: flex;
        flex-wrap: wrap;
        gap: 2mm;
        margin-bottom: 5mm;
    }
    .vital-pill {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: 2mm 3.5mm;
        min-width: 18mm;
    }
    .vital-pill.critical { background: #FEF2F2; border-color: #FECACA; }
    .vital-pill .vp-val { font-size: 12px; font-weight: 800; color: #0F172A; }
    .vital-pill.critical .vp-val { color: #DC2626; }
    .vital-pill .vp-lbl { font-size: 8px; color: #64748B; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 0.5mm; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-bottom: 5mm; }
    .aer-card {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 5mm;
    }
    .ac-head {
        background: #F8FAFC;
        color: #374151;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #E2E8F0;
    }
    .ac-head-red { background: #FEF2F2; color: #991B1B; border-bottom-color: #FECACA; }
    .ac-body { padding: 4mm; }
    .inv-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .inv-table th { background: #F8FAFC; color: #475569; font-weight: 600; text-align: left; padding: 2mm 3mm; border-bottom: 2px solid #E2E8F0; font-size: 9px; text-transform: uppercase; }
    .inv-table td { padding: 2.5mm 3mm; border-bottom: 1px solid #F1F5F9; }
    .inv-table tr.critical-row td { background: #FEF2F2; }
    .dx-list { list-style: none; padding: 0; margin: 0; }
    .dx-list li { padding: 1.5mm 0; font-size: 10.5px; border-bottom: 1px solid #F1F5F9; display: flex; justify-content: space-between; }
    .dx-list li .icd { font-size: 9px; color: #64748B; font-family: monospace; }
    .timeline-list { list-style: none; padding: 0; margin: 0; }
    .timeline-list li {
        display: flex;
        gap: 3mm;
        padding: 2mm 0;
        border-bottom: 1px solid #F1F5F9;
        font-size: 10px;
        align-items: flex-start;
    }
    .timeline-list li .tl-time {
        min-width: 12mm;
        font-weight: 700;
        color: #0F4C81;
        font-size: 9.5px;
        padding-top: 0.5mm;
    }
    .timeline-list li .tl-dot {
        width: 2mm;
        height: 2mm;
        border-radius: 50%;
        background: #DC2626;
        margin-top: 1.5mm;
        flex-shrink: 0;
    }
    .timeline-list li .tl-content { flex: 1; color: #0F172A; }
    .timeline-list li .tl-detail { color: #64748B; font-size: 9.5px; }
    .disposition-banner {
        border-radius: 6px;
        padding: 3.5mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .disp-admitted { background: #DBEAFE; border: 1px solid #93C5FD; }
    .disp-discharged { background: #D1FAE5; border: 1px solid #6EE7B7; }
    .disp-transferred { background: #FEF3C7; border: 1px solid #FCD34D; }
    .disp-deceased { background: #1F2937; border: 1px solid #374151; }
    .disp-left { background: #F3F4F6; border: 1px solid #D1D5DB; }
    .disp-admitted .disp-val { color: #1E3A8A; }
    .disp-discharged .disp-val { color: #065F46; }
    .disp-transferred .disp-val { color: #92400E; }
    .disp-deceased .disp-val { color: #F9FAFB; }
    .disp-left .disp-val { color: #374151; }
    .disp-val { font-size: 13px; font-weight: 800; text-transform: uppercase; }
    .disp-meta { font-size: 9.5px; opacity: 0.8; }
    .dc-list { list-style: none; padding: 0; margin: 0; }
    .dc-list li { padding: 1mm 0; font-size: 10px; border-bottom: 1px solid #F1F5F9; }
    .dc-list li::before { content: "→ "; color: #0F4C81; font-weight: 700; }
    .ed-time-box {
        display: flex;
        align-items: center;
        gap: 4mm;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 5mm;
    }
    .ed-time-box .et-val { font-size: 18px; font-weight: 800; color: #0F4C81; }
    .ed-time-box .et-lbl { font-size: 9px; color: #64748B; text-transform: uppercase; }
</style>

{{-- 1. Header strip with triage badge --}}
@php
    $triageNum = intval($payload['triage_category'] ?? 3);
    $triageLabels = [1 => 'P1 — Resuscitation', 2 => 'P2 — Emergent', 3 => 'P3 — Urgent', 4 => 'P4 — Semi-Urgent', 5 => 'P5 — Non-Urgent'];
    $triageLabel = $triageLabels[$triageNum] ?? 'P' . $triageNum;
@endphp
<div class="aer-header-strip aer-strip-bg">
    <div>
        <div class="strip-title">Emergency Department</div>
        <div class="strip-sub">
            Arrival: {{ $payload['arrival_date'] ?? '—' }} at {{ $payload['arrival_time'] ?? '—' }}
            &nbsp;|&nbsp; Triage: {{ $payload['triage_time'] ?? '—' }}
            &nbsp;|&nbsp; Triage Nurse: {{ $payload['triage_nurse'] ?? '—' }}
        </div>
        <div style="margin-top:2mm;">
            <span class="aer-badge abadge-slate">{{ $payload['arrival_mode'] ?? '—' }}</span>
        </div>
    </div>
    <div style="text-align:center;">
        <div class="triage-badge t-p{{ $triageNum }}">P{{ $triageNum }}</div>
        <div style="font-size:8.5px; opacity:0.85; margin-top:1mm;">{{ $triageLabel }}</div>
    </div>
</div>

{{-- Chief Complaint --}}
<div class="aer-card">
    <div class="ac-head ac-head-red">Chief Complaint</div>
    <div class="ac-body">
        <p style="margin:0; font-size:11px; font-weight:600; color:#0F172A;">{{ $payload['chief_complaint'] ?? '—' }}</p>
    </div>
</div>

{{-- 2. Triage Vitals --}}
@php
    $tv = $payload['triage_vitals'] ?? [];
    $gcs = intval($tv['gcs'] ?? 15);
    $spo2 = intval($tv['spo2'] ?? 98);
    $pain = intval($tv['pain_score'] ?? 0);
@endphp
<div class="vitals-row">
    <div class="vital-pill">
        <span class="vp-val">{{ $tv['bp'] ?? '—' }}</span>
        <span class="vp-lbl">BP (mmHg)</span>
    </div>
    <div class="vital-pill">
        <span class="vp-val">{{ $tv['pulse'] ?? '—' }}</span>
        <span class="vp-lbl">Pulse (bpm)</span>
    </div>
    <div class="vital-pill {{ $spo2 < 94 ? 'critical' : '' }}">
        <span class="vp-val">{{ $tv['spo2'] ?? '—' }}%</span>
        <span class="vp-lbl">SpO2</span>
    </div>
    <div class="vital-pill">
        <span class="vp-val">{{ $tv['temp'] ?? '—' }}°C</span>
        <span class="vp-lbl">Temp</span>
    </div>
    <div class="vital-pill">
        <span class="vp-val">{{ $tv['rr'] ?? '—' }}</span>
        <span class="vp-lbl">RR (/min)</span>
    </div>
    <div class="vital-pill {{ $gcs < 14 ? 'critical' : '' }}">
        <span class="vp-val">{{ $tv['gcs'] ?? '—' }}/15</span>
        <span class="vp-lbl">GCS</span>
    </div>
    <div class="vital-pill {{ $pain >= 8 ? 'critical' : '' }}">
        <span class="vp-val">{{ $tv['pain_score'] ?? '—' }}/10</span>
        <span class="vp-lbl">Pain Score</span>
    </div>
</div>

{{-- 3. History + Examination --}}
<div class="two-col">
    <div class="aer-card" style="margin-bottom:0;">
        <div class="ac-head">History</div>
        <div class="ac-body">
            <p style="margin:0; font-size:10px; line-height:1.5; color:#0F172A;">{{ $payload['history'] ?? '—' }}</p>
        </div>
    </div>
    <div class="aer-card" style="margin-bottom:0;">
        <div class="ac-head">Examination</div>
        <div class="ac-body">
            <p style="margin:0; font-size:10px; line-height:1.5; color:#0F172A;">{{ $payload['examination'] ?? '—' }}</p>
        </div>
    </div>
</div>
<div style="margin-bottom:5mm;"></div>

{{-- 4. Investigations --}}
@if(!empty($payload['investigations']))
<div class="aer-card">
    <div class="ac-head">Investigations</div>
    <div class="ac-body" style="padding:2mm;">
        <table class="inv-table">
            <thead>
                <tr><th>Test</th><th>Result</th><th>Flag</th></tr>
            </thead>
            <tbody>
                @foreach($payload['investigations'] as $inv)
                <tr class="{{ !empty($inv['critical']) ? 'critical-row' : '' }}">
                    <td>{{ $inv['test'] ?? '—' }}</td>
                    <td>{{ $inv['result'] ?? '—' }}</td>
                    <td>
                        @if(!empty($inv['critical']))
                            <span class="aer-badge abadge-red">CRITICAL</span>
                        @else
                            <span style="color:#94A3B8; font-size:9px;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 5. Working Diagnosis --}}
@if(!empty($payload['working_diagnosis']))
<div class="aer-card">
    <div class="ac-head">Working Diagnosis</div>
    <div class="ac-body">
        <ul class="dx-list">
            @foreach($payload['working_diagnosis'] as $dx)
            <li>
                <span>{{ $dx['diagnosis'] ?? '—' }}</span>
                <span class="icd">{{ $dx['icd10'] ?? '' }}</span>
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- 6. Treatment Timeline --}}
@if(!empty($payload['treatment_given']))
<div class="aer-card">
    <div class="ac-head">Treatment Given</div>
    <div class="ac-body">
        <ul class="timeline-list">
            @foreach($payload['treatment_given'] as $tx)
            <li>
                <span class="tl-time">{{ $tx['time'] ?? '—' }}</span>
                <span class="tl-dot"></span>
                <span class="tl-content">
                    <strong>{{ $tx['drug_or_intervention'] ?? '—' }}</strong>
                    @if(!empty($tx['dose_or_detail']))
                        <span class="tl-detail"> — {{ $tx['dose_or_detail'] }}</span>
                    @endif
                </span>
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- 7. Response to treatment --}}
<div class="aer-card">
    <div class="ac-head">Response to Treatment</div>
    <div class="ac-body">
        <p style="margin:0; font-size:10px; line-height:1.5; color:#0F172A;">{{ $payload['response_to_treatment'] ?? '—' }}</p>
    </div>
</div>

{{-- 8. Disposition banner --}}
@php
    $disp = $payload['disposition'] ?? 'Discharged';
    $dispClass = match($disp) {
        'Admitted' => 'disp-admitted',
        'Discharged' => 'disp-discharged',
        'Transferred' => 'disp-transferred',
        'Deceased in ED' => 'disp-deceased',
        default => 'disp-left',
    };
@endphp
<div class="disposition-banner {{ $dispClass }}">
    <div>
        <div class="disp-val">{{ $disp }}</div>
        <div class="disp-meta">Disposition Time: {{ $payload['disposition_time'] ?? '—' }}
            @if(!empty($payload['admitted_to'])) &nbsp;|&nbsp; Admitted to: {{ $payload['admitted_to'] }} @endif
        </div>
    </div>
    @if(!empty($payload['follow_up']))
        <div style="font-size:9.5px; max-width:80mm; text-align:right;">
            <strong>Follow-up:</strong> {{ $payload['follow_up'] }}
        </div>
    @endif
</div>

{{-- 9. Discharge instructions --}}
@if(!empty($payload['discharge_instructions']))
<div class="aer-card">
    <div class="ac-head">Discharge Instructions</div>
    <div class="ac-body">
        <ul class="dc-list">
            @foreach($payload['discharge_instructions'] as $di)
                <li>{{ $di }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- 10. ED time + Physician --}}
<div class="ed-time-box">
    <div>
        <div class="et-val">{{ $payload['total_ed_time_minutes'] ?? '—' }} <span style="font-size:12px;">min</span></div>
        <div class="et-lbl">Total ED Time</div>
    </div>
    <div style="border-left:1px solid #E2E8F0; padding-left:4mm;">
        <div style="font-size:9.5px; color:#64748B; margin-bottom:0.5mm;">Treating Physician</div>
        <div style="font-size:11px; font-weight:700; color:#0F172A;">{{ $payload['treating_physician'] ?? '—' }}</div>
    </div>
</div>
@endsection
