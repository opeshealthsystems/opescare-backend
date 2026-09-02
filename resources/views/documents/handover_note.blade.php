@extends('documents.base')

@section('title', 'Clinical Handover / SBAR Note')

@section('subtitle', 'HOV — SBAR Handover')

@section('content')
@php
    $situation      = $payload['situation']      ?? [];
    $background     = $payload['background']     ?? [];
    $assessment     = $payload['assessment']     ?? [];
    $recommendation = $payload['recommendation'] ?? [];
    $vitals         = $assessment['current_vitals'] ?? [];
    $activeProblems = $assessment['active_problems'] ?? [];
    $latestResults  = $assessment['latest_investigations'] ?? [];
    $actions        = $recommendation['actions_required'] ?? [];
    $monitoring     = $recommendation['monitoring_required'] ?? [];
    $escalation     = $recommendation['escalation_criteria'] ?? [];
    $expectedEvents = $recommendation['expected_events'] ?? [];
    $pendingResults = $recommendation['pending_results'] ?? [];
    $allergies      = $background['allergies'] ?? [];
    $currentMeds    = $background['current_medications'] ?? [];
    $recentProcs    = $background['recent_procedures'] ?? [];
    $concerns       = $situation['immediate_concerns'] ?? [];
    $handoverComplete = $payload['handover_complete'] ?? false;
    $handoverType   = $payload['handover_type'] ?? '';

    $typeColors = [
        'Nursing Shift Handover'         => 'background:#DBEAFE;color:#1E40AF',
        'Medical Team Handover'          => 'background:#EDE9FE;color:#5B21B6',
        'Interhospital Transfer Handover'=> 'background:#FEF3C7;color:#92400E',
        'On-Call Handover'               => 'background:#FEE2E2;color:#991B1B',
    ];
    $typeStyle = $typeColors[$handoverType] ?? 'background:#F1F5F9;color:#334155';

    $codeStatus = $situation['code_status'] ?? '';
    $codeStyle  = 'background:#D1FAE5;color:#065F46';
    if (str_contains($codeStatus, 'DNR') || str_contains($codeStatus, 'DNAR')) {
        $codeStyle = 'background:#FEE2E2;color:#991B1B';
    }

    $clinicalStatus = $assessment['clinical_status'] ?? '';
    $statusColors = [
        'Improving'     => 'background:#D1FAE5;color:#065F46',
        'Stable'        => 'background:#DBEAFE;color:#1E40AF',
        'Deteriorating' => 'background:#FEF3C7;color:#92400E',
        'Critical'      => 'background:#7F1D1D;color:#FEE2E2',
    ];
    $statusStyle = $statusColors[$clinicalStatus] ?? 'background:#F1F5F9;color:#334155';

    $priorityColors = [
        'Routine' => 'background:#F1F5F9;color:#334155',
        'Urgent'  => 'background:#FEF3C7;color:#92400E',
        'STAT'    => 'background:#FEE2E2;color:#991B1B',
    ];
@endphp
<style>
    .hov-header-strip {
        background: linear-gradient(135deg, #0369A1 0%, #0EA5E9 100%);
        color: #fff;
        border-radius: 6px;
        padding: 4mm 5mm;
        margin-bottom: 5mm;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .hov-header-strip .strip-title { font-size: 13px; font-weight: 700; margin-bottom: 1mm; }
    .hov-header-strip .strip-sub   { font-size: 9.5px; opacity: 0.88; }
    .hov-badge {
        display: inline-block;
        padding: 1mm 3mm;
        border-radius: 9999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .sbar-card {
        border-radius: 6px;
        margin-bottom: 4mm;
        overflow: hidden;
        border: 1px solid #CBD5E1;
    }
    .sbar-card .sc-head {
        padding: 2.5mm 4mm;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 3mm;
    }
    .sbar-card .sc-letter {
        display: inline-flex;
        width: 7mm; height: 7mm;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        flex-shrink: 0;
    }
    .sbar-card .sc-body { padding: 3mm 4mm; font-size: 10px; }
    /* Card themes */
    .card-s .sc-head  { background: #FFFBEB; color: #92400E; border-bottom: 1px solid #FDE68A; }
    .card-s .sc-letter{ background: #F59E0B; color: #fff; }
    .card-b .sc-head  { background: #F8FAFC; color: #334155; border-bottom: 1px solid #CBD5E1; }
    .card-b .sc-letter{ background: #64748B; color: #fff; }
    .card-a .sc-head  { background: #EFF6FF; color: #1E40AF; border-bottom: 1px solid #BFDBFE; }
    .card-a .sc-letter{ background: #0369A1; color: #fff; }
    .card-r .sc-head  { background: #ECFDF5; color: #065F46; border-bottom: 1px solid #6EE7B7; }
    .card-r .sc-letter{ background: #10B981; color: #fff; }

    .kv { display: flex; gap: 2mm; margin-bottom: 1.5mm; font-size: 10px; align-items: flex-start; }
    .kv .k { color: #64748B; min-width: 30mm; flex-shrink: 0; }
    .kv .v { color: #0F172A; font-weight: 600; }
    .allergy-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 2mm;
        margin-bottom: 2mm;
    }
    .allergy-badge {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FECACA;
        border-radius: 9999px;
        padding: 0.8mm 2.5mm;
        font-size: 9px;
        font-weight: 600;
    }
    .vitals-row {
        display: flex;
        gap: 3mm;
        flex-wrap: wrap;
        margin-bottom: 3mm;
        background: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 6px;
        padding: 2.5mm 4mm;
    }
    .vital-item { text-align: center; min-width: 16mm; }
    .vital-item .vl { font-size: 8px; color: #64748B; text-transform: uppercase; font-weight: 600; }
    .vital-item .vv { font-size: 11px; font-weight: 700; color: #0F172A; }
    .concern-item {
        display: flex;
        align-items: flex-start;
        gap: 2mm;
        margin-bottom: 1.5mm;
        font-size: 10px;
        color: #991B1B;
        font-weight: 600;
    }
    .concern-item::before { content: '⚠'; font-size: 10px; flex-shrink: 0; }
    .action-table { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 3mm; }
    .action-table th {
        background: #ECFDF5;
        color: #065F46;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2mm 3mm;
        border: 1px solid #6EE7B7;
        text-align: left;
    }
    .action-table td {
        padding: 2mm 3mm;
        border: 1px solid #E2E8F0;
        vertical-align: middle;
    }
    .action-table tr:nth-child(even) td { background: #F0FDF4; }
    .priority-badge {
        display: inline-block;
        padding: 0.8mm 2.5mm;
        border-radius: 9999px;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .escalation-item {
        display: flex;
        gap: 2mm;
        align-items: flex-start;
        margin-bottom: 1.5mm;
        font-size: 10px;
        color: #991B1B;
    }
    .escalation-item::before { content: '→'; font-weight: 700; flex-shrink: 0; }
    .monitor-item, .pending-item, .expected-item {
        display: flex;
        gap: 2mm;
        margin-bottom: 1mm;
        font-size: 10px;
    }
    .monitor-item::before  { content: '•'; color: #0369A1; font-weight: 700; flex-shrink: 0; }
    .pending-item::before  { content: '◦'; color: #F59E0B; font-weight: 700; flex-shrink: 0; }
    .expected-item::before { content: '▸'; color: #64748B; flex-shrink: 0; }
    .results-table { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 3mm; }
    .results-table th {
        background: #EFF6FF;
        color: #1E40AF;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 1.8mm 3mm;
        border: 1px solid #BFDBFE;
    }
    .results-table td {
        padding: 1.8mm 3mm;
        border: 1px solid #E2E8F0;
    }
    .handover-footer {
        display: flex;
        gap: 4mm;
        align-items: center;
        padding: 2.5mm 4mm;
        background: #F8FAFC;
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        margin-bottom: 4mm;
        flex-wrap: wrap;
        font-size: 10px;
    }
    .sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; margin-top: 4mm; }
    .sig-box {
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        padding: 3mm 4mm;
        text-align: center;
        font-size: 9.5px;
        color: #64748B;
    }
    .sig-box .sig-role { font-size: 9px; text-transform: uppercase; color: #64748B; margin-bottom: 0.5mm; }
    .sig-box .sig-name { font-weight: 700; color: #0F172A; font-size: 10.5px; }
    .signature-line { border-top: 1px solid #94A3B8; margin: 5mm 2mm 1.5mm; }
    .sub-label {
        font-size: 9px;
        font-weight: 700;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 1.5mm;
        margin-top: 2mm;
    }
    .tag-list { display: flex; flex-wrap: wrap; gap: 1.5mm; margin-bottom: 2mm; }
    .tag { background: #F1F5F9; color: #334155; border-radius: 3px; padding: 0.8mm 2mm; font-size: 9px; }
</style>

{{-- BLUE HEADER --}}
<div class="hov-header-strip">
    <div>
        <div class="strip-title">SBAR CLINICAL HANDOVER NOTE</div>
        <div class="strip-sub">
            Date: {{ $payload['handover_date'] ?? 'N/A' }} &nbsp;|&nbsp;
            Time: {{ $payload['handover_time'] ?? 'N/A' }} &nbsp;|&nbsp;
            Ward: {{ $payload['ward'] ?? 'N/A' }} &nbsp;|&nbsp;
            Bed: {{ $payload['bed_number'] ?? 'N/A' }}
        </div>
    </div>
    <div style="text-align:right;">
        <span class="hov-badge" style="{{ $typeStyle }}">{{ $handoverType ?: 'N/A' }}</span>
        <div style="font-size:9px;margin-top:1.5mm;opacity:0.9;">
            {{ $payload['from_person'] ?? 'N/A' }} → {{ $payload['to_person'] ?? 'N/A' }}
        </div>
    </div>
</div>

{{-- S — SITUATION --}}
<div class="sbar-card card-s">
    <div class="sc-head">
        <span class="sc-letter">S</span>
        SITUATION
    </div>
    <div class="sc-body">
        <div class="kv"><span class="k">Current Diagnosis:</span><span class="v">{{ $situation['current_diagnosis'] ?? 'N/A' }}</span></div>
        <div class="kv"><span class="k">Reason for Handover:</span><span class="v">{{ $situation['reason_for_handover'] ?? 'N/A' }}</span></div>
        <div class="kv"><span class="k">Code Status:</span>
            <span><span class="hov-badge" style="{{ $codeStyle }}">{{ $codeStatus ?: 'N/A' }}</span></span>
        </div>
        @if(count($concerns) > 0)
        <div class="sub-label" style="color:#92400E;margin-top:2mm;">Immediate Concerns</div>
        @foreach($concerns as $c)
        <div class="concern-item">{{ $c }}</div>
        @endforeach
        @endif
    </div>
</div>

{{-- B — BACKGROUND --}}
<div class="sbar-card card-b">
    <div class="sc-head">
        <span class="sc-letter">B</span>
        BACKGROUND
    </div>
    <div class="sc-body">
        <div class="kv"><span class="k">Admission Date:</span><span class="v">{{ $background['admission_date'] ?? 'N/A' }}</span></div>
        <div class="kv"><span class="k">Day of Admission:</span><span class="v">{{ $background['day_of_admission'] ?? 'N/A' }}</span></div>
        <div class="kv" style="align-items:flex-start;"><span class="k">Relevant History:</span><span class="v" style="white-space:pre-line;line-height:1.5;">{{ $background['relevant_history'] ?? 'N/A' }}</span></div>

        @if(count($allergies) > 0)
        <div class="sub-label">Allergies</div>
        <div class="allergy-strip">
            @foreach($allergies as $a)
            <span class="allergy-badge">{{ $a['allergen'] ?? '' }} — {{ $a['reaction'] ?? '' }}</span>
            @endforeach
        </div>
        @else
        <div class="kv"><span class="k">Allergies:</span><span class="v" style="color:#065F46;">NKDA</span></div>
        @endif

        @if(count($currentMeds) > 0)
        <div class="sub-label">Current Medications</div>
        <div class="tag-list">
            @foreach($currentMeds as $med)
            <span class="tag">{{ $med }}</span>
            @endforeach
        </div>
        @endif

        @if(count($recentProcs) > 0)
        <div class="sub-label">Recent Procedures</div>
        <div class="tag-list">
            @foreach($recentProcs as $rp)
            <span class="tag">{{ $rp }}</span>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- A — ASSESSMENT --}}
<div class="sbar-card card-a">
    <div class="sc-head">
        <span class="sc-letter">A</span>
        ASSESSMENT
    </div>
    <div class="sc-body">
        {{-- Vitals --}}
        <div class="vitals-row">
            <div class="vital-item">
                <div class="vl">BP</div>
                <div class="vv">{{ $vitals['bp'] ?? '—' }}</div>
            </div>
            <div class="vital-item">
                <div class="vl">Pulse</div>
                <div class="vv">{{ $vitals['pulse'] ?? '—' }}</div>
            </div>
            <div class="vital-item">
                <div class="vl">Temp</div>
                <div class="vv">{{ $vitals['temp'] ?? '—' }}</div>
            </div>
            <div class="vital-item">
                <div class="vl">SpO₂</div>
                <div class="vv">{{ $vitals['spo2'] ?? '—' }}</div>
            </div>
            <div class="vital-item">
                <div class="vl">RR</div>
                <div class="vv">{{ $vitals['rr'] ?? '—' }}</div>
            </div>
            <div class="vital-item">
                <div class="vl">Clinical Status</div>
                <div class="vv">
                    <span class="hov-badge" style="{{ $statusStyle }}">{{ $clinicalStatus ?: 'N/A' }}</span>
                </div>
            </div>
        </div>

        @if(count($activeProblems) > 0)
        <div class="sub-label">Active Problems</div>
        @foreach($activeProblems as $ap)
        <div class="kv"><span class="k">{{ $ap['problem'] ?? '' }}</span><span class="v" style="color:#0369A1;">{{ $ap['status'] ?? '' }}</span></div>
        @endforeach
        @endif

        @if(count($latestResults) > 0)
        <div class="sub-label" style="margin-top:2mm;">Latest Investigations</div>
        <table class="results-table">
            <thead><tr><th>Test</th><th>Result</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($latestResults as $lr)
                <tr>
                    <td style="font-weight:600;">{{ $lr['test'] ?? '' }}</td>
                    <td>{{ $lr['result'] ?? '' }}</td>
                    <td style="color:#64748B;">{{ $lr['date'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- R — RECOMMENDATION --}}
<div class="sbar-card card-r">
    <div class="sc-head">
        <span class="sc-letter">R</span>
        RECOMMENDATION
    </div>
    <div class="sc-body">
        @if(count($actions) > 0)
        <div class="sub-label">Actions Required</div>
        <table class="action-table">
            <thead><tr><th>Action</th><th>Priority</th><th>Responsible</th></tr></thead>
            <tbody>
                @foreach($actions as $act)
                @php $prio = $act['priority'] ?? 'Routine'; @endphp
                <tr>
                    <td>{{ $act['action'] ?? '' }}</td>
                    <td><span class="priority-badge" style="{{ $priorityColors[$prio] ?? 'background:#F1F5F9;color:#334155' }}">{{ $prio }}</span></td>
                    <td style="color:#64748B;">{{ $act['responsible'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if(count($monitoring) > 0)
        <div class="sub-label">Monitoring Required</div>
        @foreach($monitoring as $m)
        <div class="monitor-item">{{ $m }}</div>
        @endforeach
        @endif

        @if(count($escalation) > 0)
        <div class="sub-label" style="color:#991B1B;margin-top:2mm;">Escalation Criteria — Call Doctor If:</div>
        @foreach($escalation as $ec)
        <div class="escalation-item">{{ $ec }}</div>
        @endforeach
        @endif

        @if(count($expectedEvents) > 0)
        <div class="sub-label" style="margin-top:2mm;">Expected Events</div>
        @foreach($expectedEvents as $ev)
        <div class="expected-item">{{ $ev }}</div>
        @endforeach
        @endif

        @if(count($pendingResults) > 0)
        <div class="sub-label" style="margin-top:2mm;">Pending Results</div>
        @foreach($pendingResults as $pr)
        <div class="pending-item">{{ $pr }}</div>
        @endforeach
        @endif
    </div>
</div>

{{-- HANDOVER COMPLETE --}}
<div class="handover-footer">
    <span class="hov-badge" style="{{ $handoverComplete ? 'background:#D1FAE5;color:#065F46' : 'background:#FEE2E2;color:#991B1B' }}">
        Handover: {{ $handoverComplete ? 'COMPLETE' : 'INCOMPLETE' }}
    </span>
    @if(!empty($payload['questions_raised']))
    <span style="color:#374151;"><strong>Questions Raised:</strong> {{ $payload['questions_raised'] }}</span>
    @endif
</div>

{{-- SIGNATURES --}}
<div class="sig-grid">
    <div class="sig-box">
        <div class="sig-role">Handover — From</div>
        <div class="sig-name">{{ $payload['from_person'] ?? 'N/A' }}</div>
        <div class="signature-line"></div>
        <div style="font-size:8.5px;color:#94A3B8;">Signature</div>
    </div>
    <div class="sig-box">
        <div class="sig-role">Received — To</div>
        <div class="sig-name">{{ $payload['to_person'] ?? 'N/A' }}</div>
        <div class="signature-line"></div>
        <div style="font-size:8.5px;color:#94A3B8;">Signature (Receipt Confirmation)</div>
    </div>
</div>
@endsection
