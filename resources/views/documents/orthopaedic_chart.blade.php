@extends('documents.base')

@section('title', 'Orthopaedic Fracture Chart')

@section('subtitle', 'ORT — CLINICAL / CLINIQUE')

@section('content')
@php
    $mgmtColors = [
        'Conservative'              => 'bg-green-100 text-green-800',
        'Surgical — ORIF'           => 'bg-red-100 text-red-800',
        'Surgical — IM Nail'        => 'bg-red-100 text-red-800',
        'Surgical — External Fixator'=> 'bg-orange-100 text-orange-800',
        'Arthroplasty'              => 'bg-purple-100 text-purple-800',
    ];
    $wbColors = [
        'Non-weight bearing'      => 'bg-red-100 text-red-800',
        'Touch-weight bearing'    => 'bg-orange-100 text-orange-800',
        'Partial weight bearing'  => 'bg-amber-100 text-amber-800',
        'Full weight bearing'     => 'bg-green-100 text-green-800',
    ];

    $fracClass      = $payload['fracture_classification'] ?? [];
    $nvChecks       = $payload['neurovascular_checks']    ?? [];
    $imaging        = $payload['imaging']                 ?? [];
    $immobilisation = $payload['immobilisation']          ?? [];
    $traction       = $payload['traction']                ?? null;
    $complications  = $payload['complications']           ?? [];
    $followUp       = $payload['follow_up_schedule']      ?? [];
    $mgmt           = $payload['management_type']         ?? '';
    $wbStatus       = $payload['weight_bearing_status']   ?? '';
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl p-4" style="background-color:#F3F4F6;border:2px solid #374151;">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-sm font-black uppercase tracking-widest" style="color:#374151;">Orthopaedic Fracture Chart</div>
            <div class="text-xs text-gray-500 font-semibold mt-0.5 uppercase tracking-wider">Fiche de fracture orthopédique</div>
            <div class="text-xs text-gray-600 mt-1 space-x-3">
                <span><span class="font-semibold">Fracture Site:</span> {{ $payload['fracture_site'] ?? '—' }}</span>
                <span><span class="font-semibold">Mechanism:</span> {{ $payload['injury_mechanism'] ?? '—' }}</span>
                <span><span class="font-semibold">Injury Date:</span> {{ $payload['injury_date'] ?? '—' }}</span>
            </div>
        </div>
        <span class="inline-block rounded-full text-white text-xs font-bold px-3 py-1 uppercase tracking-wide" style="background-color:#374151;">ORT</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — FRACTURE CLASSIFICATION
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Fracture Classification</h3>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="flex flex-wrap gap-2 mb-3">
            <span class="inline-block rounded bg-gray-200 text-gray-800 text-xs font-semibold px-2 py-0.5">
                {{ $fracClass['system'] ?? '—' }}
            </span>
            <span class="inline-block rounded bg-gray-700 text-white text-xs font-bold px-2 py-0.5">
                {{ $fracClass['classification'] ?? '—' }}
            </span>
            @if (!empty($payload['open_fracture']))
                <span class="inline-block rounded-full bg-red-600 text-white text-xs font-bold px-2 py-0.5">OPEN FRACTURE</span>
                @if (!empty($payload['gustilo_grade']))
                    <span class="inline-block rounded-full bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5">{{ $payload['gustilo_grade'] }}</span>
                @endif
            @else
                <span class="inline-block rounded-full bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5">Closed</span>
            @endif
        </div>
        @if (!empty($fracClass['description']))
            <div class="text-xs text-gray-700">{{ $fracClass['description'] }}</div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — IMAGING SUMMARY
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($imaging))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Imaging</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Date</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Modality</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Finding</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($imaging as $img)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $img['date'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $img['modality'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-800">{{ $img['finding'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — MANAGEMENT TYPE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Management</h3>
    <div class="flex items-start gap-3 flex-wrap">
        <span class="inline-block rounded-full {{ $mgmtColors[$mgmt] ?? 'bg-gray-100 text-gray-700' }} px-4 py-1 text-sm font-bold">
            {{ $mgmt ?: '—' }}
        </span>
        @if (!empty($payload['operative_details']))
            <div class="flex-1 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700">
                <span class="font-semibold">Operative Details: </span>{{ $payload['operative_details'] }}
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — NEUROVASCULAR CHECKS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($nvChecks))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Neurovascular Checks</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Time</th>
                        <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Pulse Distal</th>
                        <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Sensation</th>
                        <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Movement</th>
                        <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Cap Refill (s)</th>
                        <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Warmth</th>
                        <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Done By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nvChecks as $nv)
                        @php
                            $critical = (($nv['pulse_distal'] ?? '') === 'Absent') || (($nv['sensation'] ?? '') === 'Absent');
                        @endphp
                        <tr class="{{ $critical ? 'bg-red-50' : 'even:bg-gray-50' }}">
                            <td class="border border-gray-200 px-2 py-1.5 {{ $critical ? 'text-red-800 font-semibold' : 'text-gray-700' }}">{{ $nv['time'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-2 py-1.5 {{ ($nv['pulse_distal'] ?? '') === 'Absent' ? 'text-red-700 font-bold' : 'text-gray-700' }}">{{ $nv['pulse_distal'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-2 py-1.5 {{ ($nv['sensation'] ?? '') === 'Absent' ? 'text-red-700 font-bold' : 'text-gray-700' }}">{{ $nv['sensation'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-2 py-1.5 {{ ($nv['movement'] ?? '') === 'Absent' ? 'text-red-700 font-bold' : 'text-gray-700' }}">{{ $nv['movement'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $nv['capillary_refill_sec'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $nv['warmth'] ?? '—' }}</td>
                            <td class="border border-gray-200 px-2 py-1.5 text-gray-600">{{ $nv['done_by'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-1 text-xs text-red-600 font-semibold">&#9888; Red rows = critical finding (absent pulse or sensation)</div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — IMMOBILISATION
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Immobilisation</h3>
    @if (!empty($immobilisation))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 mb-3">
            <div class="grid grid-cols-3 gap-3 text-xs text-gray-700 mb-3">
                <div><span class="font-semibold">Type:</span> {{ $immobilisation['type'] ?? '—' }}</div>
                <div><span class="font-semibold">Applied Date:</span> {{ $immobilisation['applied_date'] ?? '—' }}</div>
                <div><span class="font-semibold">Applied By:</span> {{ $immobilisation['applied_by'] ?? '—' }}</div>
            </div>
            @if (!empty($immobilisation['cast_checks']))
                <div class="text-xs font-semibold text-gray-700 mb-1">Cast Check Log</div>
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border border-gray-300 px-2 py-1.5 text-left font-semibold text-gray-700">Date</th>
                            <th class="border border-gray-300 px-2 py-1.5 text-left font-semibold text-gray-700">Condition</th>
                            <th class="border border-gray-300 px-2 py-1.5 text-left font-semibold text-gray-700">NV OK</th>
                            <th class="border border-gray-300 px-2 py-1.5 text-left font-semibold text-gray-700">Done By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($immobilisation['cast_checks'] as $check)
                            <tr class="even:bg-gray-50">
                                <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $check['date'] ?? '—' }}</td>
                                <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $check['condition'] ?? '—' }}</td>
                                <td class="border border-gray-200 px-2 py-1.5">
                                    @if (!empty($check['neurovascular_ok']))
                                        <span class="text-green-700 font-semibold">Yes</span>
                                    @else
                                        <span class="text-red-700 font-semibold">No</span>
                                    @endif
                                </td>
                                <td class="border border-gray-200 px-2 py-1.5 text-gray-600">{{ $check['done_by'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — TRACTION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($traction))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Traction</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 grid grid-cols-4 gap-3 text-xs text-gray-700">
            <div><span class="font-semibold">Type:</span> {{ $traction['type'] ?? '—' }}</div>
            <div><span class="font-semibold">Weight:</span> {{ $traction['weight_kg'] ?? '—' }} kg</div>
            <div><span class="font-semibold">Direction:</span> {{ $traction['direction'] ?? '—' }}</div>
            <div><span class="font-semibold">Applied:</span> {{ $traction['applied_date'] ?? '—' }}</div>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — WEIGHT-BEARING STATUS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex items-center gap-3">
    <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Weight-Bearing Status:</span>
    <span class="inline-block rounded-full {{ $wbColors[$wbStatus] ?? 'bg-gray-100 text-gray-700' }} px-5 py-1.5 text-sm font-black uppercase tracking-wide">
        {{ $wbStatus ?: '—' }}
    </span>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — COMPLICATIONS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($complications))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Complications</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 flex flex-wrap gap-2">
            @foreach ($complications as $comp)
                <span class="inline-block rounded-full {{ $comp === 'None' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} px-3 py-0.5 text-xs font-semibold">{{ $comp }}</span>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9B — PHYSIOTHERAPY PLAN
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['physiotherapy_plan']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Physiotherapy Plan</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-800">{{ $payload['physiotherapy_plan'] }}</div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — FOLLOW-UP SCHEDULE
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($followUp))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Follow-up Schedule</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Date</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">X-Ray Required</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($followUp as $fu)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $fu['date'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5">
                            @if (!empty($fu['xray_required']))
                                <span class="text-amber-700 font-semibold">Yes</span>
                            @else
                                <span class="text-gray-400">No</span>
                            @endif
                        </td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-800">{{ $fu['action'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — SURGEON SIGNATURE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t border-gray-200 pt-4">
    <div class="grid grid-cols-3 gap-6 text-xs text-gray-700">
        <div>
            <div class="font-semibold text-gray-800">Orthopaedic Surgeon</div>
            <div class="mt-4 border-b border-gray-400 w-40"></div>
            <div class="mt-1">{{ $payload['orthopaedic_surgeon'] ?? $issuer_name ?? '—' }}</div>
        </div>
        <div>
            <div class="font-semibold text-gray-800">Date</div>
            <div class="mt-4 border-b border-gray-400 w-32"></div>
            <div class="mt-1">{{ $issued_at ?? '—' }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">Document No.</div>
            <div class="font-mono font-semibold text-gray-700">{{ $document_number ?? '—' }}</div>
            @if (!empty($payload['expected_union_weeks']))
                <div class="mt-1 text-xs text-gray-500">Expected Union: {{ $payload['expected_union_weeks'] }} weeks</div>
            @endif
        </div>
    </div>
</div>
@endsection
