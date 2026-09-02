@extends('documents.base')

@section('title')
    Daily Clinical Progress Note
@endsection

@section('subtitle')
    Inpatient SOAP Note — PRG | {{ $payload['note_date'] ?? '' }} {{ $payload['note_time'] ?? '' }}
@endsection

@section('content')
<style>
    .prg-day-banner {
        background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
        border: 1px solid #93C5FD;
        border-left: 5px solid #0F4C81;
        border-radius: 0 6px 6px 0;
        padding: 2.5mm 4mm;
        margin-bottom: 5mm;
        display: flex;
        align-items: center;
        gap: 6mm;
    }
    .prg-day-value { font-size: 20px; font-weight: 900; color: #0F4C81; }
    .prg-day-meta { font-size: 9.5px; color: #475569; }
    .prg-day-meta strong { color: #0F172A; }

    .soap-section {
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        margin-bottom: 5mm;
        overflow: hidden;
    }
    .soap-header {
        display: flex;
        align-items: center;
        gap: 3mm;
        padding: 2.5mm 4mm;
        border-bottom: 1px solid #E2E8F0;
    }
    .soap-letter {
        width: 8mm;
        height: 8mm;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 900;
        color: #fff;
        flex-shrink: 0;
    }
    .letter-s { background: #7C3AED; }
    .letter-o { background: #0F4C81; }
    .letter-a { background: #DC2626; }
    .letter-p { background: #059669; }
    .soap-title { font-size: 11px; font-weight: 700; color: #0F172A; }
    .soap-subtitle { font-size: 8.5px; color: #64748B; }
    .soap-body { padding: 4mm; }

    .vitals-row {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2mm;
        margin-bottom: 3mm;
    }
    .vital-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 4px;
        padding: 2mm;
        text-align: center;
    }
    .vital-box.critical { background: #FEF2F2; border-color: #FCA5A5; }
    .vital-label { font-size: 7.5px; color: #64748B; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
    .vital-value { font-size: 11px; font-weight: 800; color: #0F172A; margin-top: 0.5mm; }
    .vital-value.crit-val { color: #DC2626; }

    .systems-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3mm;
    }
    .system-item {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 4px;
        padding: 2.5mm;
    }
    .system-label { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #64748B; letter-spacing: 0.3px; margin-bottom: 1mm; }
    .system-value { font-size: 10px; color: #0F172A; line-height: 1.5; }

    .flag-badge {
        display: inline-block;
        font-size: 7.5px;
        font-weight: 700;
        padding: 0.5mm 1.5mm;
        border-radius: 3px;
        text-transform: uppercase;
    }
    .flag-normal   { background: #ECFDF5; color: #065F46; }
    .flag-high     { background: #FEF2F2; color: #7F1D1D; }
    .flag-low      { background: #EFF6FF; color: #1E3A5F; }
    .flag-critical { background: #DC2626; color: #fff; }

    .assessment-item {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        padding: 2mm 0;
        border-bottom: 1px solid #F1F5F9;
    }
    .assessment-item:last-child { border-bottom: none; }
    .assess-num {
        width: 6mm;
        height: 6mm;
        background: #DC2626;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .assess-problem { font-weight: 600; font-size: 10.5px; color: #0F172A; }
    .assess-icd { font-size: 8px; color: #94A3B8; font-style: italic; margin-top: 0.3mm; }
    .status-badge {
        display: inline-block;
        font-size: 7.5px;
        font-weight: 700;
        padding: 0.5mm 1.5mm;
        border-radius: 3px;
        margin-left: 1mm;
    }
    .status-improving  { background: #ECFDF5; color: #065F46; }
    .status-stable     { background: #F0F9FF; color: #0369A1; }
    .status-worsening  { background: #FEF2F2; color: #7F1D1D; }
    .status-resolved   { background: #F1F5F9; color: #475569; }
    .status-new        { background: #FEF9C3; color: #713F12; }

    .plan-item {
        padding: 2mm 0;
        border-bottom: 1px solid #F1F5F9;
    }
    .plan-item:last-child { border-bottom: none; }
    .plan-prob-num {
        font-size: 9px;
        font-weight: 700;
        color: #059669;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 1mm;
    }
    .plan-action {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        font-size: 10px;
        padding: 0.5mm 0;
        color: #0F172A;
    }
    .plan-bullet { color: #059669; font-weight: 700; flex-shrink: 0; }

    .medchange-badge {
        display: inline-block;
        font-size: 7.5px;
        font-weight: 700;
        padding: 0.5mm 1.5mm;
        border-radius: 3px;
        text-transform: uppercase;
        margin-right: 1mm;
    }
    .mc-started  { background: #ECFDF5; color: #065F46; }
    .mc-stopped  { background: #FEF2F2; color: #7F1D1D; }
    .mc-changed  { background: #FFFBEB; color: #92400E; }
    .mc-held     { background: #F8FAFC; color: #475569; }

    .consult-item {
        display: flex;
        align-items: center;
        gap: 2mm;
        padding: 1.5mm 0;
        border-bottom: 1px solid #F1F5F9;
        font-size: 10px;
    }
    .consult-item:last-child { border-bottom: none; }
    .urgency-badge {
        display: inline-block;
        font-size: 7.5px;
        font-weight: 700;
        padding: 0.5mm 1.5mm;
        border-radius: 3px;
    }
    .urg-routine { background: #ECFDF5; color: #065F46; }
    .urg-urgent  { background: #FFFBEB; color: #92400E; }
    .urg-stat    { background: #DC2626; color: #fff; }

    .disposition-box {
        background: #F0FDF4;
        border: 1px solid #BBF7D0;
        border-left: 4px solid #059669;
        border-radius: 0 6px 6px 0;
        padding: 3mm 4mm;
        margin-bottom: 4mm;
    }
    .disposition-label { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #065F46; margin-bottom: 1mm; }
    .disposition-value { font-size: 11px; font-weight: 600; color: #0F172A; }

    .impression-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 6px;
        padding: 3mm 4mm;
        margin-bottom: 4mm;
        font-size: 10.5px;
        color: #334155;
        font-style: italic;
        line-height: 1.6;
    }

    .author-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6mm;
        margin-top: 5mm;
    }
    .author-sig {
        border-top: 1px solid #94A3B8;
        padding-top: 2mm;
        font-size: 9.5px;
    }
    .author-sig-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #94A3B8; margin-bottom: 5mm; }
    .author-sig-name { font-weight: 700; color: #0F172A; }
    .author-sig-role { font-size: 8.5px; color: #64748B; }
</style>

{{-- DAY BANNER --}}
<div class="prg-day-banner">
    <div class="prg-day-value">{{ $payload['day_of_admission'] ?? 'Day —' }}</div>
    <div class="prg-day-meta">
        <div><strong>Date / Time:</strong> {{ $payload['note_date'] ?? '—' }} at {{ $payload['note_time'] ?? '—' }}</div>
        <div><strong>Ward:</strong> {{ $payload['ward'] ?? '—' }} &nbsp;|&nbsp; <strong>Bed:</strong> {{ $payload['bed_number'] ?? '—' }}</div>
        <div><strong>Admitting Diagnosis:</strong> {{ $payload['admitting_diagnosis'] ?? '—' }}</div>
    </div>
</div>

{{-- S — SUBJECTIVE --}}
<div class="soap-section">
    <div class="soap-header">
        <div class="soap-letter letter-s">S</div>
        <div>
            <div class="soap-title">Subjective</div>
            <div class="soap-subtitle">Patient's reported symptoms and concerns today</div>
        </div>
    </div>
    <div class="soap-body">
        <p style="margin:0; font-size:10.5px; color:#334155; line-height:1.7; font-style:italic;">
            "{{ $payload['subjective'] ?? '—' }}"
        </p>
    </div>
</div>

{{-- O — OBJECTIVE --}}
<div class="soap-section">
    <div class="soap-header">
        <div class="soap-letter letter-o">O</div>
        <div>
            <div class="soap-title">Objective</div>
            <div class="soap-subtitle">Vitals + Physical examination findings</div>
        </div>
    </div>
    <div class="soap-body">
        @php $v = $payload['vitals'] ?? []; @endphp
        <div class="vitals-row">
            <div class="vital-box">
                <div class="vital-label">BP (mmHg)</div>
                <div class="vital-value">{{ $v['bp'] ?? '—' }}</div>
            </div>
            <div class="vital-box">
                <div class="vital-label">Pulse (bpm)</div>
                <div class="vital-value">{{ $v['pulse'] ?? '—' }}</div>
            </div>
            <div class="vital-box">
                <div class="vital-label">Temp (°C)</div>
                <div class="vital-value">{{ $v['temp'] ?? '—' }}</div>
            </div>
            <div class="vital-box">
                <div class="vital-label">SpO2 (%)</div>
                <div class="vital-value">{{ $v['spo2'] ?? '—' }}</div>
            </div>
            <div class="vital-box">
                <div class="vital-label">RR (/min)</div>
                <div class="vital-value">{{ $v['rr'] ?? '—' }}</div>
            </div>
            <div class="vital-box">
                <div class="vital-label">Weight (kg)</div>
                <div class="vital-value">{{ $v['weight_kg'] ?? '—' }}</div>
            </div>
            <div class="vital-box">
                <div class="vital-label">UO 24h (ml)</div>
                <div class="vital-value" style="font-size:9px;">{{ $v['urine_output_24h_ml'] ?? '—' }}</div>
            </div>
        </div>

        @php $obj = $payload['objective'] ?? []; @endphp
        <div class="systems-grid">
            <div class="system-item">
                <div class="system-label">General</div>
                <div class="system-value">{{ $obj['general'] ?? '—' }}</div>
            </div>
            <div class="system-item">
                <div class="system-label">Cardiovascular</div>
                <div class="system-value">{{ $obj['cardiovascular'] ?? '—' }}</div>
            </div>
            <div class="system-item">
                <div class="system-label">Respiratory</div>
                <div class="system-value">{{ $obj['respiratory'] ?? '—' }}</div>
            </div>
            <div class="system-item">
                <div class="system-label">Abdomen</div>
                <div class="system-value">{{ $obj['abdomen'] ?? '—' }}</div>
            </div>
            @if(!empty($obj['other']))
            <div class="system-item" style="grid-column: span 2;">
                <div class="system-label">Other Findings</div>
                <div class="system-value">{{ $obj['other'] }}</div>
            </div>
            @endif
        </div>

        @php $invx = $payload['investigations_today'] ?? []; @endphp
        @if(!empty($invx))
        <div style="margin-top:3mm;">
            <div style="font-size:9px; font-weight:700; text-transform:uppercase; color:#475569; margin-bottom:2mm; letter-spacing:0.3px;">Investigations Today</div>
            <table style="width:100%; border-collapse:collapse; font-size:9.5px;">
                <thead>
                    <tr>
                        <th style="background:#F8FAFC; color:#475569; font-weight:600; text-align:left; padding:2mm; border:1px solid #E2E8F0; text-transform:uppercase; font-size:8.5px;">Test</th>
                        <th style="background:#F8FAFC; color:#475569; font-weight:600; text-align:left; padding:2mm; border:1px solid #E2E8F0; text-transform:uppercase; font-size:8.5px;">Result</th>
                        <th style="background:#F8FAFC; color:#475569; font-weight:600; text-align:center; padding:2mm; border:1px solid #E2E8F0; text-transform:uppercase; font-size:8.5px; width:18%;">Flag</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invx as $inv)
                    <tr>
                        <td style="padding:2mm; border:1px solid #E2E8F0; font-weight:600;">{{ $inv['test'] ?? '—' }}</td>
                        <td style="padding:2mm; border:1px solid #E2E8F0;">{{ $inv['result'] ?? '—' }}</td>
                        <td style="padding:2mm; border:1px solid #E2E8F0; text-align:center;">
                            @php
                                $f = $inv['flag'] ?? 'Normal';
                                $fc = match($f) {
                                    'High'     => 'flag-high',
                                    'Low'      => 'flag-low',
                                    'Critical' => 'flag-critical',
                                    default    => 'flag-normal',
                                };
                            @endphp
                            <span class="flag-badge {{ $fc }}">{{ $f }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- A — ASSESSMENT --}}
<div class="soap-section">
    <div class="soap-header">
        <div class="soap-letter letter-a">A</div>
        <div>
            <div class="soap-title">Assessment</div>
            <div class="soap-subtitle">Active problem list with today's assessment</div>
        </div>
    </div>
    <div class="soap-body">
        @forelse($payload['assessment'] ?? [] as $idx => $prob)
        @php
            $s = $prob['status'] ?? 'Stable';
            $sc = match(strtolower($s)) {
                'improving'  => 'status-improving',
                'stable'     => 'status-stable',
                'worsening'  => 'status-worsening',
                'resolved'   => 'status-resolved',
                default      => 'status-new',
            };
        @endphp
        <div class="assessment-item">
            <div class="assess-num">{{ $idx + 1 }}</div>
            <div>
                <div class="assess-problem">
                    {{ $prob['problem'] ?? '—' }}
                    <span class="status-badge {{ $sc }}">{{ $s }}</span>
                </div>
                @if(!empty($prob['icd10']))
                <div class="assess-icd">ICD-10: {{ $prob['icd10'] }}</div>
                @endif
            </div>
        </div>
        @empty
        <div style="color:#94A3B8; font-style:italic; font-size:9px;">No active problems recorded.</div>
        @endforelse
    </div>
</div>

{{-- P — PLAN --}}
<div class="soap-section">
    <div class="soap-header">
        <div class="soap-letter letter-p">P</div>
        <div>
            <div class="soap-title">Plan</div>
            <div class="soap-subtitle">Management plan numbered to match assessment</div>
        </div>
    </div>
    <div class="soap-body">
        @forelse($payload['plan'] ?? [] as $plan)
        <div class="plan-item">
            <div class="plan-prob-num">Problem {{ $plan['problem_number'] ?? '?' }}</div>
            @foreach($plan['actions'] ?? [] as $action)
            <div class="plan-action">
                <span class="plan-bullet">&#10003;</span>
                <span>{{ $action }}</span>
            </div>
            @endforeach
        </div>
        @empty
        <div style="color:#94A3B8; font-style:italic; font-size:9px;">No plan recorded.</div>
        @endforelse
    </div>
</div>

{{-- MEDICATION CHANGES --}}
@php $medChanges = $payload['medication_changes'] ?? []; @endphp
@if(!empty($medChanges))
<div class="content-card" style="margin-bottom:5mm;">
    <div class="card-header">Medication Changes Today</div>
    <div class="card-body">
        @foreach($medChanges as $mc)
        @php
            $ct = $mc['change_type'] ?? '';
            $cc = match($ct) {
                'Started'      => 'mc-started',
                'Stopped'      => 'mc-stopped',
                'Dose changed' => 'mc-changed',
                default        => 'mc-held',
            };
        @endphp
        <div style="display:flex; align-items:flex-start; gap:2mm; padding:1.5mm 0; border-bottom:1px solid #F1F5F9; font-size:10px;">
            <span class="medchange-badge {{ $cc }}">{{ $ct }}</span>
            <div>
                <strong>{{ $mc['drug'] ?? '—' }}</strong>
                <span style="color:#64748B; font-size:9px;"> — {{ $mc['reason'] ?? '' }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- PENDING + CONSULTS --}}
@php $pending = $payload['pending_results'] ?? []; $consults = $payload['consults_requested'] ?? []; @endphp
@if(!empty($pending) || !empty($consults))
<div style="display:grid; grid-template-columns:1fr 1fr; gap:4mm; margin-bottom:5mm;">
    @if(!empty($pending))
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Pending Results</div>
        <div class="card-body">
            @foreach($pending as $p)
            <div style="display:flex; gap:2mm; font-size:10px; padding:1mm 0; border-bottom:1px solid #F1F5F9;">
                <span style="color:#D97706; font-weight:700;">&#9679;</span>
                <span>{{ $p }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @if(!empty($consults))
    <div class="content-card" style="margin-bottom:0;">
        <div class="card-header">Consults Requested</div>
        <div class="card-body">
            @foreach($consults as $c)
            @php
                $uc = match($c['urgency'] ?? 'Routine') {
                    'Urgent' => 'urg-urgent',
                    'STAT'   => 'urg-stat',
                    default  => 'urg-routine',
                };
            @endphp
            <div class="consult-item">
                <span class="urgency-badge {{ $uc }}">{{ $c['urgency'] ?? 'Routine' }}</span>
                <div>
                    <strong>{{ $c['specialty'] ?? '—' }}</strong>
                    <div style="font-size:8.5px; color:#64748B;">{{ $c['reason'] ?? '' }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

{{-- CLINICAL IMPRESSION --}}
@if(!empty($payload['clinical_impression']))
<div class="impression-box">
    <div style="font-size:8px; font-weight:700; text-transform:uppercase; color:#64748B; margin-bottom:1mm; font-style:normal;">Clinical Impression</div>
    {{ $payload['clinical_impression'] }}
</div>
@endif

{{-- DISPOSITION --}}
<div class="disposition-box">
    <div class="disposition-label">Disposition Plan</div>
    <div class="disposition-value">{{ $payload['disposition_plan'] ?? '—' }}</div>
</div>

{{-- AUTHOR SIGNATURE --}}
<div class="author-row">
    <div class="author-sig">
        <div class="author-sig-label">Attending Physician / Author</div>
        <div class="author-sig-name">{{ $payload['author'] ?? $issuer_name }}</div>
        <div class="author-sig-role">{{ $payload['author_role'] ?? $issuer_role }}</div>
    </div>
    @if(!empty($payload['countersigned_by']))
    <div class="author-sig">
        <div class="author-sig-label">Countersigned By (Consultant)</div>
        <div class="author-sig-name">{{ $payload['countersigned_by'] }}</div>
    </div>
    @endif
</div>
@endsection
