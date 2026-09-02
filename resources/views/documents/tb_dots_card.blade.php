@extends('documents.base')

@section('content')
@php
    $accentColor   = '#B45309';
    $accentLight   = '#FEF3C7';
    $accentMid     = '#FDE68A';
    $documentCode  = 'DOTS';
    $documentTitle = 'Tuberculosis Treatment Card — DOTS Programme';
@endphp

<style>
    /* ── DOTS-specific overrides ───────────────────────────────────── */
    .dots-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 8px 12px;
        border-radius: 4px 4px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0;
    }
    .dots-header-strip h1 {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin: 0;
        text-transform: uppercase;
    }
    .dots-header-strip .dots-meta {
        font-size: 10px;
        text-align: right;
    }
    .dots-header-strip .dots-meta strong {
        display: block;
        font-size: 12px;
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
    }

    /* Regimen card */
    .regimen-card {
        background: {{ $accentLight }};
        border: 1px solid {{ $accentMid }};
        border-radius: 4px;
        padding: 8px 10px;
        margin-bottom: 8px;
    }
    .regimen-card .regimen-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        color: {{ $accentColor }};
        margin-bottom: 3px;
    }
    .regimen-card .regimen-text {
        font-size: 11px;
        font-weight: 600;
        color: #1C1917;
    }
    .regimen-card .tb-type {
        font-size: 10px;
        color: #44403C;
        margin-bottom: 4px;
    }

    /* Timeline */
    .timeline-row {
        display: flex;
        align-items: flex-start;
        gap: 0;
        margin-bottom: 8px;
    }
    .tl-node {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        position: relative;
    }
    .tl-node:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 8px;
        left: 50%;
        right: -50%;
        height: 2px;
        background: {{ $accentColor }};
        z-index: 0;
    }
    .tl-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: {{ $accentColor }};
        border: 2px solid #FFFFFF;
        box-shadow: 0 0 0 2px {{ $accentColor }};
        z-index: 1;
        margin-bottom: 4px;
        flex-shrink: 0;
    }
    .tl-label {
        font-size: 8.5px;
        font-weight: 600;
        text-align: center;
        color: #374151;
        line-height: 1.2;
    }
    .tl-date {
        font-size: 8px;
        color: #6B7280;
        text-align: center;
    }

    /* Badge */
    .badge {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }
    .badge-amber { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .badge-green { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red   { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-blue  { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    .badge-gray  { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }
    .badge-indigo { background: #EEF2FF; color: #3730A3; border: 1px solid #C7D2FE; }

    /* Tables */
    .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .data-table th {
        background: #F9FAFB;
        padding: 4px 6px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #E5E7EB;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .data-table td {
        padding: 4px 6px;
        border-bottom: 1px solid #F3F4F6;
        color: #1F2937;
        vertical-align: middle;
    }
    .data-table tr:last-child td { border-bottom: none; }

    /* Adherence grid */
    .adh-cell {
        text-align: center;
        padding: 5px 4px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: 600;
    }
    .adh-green  { background: #D1FAE5; color: #065F46; }
    .adh-yellow { background: #FEF3C7; color: #92400E; }
    .adh-red    { background: #FEE2E2; color: #991B1B; }
    .adh-na     { background: #F3F4F6; color: #9CA3AF; }

    /* Two-column grid */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-bottom: 6px; }

    /* kv rows */
    .kv-row { display: flex; gap: 4px; align-items: baseline; margin-bottom: 3px; }
    .kv-label { font-size: 9px; font-weight: 600; color: #6B7280; text-transform: uppercase; min-width: 110px; }
    .kv-value { font-size: 10px; color: #111827; }

    .divider { border: none; border-top: 1px solid #E5E7EB; margin: 8px 0; }

    .outcome-banner {
        padding: 8px 12px;
        border-radius: 4px;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .outcome-completed  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .outcome-ontreatment { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    .outcome-defaulted  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .outcome-failed     { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .outcome-died       { background: #1F2937; color: #F9FAFB; border: 1px solid #374151; }

    .programme-note {
        font-size: 9px;
        color: #6B7280;
        margin-top: 4px;
        text-align: center;
        font-style: italic;
    }
</style>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- DOTS HEADER STRIP                                                  --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="dots-header-strip">
    <div>
        <h1>Tuberculosis Treatment Card — Programme DOTS</h1>
        <div style="font-size:9px; margin-top:3px; opacity:0.85;">
            MINSANTE / WHO Directly Observed Therapy, Short-Course &nbsp;|&nbsp; {{ $facility_name }}
        </div>
    </div>
    <div class="dots-meta">
        <strong>{{ $payload['tb_registration_number'] }}</strong>
        <span class="badge badge-amber" style="margin-top:3px; display:inline-block;">
            {{ $payload['treatment_category'] }}
        </span>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- TB TYPE + REGIMEN                                                  --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="regimen-card" style="border-radius: 0 0 4px 4px; margin-top:0;">
    <div class="regimen-label">Diagnosis &amp; Regimen</div>
    <div class="tb-type">{{ $payload['tb_type'] }}</div>
    <div class="regimen-text">{{ $payload['regimen'] }}</div>
    <div style="margin-top:5px; display:flex; gap:8px; flex-wrap:wrap;">
        <div style="font-size:9px; color:#78716C;">
            <span style="font-weight:600;">Intensive:</span> {{ $payload['intensive_phase_months'] }} months
        </div>
        <div style="font-size:9px; color:#78716C;">
            <span style="font-weight:600;">Continuation:</span> {{ $payload['continuation_phase_months'] }} months
        </div>
        <div style="font-size:9px; color:#78716C;">
            <span style="font-weight:600;">Initial Weight:</span> {{ $payload['initial_weight_kg'] }} kg
        </div>
        <div style="font-size:9px; color:#78716C;">
            <span style="font-weight:600;">Diagnosis:</span> {{ $payload['diagnosis_date'] }}
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- TREATMENT TIMELINE                                                  --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="section-card" style="margin-bottom:8px;">
    <div class="section-card-title">Treatment Timeline</div>
    <div class="section-card-body">
        <div class="timeline-row">
            <div class="tl-node">
                <div class="tl-dot"></div>
                <div class="tl-label">Treatment Start</div>
                <div class="tl-date">{{ $payload['treatment_start_date'] }}</div>
            </div>
            <div class="tl-node">
                <div class="tl-dot"></div>
                <div class="tl-label">End of Intensive Phase</div>
                <div class="tl-date">Month {{ $payload['intensive_phase_months'] }}</div>
            </div>
            <div class="tl-node">
                <div class="tl-dot"></div>
                <div class="tl-label">End of Continuation Phase</div>
                <div class="tl-date">Month {{ $payload['intensive_phase_months'] + $payload['continuation_phase_months'] }}</div>
            </div>
            <div class="tl-node">
                <div class="tl-dot" style="background:#065F46; box-shadow: 0 0 0 2px #065F46;"></div>
                <div class="tl-label">Expected End</div>
                <div class="tl-date">{{ $payload['expected_end_date'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- HIV STATUS + COMORBIDITIES                                         --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div style="display:flex; gap:6px; margin-bottom:8px; flex-wrap:wrap; align-items:center;">
    <span style="font-size:9px; font-weight:700; color:#374151; text-transform:uppercase;">Co-factors:</span>

    @php
        $hivBadge = match($payload['hiv_status']) {
            'Positive' => 'badge-red',
            'Negative' => 'badge-green',
            default    => 'badge-gray',
        };
    @endphp
    <span class="badge {{ $hivBadge }}">HIV: {{ $payload['hiv_status'] }}</span>

    @if($payload['diabetes_comorbidity'])
        <span class="badge badge-amber">Diabetes: Yes</span>
    @else
        <span class="badge badge-gray">Diabetes: No</span>
    @endif

    <span class="badge {{ $payload['contact_tracing_done'] ? 'badge-green' : 'badge-red' }}">
        Contact Tracing: {{ $payload['contact_tracing_done'] ? 'Done' : 'Pending' }}
    </span>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- SMEAR RESULTS + WEIGHT MONITORING (side by side)                  --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Smear Results</div>
        <div class="section-card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['smear_results'] as $smear)
                    <tr>
                        <td>{{ $smear['month'] }}</td>
                        <td>
                            @php
                                $isNeg = strtolower($smear['result']) === 'negative';
                            @endphp
                            <span class="badge {{ $isNeg ? 'badge-green' : 'badge-red' }}">
                                {{ $smear['result'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Weight Monitoring</div>
        <div class="section-card-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Weight (kg)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payload['weight_monitoring'] as $wt)
                    <tr>
                        <td>{{ $wt['date'] }}</td>
                        <td style="font-weight:600;">{{ $wt['weight_kg'] }} kg</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- MONTHLY ADHERENCE TRACKING — KEY VISUAL                           --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="section-card" style="margin-top:8px;">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Monthly Adherence Tracking
    </div>
    <div class="section-card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th style="text-align:center;">Doses Expected</th>
                    <th style="text-align:center;">Doses Taken</th>
                    <th style="text-align:center;">Adherence %</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payload['monthly_adherence'] as $adh)
                @php
                    $pct = (float)$adh['adherence_pct'];
                    $cellClass = $pct >= 90 ? 'adh-green' : ($pct >= 70 ? 'adh-yellow' : 'adh-red');
                    if (!isset($adh['doses_expected']) || $adh['doses_expected'] == 0) {
                        $cellClass = 'adh-na';
                    }
                @endphp
                <tr>
                    <td style="font-weight:600;">{{ $adh['month_label'] }}</td>
                    <td style="text-align:center;">{{ $adh['doses_expected'] }}</td>
                    <td style="text-align:center;">{{ $adh['doses_taken'] }}</td>
                    <td style="text-align:center;">
                        <div class="adh-cell {{ $cellClass }}">
                            {{ $adh['doses_expected'] > 0 ? $pct . '%' : '—' }}
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge {{ $pct >= 90 ? 'badge-green' : ($pct >= 70 ? 'badge-amber' : 'badge-red') }}" style="{{ $adh['doses_expected'] == 0 ? 'opacity:0.4;' : '' }}">
                            {{ $adh['status'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:5px 10px; display:flex; gap:10px; background:#FAFAFA; border-top:1px solid #F3F4F6;">
            <span style="font-size:8.5px; color:#6B7280;">
                <span class="adh-cell adh-green" style="display:inline-block; padding:1px 6px;">≥ 90%</span> Good
            </span>
            <span style="font-size:8.5px; color:#6B7280;">
                <span class="adh-cell adh-yellow" style="display:inline-block; padding:1px 6px;">70–89%</span> Moderate
            </span>
            <span style="font-size:8.5px; color:#6B7280;">
                <span class="adh-cell adh-red" style="display:inline-block; padding:1px 6px;">< 70%</span> Poor
            </span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- ADVERSE EFFECTS                                                    --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if(!empty($payload['adverse_effects']))
<div class="section-card">
    <div class="section-card-title" style="color:#991B1B;">Adverse Effects Reported</div>
    <div class="section-card-body">
        <ul style="margin:0; padding-left:16px;">
            @foreach($payload['adverse_effects'] as $effect)
            <li style="font-size:10px; margin-bottom:2px; color:#1F2937;">{{ $effect }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- OUTCOME BADGE                                                      --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if(!empty($payload['outcome']))
@php
    $outcomeClass = match($payload['outcome']) {
        'Treatment Completed' => 'outcome-completed',
        'On Treatment'        => 'outcome-ontreatment',
        'Defaulted'           => 'outcome-defaulted',
        'Failed'              => 'outcome-failed',
        'Died'                => 'outcome-died',
        default               => 'outcome-ontreatment',
    };
@endphp
<div class="outcome-banner {{ $outcomeClass }}">
    Treatment Outcome: {{ $payload['outcome'] }}
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- DOTS SUPPORTER + TREATMENT OFFICER                                 --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="two-col" style="margin-top:4px;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Community DOT Supporter</div>
        <div class="section-card-body">
            <div class="kv-row">
                <span class="kv-label">Supporter Name</span>
                <span class="kv-value" style="font-weight:600;">{{ $payload['dots_supporter'] }}</span>
            </div>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Treatment Officer</div>
        <div class="section-card-body">
            <div class="kv-row">
                <span class="kv-label">Officer Name</span>
                <span class="kv-value" style="font-weight:600;">{{ $payload['treatment_officer'] }}</span>
            </div>
        </div>
    </div>
</div>

<p class="programme-note">
    This card is issued under the MINSANTE DOTS Programme in accordance with WHO TB Management Guidelines.
    Retain for the full duration of treatment ({{ $payload['intensive_phase_months'] + $payload['continuation_phase_months'] }} months).
</p>
@endsection
