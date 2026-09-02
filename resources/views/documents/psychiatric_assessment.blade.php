@extends('documents.base')

@section('content')
@php
    $accentColor = '#4F46E5';
    $accentLight = '#EEF2FF';
    $accentMid   = '#C7D2FE';
    $documentCode  = 'PSY';
    $documentTitle = 'Psychiatric Assessment Report';
@endphp

<style>
    /* ── PSY-specific overrides ───────────────────────────────────── */
    .psy-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 8px 12px;
        border-radius: 4px 4px 0 0;
        margin-bottom: 0;
    }
    .psy-header-strip h1 {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin: 0 0 3px 0;
        text-transform: uppercase;
    }
    .psy-header-strip .psy-sub {
        font-size: 9.5px;
        opacity: 0.85;
    }

    .section-card {
        border: 1px solid #E5E7EB;
        border-radius: 4px;
        margin-bottom: 8px;
        overflow: hidden;
    }
    .section-card-title {
        background: #F3F4F6;
        padding: 4px 10px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }
    .section-card-body {
        padding: 8px 10px;
        font-size: 10px;
        color: #1F2937;
        line-height: 1.5;
    }

    .badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }
    .badge-indigo { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .badge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gray   { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }
    .badge-blue   { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    .badge-capacity { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; font-size:11px; padding:4px 12px; }
    .badge-no-capacity { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; font-size:11px; padding:4px 12px; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px; }
    .four-col { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 6px; margin-bottom: 6px; }

    /* MSE table */
    .mse-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .mse-table th {
        background: {{ $accentLight }};
        padding: 4px 8px;
        text-align: left;
        font-weight: 700;
        color: {{ $accentColor }};
        border-bottom: 1px solid {{ $accentMid }};
        font-size: 9px;
        text-transform: uppercase;
    }
    .mse-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        color: #1F2937;
        vertical-align: top;
    }
    .mse-table tr:last-child td { border-bottom: none; }
    .mse-table .domain-cell {
        font-weight: 600;
        color: #374151;
        width: 30%;
        background: #FAFAFA;
    }

    /* Risk assessment */
    .risk-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .risk-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: middle;
    }
    .risk-table tr:last-child td { border-bottom: none; }
    .risk-label { font-weight: 600; color: #374151; width: 40%; }

    /* Diagnosis table */
    .diag-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .diag-table th {
        background: #F9FAFB;
        padding: 4px 8px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
    }
    .diag-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: middle;
    }
    .diag-table tr:last-child td { border-bottom: none; }

    /* Capacity check */
    .cap-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        border-bottom: 1px solid #F3F4F6;
    }
    .cap-item:last-child { border-bottom: none; }
    .cap-check {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .cap-yes { background: #D1FAE5; color: #065F46; }
    .cap-no  { background: #FEE2E2; color: #991B1B; }
    .cap-text { font-size: 10px; color: #1F2937; flex: 1; }

    /* Mgmt list */
    .mgmt-list { margin: 0; padding-left: 16px; }
    .mgmt-list li { font-size: 10px; margin-bottom: 3px; color: #1F2937; }

    /* Confidentiality notice */
    .confidentiality-notice {
        background: #FFF7ED;
        border: 1px solid #FED7AA;
        border-radius: 4px;
        padding: 8px 12px;
        margin-top: 8px;
        font-size: 9px;
        color: #92400E;
        text-align: center;
        font-style: italic;
    }

    .overall-risk-banner {
        padding: 6px 12px;
        border-radius: 4px;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 6px;
    }
    .risk-low    { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .risk-medium { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .risk-high   { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
</style>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- PSY HEADER STRIP                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="psy-header-strip">
    <h1>Psychiatric Assessment Report</h1>
    <div class="psy-sub">{{ $facility_name }} &nbsp;|&nbsp; {{ $payload['assessment_date'] }}</div>
</div>

{{-- Assessment type + referral --}}
<div style="background:{{ $accentLight }}; border:1px solid {{ $accentMid }}; border-top:none; border-radius:0 0 4px 4px; padding:6px 12px; display:flex; gap:8px; align-items:center; margin-bottom:8px;">
    <span class="badge badge-indigo">{{ $payload['assessment_type'] }}</span>
    <span style="font-size:9px; color:#6B7280;">Referral source:</span>
    <span class="badge badge-blue">{{ $payload['referral_source'] }}</span>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- PRESENTING COMPLAINT + HPI                                         --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Presenting Complaint</div>
        <div class="section-card-body">{{ $payload['presenting_complaint'] }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">History of Presenting Illness</div>
        <div class="section-card-body">{{ $payload['history_of_presenting_illness'] }}</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- HISTORY CARDS                                                      --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Psychiatric History</div>
        <div class="section-card-body">{{ $payload['psychiatric_history'] }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Substance Use</div>
        <div class="section-card-body">{{ $payload['substance_use'] }}</div>
    </div>
</div>
<div class="two-col" style="margin-top:6px;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Family Psychiatric History</div>
        <div class="section-card-body">{{ $payload['family_psychiatric_history'] }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Forensic History</div>
        <div class="section-card-body">{{ $payload['forensic_history'] }}</div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MENTAL STATUS EXAMINATION (MSE)                                    --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php
    $mse = $payload['mse'];
    $mseDomains = [
        'appearance'    => 'Appearance',
        'behaviour'     => 'Behaviour',
        'speech'        => 'Speech',
        'mood'          => 'Mood',
        'affect'        => 'Affect',
        'thought_form'  => 'Thought Form',
        'thought_content' => 'Thought Content',
        'perceptions'   => 'Perceptions',
        'cognition'     => 'Cognition',
        'insight'       => 'Insight',
        'judgement'     => 'Judgement',
    ];
@endphp
<div class="section-card" style="margin-top:8px;">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Mental Status Examination (MSE)
    </div>
    <div class="section-card-body" style="padding:0;">
        <table class="mse-table">
            <thead>
                <tr>
                    <th style="width:28%;">Domain</th>
                    <th>Finding</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mseDomains as $key => $label)
                <tr>
                    <td class="domain-cell">{{ $label }}</td>
                    <td>{{ $mse[$key] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- RISK ASSESSMENT                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php
    $risk = $payload['risk_assessment'];
    $overallRiskClass = match(strtolower($risk['overall_risk'] ?? 'low')) {
        'high'   => 'risk-high',
        'medium' => 'risk-medium',
        default  => 'risk-low',
    };
    $siClass = str_contains(strtolower($risk['suicidal_ideation'] ?? ''), 'active') ? 'badge-red'
             : (str_contains(strtolower($risk['suicidal_ideation'] ?? ''), 'present') ? 'badge-amber'
             : 'badge-green');
    $hiClass = str_contains(strtolower($risk['homicidal_ideation'] ?? ''), 'present') ? 'badge-red' : 'badge-green';
    $shClass = str_contains(strtolower($risk['self_harm'] ?? ''), 'history') ? 'badge-amber'
             : (str_contains(strtolower($risk['self_harm'] ?? ''), 'present') ? 'badge-red' : 'badge-green');
@endphp
<div class="section-card">
    <div class="section-card-title" style="color:#991B1B;">Risk Assessment</div>
    <div class="section-card-body" style="padding:0;">
        <table class="risk-table">
            <tr>
                <td class="risk-label">Suicidal Ideation</td>
                <td><span class="badge {{ $siClass }}">{{ $risk['suicidal_ideation'] }}</span></td>
            </tr>
            <tr>
                <td class="risk-label">Homicidal Ideation</td>
                <td><span class="badge {{ $hiClass }}">{{ $risk['homicidal_ideation'] }}</span></td>
            </tr>
            <tr>
                <td class="risk-label">Self-Harm</td>
                <td><span class="badge {{ $shClass }}">{{ $risk['self_harm'] }}</span></td>
            </tr>
        </table>
        <div style="padding:6px 10px;">
            <div class="overall-risk-banner {{ $overallRiskClass }}">
                Overall Risk Level: {{ $risk['overall_risk'] }}
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- DIAGNOSIS                                                          --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="section-card">
    <div class="section-card-title">Diagnosis</div>
    <div class="section-card-body" style="padding:0;">
        <table class="diag-table">
            <thead>
                <tr>
                    <th style="width:18%;">Type</th>
                    <th>Diagnosis</th>
                    <th style="width:20%;">ICD-11 Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['diagnosis'] as $diag)
                <tr>
                    <td>
                        <span class="badge {{ $diag['type'] === 'Primary' ? 'badge-indigo' : 'badge-gray' }}">
                            {{ $diag['type'] }}
                        </span>
                    </td>
                    <td style="font-weight:{{ $diag['type'] === 'Primary' ? '600' : '400' }};">
                        {{ $diag['diagnosis'] }}
                    </td>
                    <td style="font-family:monospace; font-size:9.5px; color:#374151;">
                        {{ $diag['icd11_code'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- CAPACITY ASSESSMENT                                                --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@php
    $cap = $payload['capacity_assessment'];
    $capItems = [
        'understands'  => 'Patient understands the relevant information',
        'retains'      => 'Patient retains the information long enough to make a decision',
        'weighs'       => 'Patient weighs up the information to make a decision',
        'communicates' => 'Patient communicates their decision',
    ];
@endphp
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Capacity Assessment (Mental Capacity Act Criteria)
    </div>
    <div class="section-card-body" style="padding:0;">
        @foreach($capItems as $key => $label)
        <div class="cap-item">
            <div class="cap-check {{ $cap[$key] ? 'cap-yes' : 'cap-no' }}">
                {{ $cap[$key] ? '✓' : '✗' }}
            </div>
            <div class="cap-text">{{ $label }}</div>
            <span class="badge {{ $cap[$key] ? 'badge-green' : 'badge-red' }}">
                {{ $cap[$key] ? 'Met' : 'Not Met' }}
            </span>
        </div>
        @endforeach
        <div style="padding:8px 10px; text-align:center; border-top:1px solid #E5E7EB;">
            <span class="badge {{ $cap['has_capacity'] ? 'badge-capacity' : 'badge-no-capacity' }}">
                {{ $cap['has_capacity'] ? 'CAPACITY CONFIRMED' : 'LACKS CAPACITY' }}
            </span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- FORMULATION                                                        --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="section-card">
    <div class="section-card-title">Biopsychosocial Formulation</div>
    <div class="section-card-body">{{ $payload['formulation'] }}</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MANAGEMENT PLAN + MEDICATIONS + FOLLOW-UP                         --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Management Plan</div>
        <div class="section-card-body">
            <ul class="mgmt-list">
                @foreach($payload['management_plan'] as $step)
                <li>{{ $step }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Medications Recommended</div>
        <div class="section-card-body">
            @if(!empty($payload['medications_recommended']))
            <ul class="mgmt-list">
                @foreach($payload['medications_recommended'] as $med)
                <li>{{ $med }}</li>
                @endforeach
            </ul>
            @else
            <span style="color:#9CA3AF; font-style:italic;">None at this time</span>
            @endif
        </div>
    </div>
</div>

<div class="section-card" style="margin-top:6px;">
    <div class="section-card-title">Follow-Up</div>
    <div class="section-card-body" style="font-weight:600; color:{{ $accentColor }};">
        {{ $payload['follow_up'] }}
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- CONFIDENTIALITY NOTICE                                             --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="confidentiality-notice">
    This report contains sensitive psychiatric information.
    Disclosure is restricted in accordance with the Cameroon Mental Health Act and Law No. 2010/012 on the Protection of Personal Data.
    Unauthorised disclosure is a criminal offence.
</div>
@endsection
