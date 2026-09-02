@extends('documents.base')

@section('title', 'Newborn Assessment Sheet')
@section('subtitle', 'NBA — Neonatal Examination at Birth')

@section('content')
@php
    $apgar  = $payload['apgar'] ?? [];
    $phyExam = $payload['physical_examination'] ?? [];
    $reflexes = $phyExam['reflexes'] ?? [];
    $anomalies = $payload['congenital_anomalies'] ?? ['None detected'];
    $prophylaxis = $payload['prophylaxis'] ?? [];

    $apgarTotal = function(string $suffix) use ($apgar): int {
        $keys = ['hr_'.$suffix, 'resp_'.$suffix, 'colour_'.$suffix, 'tone_'.$suffix, 'reflex_'.$suffix];
        $sum = 0;
        foreach ($keys as $k) {
            $sum += (int)($apgar[$k] ?? 0);
        }
        return $sum;
    };

    $apgarTotalColour = function(int $score): string {
        if ($score <= 6) return 'background:#fee2e2;color:#991b1b;';
        if ($score <= 8) return 'background:#fef3c7;color:#92400e;';
        return 'background:#d1fae5;color:#065f46;';
    };

    $classColour = function(string $c): string {
        if (str_contains($c, 'SGA') || str_contains($c, 'Preterm')) return 'background:#fef3c7;color:#92400e;';
        if (str_contains($c, 'LGA') || str_contains($c, 'Post-term')) return 'background:#dbeafe;color:#1e40af;';
        return 'background:#d1fae5;color:#065f46;';
    };

    $systemLabels = [
        'general'     => 'General',
        'head'        => 'Head',
        'fontanelle'  => 'Fontanelle',
        'eyes'        => 'Eyes',
        'ears'        => 'Ears',
        'nose'        => 'Nose',
        'mouth'       => 'Mouth',
        'neck'        => 'Neck',
        'chest'       => 'Chest',
        'heart'       => 'Heart',
        'lungs'       => 'Lungs',
        'abdomen'     => 'Abdomen',
        'spine'       => 'Spine',
        'genitalia'   => 'Genitalia',
        'limbs'       => 'Limbs',
        'skin'        => 'Skin',
    ];

    $reflexLabels = [
        'moro'    => 'Moro',
        'rooting' => 'Rooting',
        'sucking' => 'Sucking',
        'grasp'   => 'Grasp',
        'plantar' => 'Plantar',
    ];

    $score1 = isset($apgar['score_1min']) ? (int)$apgar['score_1min'] : $apgarTotal('1min');
    $score5 = isset($apgar['score_5min']) ? (int)$apgar['score_5min'] : $apgarTotal('5min');
    $has10  = isset($apgar['score_10min']) && $apgar['score_10min'] !== null;
    $score10 = $has10 ? (int)$apgar['score_10min'] : null;

    $classStr = $payload['classification'] ?? '';
@endphp

{{-- ══════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════ --}}
<div style="background:#db2777;color:#fff;border-radius:8px;padding:18px 22px;margin-bottom:18px;">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;">
        <div style="width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
            {{ ($payload['patient_sex'] ?? '') === 'Male' ? '♂' : '♀' }}
        </div>
        <div style="flex:1;">
            <div style="font-size:11px;opacity:.8;text-transform:uppercase;letter-spacing:.05em;">Birth Date &amp; Time</div>
            <div style="font-size:18px;font-weight:700;">{{ $payload['birth_datetime'] ?? '—' }}</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">
                {{ $payload['gestational_age_weeks'] ?? '—' }} weeks gestation
            </span>
            @if ($classStr)
            <span style="border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;{{ $classColour($classStr) }}">
                {{ $classStr }}
            </span>
            @endif
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">
                {{ $payload['birth_type'] ?? '—' }}
            </span>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     BIRTH MEASUREMENTS STRIP
══════════════════════════════════════════════ --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    @foreach ([
        ['label' => 'Birth Weight', 'value' => number_format((float)($payload['birth_weight_kg'] ?? 0), 2).' kg'],
        ['label' => 'Birth Length', 'value' => ($payload['birth_length_cm'] ?? '—').' cm'],
        ['label' => 'Head Circumference', 'value' => ($payload['head_circumference_cm'] ?? '—').' cm'],
        ['label' => 'Chest Circumference', 'value' => ($payload['chest_circumference_cm'] ?? '—').' cm'],
    ] as $m)
    <div style="flex:1;min-width:120px;background:#fce7f3;border-radius:8px;padding:10px 14px;text-align:center;">
        <div style="font-size:10px;color:#9d174d;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px;">{{ $m['label'] }}</div>
        <div style="font-size:16px;font-weight:700;color:#831843;">{{ $m['value'] }}</div>
    </div>
    @endforeach
</div>

{{-- ══════════════════════════════════════════════
     APGAR SCORE TABLE
══════════════════════════════════════════════ --}}
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#9d174d;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">APGAR Score</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#fce7f3;color:#9d174d;">
                <th style="padding:7px 10px;text-align:left;border:1px solid #fbcfe8;">Criterion</th>
                <th style="padding:7px 10px;text-align:center;border:1px solid #fbcfe8;">1 min</th>
                <th style="padding:7px 10px;text-align:center;border:1px solid #fbcfe8;">5 min</th>
                @if ($has10)
                <th style="padding:7px 10px;text-align:center;border:1px solid #fbcfe8;">10 min</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['key' => 'hr',     'label' => 'Heart Rate'],
                ['key' => 'resp',   'label' => 'Respiratory Effort'],
                ['key' => 'colour', 'label' => 'Colour'],
                ['key' => 'tone',   'label' => 'Muscle Tone'],
                ['key' => 'reflex', 'label' => 'Reflex Irritability'],
            ] as $idx => $row)
            <tr style="background:{{ $idx % 2 === 0 ? '#fff' : '#fdf2f8' }};">
                <td style="padding:6px 10px;border:1px solid #fbcfe8;">{{ $row['label'] }}</td>
                <td style="padding:6px 10px;border:1px solid #fbcfe8;text-align:center;">{{ $apgar[$row['key'].'_1min'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #fbcfe8;text-align:center;">{{ $apgar[$row['key'].'_5min'] ?? '—' }}</td>
                @if ($has10)
                <td style="padding:6px 10px;border:1px solid #fbcfe8;text-align:center;">—</td>
                @endif
            </tr>
            @endforeach
            <tr style="font-weight:700;">
                <td style="padding:7px 10px;border:1px solid #fbcfe8;color:#9d174d;">TOTAL</td>
                <td style="padding:7px 10px;border:1px solid #fbcfe8;text-align:center;">
                    <span style="border-radius:6px;padding:3px 10px;{{ $apgarTotalColour($score1) }}">{{ $score1 }}</span>
                </td>
                <td style="padding:7px 10px;border:1px solid #fbcfe8;text-align:center;">
                    <span style="border-radius:6px;padding:3px 10px;{{ $apgarTotalColour($score5) }}">{{ $score5 }}</span>
                </td>
                @if ($has10)
                <td style="padding:7px 10px;border:1px solid #fbcfe8;text-align:center;">
                    <span style="border-radius:6px;padding:3px 10px;{{ $apgarTotalColour($score10) }}">{{ $score10 }}</span>
                </td>
                @endif
            </tr>
        </tbody>
    </table>
</div>

{{-- Resuscitation --}}
<div style="margin-bottom:18px;">
    @if (!empty($payload['resuscitation_required']))
    <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:6px;padding:10px 14px;font-size:12px;color:#991b1b;">
        <strong>Resuscitation Required</strong>
        @if (!empty($payload['resuscitation_details']))
        — {{ $payload['resuscitation_details'] }}
        @endif
    </div>
    @else
    <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">No Resuscitation Required</span>
    @endif
</div>

{{-- ══════════════════════════════════════════════
     PHYSICAL EXAMINATION
══════════════════════════════════════════════ --}}
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#9d174d;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Physical Examination</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 20px;">
        @foreach ($systemLabels as $key => $label)
        @if (!empty($phyExam[$key]))
        <div style="display:flex;gap:6px;padding:4px 0;border-bottom:1px solid #fce7f3;font-size:12px;">
            <span style="color:#9d174d;font-weight:600;min-width:110px;">{{ $label }}</span>
            <span style="color:#374151;">{{ $phyExam[$key] }}</span>
        </div>
        @endif
        @endforeach
    </div>
</div>

{{-- Reflexes --}}
@if (!empty($reflexes))
<div style="margin-bottom:18px;">
    <div style="font-size:12px;font-weight:700;color:#9d174d;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Primitive Reflexes</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach ($reflexLabels as $key => $label)
        @if (isset($reflexes[$key]))
        <div style="background:#fce7f3;border-radius:8px;padding:8px 14px;text-align:center;min-width:80px;">
            <div style="font-size:11px;color:#9d174d;margin-bottom:3px;">{{ $label }}</div>
            @php $present = strtolower($reflexes[$key]) === 'present' || $reflexes[$key] === true; @endphp
            <span style="border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;{{ $present ? 'background:#d1fae5;color:#065f46;' : 'background:#fee2e2;color:#991b1b;' }}">
                {{ $present ? 'Present' : 'Absent' }}
            </span>
        </div>
        @endif
        @endforeach
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     CONGENITAL ANOMALIES
══════════════════════════════════════════════ --}}
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#9d174d;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Congenital Anomalies</div>
    @if (count($anomalies) === 1 && strtolower($anomalies[0]) === 'none detected')
    <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600;">None Detected</span>
    @else
    <ul style="margin:0;padding-left:18px;font-size:12px;color:#991b1b;">
        @foreach ($anomalies as $a)
        <li style="margin-bottom:2px;">{{ $a }}</li>
        @endforeach
    </ul>
    @endif
</div>

{{-- ══════════════════════════════════════════════
     INITIAL INVESTIGATIONS
══════════════════════════════════════════════ --}}
<div style="background:#fdf2f8;border:1px solid #fbcfe8;border-radius:8px;padding:14px;margin-bottom:18px;">
    <div style="font-size:12px;font-weight:700;color:#9d174d;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Initial Investigations</div>
    <div style="display:flex;flex-wrap:wrap;gap:20px;font-size:12px;">
        <div><span style="color:#6b7280;">Blood Group: </span><strong>{{ $payload['blood_group'] ?? 'Not done' }}</strong></div>
        <div><span style="color:#6b7280;">Direct Coombs (DCST): </span><strong>{{ $payload['dcst'] ?? 'Not done' }}</strong></div>
        <div><span style="color:#6b7280;">Blood Glucose: </span><strong>{{ $payload['glucose_mmol'] !== null ? ($payload['glucose_mmol'].' mmol/L') : 'Not done' }}</strong></div>
        <div><span style="color:#6b7280;">Temperature: </span><strong>{{ $payload['temperature'] ?? '—' }}</strong></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     PROPHYLAXIS CHECKLIST
══════════════════════════════════════════════ --}}
@if (!empty($prophylaxis))
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#9d174d;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Prophylaxis Administered</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#fce7f3;color:#9d174d;">
                <th style="padding:6px 10px;text-align:left;border:1px solid #fbcfe8;">Intervention</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #fbcfe8;">Status</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #fbcfe8;">Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($prophylaxis as $i => $p)
            <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#fdf2f8' }};">
                <td style="padding:6px 10px;border:1px solid #fbcfe8;">{{ $p['intervention'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #fbcfe8;text-align:center;">
                    @if (!empty($p['given']))
                    <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;">Given</span>
                    @else
                    <span style="background:#fee2e2;color:#991b1b;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;">Not Given</span>
                    @endif
                </td>
                <td style="padding:6px 10px;border:1px solid #fbcfe8;text-align:center;">{{ $p['time'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Feeding + NICU --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="background:#fdf2f8;border:1px solid #fbcfe8;border-radius:6px;padding:10px 16px;font-size:12px;">
        <span style="color:#6b7280;">Feeding: </span>
        <strong style="color:#374151;">{{ $payload['feeding'] ?? '—' }}</strong>
    </div>
    <div style="background:{{ !empty($payload['nicu_required']) ? '#fef2f2' : '#d1fae5' }};border-radius:6px;padding:10px 16px;font-size:12px;font-weight:600;color:{{ !empty($payload['nicu_required']) ? '#991b1b' : '#065f46' }};">
        {{ !empty($payload['nicu_required']) ? 'NICU Admission Required' : 'NICU Not Required' }}
    </div>
</div>

{{-- Signature --}}
<div style="border-top:2px solid #db2777;padding-top:10px;margin-top:6px;">
    <div style="font-size:12px;font-weight:700;color:#374151;">{{ $payload['examining_pediatrician'] ?? '—' }}</div>
    <div style="font-size:11px;color:#6b7280;">Examining Paediatrician</div>
</div>
@endsection
