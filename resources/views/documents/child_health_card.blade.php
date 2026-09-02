@extends('documents.base')

@section('title', 'Child Health Card (Under-5)')
@section('subtitle', 'CHC — MINSANTE Growth Monitoring Programme')

@section('content')
@php
    $growthVisits    = $payload['growth_visits'] ?? [];
    $immunizations   = $payload['immunizations'] ?? [];
    $vitaminA        = $payload['vitamin_a_doses'] ?? [];
    $deworming       = $payload['deworming'] ?? [];
    $illnesses       = $payload['illnesses'] ?? [];
    $counselling     = $payload['nutrition_counselling'] ?? [];

    $nutColour = function(string $status): string {
        if ($status === 'SAM') return 'background:#fee2e2;color:#991b1b;';
        if ($status === 'MAM') return 'background:#fef3c7;color:#92400e;';
        return 'background:#d1fae5;color:#065f46;';
    };

    $imuColour = function(string $status): string {
        if ($status === 'given')    return 'background:#d1fae5;color:#065f46;';
        if ($status === 'overdue')  return 'background:#fee2e2;color:#991b1b;';
        return 'background:#dbeafe;color:#1e40af;';
    };
@endphp

{{-- ══════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════ --}}
<div style="background:#059669;color:#fff;border-radius:8px;padding:18px 22px;margin-bottom:18px;">
    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
        {{-- Photo placeholder --}}
        <div style="width:60px;height:60px;background:rgba(255,255,255,.2);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;text-align:center;flex-shrink:0;color:rgba(255,255,255,.8);">
            Photo
        </div>
        <div style="flex:1;min-width:160px;">
            <div style="font-size:11px;opacity:.8;text-transform:uppercase;letter-spacing:.05em;">Child Name</div>
            <div style="font-size:20px;font-weight:700;">{{ $payload['child_name'] ?? '—' }}</div>
            <div style="font-size:12px;margin-top:3px;opacity:.9;">
                DOB: {{ $payload['child_dob'] ?? '—' }} &nbsp;|&nbsp;
                Birth Weight: {{ $payload['birth_weight_kg'] ?? '—' }} kg &nbsp;|&nbsp;
                Birth Order: {{ $payload['birth_order'] ?? '—' }}
            </div>
        </div>
        <div>
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:5px 14px;font-size:13px;font-weight:700;">
                {{ $payload['child_sex'] ?? '—' }}
            </span>
        </div>
    </div>
</div>

{{-- Mother / Father / Location strip --}}
<div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:12px 16px;margin-bottom:18px;display:flex;flex-wrap:wrap;gap:20px;font-size:12px;">
    <div><span style="color:#065f46;font-weight:600;">Mother: </span>{{ $payload['mother_name'] ?? '—' }}</div>
    <div><span style="color:#065f46;font-weight:600;">Father: </span>{{ $payload['father_name'] ?? '—' }}</div>
    <div><span style="color:#065f46;font-weight:600;">Village / Quarter: </span>{{ $payload['village_quarter'] ?? '—' }}</div>
    <div><span style="color:#065f46;font-weight:600;">Health Centre: </span>{{ $payload['health_centre'] ?? '—' }}</div>
</div>

{{-- ══════════════════════════════════════════════
     GROWTH MONITORING TABLE
══════════════════════════════════════════════ --}}
<div style="margin-bottom:20px;">
    <div style="font-size:13px;font-weight:700;color:#065f46;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Growth Monitoring</div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:#d1fae5;color:#065f46;">
                    <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Date</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Age (months)</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Weight (kg)</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Height (cm)</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">MUAC (cm)</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Nutritional Status</th>
                    <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Feeding</th>
                    <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($growthVisits as $i => $v)
                <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f0fdf4' }};">
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;white-space:nowrap;">{{ $v['date'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">{{ $v['age_months'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">{{ $v['weight_kg'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">{{ $v['height_cm'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">{{ $v['muac_cm'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">
                        @if (!empty($v['nutritional_status']))
                        <span style="border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;{{ $nutColour($v['nutritional_status']) }}">
                            {{ $v['nutritional_status'] }}
                        </span>
                        @else
                        —
                        @endif
                    </td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;">{{ $v['feeding'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;font-size:11px;">{{ $v['notes'] ?? '' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="padding:12px;text-align:center;color:#9ca3af;border:1px solid #a7f3d0;">No growth visits recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     IMMUNIZATION SCHEDULE
══════════════════════════════════════════════ --}}
<div style="margin-bottom:20px;">
    <div style="font-size:13px;font-weight:700;color:#065f46;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Immunization Schedule</div>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:#d1fae5;color:#065f46;">
                    <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Vaccine</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Age Due</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Date Given</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Batch</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Site</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Status</th>
                    <th style="padding:6px 10px;text-align:center;border:1px solid #a7f3d0;">Next Due</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($immunizations as $i => $imm)
                @php
                    $imuStatus = !empty($imm['date_given']) ? 'given' : (!empty($imm['next_due']) ? 'due' : 'due');
                @endphp
                <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f0fdf4' }};">
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;font-weight:600;">{{ $imm['vaccine'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">{{ $imm['age_given'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;white-space:nowrap;">{{ $imm['date_given'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;font-size:11px;">{{ $imm['batch'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">{{ $imm['site'] ?? '—' }}</td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;">
                        <span style="border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;{{ $imuColour($imuStatus) }}">
                            {{ ucfirst($imuStatus) }}
                        </span>
                    </td>
                    <td style="padding:5px 10px;border:1px solid #a7f3d0;text-align:center;white-space:nowrap;">{{ $imm['next_due'] ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:12px;text-align:center;color:#9ca3af;border:1px solid #a7f3d0;">No immunizations recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Vitamin A + Deworming side by side --}}
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
    {{-- Vitamin A --}}
    <div style="flex:1;min-width:240px;">
        <div style="font-size:12px;font-weight:700;color:#065f46;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Vitamin A Supplementation</div>
        @if (!empty($vitaminA))
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#d1fae5;color:#065f46;">
                    <th style="padding:5px 8px;text-align:left;border:1px solid #a7f3d0;">Date</th>
                    <th style="padding:5px 8px;text-align:center;border:1px solid #a7f3d0;">Dose (IU)</th>
                    <th style="padding:5px 8px;text-align:center;border:1px solid #a7f3d0;">Age (months)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vitaminA as $i => $v)
                <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f0fdf4' }};">
                    <td style="padding:4px 8px;border:1px solid #a7f3d0;">{{ $v['date'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $v['dose_iu'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $v['age_months'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="font-size:11px;color:#6b7280;">None recorded.</p>
        @endif
    </div>

    {{-- Deworming --}}
    <div style="flex:1;min-width:240px;">
        <div style="font-size:12px;font-weight:700;color:#065f46;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Deworming</div>
        @if (!empty($deworming))
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#d1fae5;color:#065f46;">
                    <th style="padding:5px 8px;text-align:left;border:1px solid #a7f3d0;">Date</th>
                    <th style="padding:5px 8px;text-align:left;border:1px solid #a7f3d0;">Drug</th>
                    <th style="padding:5px 8px;text-align:center;border:1px solid #a7f3d0;">Dose</th>
                    <th style="padding:5px 8px;text-align:center;border:1px solid #a7f3d0;">Age (m)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deworming as $i => $d)
                <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f0fdf4' }};">
                    <td style="padding:4px 8px;border:1px solid #a7f3d0;">{{ $d['date'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a7f3d0;">{{ $d['drug'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $d['dose'] ?? '—' }}</td>
                    <td style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $d['age_months'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="font-size:11px;color:#6b7280;">None recorded.</p>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════
     ILLNESSES HISTORY
══════════════════════════════════════════════ --}}
@if (!empty($illnesses))
<div style="margin-bottom:18px;">
    <div style="font-size:13px;font-weight:700;color:#065f46;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;">Illness History</div>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#d1fae5;color:#065f46;">
                <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Date</th>
                <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Illness</th>
                <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Treatment</th>
                <th style="padding:6px 10px;text-align:left;border:1px solid #a7f3d0;">Outcome</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($illnesses as $i => $ill)
            <tr style="background:{{ $i % 2 === 0 ? '#fff' : '#f0fdf4' }};">
                <td style="padding:5px 10px;border:1px solid #a7f3d0;white-space:nowrap;">{{ $ill['date'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #a7f3d0;">{{ $ill['illness'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #a7f3d0;">{{ $ill['treatment'] ?? '—' }}</td>
                <td style="padding:5px 10px;border:1px solid #a7f3d0;">{{ $ill['outcome'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ══════════════════════════════════════════════
     NUTRITION COUNSELLING + BREASTFEEDING
══════════════════════════════════════════════ --}}
<div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:14px;margin-bottom:18px;">
    <div style="font-size:12px;font-weight:700;color:#065f46;margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em;">Nutrition Counselling &amp; Feeding</div>
    <div style="display:flex;flex-wrap:wrap;gap:20px;font-size:12px;">
        <div>
            <span style="color:#065f46;font-weight:600;">Exclusive Breastfeeding: </span>
            {{ $payload['exclusive_breastfeeding_months'] ?? '—' }} months
        </div>
        <div>
            <span style="color:#065f46;font-weight:600;">Complementary Feeding Started: </span>
            {{ $payload['complementary_feeding_started'] ?? 'Not recorded' }}
        </div>
    </div>
    @if (!empty($counselling))
    <div style="margin-top:8px;font-size:11px;color:#374151;">
        <strong style="color:#065f46;">Counselling Dates:</strong>
        {{ implode(', ', $counselling) }}
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════
     HEALTH CENTRE STAMP + SIGNATURE
══════════════════════════════════════════════ --}}
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:6px;">
    <div style="flex:1;min-width:160px;border:2px dashed #a7f3d0;border-radius:8px;padding:14px;text-align:center;">
        <div style="font-size:11px;color:#065f46;font-weight:600;margin-bottom:4px;">HEALTH CENTRE STAMP</div>
        <div style="height:40px;"></div>
        <div style="font-size:10px;color:#9ca3af;">{{ $payload['health_centre'] ?? '' }}</div>
    </div>
    <div style="flex:1;min-width:180px;border-top:2px solid #059669;padding-top:10px;margin-top:auto;">
        <div style="height:28px;"></div>
        <div style="font-size:12px;font-weight:700;color:#374151;">{{ $issuer_name ?? '—' }}</div>
        <div style="font-size:11px;color:#6b7280;">Health Worker / {{ $issuer_role ?? 'Nurse' }}</div>
    </div>
</div>
@endsection
