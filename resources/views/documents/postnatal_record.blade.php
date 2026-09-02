@extends('documents.base')

@section('title', 'Postnatal Care Visit Record')
@section('subtitle', 'PNC — WHO 4-Visit Postnatal Care Programme')

@section('content')
@php
    $accentColor     = '#DB2777';
    $deliveryDate    = $payload['delivery_date']       ?? '—';
    $deliveryType    = $payload['delivery_type']       ?? '—';
    $babySex         = $payload['baby_sex']            ?? '—';
    $birthWeight     = $payload['birth_weight_kg']     ?? '—';
    $pncVisits       = $payload['pnc_visits']          ?? [];
    $dischargeAdvice = $payload['discharge_advice_given'] ?? [];

    $lochiaColors = [
        'Rubra'     => '#dc2626',
        'Serosa'    => '#d97706',
        'Alba'      => '#d1d5db',
        'Excessive' => '#7c3aed',
        'Absent'    => '#6b7280',
    ];
    $bfColors = [
        'Exclusive'    => '#15803d',
        'Mixed'        => '#0369a1',
        'Formula only' => '#d97706',
        'Stopped'      => '#dc2626',
    ];
    $depressionColors = [
        'Negative (EPDS < 10)' => '#15803d',
        'Positive — referred'  => '#dc2626',
        'Not done'             => '#6b7280',
    ];
@endphp

{{-- ── Section 1: Delivery Summary Header ── --}}
<div style="background:{{ $accentColor }};color:#fff;padding:14px 20px;border-radius:6px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-size:13px;"><strong>Delivery Date:</strong> {{ $deliveryDate }}</span>
        <span style="background:rgba(0,0,0,.25);padding:3px 10px;border-radius:4px;font-size:12px;font-weight:700;">
            {{ $deliveryType }}
        </span>
        <span style="background:{{ $babySex === 'Male' ? '#1d4ed8' : '#be185d' }};padding:3px 10px;border-radius:4px;font-size:12px;font-weight:700;">
            Baby: {{ $babySex }}
        </span>
        <span style="background:rgba(0,0,0,.2);padding:3px 10px;border-radius:4px;font-size:12px;">
            Birth Weight: <strong>{{ $birthWeight }} kg</strong>
        </span>
    </div>
</div>

{{-- ── Section 2: PNC Visit Cards ── --}}
@foreach($pncVisits as $visit)
@php
    $vn      = $visit['visit_number']   ?? '?';
    $vd      = $visit['visit_date']     ?? '—';
    $dpp     = $visit['days_postpartum']?? '—';
    $vitals  = $visit['maternal_vitals']?? [];
    $lochia  = $visit['lochia']         ?? '—';
    $lColor  = $lochiaColors[$lochia]   ?? '#6b7280';
    $bf      = $visit['breastfeeding']  ?? '—';
    $bfColor = $bfColors[$bf]           ?? '#6b7280';
    $dep     = $visit['depression_screen'] ?? 'Not done';
    $depCol  = $depressionColors[$dep]  ?? '#6b7280';
    $fp      = $visit['family_planning_discussed'] ?? false;
    $fpM     = $visit['fp_method_chosen'] ?? null;
    $immuns  = $visit['immunizations_given'] ?? [];
    $probs   = $visit['problems']       ?? [];
    $mgmt    = $visit['management']     ?? [];
@endphp
<div style="border:1px solid #fbcfe8;border-radius:6px;margin-bottom:16px;overflow:hidden;">
    {{-- Visit header --}}
    <div style="background:#fce7f3;padding:10px 16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;border-bottom:1px solid #fbcfe8;">
        <span style="background:{{ $accentColor }};color:#fff;padding:4px 14px;border-radius:20px;font-weight:700;font-size:13px;">
            Visit {{ $vn }} / 4
        </span>
        <span style="font-size:13px;font-weight:600;">{{ $vd }}</span>
        <span style="background:#f9a8d4;color:#9d174d;padding:3px 10px;border-radius:12px;font-size:12px;">
            Day {{ $dpp }} postpartum
        </span>
    </div>

    <div style="padding:14px 16px;">
        {{-- Maternal Vitals --}}
        <div style="background:#fdf2f8;border-radius:4px;padding:8px 12px;margin-bottom:12px;display:flex;gap:20px;flex-wrap:wrap;">
            <span style="font-size:12px;"><strong>BP:</strong> {{ $vitals['bp'] ?? '—' }}</span>
            <span style="font-size:12px;"><strong>Pulse:</strong> {{ $vitals['pulse'] ?? '—' }}</span>
            <span style="font-size:12px;"><strong>Temp:</strong> {{ $vitals['temp'] ?? '—' }}</span>
        </div>

        {{-- Uterine / Lochia / Wound row --}}
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
            <div style="flex:1;min-width:140px;background:#fff0f5;border:1px solid #fbcfe8;border-radius:4px;padding:8px 10px;">
                <p style="font-size:10px;color:#9d174d;text-transform:uppercase;margin:0 0 4px;font-weight:700;">Uterine Involution</p>
                <p style="font-size:12px;margin:0;">{{ $visit['uterine_involution'] ?? '—' }}</p>
            </div>
            <div style="flex:1;min-width:140px;background:#fff0f5;border:1px solid #fbcfe8;border-radius:4px;padding:8px 10px;">
                <p style="font-size:10px;color:#9d174d;text-transform:uppercase;margin:0 0 4px;font-weight:700;">Lochia</p>
                <span style="background:{{ $lColor }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">{{ $lochia }}</span>
            </div>
            <div style="flex:1;min-width:140px;background:#fff0f5;border:1px solid #fbcfe8;border-radius:4px;padding:8px 10px;">
                <p style="font-size:10px;color:#9d174d;text-transform:uppercase;margin:0 0 4px;font-weight:700;">Perineal Wound</p>
                <p style="font-size:12px;margin:0;">{{ $visit['perineal_wound'] ?? '—' }}</p>
            </div>
            @if(($deliveryType ?? '') === 'Caesarean')
            <div style="flex:1;min-width:140px;background:#fff0f5;border:1px solid #fbcfe8;border-radius:4px;padding:8px 10px;">
                <p style="font-size:10px;color:#9d174d;text-transform:uppercase;margin:0 0 4px;font-weight:700;">C/S Wound</p>
                <p style="font-size:12px;margin:0;">{{ $visit['cs_wound'] ?? '—' }}</p>
            </div>
            @endif
        </div>

        {{-- Breastfeeding --}}
        <div style="margin-bottom:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:12px;font-weight:600;">Breastfeeding:</span>
            <span style="background:{{ $bfColor }};color:#fff;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">
                {{ $bf }}
            </span>
            <span style="font-size:12px;color:#6b7280;">{{ $visit['breast_condition'] ?? '' }}</span>
        </div>

        {{-- Edinburgh Depression Screen --}}
        <div style="margin-bottom:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:12px;font-weight:600;">Edinburgh Depression Screen:</span>
            <span style="background:{{ $depCol }};color:#fff;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">
                {{ $dep }}
            </span>
        </div>

        {{-- Baby --}}
        @if(!empty($visit['baby_weight_kg']) || !empty($visit['baby_condition']))
        <div style="background:#fdf2f8;border-radius:4px;padding:8px 12px;margin-bottom:10px;display:flex;gap:20px;flex-wrap:wrap;">
            @if(!empty($visit['baby_weight_kg']))
            <span style="font-size:12px;"><strong>Baby Weight:</strong> {{ $visit['baby_weight_kg'] }} kg</span>
            @endif
            @if(!empty($visit['baby_condition']))
            <span style="font-size:12px;"><strong>Baby Condition:</strong> {{ $visit['baby_condition'] }}</span>
            @endif
        </div>
        @endif

        {{-- Family Planning --}}
        <div style="margin-bottom:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:12px;font-weight:600;">Family Planning Discussed:</span>
            @if($fp)
                <span style="background:#15803d;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">Yes</span>
                @if($fpM)
                    <span style="font-size:12px;color:#374151;">Method: <strong>{{ $fpM }}</strong></span>
                @endif
            @else
                <span style="background:#6b7280;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">Not yet</span>
            @endif
        </div>

        {{-- Immunizations --}}
        @if(count($immuns) > 0)
        <div style="margin-bottom:10px;">
            <span style="font-size:12px;font-weight:600;">Immunizations Given: </span>
            @foreach($immuns as $imm)
                <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:10px;font-size:11px;margin-right:4px;">{{ $imm }}</span>
            @endforeach
        </div>
        @endif

        {{-- Problems & Management --}}
        @if(count($probs) > 0 || count($mgmt) > 0)
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
            @if(count($probs) > 0)
            <div style="flex:1;min-width:180px;">
                <p style="font-size:11px;font-weight:700;color:#9d174d;text-transform:uppercase;margin:0 0 4px;">Problems</p>
                <ul style="margin:0;padding-left:16px;">
                    @foreach($probs as $p)
                    <li style="font-size:11px;margin-bottom:2px;">{{ $p }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(count($mgmt) > 0)
            <div style="flex:1;min-width:180px;">
                <p style="font-size:11px;font-weight:700;color:#166534;text-transform:uppercase;margin:0 0 4px;">Management</p>
                <ul style="margin:0;padding-left:16px;">
                    @foreach($mgmt as $m)
                    <li style="font-size:11px;margin-bottom:2px;">{{ $m }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif

        {{-- Footer: next visit + seen by --}}
        <div style="border-top:1px solid #fbcfe8;padding-top:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            @if(!empty($visit['next_visit_date']))
            <span style="font-size:12px;"><strong>Next Visit:</strong> {{ $visit['next_visit_date'] }}</span>
            @endif
            <span style="font-size:12px;"><strong>Seen by:</strong> {{ $visit['seen_by'] ?? '—' }}</span>
        </div>
    </div>
</div>
@endforeach

{{-- ── Section 3: Discharge Advice Checklist ── --}}
@if(count($dischargeAdvice) > 0)
<div style="border:1px solid #fbcfe8;border-radius:6px;padding:14px 16px;margin-top:8px;">
    <h3 style="font-size:13px;font-weight:700;color:#9d174d;text-transform:uppercase;margin-bottom:10px;">
        Discharge Advice Given
    </h3>
    <ul style="margin:0;padding-left:0;list-style:none;">
        @foreach($dischargeAdvice as $advice)
        <li style="font-size:12px;margin-bottom:6px;display:flex;align-items:flex-start;gap:8px;">
            <span style="color:#DB2777;font-weight:700;flex-shrink:0;">&#10003;</span>
            {{ $advice }}
        </li>
        @endforeach
    </ul>
</div>
@endif
@endsection
