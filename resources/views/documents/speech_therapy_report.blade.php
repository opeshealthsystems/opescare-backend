@extends('documents.base')

@section('title', 'SPEECH & LANGUAGE THERAPY REPORT')
@section('subtitle', 'Swallowing Assessment (Dysphagia) | Communication | Modified Diet Recommendations')

@section('content')
@php
    $accentColor = '#0F766E';
    $accentLight = '#F0FDFA';
    $accentMid   = '#99F6E4';

    $referralReason      = $payload['referral_reason']     ?? '—';
    $assessmentDate      = $payload['assessment_date']     ?? '—';
    $sessionType         = $payload['session_type']        ?? 'Initial Assessment';
    $speechTherapist     = $payload['speech_therapist']    ?? '—';
    $sltReg              = $payload['slt_reg']             ?? '—';
    $referringPhysician  = $payload['referring_physician'] ?? '—';
    $medicalDiagnosis    = $payload['medical_diagnosis']   ?? '—';
    $presentingComplaint = $payload['presenting_complaint'] ?? '—';

    $swallowing    = $payload['swallowing_assessment']    ?? [];
    $iddsi         = $payload['iddsi_framework']          ?? null;
    $fluidRec      = $payload['fluid_recommendation']     ?? null;
    $commAssess    = $payload['communication_assessment'] ?? [];
    $voiceAssess   = $payload['voice_assessment']         ?? null;
    $oralHygiene   = $payload['oral_hygiene']             ?? null;
    $ngtPegRec     = $payload['ngt_peg_recommendation']   ?? null;
    $problemsList  = $payload['problems_identified']      ?? [];
    $goals         = $payload['goals']                    ?? [];
    $interventions = $payload['interventions']            ?? [];
    $homeProgramme = $payload['home_programme']           ?? [];
    $caregiverEd   = $payload['caregiver_education']      ?? [];
    $reviewDate    = $payload['review_date']              ?? '—';
    $prognosis     = $payload['prognosis']                ?? '—';

    // IDDSI level colour-coding (0-7)
    $iddsiColors = [
        0 => ['bg' => '#FFFFFF', 'text' => '#111827', 'border' => '#D1D5DB'],
        1 => ['bg' => '#FEF9C3', 'text' => '#713F12', 'border' => '#FDE68A'],
        2 => ['bg' => '#FEF3C7', 'text' => '#92400E', 'border' => '#FCD34D'],
        3 => ['bg' => '#FED7AA', 'text' => '#9A3412', 'border' => '#FB923C'],
        4 => ['bg' => '#FCA5A5', 'text' => '#991B1B', 'border' => '#F87171'],
        5 => ['bg' => '#FBCFE8', 'text' => '#9D174D', 'border' => '#F472B6'],
        6 => ['bg' => '#DDD6FE', 'text' => '#4C1D95', 'border' => '#A78BFA'],
        7 => ['bg' => '#D1FAE5', 'text' => '#065F46', 'border' => '#6EE7B7'],
    ];

    $iddsiLevel   = (int)($swallowing['iddsi_level_recommended'] ?? ($iddsi['level'] ?? -1));
    $iddsiColors2 = $iddsiLevel >= 0 ? ($iddsiColors[$iddsiLevel] ?? $iddsiColors[0]) : null;

    $aspirationRisk = $swallowing['aspiration_risk'] ?? null;
    $aspirationClass = match(true) {
        $aspirationRisk === null                          => 'badge-gray',
        str_contains($aspirationRisk ?? '', 'High')      => 'badge-red',
        str_contains($aspirationRisk ?? '', 'Moderate')  => 'badge-amber',
        str_contains($aspirationRisk ?? '', 'Low')       => 'badge-amber',
        default                                           => 'badge-green',
    };

    $ngtPegClass = match(true) {
        str_contains($ngtPegRec ?? '', 'NGT')      => 'badge-red',
        str_contains($ngtPegRec ?? '', 'PEG')      => 'badge-red',
        str_contains($ngtPegRec ?? '', 'Oral')     => 'badge-green',
        default                                     => 'badge-amber',
    };
@endphp

<style>
    .slt-header-strip {
        background: {{ $accentColor }};
        color: #FFFFFF;
        padding: 10px 14px;
        border-radius: 4px 4px 0 0;
    }
    .slt-header-strip h2 {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 4px 0;
    }
    .slt-header-strip .slt-sub { font-size: 9.5px; opacity: 0.8; }

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
    .badge-teal  { background: {{ $accentLight }}; color: {{ $accentColor }}; border: 1px solid {{ $accentMid }}; }
    .badge-green { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .badge-red   { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .badge-amber { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .badge-gray  { background: #F3F4F6; color: #374151; border: 1px solid #D1D5DB; }

    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 8px; }

    .iddsi-badge-large {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        width: 22mm;
        height: 22mm;
        border-radius: 50%;
        border: 3px solid;
        font-weight: 800;
    }
    .iddsi-badge-large .iddsi-num  { font-size: 20px; line-height: 1; }
    .iddsi-badge-large .iddsi-name { font-size: 7px; text-align: center; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }

    .swallow-info { flex: 1; }
    .sw-row {
        display: flex;
        gap: 6px;
        align-items: flex-start;
        padding: 4px 0;
        border-bottom: 1px solid #F3F4F6;
        font-size: 10px;
    }
    .sw-row:last-child { border-bottom: none; }
    .sw-label { font-weight: 600; color: #374151; width: 40mm; flex-shrink: 0; }

    .ngt-peg-banner {
        border-radius: 4px;
        padding: 8px 14px;
        text-align: center;
        margin-bottom: 8px;
    }
    .ngt-peg-label { font-size: 9px; text-transform: uppercase; font-weight: 600; margin-bottom: 2px; }
    .ngt-peg-text  { font-size: 11px; font-weight: 700; }

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
<div class="slt-header-strip">
    <h2>Speech &amp; Language Therapy Report</h2>
    <div class="slt-sub">
        {{ $facility_name }} &nbsp;|&nbsp; {{ $assessmentDate }} &nbsp;|&nbsp; Dx: {{ $medicalDiagnosis }}
    </div>
    <div style="margin-top:5px; display:flex; gap:6px; flex-wrap:wrap;">
        <span class="badge badge-teal" style="font-size:10px; padding:3px 10px;">{{ $sessionType }}</span>
        <span class="badge badge-teal" style="font-size:10px; padding:3px 10px;">{{ $referralReason }}</span>
        <span style="font-size:9px; opacity:0.75;">Ref: {{ $referringPhysician }}</span>
    </div>
</div>

{{-- ── PRESENTING COMPLAINT ─────────────────────────────────────── --}}
<div class="section-card" style="margin-top:8px;">
    <div class="section-card-title">Presenting Complaint</div>
    <div class="section-card-body">{{ $presentingComplaint }}</div>
</div>

{{-- ── SWALLOWING ASSESSMENT ────────────────────────────────────── --}}
@if(!empty($swallowing['performed']))
<div class="section-card" style="border-color:{{ $accentColor }};">
    <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">
        Swallowing Assessment (Dysphagia)
    </div>
    <div class="section-card-body">
        <div style="display:flex; gap:12px; align-items:flex-start;">
            {{-- IDDSI badge --}}
            @if($iddsiColors2 !== null)
            <div class="iddsi-badge-large" style="background:{{ $iddsiColors2['bg'] }}; color:{{ $iddsiColors2['text'] }}; border-color:{{ $iddsiColors2['border'] }}; flex-shrink:0;">
                <span class="iddsi-num">{{ $iddsiLevel }}</span>
                <span class="iddsi-name">
                    {{ $iddsi['name'] ?? 'IDDSI Level '.$iddsiLevel }}
                </span>
            </div>
            @endif
            <div class="swallow-info">
                @if(!empty($swallowing['bedside_assessment']))
                <div class="sw-row">
                    <span class="sw-label">Bedside Assessment:</span>
                    <span>{{ $swallowing['bedside_assessment'] }}</span>
                </div>
                @endif
                @if(!empty($swallowing['videofluoroscopy']))
                <div class="sw-row">
                    <span class="sw-label">Videofluoroscopy:</span>
                    <span class="badge {{ $swallowing['videofluoroscopy'] === 'Performed' ? 'badge-teal' : 'badge-gray' }}">
                        {{ $swallowing['videofluoroscopy'] }}
                    </span>
                </div>
                @endif
                @if(!empty($swallowing['oral_phase']))
                <div class="sw-row">
                    <span class="sw-label">Oral Phase:</span>
                    <span>{{ $swallowing['oral_phase'] }}</span>
                </div>
                @endif
                @if(!empty($swallowing['pharyngeal_phase']))
                <div class="sw-row">
                    <span class="sw-label">Pharyngeal Phase:</span>
                    <span>{{ $swallowing['pharyngeal_phase'] }}</span>
                </div>
                @endif
                @if($aspirationRisk !== null)
                <div class="sw-row">
                    <span class="sw-label">Aspiration Risk:</span>
                    <span class="badge {{ $aspirationClass }}">{{ $aspirationRisk }}</span>
                    @if(str_contains($aspirationRisk ?? '', 'High'))
                    <span style="font-size:9px; color:#991B1B; font-weight:600; margin-left:4px;">&#9888; Silent aspiration risk — strict oral feeding precautions required</span>
                    @endif
                </div>
                @endif
                @if($fluidRec)
                <div class="sw-row">
                    <span class="sw-label">Fluid Recommendation:</span>
                    <span class="badge badge-teal">{{ $fluidRec }}</span>
                </div>
                @endif
            </div>
        </div>
        @if($iddsi !== null && !empty($iddsi['description']))
        <div style="margin-top:6px; background:{{ $iddsiColors2['bg'] ?? '#F9FAFB' }}; border:1px solid {{ $iddsiColors2['border'] ?? '#E5E7EB' }}; border-radius:4px; padding:6px 10px; font-size:9.5px; color:{{ $iddsiColors2['text'] ?? '#374151' }};">
            <span style="font-weight:600;">IDDSI Level {{ $iddsiLevel }} — {{ $iddsi['name'] ?? '' }}:</span> {{ $iddsi['description'] }}
        </div>
        @endif
    </div>
</div>
@endif

{{-- ── NGT/PEG RECOMMENDATION ───────────────────────────────────── --}}
@if($ngtPegRec)
<div class="ngt-peg-banner" style="background:{{ str_contains($ngtPegRec, 'NGT') || str_contains($ngtPegRec, 'PEG') ? '#FEE2E2' : '#D1FAE5' }}; border:2px solid {{ str_contains($ngtPegRec, 'NGT') || str_contains($ngtPegRec, 'PEG') ? '#F87171' : '#6EE7B7' }};">
    <div class="ngt-peg-label" style="color:{{ str_contains($ngtPegRec, 'NGT') || str_contains($ngtPegRec, 'PEG') ? '#991B1B' : '#065F46' }};">Feeding Route Recommendation</div>
    <div class="ngt-peg-text" style="color:{{ str_contains($ngtPegRec, 'NGT') || str_contains($ngtPegRec, 'PEG') ? '#991B1B' : '#065F46' }};">{{ $ngtPegRec }}</div>
</div>
@endif

{{-- ── COMMUNICATION ASSESSMENT ─────────────────────────────────── --}}
@if(!empty($commAssess['performed']))
<div class="section-card">
    <div class="section-card-title">Communication Assessment</div>
    <div class="section-card-body">
        <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:6px;">
            @if(!empty($commAssess['speech_intelligibility']))
            <span class="badge {{ str_contains($commAssess['speech_intelligibility'], 'Normal') ? 'badge-green' : (str_contains($commAssess['speech_intelligibility'], 'Severely') ? 'badge-red' : 'badge-amber') }}">
                Intelligibility: {{ $commAssess['speech_intelligibility'] }}
            </span>
            @endif
            @if(!empty($commAssess['aphasia_type']))
            <span class="badge badge-amber">Aphasia: {{ $commAssess['aphasia_type'] }}</span>
            @endif
        </div>
        @if(!empty($commAssess['language']))
        <div style="font-size:10px; color:#374151; margin-bottom:4px;"><span style="font-weight:600;">Language:</span> {{ $commAssess['language'] }}</div>
        @endif
        @if(!empty($commAssess['augmentative_communication']))
        <div style="font-size:10px; color:#374151;"><span style="font-weight:600;">AAC / Augmentative:</span> {{ $commAssess['augmentative_communication'] }}</div>
        @endif
    </div>
</div>
@endif

{{-- ── VOICE + ORAL HYGIENE ─────────────────────────────────────── --}}
@if($voiceAssess || $oralHygiene)
<div class="two-col">
    @if($voiceAssess)
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Voice Assessment</div>
        <div class="section-card-body">{{ $voiceAssess }}</div>
    </div>
    @endif
    @if($oralHygiene)
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Oral Hygiene</div>
        <div class="section-card-body">{{ $oralHygiene }}</div>
    </div>
    @endif
</div>
@endif

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

{{-- ── GOALS + INTERVENTIONS ────────────────────────────────────── --}}
<div class="two-col">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title" style="background:{{ $accentLight }}; color:{{ $accentColor }};">Goals</div>
        <div class="section-card-body" style="padding:0;">
            <table class="goals-table">
                <thead><tr><th>Goal</th><th style="width:25%;">Timeframe</th></tr></thead>
                <tbody>
                    @foreach($goals as $goal)
                    <tr>
                        <td>{{ $goal['goal'] ?? '—' }}</td>
                        <td>{{ $goal['timeframe'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Interventions</div>
        <div class="section-card-body">
            @if(!empty($interventions))
            <ul class="bullet-list">
                @foreach($interventions as $intv)
                <li>{{ $intv }}</li>
                @endforeach
            </ul>
            @else
            <span style="color:#9CA3AF; font-style:italic;">None recorded</span>
            @endif
        </div>
    </div>
</div>

{{-- ── CAREGIVER EDUCATION + HOME PROGRAMME ────────────────────── --}}
@if(!empty($caregiverEd) || !empty($homeProgramme))
<div class="two-col">
    @if(!empty($caregiverEd))
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Caregiver Education</div>
        <div class="section-card-body">
            <ul class="bullet-list">
                @foreach($caregiverEd as $ed)
                <li>{{ $ed }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
    @if(!empty($homeProgramme))
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Home Programme</div>
        <div class="section-card-body">
            <ul class="bullet-list">
                @foreach($homeProgramme as $hp)
                <li>{{ $hp }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ── REVIEW DATE + PROGNOSIS + SIGNATURE ─────────────────────── --}}
<div class="two-col" style="margin-bottom:0;">
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Review &amp; Prognosis</div>
        <div class="section-card-body">
            <div style="margin-bottom:4px;">
                <span style="font-size:9px; color:#6B7280;">Next review:</span>
                <span style="font-weight:600; color:{{ $accentColor }}; font-size:11px; margin-left:6px;">{{ $reviewDate }}</span>
            </div>
            <div style="font-size:10px; color:#374151;">{{ $prognosis }}</div>
        </div>
    </div>
    <div class="section-card" style="margin-bottom:0;">
        <div class="section-card-title">Speech &amp; Language Therapist</div>
        <div class="section-card-body">
            <div style="font-weight:600; font-size:11px;">{{ $speechTherapist }}</div>
            <div style="color:#6B7280; font-size:9.5px; margin-top:2px;">SLT Reg: {{ $sltReg }}</div>
            <div class="sig-line" style="margin-top:10mm;"><span style="color:#9CA3AF;">Signature</span></div>
        </div>
    </div>
</div>
@endsection
