@extends('documents.base')

@section('title', 'Wound Care / Dressing Chart')
@section('subtitle', 'WND — Daily Wound Assessment & Dressing Record')

@section('content')
@php
    $accentColor = '#B45309';
    $woundType    = $payload['wound_type']              ?? 'Unknown';
    $location     = $payload['wound_location']          ?? '—';
    $onset        = $payload['wound_onset_date']        ?? '—';
    $assessments  = $payload['assessments']             ?? [];
    $dims         = $payload['current_wound_dimensions']?? [];
    $swabSent     = $payload['wound_swab_sent']         ?? false;
    $swabResult   = $payload['wound_swab_result']       ?? null;
    $referrals    = $payload['referrals']               ?? [];
    $nutrition    = $payload['nutrition_support']       ?? false;
    $nutNotes     = $payload['nutrition_notes']         ?? null;
    $nurse        = $payload['wound_care_nurse']        ?? '—';

    $tissueColors = [
        'Necrotic'        => '#1f2937',
        'Sloughy'         => '#92400e',
        'Granulating'     => '#15803d',
        'Epithelialising' => '#0369a1',
        'Mixed'           => '#7c3aed',
    ];
@endphp

{{-- ── Section 1: Header strip ── --}}
<div style="background:{{ $accentColor }};color:#fff;padding:14px 20px;border-radius:6px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="background:rgba(0,0,0,.25);padding:4px 12px;border-radius:4px;font-weight:700;font-size:13px;">
            {{ $woundType }}
        </span>
        <span style="font-size:13px;"><strong>Location:</strong> {{ $location }}</span>
        <span style="font-size:13px;"><strong>Onset:</strong> {{ $onset }}</span>
    </div>
</div>

{{-- ── Section 2: Current Wound Dimensions ── --}}
@if(!empty($dims))
<div style="margin-bottom:20px;">
    <h3 style="font-size:13px;font-weight:700;color:#78350f;text-transform:uppercase;margin-bottom:8px;border-bottom:2px solid #fde68a;padding-bottom:4px;">
        Current Wound Dimensions
    </h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        @foreach(['length_cm'=>'Length','width_cm'=>'Width','depth_cm'=>'Depth'] as $key=>$label)
        <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:20px;padding:6px 16px;font-size:13px;">
            <strong>{{ $label }}:</strong> {{ $dims[$key] ?? '—' }} cm
        </div>
        @endforeach
        <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:20px;padding:6px 16px;font-size:13px;">
            <strong>Area:</strong>
            {{ isset($dims['length_cm'], $dims['width_cm'])
                ? round((float)$dims['length_cm'] * (float)$dims['width_cm'], 1)
                : '—' }} cm²
        </div>
    </div>
</div>
@endif

{{-- ── Section 3: Wound Assessments Table ── --}}
<div style="margin-bottom:20px;">
    <h3 style="font-size:13px;font-weight:700;color:#78350f;text-transform:uppercase;margin-bottom:8px;border-bottom:2px solid #fde68a;padding-bottom:4px;">
        Wound Assessment &amp; Dressing Log
    </h3>
    @if(count($assessments) > 0)
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#fef3c7;color:#78350f;">
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;white-space:nowrap;">Date / Time</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;white-space:nowrap;">Dimensions (L×W×D)</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;">Tissue Type</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;">Exudate</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;">Edges</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;">Periwound</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:center;">Odour</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:center;">Pain</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;">Dressing Removed → Applied</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:center;white-space:nowrap;">Next (days)</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;">Done By</th>
                    <th style="padding:6px 8px;border:1px solid #fcd34d;text-align:left;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assessments as $i => $a)
                @php
                    $tissue      = $a['tissue_type'] ?? '—';
                    $tissueColor = $tissueColors[$tissue] ?? '#374151';
                    $rowBg       = ($i % 2 === 0) ? '#fffbeb' : '#fff';
                @endphp
                <tr style="background:{{ $rowBg }};">
                    <td style="padding:6px 8px;border:1px solid #fcd34d;white-space:nowrap;">
                        {{ $a['date'] ?? '—' }}<br>
                        <span style="color:#92400e;font-size:10px;">{{ $a['time'] ?? '' }}</span>
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;white-space:nowrap;font-weight:600;">
                        {{ $a['length_cm'] ?? '?' }}×{{ $a['width_cm'] ?? '?' }}×{{ $a['depth_cm'] ?? '?' }} cm
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;">
                        <span style="background:{{ $tissueColor }};color:#fff;padding:2px 7px;border-radius:10px;font-size:10px;font-weight:600;white-space:nowrap;">
                            {{ $tissue }}
                        </span>
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;">
                        <span style="font-weight:600;">{{ $a['exudate_amount'] ?? '—' }}</span>
                        @if(!empty($a['exudate_type']))
                        <br><span style="font-size:10px;color:#6b7280;">{{ $a['exudate_type'] }}</span>
                        @endif
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;font-size:10px;">{{ $a['wound_edges'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;font-size:10px;">{{ $a['periwound_skin'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;text-align:center;">
                        @if(($a['odour'] ?? '') === 'Present')
                            <span style="color:#dc2626;font-weight:700;">&#9679;</span>
                        @else
                            <span style="color:#15803d;">None</span>
                        @endif
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;text-align:center;font-weight:700;">
                        @php $pain = (int)($a['pain_score'] ?? 0); @endphp
                        <span style="color:{{ $pain >= 7 ? '#dc2626' : ($pain >= 4 ? '#d97706' : '#15803d') }};">
                            {{ $pain }}/10
                        </span>
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;font-size:10px;">
                        {{ $a['dressing_removed'] ?? '—' }}<br>
                        <span style="color:#b45309;">→ {{ $a['dressing_applied'] ?? '—' }}</span>
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;text-align:center;font-weight:600;">
                        {{ $a['next_change_days'] ?? '—' }}
                    </td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;font-size:10px;white-space:nowrap;">{{ $a['done_by'] ?? '—' }}</td>
                    <td style="padding:6px 8px;border:1px solid #fcd34d;font-size:10px;">{{ $a['notes'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p style="color:#92400e;font-style:italic;font-size:12px;">No assessment entries recorded.</p>
    @endif
</div>

{{-- ── Section 4: Wound Swab ── --}}
<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
    <div style="flex:1;min-width:220px;border:1px solid #fcd34d;border-radius:6px;padding:12px;">
        <h3 style="font-size:12px;font-weight:700;color:#78350f;text-transform:uppercase;margin-bottom:8px;">Wound Swab</h3>
        <div style="margin-bottom:6px;">
            <span style="font-size:12px;font-weight:600;">Swab Sent: </span>
            @if($swabSent)
                <span style="background:#15803d;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">Yes</span>
            @else
                <span style="background:#6b7280;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">No</span>
            @endif
        </div>
        <div style="font-size:12px;">
            <strong>Result:</strong>
            <span style="color:{{ $swabResult ? '#dc2626' : '#6b7280' }};">
                {{ $swabResult ?? 'Pending / Not taken' }}
            </span>
        </div>
    </div>

    {{-- Section 5: Referrals --}}
    <div style="flex:1;min-width:220px;border:1px solid #fcd34d;border-radius:6px;padding:12px;">
        <h3 style="font-size:12px;font-weight:700;color:#78350f;text-transform:uppercase;margin-bottom:8px;">Referrals</h3>
        @if(count($referrals) > 0)
            <ul style="margin:0;padding-left:16px;">
                @foreach($referrals as $ref)
                    <li style="font-size:12px;margin-bottom:4px;">{{ $ref }}</li>
                @endforeach
            </ul>
        @else
            <p style="font-size:12px;color:#6b7280;margin:0;font-style:italic;">No referrals made.</p>
        @endif
    </div>

    {{-- Section 6: Nutrition Support --}}
    <div style="flex:1;min-width:220px;border:1px solid #fcd34d;border-radius:6px;padding:12px;">
        <h3 style="font-size:12px;font-weight:700;color:#78350f;text-transform:uppercase;margin-bottom:8px;">Nutrition Support</h3>
        <div style="margin-bottom:6px;">
            @if($nutrition)
                <span style="background:#15803d;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">Active</span>
            @else
                <span style="background:#6b7280;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">Not prescribed</span>
            @endif
        </div>
        @if($nutNotes)
            <p style="font-size:11px;color:#374151;margin:0;">{{ $nutNotes }}</p>
        @endif
    </div>
</div>

{{-- ── Section 7: Wound Care Nurse Signature ── --}}
<div style="border-top:2px solid #fde68a;padding-top:14px;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;">
    <div>
        <p style="font-size:12px;margin:0;color:#374151;"><strong>Wound Care Nurse:</strong> {{ $nurse }}</p>
        <p style="font-size:11px;color:#6b7280;margin:4px 0 0;">Signature: _______________________________</p>
    </div>
    <div style="text-align:right;">
        <p style="font-size:11px;color:#6b7280;margin:0;">Date: ___________________</p>
    </div>
</div>
@endsection
