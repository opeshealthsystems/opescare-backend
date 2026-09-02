@extends('documents.base')

@section('title', 'OCCUPATIONAL THERAPY ASSESSMENT')
@section('subtitle', 'ADL Assessment | Upper Limb Function | Cognitive Function | Home Adaptation')

@section('content')
@php
    $accentColor = '#0891B2';
    $accentLight = '#ECFEFF';
    $accentMid   = '#A5F3FC';

    $referralDiagnosis    = $payload['referral_diagnosis']    ?? '—';
    $assessmentDate       = $payload['assessment_date']       ?? '—';
    $sessionType          = $payload['session_type']          ?? 'Initial Assessment';
    $occupationalTherapist = $payload['occupational_therapist'] ?? '—';
    $otReg                = $payload['ot_reg']                ?? '—';
    $referringPhysician   = $payload['referring_physician']   ?? '—';
    $presentingProblems   = $payload['presenting_occupational_problems'] ?? '—';

    $adl           = $payload['adl_assessment']     ?? [];
    $ulFunction    = $payload['upper_limb_function'] ?? [];
    $cogAssessment = $payload['cognitive_assessment'] ?? [];
    $vocational    = $payload['vocational_assessment'] ?? null;
    $homeEnv       = $payload['home_environment']    ?? [];
    $problemsList  = $payload['problems_identified'] ?? [];
    $goals         = $payload['goals']               ?? [];
    $interventions = $payload['interventions']       ?? [];
    $splints       = $payload['splints_orthoses']    ?? [];
    $adaptiveEquip = $payload['adaptive_equipment']  ?? [];
    $homeRecs      = $payload['home_recommendations'] ?? [];
    $prognosis     = $payload['prognosis']           ?? '—';

    $adlActivities = [
        'feeding'           => 'Feeding',
        'grooming'          => 'Grooming',
        'bathing'           => 'Bathing',
        'upper_body_dressing' => 'Upper Body Dressing',
        'lower_body_dressing' => 'Lower Body Dressing',
        'toileting'         => 'Toileting',
        'transfers'         => 'Transfers',
        'meal_preparation'  => 'Meal Preparation',
    ];

    $fimColorFn = static function(int $score): string {
        if ($score <= 2) return '#FEE2E2';
        if ($score <= 4) return '#FEF3C7';
        return '#D1FAE5';
    };
    $fimTextFn = static function(int $score): string {
        if ($score <= 2) return '#991B1B';
        if ($score <= 4) return '#92400E';
        return '#065F46';
    };
    $fimLabelFn = static function(int $score): string {
        if ($score === 1) return 'Total Assist';
        if ($score === 2) return 'Maximal Assist';
        if ($score === 3) return 'Moderate Assist';
        if ($score === 4) return 'Minimal Assist';
        if ($score === 5) return 'Supervision';
        if ($score === 6) return 'Modified Indep.';
        return 'Independent';
    };

    $totalFim = (int)($adl['total_fim_adl'] ?? 0);
@endphp

<style>
    .ota-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 10px 14px;
        border-radius: 4px 4px 0 0;
    }
    .ota-header-strip h2 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 4px 0;
    }
    .ota-header-strip .ota-sub { font-size: 9.5px; opacity: 0.8; }

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
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: 600;
    }
    .badge-cyan   { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .badge-green  { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red    { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-amber  { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gray   { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }

    .adl-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .adl-table th {
        background: {{ $accentLight }};
        color: {{ $accentColor }};
        font-weight: 700;
        text-align: left;
        padding: 4px 8px;
        border-bottom: 1px solid {{ $accentMid }};
        font-size: 9px;
        text-transform: uppercase;
    }
    .adl-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: top;
    }
    .adl-table tr:last-child td { border-bottom: none; }
    .adl-table .activity-col { font-weight: 600; color: #374151; background: #FAFAFA; width: 24%; }

    .fim-score {
        display: inline-block;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        text-align: center;
        line-height: 22px;
        font-size: 10px;
        font-weight: 700;
    }

    .fim-total-box {
        background: {{ $accentLight }};
        border: 1px solid {{ $accentMid }};
        border-radius: 4px;
        padding: 6px 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ul-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; font-size: 10px; }
    .ul-item { padding: 4px 6px; background: #F9FAFB; border-radius: 3px; }
    .ul-label { font-weight: 600; color: #374151; font-size: 9px; margin-bottom: 2px; }
    .ul-value { color: #1F2937; }

    .cog-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .cog-table th {
        background: #F9FAFB;
        font-weight: 700;
        font-size: 9px;
        text-transform: uppercase;
        color: #374151;
        padding: 4px 8px;
        border-bottom: 1px solid #E5E7EB;
        text-align: left;
    }
    .cog-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
    }
    .cog-table tr:last-child td { border-bottom: none; }
    .cog-domain { font-weight: 600; color: #374151; width: 30%; background: #FAFAFA; }

    .goals-table { width: 100%; border-collapse: collapse; font-size: 10px; }
    .goals-table th {
        background: {{ $accentLight }};
        color: {{ $accentColor }};
        font-weight: 700;
        text-align: left;
        padding: 4px 8px;
        border-bottom: 1px solid {{ $accentMid }};
        font-size: 9px;
        text-transform: uppercase;
    }
    .goals-table td {
        padding: 5px 8px;
        border-bottom: 1px solid #F3F4F6;
        vertical-align: top;
    }
    .goals-table tr:last-child td { border-bottom: none; }

    .bullet-list { margin: 0; padding-left: 14px; }
    .bullet-list li { font-size: 10px; margin-bottom: 3px; color: #1F2937; }

    .sig-line {
        border-top: 1px solid #9CA3AF;
        padding-top: 4px;
        margin-top: 12mm;
        font-size: 9px;
        color: #374151;
    }
</style>

{{-- ── HEADER STRIP ─────────────────────────────────────────────── --}}
<div class="ota-header-strip">
    <h2>Occupational Therapy Assessment</h2>
    <div class="ota-sub">
        {{ $facility_name }} &nbsp;|&nbsp; {{ $assessmentDate }} &nbsp;|&nbsp; Ref. Dx: {{ $referralDiagnosis }}
    </div>
    <div style="margin-top:5px; display:flex; gap:6px; flex-wrap:wrap;">
        <span class="badge badge-cyan" style="font-size:10px; padding:3px 10px;">{{ $sessionType }}</span>
        <span style="font-size:9px; opacity:0.75;">Referring: {{ $referringPhysician }}</span>
    </div>
</div>

{{-- ── PRESENTING OCCUPATIONAL PROBLEMS ────────────────────────── --}}
<div class="section-card" style="margin-top:8px;">
    <div class="section-card-title">Presenting Occupational Problems</div>
    <div class="section-card-body">{{ $presentingProblems }}</div>
</div>

{{-- ── ADL ASSESSMENT ───────────────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        ADL Assessment — Functional Independence Measure (FIM Scale 1–7)
    </div>
    <div class="section-card-body" style="padding:0;">
        <table class="adl-table">
            <thead>
                <tr>
                    <th style="width:24%;">Activity</th>
                    <th>Method / Observation</th>
                    <th style="width:25%;">Aids / Equipment Needed</th>
                    <th style="width:12%; text-align:center;">FIM Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adlActivities as $aKey => $aLabel)
                @php
                    $aData  = $adl[$aKey] ?? [];
                    $aScore = (int)($aData['score'] ?? 0);
                    $aBg    = $fimColorFn($aScore);
                    $aText  = $fimTextFn($aScore);
                    $aLbl   = $fimLabelFn($aScore);
                @endphp
                <tr>
                    <td class="activity-col">{{ $aLabel }}</td>
                    <td>{{ $aData['method'] ?? '—' }}</td>
                    <td style="font-style:italic; color:#6B7280;">{{ $aData['aids_needed'] ?? 'None' }}</td>
                    <td style="text-align:center;">
                        @if($aScore > 0)
                        <span class="fim-score" style="background:{{ $aBg }}; color:{{ $aText }};">{{ $aScore }}</span>
                        <div style="font-size:8px; color:{{ $aText }}; margin-top:1px;">{{ $aLbl }}</div>
                        @else
                        <span style="color:#9CA3AF;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:6px 10px; border-top:1px solid #E5E7EB;">
            <div class="fim-total-box">
                <span style="font-size:9px; font-weight:600; color:{{ $accentColor }}; text-transform:uppercase;">Total FIM ADL Score:</span>
                @php
                    $totalBg   = $fimColorFn(max(1, (int)round($totalFim / 8)));
                    $totalText = $fimTextFn(max(1, (int)round($totalFim / 8)));
                @endphp
                <span style="font-size:14px; font-weight:700; color:{{ $totalText }};">{{ $totalFim }}</span>
                <span style="font-size:9px; color:#6B7280;">/ 56</span>
                <span style="font-size:8.5px; margin-left:8px; color:#6B7280; font-style:italic;">(FIM scale: 1=Total Assist → 7=Independent)</span>
            </div>
        </div>
    </div>
</div>

{{-- ── UPPER LIMB FUNCTION ──────────────────────────────────────── --}}
<div class="section-card">
    <div class="section-card-title">Upper Limb Function</div>
    <div class="section-card-body">
        @php $ul = is_array($ulFunction) ? $ulFunction : []; @endphp
        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:6px;">
            <span class="badge badge-cyan">Dominant hand: {{ $ul['dominant_hand'] ?? '—' }}</span>
            @if(!empty($ul['grip_strength_r']))
            <span class="badge badge-gray">Grip R: {{ $ul['grip_strength_r'] }}</span>
            @endif
            @if(!empty($ul['grip_strength_l']))
            <span class="badge badge-gray">Grip L: {{ $ul['grip_strength_l'] }}</span>
            @endif
            @if(!empty($ul['pinch_grip']))
            <span class="badge badge-gray">Pinch: {{ $ul['pinch_grip'] }}</span>
            @endif
        </div>
        <div class="ul-grid">
            <div class="ul-item"><div class="ul-label">Coordination</div><div class="ul-value">{{ $ul['coordination'] ?? '—' }}</div></div>
            <div class="ul-item"><div class="ul-label">Sensation</div><div class="ul-value">{{ $ul['sensation'] ?? '—' }}</div></div>
            @if(!empty($ul['spasticity']))
            <div class="ul-item"><div class="ul-label">Spasticity</div><div class="ul-value">{{ $ul['spasticity'] }}</div></div>
            @endif
            @if(!empty($ul['oedema']))
            <div class="ul-item"><div class="ul-label">Oedema</div><div class="ul-value">{{ $ul['oedema'] }}</div></div>
            @endif
        </div>
    </div>
</div>

{{-- ── COGNITIVE ASSESSMENT ─────────────────────────────────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Cognitive Assessment</div>
        <div class="section-card-body" style="padding:0;">
            @php
                $cog = is_array($cogAssessment) ? $cogAssessment : [];
                $cogDomains = [
                    'orientation'      => 'Orientation',
                    'attention'        => 'Attention',
                    'memory'           => 'Memory',
                    'problem_solving'  => 'Problem Solving',
                    'safety_awareness' => 'Safety Awareness',
                ];
            @endphp
            <table class="cog-table">
                <thead><tr><th>Domain</th><th>Finding</th></tr></thead>
                <tbody>
                    @foreach($cogDomains as $cKey => $cLabel)
                    <tr>
                        <td class="cog-domain">{{ $cLabel }}</td>
                        <td>{{ $cog[$cKey] ?? '—' }}</td>
                    </tr>
                    @endforeach
                    @if(!empty($cog['moca_score']))
                    <tr>
                        <td class="cog-domain">MoCA Score</td>
                        <td style="font-weight:600;">{{ $cog['moca_score'] }} / 30</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <div>
        {{-- Home environment --}}
        <div class="section-card">
            <div class="section-card-title">Home Environment</div>
            <div class="section-card-body">
                @php $home = is_array($homeEnv) ? $homeEnv : []; @endphp
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                    @if(!empty($home['type']))
                    <span class="badge badge-gray">Type: {{ $home['type'] }}</span>
                    @endif
                    @if(!empty($home['floor_level']))
                    <span class="badge badge-gray">Floor: {{ $home['floor_level'] }}</span>
                    @endif
                    @if(isset($home['stairs']))
                    <span class="badge {{ $home['stairs'] ? 'badge-amber' : 'badge-green' }}">
                        Stairs: {{ $home['stairs'] ? 'Yes' : 'No' }}
                    </span>
                    @endif
                </div>
                @if(!empty($home['bathroom_adaptations_needed']))
                <div style="font-size:9.5px; color:#374151; margin-top:4px;">
                    <span style="font-weight:600;">Bathroom adaptations needed:</span> {{ $home['bathroom_adaptations_needed'] }}
                </div>
                @endif
            </div>
        </div>
        {{-- Vocational --}}
        @if($vocational)
        <div class="section-card">
            <div class="section-card-title">Vocational Assessment</div>
            <div class="section-card-body">{{ $vocational }}</div>
        </div>
        @endif
    </div>
</div>

{{-- ── PROBLEMS IDENTIFIED ──────────────────────────────────────── --}}
@if(!empty($problemsList))
<div class="section-card">
    <div class="section-card-title">Problems Identified</div>
    <div class="section-card-body">
        <ul class="bullet-list">
            @foreach($problemsList as $prob)
            <li>{{ $prob }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- ── GOALS ─────────────────────────────────────────────────────── --}}
@if(!empty($goals))
<div class="section-card">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">Goals</div>
    <div class="section-card-body" style="padding:0;">
        <table class="goals-table">
            <thead>
                <tr>
                    <th>Goal</th>
                    <th style="width:20%;">Timeframe</th>
                    <th style="width:15%; text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($goals as $goal)
                <tr>
                    <td>{{ $goal['goal'] ?? '—' }}</td>
                    <td>{{ $goal['timeframe'] ?? '—' }}</td>
                    <td style="text-align:center;">
                        @php $achieved = (bool)($goal['achieved'] ?? false); @endphp
                        <span class="badge {{ $achieved ? 'badge-green' : 'badge-amber' }}">
                            {{ $achieved ? 'Achieved' : 'In Progress' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── INTERVENTIONS + SPLINTS + ADAPTIVE EQUIPMENT ───────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Interventions</div>
        <div class="section-card-body">
            @if(!empty($interventions))
            <ul class="bullet-list">
                @foreach($interventions as $intv)
                <li>
                    {{ $intv['intervention'] ?? $intv }}
                    @if(is_array($intv) && !empty($intv['frequency']))
                    <span style="color:#6B7280; font-size:9px;"> — {{ $intv['frequency'] }}</span>
                    @endif
                </li>
                @endforeach
            </ul>
            @else
            <span style="color:#9CA3AF; font-style:italic;">None recorded</span>
            @endif
        </div>
    </div>
    <div>
        @if(!empty($splints))
        <div class="section-card">
            <div class="section-card-title">Splints &amp; Orthoses</div>
            <div class="section-card-body">
                @foreach($splints as $sp)
                <div style="font-size:10px; margin-bottom:4px; border-bottom:1px solid #F3F4F6; padding-bottom:4px;">
                    <span style="font-weight:600;">{{ $sp['item'] ?? '—' }}</span>
                    @if(!empty($sp['purpose']))
                    <span style="color:#6B7280;"> — {{ $sp['purpose'] }}</span>
                    @endif
                    @if(!empty($sp['wearing_schedule']))
                    <div style="font-size:9px; color:#374151; margin-top:1px;">Schedule: {{ $sp['wearing_schedule'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @if(!empty($adaptiveEquip))
        <div class="section-card">
            <div class="section-card-title">Adaptive Equipment</div>
            <div class="section-card-body">
                <ul class="bullet-list">
                    @foreach($adaptiveEquip as $eq)
                    <li>{{ $eq }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── HOME RECOMMENDATIONS + PROGNOSIS + SIGNATURE ────────────── --}}
@if(!empty($homeRecs))
<div class="section-card">
    <div class="section-card-title">Home Recommendations</div>
    <div class="section-card-body">
        <ul class="bullet-list">
            @foreach($homeRecs as $rec)
            <li>{{ $rec }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="two-col" style="margin-bottom:0;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Prognosis</div>
        <div class="section-card-body">{{ $prognosis }}</div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Occupational Therapist</div>
        <div class="section-card-body">
            <div style="font-weight:600; font-size:11px;">{{ $occupationalTherapist }}</div>
            <div style="color:#6B7280; font-size:9.5px; margin-top:2px;">OT Reg: {{ $otReg }}</div>
            <div class="sig-line" style="margin-top:10mm;"><span style="color:#9CA3AF;">Signature</span></div>
        </div>
    </div>
</div>
@endsection
