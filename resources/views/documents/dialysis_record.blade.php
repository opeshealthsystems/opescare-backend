@extends('documents.base')

@section('title', 'Dialysis Session Record')
@section('subtitle', 'DLY — Haemodialysis Session Chart')

@section('content')
@php
    $intraVitals   = $payload['intra_vitals'] ?? [];
    $preLabs       = $payload['pre_labs'] ?? null;
    $postLabs      = $payload['post_labs'] ?? null;
    $complications = $payload['complications'] ?? ['None'];
    $medications   = $payload['medications_given'] ?? [];
    $anticoag      = $payload['anticoagulation'] ?? [];
    $preVitals     = $payload['pre_vitals'] ?? [];
    $postVitals    = $payload['post_vitals'] ?? [];

    $ufTarget   = (int)($payload['ultrafiltration_target_ml'] ?? 0);
    $ufAchieved = (int)($payload['ultrafiltration_achieved_ml'] ?? 0);
    $ufPct      = $ufTarget > 0 ? round($ufAchieved / $ufTarget * 100) : null;

    $ktv        = $payload['kt_v'] ?? null;
    $ktvColour  = $ktv !== null
        ? ($ktv >= 1.2 ? 'background:#d1fae5;color:#065f46;' : 'background:#fee2e2;color:#991b1b;')
        : '';
@endphp

{{-- ══════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════ --}}
<div style="background:#0891b2;color:#fff;border-radius:8px;padding:18px 22px;margin-bottom:18px;">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;">
        <div style="flex:1;min-width:160px;">
            <div style="font-size:11px;opacity:.8;text-transform:uppercase;letter-spacing:.05em;">Session</div>
            <div style="font-size:20px;font-weight:700;">{{ $payload['session_number'] ?? '—' }}</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">
                {{ $payload['modality'] ?? '—' }}
            </span>
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;">
                {{ $payload['session_date'] ?? '—' }}
            </span>
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;">
                {{ $payload['session_start'] ?? '—' }} – {{ $payload['session_end'] ?? '—' }}
                ({{ $payload['duration_hours'] ?? '—' }} hr)
            </span>
        </div>
    </div>
    <div style="margin-top:10px;font-size:12px;opacity:.9;">
        <strong>Access:</strong> {{ $payload['access_type'] ?? '—' }} &nbsp;|&nbsp;
        {{ $payload['access_condition'] ?? '' }}
    </div>
</div>

{{-- ══════════════════════════════════════════════
     WEIGHT MANAGEMENT
══════════════════════════════════════════════ --}}
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="flex:2;min-width:240px;background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;padding:14px;">
        <div style="font-size:12px;font-weight:700;color:#155e75;margin-bottom:10px;text-transform:uppercase;letter-spacing:.04em;">Weight Management</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;">
            <div style="text-align:center;flex:1;min-width:70px;">
                <div style="font-size:10px;color:#0891b2;text-transform:uppercase;margin-bottom:2px;">Pre-weight</div>
                <div style="font-size:20px;font-weight:700;color:#0c4a6e;">{{ $payload['pre_weight_kg'] ?? '—' }}<span style="font-size:11px;font-weight:400;"> kg</span></div>
            </div>
            <div style="color:#94a3b8;font-size:18px;">→</div>
            <div style="text-align:center;flex:1;min-width:70px;">
                <div style="font-size:10px;color:#0891b2;text-transform:uppercase;margin-bottom:2px;">Dry weight</div>
                <div style="font-size:20px;font-weight:700;color:#0c4a6e;">{{ $payload['dry_weight_kg'] ?? '—' }}<span style="font-size:11px;font-weight:400;"> kg</span></div>
            </div>
            <div style="color:#94a3b8;font-size:18px;">→</div>
            <div style="text-align:center;flex:1;min-width:70px;">
                <div style="font-size:10px;color:#0891b2;text-transform:uppercase;margin-bottom:2px;">Post-weight</div>
                <div style="font-size:20px;font-weight:700;color:#0c4a6e;">{{ $payload['post_weight_kg'] ?? '—' }}<span style="font-size:11px;font-weight:400;"> kg</span></div>
            </div>
        </div>
    </div>
    <div style="flex:1;min-width:180px;background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;padding:14px;">
        <div style="font-size:12px;font-weight:700;color:#155e75;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Ultrafiltration</div>
        <table style="font-size:12px;width:100%;border-collapse:collapse;">
            <tr><td style="padding:2px 0;color:#0891b2;">Target</td><td style="font-weight:600;">{{ number_format($ufTarget) }} mL</td></tr>
            <tr><td style="padding:2px 0;color:#0891b2;">Achieved</td><td style="font-weight:600;">{{ number_format($ufAchieved) }} mL</td></tr>
            @if ($ufPct !== null)
            <tr>
                <td style="padding:2px 0;color:#0891b2;">Achieved %</td>
                <td>
                    <span style="border-radius:20px;padding:2px 8px;font-size:11px;font-weight:700;{{ $ufPct >= 90 ? 'background:#d1fae5;color:#065f46;' : 'background:#fef3c7;color:#92400e;' }}">
                        {{ $ufPct }}%
                    </span>
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     MACHINE SETTINGS
══════════════════════════════════════════════ --}}
<div style="background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;padding:14px;margin-bottom:18px;">
    <div style="font-size:12px;font-weight:700;color:#155e75;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Machine Settings &amp; Anticoagulation</div>
    <div style="display:flex;flex-wrap:wrap;gap:20px;font-size:12px;">
        <div><span style="color:#0891b2;">Dialyser: </span><strong>{{ $payload['dialyser'] ?? '—' }}</strong></div>
        <div><span style="color:#0891b2;">Blood Flow: </span><strong>{{ $payload['blood_flow_ml_min'] ?? '—' }} mL/min</strong></div>
        <div><span style="color:#0891b2;">Dialysate Flow: </span><strong>{{ $payload['dialysate_flow_ml_min'] ?? '—' }} mL/min</strong></div>
        <div><span style="color:#0891b2;">Dialysate Temp: </span><strong>{{ $payload['dialysate_temp'] ?? '—' }}</strong></div>
        @if (!empty($anticoag))
        <div><span style="color:#0891b2;">Anticoagulation: </span>
            <strong>{{ $anticoag['drug'] ?? '—' }}</strong>
            — Loading {{ $anticoag['loading_dose'] ?? '—' }},
            Maintenance {{ $anticoag['maintenance'] ?? '—' }},
            Total {{ $anticoag['total_dose'] ?? '—' }}
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════
     VITALS MONITORING
══════════════════════════════════════════════ --}}
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#155e75;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Vitals Monitoring</div>
    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:12px;">
        {{-- Pre vitals --}}
        <div style="flex:1;min-width:150px;background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;padding:12px;">
            <div style="font-size:11px;font-weight:700;color:#0891b2;margin-bottom:6px;text-transform:uppercase;">Pre-Session</div>
            <table style="font-size:12px;border-collapse:collapse;width:100%;">
                <tr><td style="padding:2px 0;color:#155e75;width:45%;">BP</td><td style="font-weight:600;">{{ $preVitals['bp'] ?? '—' }}</td></tr>
                <tr><td style="padding:2px 0;color:#155e75;">Pulse</td><td style="font-weight:600;">{{ $preVitals['pulse'] ?? '—' }}</td></tr>
                <tr><td style="padding:2px 0;color:#155e75;">Temp</td><td style="font-weight:600;">{{ $preVitals['temp'] ?? '—' }}</td></tr>
                <tr><td style="padding:2px 0;color:#155e75;">SpO₂</td><td style="font-weight:600;">{{ $preVitals['spo2'] ?? '—' }}</td></tr>
            </table>
        </div>

        {{-- Intra vitals --}}
        <div style="flex:3;min-width:240px;">
            <div style="font-size:11px;font-weight:700;color:#0891b2;margin-bottom:6px;text-transform:uppercase;">Intra-Session Monitoring</div>
            @if (!empty($intraVitals))
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <thead>
                        <tr style="background:#cffafe;color:#155e75;">
                            <th style="padding:5px 8px;text-align:left;border:1px solid #a5f3fc;">Time</th>
                            <th style="padding:5px 8px;text-align:center;border:1px solid #a5f3fc;">BP</th>
                            <th style="padding:5px 8px;text-align:center;border:1px solid #a5f3fc;">Pulse</th>
                            <th style="padding:5px 8px;text-align:center;border:1px solid #a5f3fc;">SpO₂</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($intraVitals as $i => $v)
                        <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#ecfeff' }};">
                            <td style="padding:4px 8px;border:1px solid #a5f3fc;white-space:nowrap;">{{ $v['time'] ?? '—' }}</td>
                            <td style="padding:4px 8px;border:1px solid #a5f3fc;text-align:center;">{{ $v['bp'] ?? '—' }}</td>
                            <td style="padding:4px 8px;border:1px solid #a5f3fc;text-align:center;">{{ $v['pulse'] ?? '—' }}</td>
                            <td style="padding:4px 8px;border:1px solid #a5f3fc;text-align:center;">{{ $v['spo2'] ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p style="font-size:11px;color:#9ca3af;">No intra-session readings recorded.</p>
            @endif
        </div>

        {{-- Post vitals --}}
        <div style="flex:1;min-width:150px;background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;padding:12px;">
            <div style="font-size:11px;font-weight:700;color:#0891b2;margin-bottom:6px;text-transform:uppercase;">Post-Session</div>
            <table style="font-size:12px;border-collapse:collapse;width:100%;">
                <tr><td style="padding:2px 0;color:#155e75;width:45%;">BP</td><td style="font-weight:600;">{{ $postVitals['bp'] ?? '—' }}</td></tr>
                <tr><td style="padding:2px 0;color:#155e75;">Pulse</td><td style="font-weight:600;">{{ $postVitals['pulse'] ?? '—' }}</td></tr>
                <tr><td style="padding:2px 0;color:#155e75;">Temp</td><td style="font-weight:600;">{{ $postVitals['temp'] ?? '—' }}</td></tr>
                <tr><td style="padding:2px 0;color:#155e75;">SpO₂</td><td style="font-weight:600;">{{ $postVitals['spo2'] ?? '—' }}</td></tr>
            </table>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     PRE / POST LABS
══════════════════════════════════════════════ --}}
@if ($preLabs || $postLabs)
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#155e75;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Pre / Post Dialysis Labs</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#cffafe;color:#155e75;">
                <th style="padding:6px 10px;text-align:left;border:1px solid #a5f3fc;">Parameter</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #a5f3fc;">Pre-Dialysis</th>
                <th style="padding:6px 10px;text-align:center;border:1px solid #a5f3fc;">Post-Dialysis</th>
            </tr>
        </thead>
        <tbody>
            @foreach ([
                ['key' => 'bun_mmol',     'label' => 'BUN (mmol/L)'],
                ['key' => 'creatinine',   'label' => 'Creatinine'],
                ['key' => 'potassium',    'label' => 'Potassium'],
                ['key' => 'bicarbonate',  'label' => 'Bicarbonate'],
            ] as $i => $row)
            <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#ecfeff' }};">
                <td style="padding:5px 10px;border:1px solid #a5f3fc;">{{ $row['label'] }}</td>
                <td style="padding:5px 10px;border:1px solid #a5f3fc;text-align:center;">{{ $preLabs[$row['key']] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #a5f3fc;text-align:center;">{{ $postLabs[$row['key']] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Kt/V --}}
@if ($ktv !== null)
<div style="margin-bottom:18px;display:flex;align-items:center;gap:12px;font-size:12px;">
    <span style="color:#155e75;font-weight:600;">Kt/V Dialysis Adequacy:</span>
    <span style="border-radius:6px;padding:4px 14px;font-size:14px;font-weight:700;{{ $ktvColour }}">
        {{ number_format((float)$ktv, 2) }}
    </span>
    <span style="color:#6b7280;font-size:11px;">{{ $ktv >= 1.2 ? 'Adequate (target ≥1.2)' : 'Inadequate — review prescription' }}</span>
</div>
@endif

{{-- Complications + Medications --}}
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="flex:1;min-width:200px;background:#ecfeff;border:1px solid #a5f3fc;border-radius:8px;padding:14px;">
        <div style="font-size:12px;font-weight:700;color:#155e75;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Complications</div>
        @if (count($complications) === 1 && strtolower($complications[0]) === 'none')
        <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:600;">None</span>
        @else
        <ul style="margin:0;padding-left:16px;font-size:12px;color:#991b1b;">
            @foreach ($complications as $c)
            <li>{{ $c }}</li>
            @endforeach
        </ul>
        @endif
    </div>

    @if (!empty($medications))
    <div style="flex:2;min-width:200px;">
        <div style="font-size:12px;font-weight:700;color:#155e75;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Medications Given</div>
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#cffafe;color:#155e75;">
                    <th style="padding:5px 8px;text-align:left;border:1px solid #a5f3fc;">Drug</th>
                    <th style="padding:5px 8px;text-align:center;border:1px solid #a5f3fc;">Dose</th>
                    <th style="padding:5px 8px;text-align:center;border:1px solid #a5f3fc;">Route</th>
                    <th style="padding:5px 8px;text-align:center;border:1px solid #a5f3fc;">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($medications as $i => $m)
                <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#ecfeff' }};">
                    <td style="padding:4px 8px;border:1px solid #a5f3fc;">{{ $m['drug'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a5f3fc;text-align:center;">{{ $m['dose'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a5f3fc;text-align:center;">{{ $m['route'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a5f3fc;text-align:center;">{{ $m['time'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Nursing notes --}}
@if (!empty($payload['nursing_notes']))
<div style="background:#f0fdff;border-left:4px solid #0891b2;border-radius:0 8px 8px 0;padding:12px 16px;margin-bottom:18px;font-size:12px;color:#374151;">
    <div style="font-size:11px;font-weight:700;color:#0891b2;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">Nursing Notes</div>
    {{ $payload['nursing_notes'] }}
</div>
@endif

{{-- Signatures --}}
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:6px;">
    <div style="flex:1;min-width:180px;border-top:2px solid #0891b2;padding-top:8px;">
        <div style="font-size:12px;font-weight:700;color:#374151;">{{ $payload['dialysis_nurse'] ?? '—' }}</div>
        <div style="font-size:11px;color:#6b7280;">Dialysis Nurse</div>
    </div>
    <div style="flex:1;min-width:180px;border-top:2px solid #0891b2;padding-top:8px;">
        <div style="font-size:12px;font-weight:700;color:#374151;">{{ $payload['nephrologist'] ?? '—' }}</div>
        <div style="font-size:11px;color:#6b7280;">Nephrologist</div>
    </div>
</div>
@endsection
