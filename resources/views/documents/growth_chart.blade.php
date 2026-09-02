@extends('documents.base')

@section('title', 'Paediatric Growth Chart')
@section('subtitle', 'GCH — WHO Weight-for-Age &amp; Height-for-Age (0–60 months)')

@section('content')
@php
    $accentColor    = '#059669';
    $childName      = $payload['child_name']              ?? '—';
    $childDob       = $payload['child_dob']               ?? '—';
    $childSex       = $payload['child_sex']               ?? '—';
    $motherName     = $payload['mother_name']             ?? '—';
    $measurements   = $payload['measurements']            ?? [];
    $latestWeight   = $payload['latest_weight_kg']        ?? '—';
    $latestHeight   = $payload['latest_height_cm']        ?? '—';
    $latestAge      = $payload['latest_age_months']       ?? '—';
    $latestMuac     = $payload['latest_muac_cm']          ?? '—';
    $wfaZ           = $payload['weight_for_age_z']        ?? '—';
    $hfaZ           = $payload['height_for_age_z']        ?? '—';
    $wfhZ           = $payload['weight_for_height_z']     ?? '—';
    $nutClass       = $payload['nutritional_classification'] ?? '—';
    $oedema         = $payload['oedema_present']          ?? false;
    $interventions  = $payload['interventions']           ?? [];
    $refNutrition   = $payload['referred_to_nutrition']   ?? false;
    $therapFeeding  = $payload['therapeutic_feeding']     ?? null;
    $nextMeasDate   = $payload['next_measurement_date']   ?? '—';
    $healthWorker   = $payload['health_worker']           ?? '—';

    $nutClassColors = [
        'Well Nourished'       => '#15803d',
        'Underweight'          => '#d97706',
        'Stunted'              => '#d97706',
        'Wasted'               => '#ea580c',
        'Severely Wasted (SAM)'=> '#dc2626',
        'Overweight'           => '#7c3aed',
    ];
    $nutClassBg = $nutClassColors[$nutClass] ?? '#374151';

    // Z-score badge color helper
    $zColor = function (string $z): string {
        if (str_contains($z, 'Normal') || str_contains($z, '≥-1')) {
            return '#15803d';
        }
        if (str_contains($z, '-1') && !str_contains($z, '-2') && !str_contains($z, '-3')) {
            return '#d97706';
        }
        if (str_contains($z, '-2') && !str_contains($z, '-3')) {
            return '#d97706';
        }
        if (str_contains($z, '-3')) {
            return '#dc2626';
        }
        return '#374151';
    };

    $sexBg = ($childSex === 'Male') ? '#1d4ed8' : '#be185d';
@endphp

{{-- ── Section 1: Header ── --}}
<div style="background:{{ $accentColor }};color:#fff;padding:14px 20px;border-radius:6px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-size:15px;font-weight:800;">{{ $childName }}</span>
        <span style="background:{{ $sexBg }};padding:3px 10px;border-radius:4px;font-size:12px;font-weight:700;">
            {{ $childSex }}
        </span>
        <span style="font-size:13px;"><strong>DOB:</strong> {{ $childDob }}</span>
        <span style="background:rgba(0,0,0,.2);padding:3px 10px;border-radius:4px;font-size:12px;">
            Age: <strong>{{ $latestAge }} months</strong>
        </span>
        <span style="font-size:12px;opacity:.85;margin-left:auto;"><strong>Mother:</strong> {{ $motherName }}</span>
    </div>
</div>

{{-- ── Section 2: Latest Measurements Pills ── --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
    @foreach([
        ['Weight',    $latestWeight . ' kg',  '#065f46'],
        ['Height',    $latestHeight . ' cm',  '#065f46'],
        ['MUAC',      $latestMuac  . ' cm',   '#0c4a6e'],
        ['Age',       $latestAge   . ' mo',   '#374151'],
    ] as $pill)
    <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:20px;padding:8px 18px;text-align:center;min-width:100px;">
        <p style="font-size:10px;text-transform:uppercase;color:{{ $pill[2] }};font-weight:700;margin:0 0 2px;">{{ $pill[0] }}</p>
        <p style="font-size:16px;font-weight:800;color:{{ $pill[2] }};margin:0;">{{ $pill[1] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Section 3: Z-Scores ── --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
    @foreach([
        ['Weight-for-Age',    $wfaZ],
        ['Height-for-Age',    $hfaZ],
        ['Weight-for-Height', $wfhZ],
    ] as $zs)
    @php $zc = $zColor($zs[1]); @endphp
    <div style="flex:1;min-width:160px;border:2px solid {{ $zc }};border-radius:6px;padding:10px 14px;text-align:center;">
        <p style="font-size:10px;text-transform:uppercase;color:#374151;font-weight:700;margin:0 0 4px;">{{ $zs[0] }}</p>
        <p style="font-size:13px;font-weight:700;color:{{ $zc }};margin:0;">{{ $zs[1] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Section 4: Nutritional Classification ── --}}
<div style="text-align:center;margin-bottom:20px;padding:14px;background:{{ $nutClassBg }};color:#fff;border-radius:8px;">
    <p style="font-size:10px;text-transform:uppercase;letter-spacing:1px;opacity:.8;margin:0 0 4px;">Nutritional Classification</p>
    <p style="font-size:20px;font-weight:800;margin:0;">{{ $nutClass }}</p>
</div>

{{-- ── Section 5: Oedema Warning ── --}}
@if($oedema)
<div style="background:#fee2e2;border:2px solid #dc2626;border-radius:6px;padding:10px 14px;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:20px;">&#9888;</span>
    <p style="font-size:13px;font-weight:700;color:#991b1b;margin:0;">
        Bilateral Pitting Oedema Present — Possible Kwashiorkor / SAM. Urgent management required.
    </p>
</div>
@endif

{{-- ── Section 6: Growth Measurements Table ── --}}
<div style="margin-bottom:20px;">
    <h3 style="font-size:12px;font-weight:700;color:#065f46;text-transform:uppercase;border-bottom:2px solid #a7f3d0;padding-bottom:4px;margin-bottom:10px;">
        Growth Measurements Log
    </h3>
    @if(count($measurements) > 0)
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#d1fae5;color:#065f46;">
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Date</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">Age (mo)</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">Weight (kg)</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">Height (cm)</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">MUAC (cm)</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">BMI</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">Oedema</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Status</th>
                    <th style="padding:6px 8px;border:1px solid #a7f3d0;text-align:left;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($measurements as $i => $m)
                @php
                    $rowBg   = ($i % 2 === 0) ? '#f0fdf4' : '#fff';
                    $hasOed  = !empty($m['oedema']);
                    $status  = $m['nutritional_status'] ?? '—';
                    $statusBg = $nutClassColors[$status] ?? '#6b7280';
                @endphp
                <tr style="background:{{ $hasOed ? '#fee2e2' : $rowBg }};">
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;white-space:nowrap;">{{ $m['date'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;font-weight:600;">{{ $m['age_months'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;font-weight:700;">{{ $m['weight_kg'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $m['height_cm'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $m['muac_cm'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $m['bmi'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;text-align:center;">
                        @if($hasOed)
                            <span style="color:#dc2626;font-weight:700;">Yes</span>
                        @else
                            <span style="color:#15803d;">No</span>
                        @endif
                    </td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;">
                        <span style="background:{{ $statusBg }};color:#fff;padding:1px 6px;border-radius:8px;font-size:9px;white-space:nowrap;">
                            {{ $status }}
                        </span>
                    </td>
                    <td style="padding:6px 8px;border:1px solid #a7f3d0;font-size:10px;">{{ $m['notes'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p style="font-size:12px;color:#6b7280;font-style:italic;">No measurements recorded.</p>
    @endif
</div>

{{-- ── Section 7: Weight-for-Age Trend (ASCII) ── --}}
@if(count($measurements) > 1)
<div style="margin-bottom:20px;border:1px solid #a7f3d0;border-radius:6px;padding:12px 16px;">
    <h3 style="font-size:11px;font-weight:700;color:#065f46;text-transform:uppercase;margin-bottom:8px;">
        Weight-for-Age Trend
    </h3>
    <table style="border-collapse:collapse;font-size:11px;width:100%;">
        <thead>
            <tr style="background:#d1fae5;">
                <th style="padding:4px 8px;border:1px solid #a7f3d0;text-align:left;">Date</th>
                <th style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">Age (mo)</th>
                <th style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">Weight (kg)</th>
                <th style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">Trend</th>
            </tr>
        </thead>
        <tbody>
            @foreach($measurements as $i => $m)
            @php
                if ($i === 0) {
                    $trend = '—';
                    $trendColor = '#6b7280';
                } else {
                    $prevW = (float)($measurements[$i - 1]['weight_kg'] ?? 0);
                    $currW = (float)($m['weight_kg'] ?? 0);
                    if ($currW > $prevW) {
                        $trend = '&#8593; +' . round($currW - $prevW, 2) . ' kg';
                        $trendColor = '#15803d';
                    } elseif ($currW < $prevW) {
                        $trend = '&#8595; -' . round($prevW - $currW, 2) . ' kg';
                        $trendColor = '#dc2626';
                    } else {
                        $trend = '&#8594; No change';
                        $trendColor = '#d97706';
                    }
                }
            @endphp
            <tr style="background:{{ $i % 2 === 0 ? '#f0fdf4' : '#fff' }};">
                <td style="padding:4px 8px;border:1px solid #a7f3d0;">{{ $m['date'] ?? '—' }}</td>
                <td style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;">{{ $m['age_months'] ?? '—' }}</td>
                <td style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;font-weight:700;">{{ $m['weight_kg'] ?? '—' }}</td>
                <td style="padding:4px 8px;border:1px solid #a7f3d0;text-align:center;font-weight:700;color:{{ $trendColor }};">
                    {!! $trend !!}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Section 8: Interventions ── --}}
@if(count($interventions) > 0)
<div style="margin-bottom:20px;">
    <h3 style="font-size:12px;font-weight:700;color:#065f46;text-transform:uppercase;border-bottom:2px solid #a7f3d0;padding-bottom:4px;margin-bottom:10px;">
        Interventions
    </h3>
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
            <tr style="background:#d1fae5;color:#065f46;">
                <th style="padding:6px 10px;border:1px solid #a7f3d0;text-align:left;">Date</th>
                <th style="padding:6px 10px;border:1px solid #a7f3d0;text-align:left;">Intervention</th>
                <th style="padding:6px 10px;border:1px solid #a7f3d0;text-align:left;">Outcome</th>
            </tr>
        </thead>
        <tbody>
            @foreach($interventions as $i => $intv)
            <tr style="background:{{ $i % 2 === 0 ? '#f0fdf4' : '#fff' }};">
                <td style="padding:6px 10px;border:1px solid #a7f3d0;white-space:nowrap;">{{ $intv['date'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #a7f3d0;">{{ $intv['intervention'] ?? '—' }}</td>
                <td style="padding:6px 10px;border:1px solid #a7f3d0;">{{ $intv['outcome'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Section 9: Therapeutic Feeding ── --}}
@if($therapFeeding)
<div style="margin-bottom:20px;background:#ecfdf5;border:1px solid #6ee7b7;border-radius:6px;padding:10px 14px;">
    <p style="font-size:10px;text-transform:uppercase;font-weight:700;color:#065f46;margin:0 0 4px;">Therapeutic Feeding Prescription</p>
    <p style="font-size:13px;font-weight:600;margin:0;color:#065f46;">{{ $therapFeeding }}</p>
</div>
@endif

{{-- ── Section 10: Referred to Nutrition + Next Measurement + Signature ── --}}
<div style="border-top:2px solid #a7f3d0;padding-top:14px;display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;">
    <div>
        <div style="margin-bottom:8px;display:flex;align-items:center;gap:8px;">
            <span style="font-size:12px;font-weight:600;">Referred to Nutrition:</span>
            <span style="background:{{ $refNutrition ? '#15803d' : '#6b7280' }};color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;">
                {{ $refNutrition ? 'Yes' : 'No' }}
            </span>
        </div>
        <p style="font-size:12px;margin:0;"><strong>Next Measurement:</strong> {{ $nextMeasDate }}</p>
    </div>
    <div style="text-align:right;">
        <p style="font-size:12px;font-weight:700;margin:0;">{{ $healthWorker }}</p>
        <p style="font-size:11px;color:#6b7280;margin:2px 0 0;">Health Worker</p>
        <p style="font-size:11px;color:#9ca3af;margin:8px 0 0;">Signature: _______________________________</p>
    </div>
</div>
@endsection
