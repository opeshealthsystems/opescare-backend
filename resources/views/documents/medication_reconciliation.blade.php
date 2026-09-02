@extends('documents.base')

@section('title', 'Medication Reconciliation Form')

@section('subtitle', 'MRC — ' . ($payload['reconciliation_type'] ?? ''))

@section('content')
@php
    $typeColors = [
        'Admission'  => 'bg-blue-700 text-white',
        'Discharge'  => 'bg-green-700 text-white',
        'Transfer'   => 'bg-purple-700 text-white',
    ];
    $reliabilityColors = [
        'Reliable'    => 'bg-green-100 text-green-800',
        'Uncertain'   => 'bg-amber-100 text-amber-800',
        'Unavailable' => 'bg-red-100 text-red-800',
    ];
    $homeStatusColors = [
        'Taking as prescribed' => 'bg-green-100 text-green-800',
        'Not taking'           => 'bg-red-100 text-red-800',
        'Taking differently'   => 'bg-amber-100 text-amber-800',
        'Unknown'              => 'bg-gray-100 text-gray-600',
    ];
    $decisionColors = [
        'Continue'            => 'bg-green-100 text-green-800',
        'Stop'                => 'bg-red-100 text-red-800',
        'Dose change'         => 'bg-amber-100 text-amber-800',
        'New'                 => 'bg-blue-100 text-blue-800',
        'Withhold temporarily'=> 'bg-orange-100 text-orange-800',
        'Withhold'            => 'bg-orange-100 text-orange-800',
    ];

    $meds     = $payload['medications']          ?? [];
    $allergies = $payload['allergies_confirmed'] ?? [];
    $sources  = $payload['source_of_information'] ?? [];
    $newMeds  = $payload['new_medications_added'] ?? [];

    $countContinued    = 0;
    $countStopped      = 0;
    $countDoseChanged  = 0;
    $countNew          = 0;
    foreach ($meds as $m) {
        $dec = $m['discharge_decision'] ?? $m['admission_decision'] ?? '';
        if ($dec === 'Continue')     $countContinued++;
        elseif ($dec === 'Stop')     $countStopped++;
        elseif ($dec === 'Dose change') $countDoseChanged++;
        elseif ($dec === 'New')      $countNew++;
    }
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — RECONCILIATION HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-blue-200 bg-blue-50 p-5 mb-6">
    <div class="flex flex-wrap gap-2 mb-3">
        <span class="inline-flex items-center rounded-full {{ $typeColors[$payload['reconciliation_type'] ?? ''] ?? 'bg-blue-700 text-white' }} px-3 py-1 text-xs font-semibold uppercase tracking-wide">
            {{ $payload['reconciliation_type'] ?? '—' }} Reconciliation
        </span>
    </div>
    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-blue-900">
        <div><span class="font-semibold">Date:</span> {{ $payload['reconciliation_date'] ?? '—' }}</div>
        <div><span class="font-semibold">Pharmacist:</span> {{ $payload['pharmacist'] ?? '—' }}</div>
        <div><span class="font-semibold">Prescriber:</span> {{ $payload['prescriber'] ?? '—' }}</div>
    </div>
    @if (!empty($sources))
        <div class="mt-3 pt-3 border-t border-blue-200">
            <div class="text-xs font-semibold text-blue-800 uppercase tracking-wide mb-1">Sources of Information</div>
            <div class="flex flex-wrap gap-2">
                @foreach ($sources as $src)
                    <span class="inline-block rounded-full bg-blue-100 text-blue-800 px-2 py-0.5 text-xs">{{ $src }}</span>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — INFORMATION RELIABILITY
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['information_reliability']))
    <div class="mb-4 flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-700">Information Reliability:</span>
        @php $rel = $payload['information_reliability']; @endphp
        <span class="inline-block rounded-full {{ $reliabilityColors[$rel] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-semibold">
            {{ $rel }}
        </span>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — ALLERGIES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($allergies))
    <div class="mb-6 rounded-lg border-2 border-red-400 bg-red-50 p-4">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-sm font-bold text-red-800 uppercase tracking-wide">Confirmed Allergies</span>
        </div>
        <div class="grid grid-cols-3 gap-3">
            @foreach ($allergies as $a)
                <div class="rounded-md bg-white border border-red-200 p-2 text-xs">
                    <div class="font-bold text-red-700">{{ $a['allergen'] ?? '—' }}</div>
                    <div class="text-gray-700 mt-0.5">{{ $a['reaction'] ?? '—' }}</div>
                    <div class="text-red-500 mt-0.5 font-medium">{{ $a['severity'] ?? '' }}</div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 font-semibold">
        No Known Drug Allergies (NKDA)
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — MEDICATIONS TABLE
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($meds))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Medication Reconciliation</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-blue-700 text-white uppercase tracking-wider">
                        <th class="py-2 px-2 text-left font-semibold">Drug</th>
                        <th class="py-2 px-2 text-center font-semibold">Dose</th>
                        <th class="py-2 px-2 text-center font-semibold">Freq</th>
                        <th class="py-2 px-2 text-center font-semibold">Route</th>
                        <th class="py-2 px-2 text-left font-semibold">Indication</th>
                        <th class="py-2 px-2 text-center font-semibold">Home Status</th>
                        <th class="py-2 px-2 text-center font-semibold">Admission</th>
                        <th class="py-2 px-2 text-center font-semibold">Discharge</th>
                        <th class="py-2 px-2 text-left font-semibold">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($meds as $med)
                        @php
                            $admDec = $med['admission_decision'] ?? '';
                            $disDec = $med['discharge_decision'] ?? '';
                            $isHighAlert = !empty($med['high_alert']);
                            $isStopped   = ($disDec === 'Stop' || $admDec === 'Stop');
                            $isNew       = ($disDec === 'New'  || $admDec === 'New');
                            $rowClass = 'hover:bg-gray-50';
                            if ($isHighAlert)  $rowClass = 'bg-orange-50';
                            elseif ($isStopped) $rowClass = 'bg-red-50';
                            elseif ($isNew)     $rowClass = 'bg-green-50';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="py-2 px-2 font-semibold text-gray-800">
                                {{ $med['drug_name'] ?? '—' }}
                                @if ($isHighAlert)
                                    <span class="ml-1 inline-block rounded bg-orange-200 text-orange-800 px-1 py-0.5 text-[10px] font-bold uppercase">HIGH ALERT</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-center text-gray-700">{{ $med['dose'] ?? '—' }}</td>
                            <td class="py-2 px-2 text-center text-gray-700">{{ $med['frequency'] ?? '—' }}</td>
                            <td class="py-2 px-2 text-center text-gray-700">{{ $med['route'] ?? '—' }}</td>
                            <td class="py-2 px-2 text-gray-600">{{ $med['indication'] ?? '—' }}</td>
                            <td class="py-2 px-2 text-center">
                                @if (!empty($med['home_status']))
                                    <span class="inline-block rounded-full {{ $homeStatusColors[$med['home_status']] ?? 'bg-gray-100 text-gray-700' }} px-2 py-0.5 text-[10px] font-semibold">
                                        {{ $med['home_status'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-center">
                                @if (!empty($admDec))
                                    <span class="inline-block rounded-full {{ $decisionColors[$admDec] ?? 'bg-gray-100 text-gray-700' }} px-2 py-0.5 text-[10px] font-semibold">
                                        {{ $admDec }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-center">
                                @if (!empty($disDec))
                                    <span class="inline-block rounded-full {{ $decisionColors[$disDec] ?? 'bg-gray-100 text-gray-700' }} px-2 py-0.5 text-[10px] font-semibold">
                                        {{ $disDec }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-gray-600 italic">{{ $med['reason_for_change'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — SUMMARY BOX
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-4 gap-3">
    <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-center">
        <div class="text-2xl font-bold text-green-700">{{ $countContinued }}</div>
        <div class="text-xs text-green-600 font-semibold mt-1">Continued</div>
    </div>
    <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-center">
        <div class="text-2xl font-bold text-red-700">{{ $countStopped }}</div>
        <div class="text-xs text-red-600 font-semibold mt-1">Stopped</div>
    </div>
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-center">
        <div class="text-2xl font-bold text-amber-700">{{ $countDoseChanged }}</div>
        <div class="text-xs text-amber-600 font-semibold mt-1">Dose Changed</div>
    </div>
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-center">
        <div class="text-2xl font-bold text-blue-700">{{ $countNew }}</div>
        <div class="text-xs text-blue-600 font-semibold mt-1">New Added</div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — PATIENT COUNSELLING & GP
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex flex-wrap gap-4">
    <div>
        <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Patient Counselled</span>
        @php $counselled = $payload['patient_counselled'] ?? false; @endphp
        <span class="inline-block rounded-full {{ $counselled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} px-3 py-1 text-xs font-semibold">
            {{ $counselled ? 'Yes' : 'No' }}
        </span>
    </div>
    @if (!empty($payload['counselling_notes']))
        <div class="flex-1">
            <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Counselling Notes</span>
            <p class="text-xs text-gray-700">{{ $payload['counselling_notes'] }}</p>
        </div>
    @endif
    <div>
        <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">GP Informed</span>
        @php $gpInformed = $payload['gp_informed'] ?? false; @endphp
        <span class="inline-block rounded-full {{ $gpInformed ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }} px-3 py-1 text-xs font-semibold">
            {{ $gpInformed ? 'Yes' : 'Pending' }}
        </span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — SIGNATURES
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t pt-5">
    <div class="grid grid-cols-2 gap-8">
        <div class="text-sm">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Pharmacist</div>
            <div class="font-bold text-gray-900">{{ $payload['pharmacist'] ?? '—' }}</div>
            <div class="text-gray-500 text-xs mt-1">{{ $issued_at }}</div>
        </div>
        <div class="text-sm">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Prescriber</div>
            <div class="font-bold text-gray-900">{{ $payload['prescriber'] ?? '—' }}</div>
            <div class="text-gray-500 text-xs mt-1">{{ $issued_at }}</div>
        </div>
    </div>
</div>
@endsection
