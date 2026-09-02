@extends('documents.base')

@section('title', 'WHO Partograph / Labour Chart')
@section('subtitle', 'PTG — Maternity Labour Monitoring Record')

@section('content')
@php
    $obs = $payload['observations'] ?? [];
    $complications = $payload['complications'] ?? [];
    $alertCrossed = $payload['alert_line_crossed'] ?? false;
    $actionCrossed = $payload['action_line_crossed'] ?? false;

    $cervixColour = function(int $cm): string {
        if ($cm < 4) return 'background:#d1d5db;color:#111827;';
        if ($cm < 7) return 'background:#fef3c7;color:#92400e;';
        return 'background:#d1fae5;color:#065f46;';
    };

    $fhrColour = function($bpm): string {
        if ($bpm === null || $bpm === '') return '';
        $v = (int) $bpm;
        if ($v < 110 || $v > 160) return 'background:#fee2e2;color:#991b1b;font-weight:600;';
        return '';
    };

    $apgarColour = function($score): string {
        $s = (int) $score;
        if ($s <= 6) return 'background:#fee2e2;color:#991b1b;';
        if ($s <= 8) return 'background:#fef3c7;color:#92400e;';
        return 'background:#d1fae5;color:#065f46;';
    };
@endphp

{{-- ══════════════════════════════════════════════
     HEADER STRIP
══════════════════════════════════════════════ --}}
<div style="background:#db2777;color:#fff;border-radius:8px;padding:18px 22px;margin-bottom:18px;">
    <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:center;">
        <div style="flex:1;min-width:160px;">
            <div style="font-size:11px;opacity:.8;text-transform:uppercase;letter-spacing:.05em;">Gravida / Parity</div>
            <div style="font-size:22px;font-weight:700;">{{ $payload['gravida'] ?? '—' }}</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">
                Labour: {{ $payload['labour_onset'] ?? '—' }}
                @if (!empty($payload['induction_method']))
                    — {{ $payload['induction_method'] }}
                @endif
            </span>
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">
                Membranes: {{ $payload['membranes'] ?? '—' }}
            </span>
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:4px 12px;font-size:12px;font-weight:600;">
                Presentation: {{ $payload['presentation'] ?? '—' }}
            </span>
        </div>
    </div>
    <div style="margin-top:10px;display:flex;gap:24px;flex-wrap:wrap;font-size:12px;">
        <span><strong>Liquor:</strong> {{ $payload['liquor_colour'] ?? '—' }}</span>
        <span><strong>Active Phase Start:</strong> {{ $payload['active_phase_start'] ?? '—' }}</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     ALERT / ACTION LINE WARNINGS
══════════════════════════════════════════════ --}}
@if ($alertCrossed || $actionCrossed)
<div style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;">
    @if ($alertCrossed)
    <div style="background:#fef2f2;border:1.5px solid #f87171;border-radius:6px;padding:8px 16px;color:#991b1b;font-weight:700;font-size:13px;">
        ⚠ ALERT LINE CROSSED — Reassess management
    </div>
    @endif
    @if ($actionCrossed)
    <div style="background:#fef2f2;border:2px solid #dc2626;border-radius:6px;padding:8px 16px;color:#7f1d1d;font-weight:700;font-size:13px;">
        ⛔ ACTION LINE CROSSED — Immediate intervention required
    </div>
    @endif
</div>
@endif

{{-- ══════════════════════════════════════════════
     LABOUR OBSERVATIONS TABLE
══════════════════════════════════════════════ --}}
<div style="margin-bottom:20px;">
    <div style="font-size:13px;font-weight:700;color:#9d174d;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Labour Observations</div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#fce7f3;color:#9d174d;">
                    <th style="padding:6px 8px;text-align:left;border:1px solid #fbcfe8;white-space:nowrap;">Time</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">FHR<br>(bpm)</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Contractions<br>/10 min</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Contraction<br>Duration (s)</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Cervix<br>(cm)</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Descent<br>(fifths)</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Liquor</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Moulding</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Oxytocin<br>(units)</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">BP</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Pulse</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Temp</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Urine<br>(mL)</th>
                    <th style="padding:6px 8px;text-align:center;border:1px solid #fbcfe8;white-space:nowrap;">Protein</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($obs as $i => $o)
                <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#fdf2f8' }};">
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;white-space:nowrap;">{{ $o['time'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;{{ $fhrColour($o['fhr_bpm'] ?? null) }}">{{ $o['fhr_bpm'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['contractions_in_10min'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['contraction_duration_sec'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">
                        @if (isset($o['cervix_cm']) && $o['cervix_cm'] !== null)
                        <span style="border-radius:4px;padding:2px 6px;{{ $cervixColour((int)$o['cervix_cm']) }}">{{ $o['cervix_cm'] }}</span>
                        @else
                        —
                        @endif
                    </td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['descent_fifths'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['liquor'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['moulding'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['oxytocin_units'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;white-space:nowrap;">{{ $o['bp'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['pulse'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;white-space:nowrap;">{{ $o['temp'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['urine_vol_ml'] ?? '—' }}</td>
                    <td style="padding:5px 8px;border:1px solid #fbcfe8;text-align:center;">{{ $o['urine_protein'] ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="14" style="padding:12px;text-align:center;color:#9ca3af;border:1px solid #fbcfe8;">No observations recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     DELIVERY DETAILS
══════════════════════════════════════════════ --}}
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="flex:1;min-width:200px;background:#fdf2f8;border:1px solid #fbcfe8;border-radius:8px;padding:14px;">
        <div style="font-size:12px;font-weight:700;color:#9d174d;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Delivery Details</div>
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <tr><td style="padding:3px 0;color:#6b7280;width:50%;">Delivery Time</td><td style="font-weight:600;">{{ $payload['delivery_time'] ?? '—' }}</td></tr>
            <tr><td style="padding:3px 0;color:#6b7280;">Delivery Type</td><td style="font-weight:600;">{{ $payload['delivery_type'] ?? '—' }}</td></tr>
            @if (!empty($payload['delivery_reason']))
            <tr><td style="padding:3px 0;color:#6b7280;">Reason</td><td style="font-weight:600;">{{ $payload['delivery_reason'] }}</td></tr>
            @endif
        </table>
    </div>

    {{-- Baby Outcome --}}
    <div style="flex:1;min-width:200px;background:#fdf2f8;border:1px solid #fbcfe8;border-radius:8px;padding:14px;">
        <div style="font-size:12px;font-weight:700;color:#9d174d;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Baby Outcome</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
            <span style="background:#db2777;color:#fff;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:600;">
                {{ $payload['baby_sex'] ?? '—' }}
            </span>
            <span style="background:#f3f4f6;color:#374151;border-radius:20px;padding:3px 10px;font-size:11px;font-weight:600;">
                {{ number_format((float)($payload['birth_weight_kg'] ?? 0), 2) }} kg
            </span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <div style="text-align:center;">
                <div style="font-size:10px;color:#6b7280;margin-bottom:2px;">APGAR 1 min</div>
                <span style="border-radius:6px;padding:4px 10px;font-size:14px;font-weight:700;{{ $apgarColour($payload['apgar_1min'] ?? 0) }}">
                    {{ $payload['apgar_1min'] ?? '—' }}
                </span>
            </div>
            <div style="text-align:center;">
                <div style="font-size:10px;color:#6b7280;margin-bottom:2px;">APGAR 5 min</div>
                <span style="border-radius:6px;padding:4px 10px;font-size:14px;font-weight:700;{{ $apgarColour($payload['apgar_5min'] ?? 0) }}">
                    {{ $payload['apgar_5min'] ?? '—' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Third Stage --}}
    <div style="flex:1;min-width:200px;background:#fdf2f8;border:1px solid #fbcfe8;border-radius:8px;padding:14px;">
        <div style="font-size:12px;font-weight:700;color:#9d174d;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Third Stage</div>
        <table style="width:100%;font-size:12px;border-collapse:collapse;">
            <tr><td style="padding:3px 0;color:#6b7280;width:55%;">Placenta Delivery</td><td style="font-weight:600;">{{ $payload['placenta_delivery_time'] ?? '—' }}</td></tr>
            <tr>
                <td style="padding:3px 0;color:#6b7280;">Placenta Complete</td>
                <td>
                    @if (!empty($payload['placenta_complete']))
                    <span style="background:#d1fae5;color:#065f46;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;">Yes</span>
                    @else
                    <span style="background:#fee2e2;color:#991b1b;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;">Incomplete</span>
                    @endif
                </td>
            </tr>
            <tr><td style="padding:3px 0;color:#6b7280;">Blood Loss</td><td style="font-weight:600;">{{ $payload['blood_loss_ml'] ?? '—' }} mL</td></tr>
            <tr><td style="padding:3px 0;color:#6b7280;">Episiotomy</td><td style="font-weight:600;">{{ !empty($payload['episiotomy']) ? 'Yes' : 'No' }}</td></tr>
            <tr><td style="padding:3px 0;color:#6b7280;">Perineal Tears</td><td style="font-weight:600;">{{ $payload['perineal_tears'] ?? '—' }}</td></tr>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     COMPLICATIONS
══════════════════════════════════════════════ --}}
@if (!empty($complications))
<div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:8px;padding:14px;margin-bottom:18px;">
    <div style="font-size:12px;font-weight:700;color:#991b1b;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Complications</div>
    <ul style="margin:0;padding-left:18px;font-size:12px;color:#7f1d1d;">
        @foreach ($complications as $c)
        <li style="margin-bottom:2px;">{{ $c }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- ══════════════════════════════════════════════
     SIGNATURES
══════════════════════════════════════════════ --}}
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:6px;">
    <div style="flex:1;min-width:180px;border-top:2px solid #db2777;padding-top:8px;">
        <div style="font-size:12px;font-weight:700;color:#374151;">{{ $payload['midwife'] ?? '—' }}</div>
        <div style="font-size:11px;color:#6b7280;">Midwife / Birth Attendant</div>
    </div>
    @if (!empty($payload['obstetrician']))
    <div style="flex:1;min-width:180px;border-top:2px solid #db2777;padding-top:8px;">
        <div style="font-size:12px;font-weight:700;color:#374151;">{{ $payload['obstetrician'] }}</div>
        <div style="font-size:11px;color:#6b7280;">Obstetrician</div>
    </div>
    @endif
</div>
@endsection
