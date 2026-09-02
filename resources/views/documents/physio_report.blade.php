@extends('documents.base')

@section('title', 'Physiotherapy / Rehabilitation Report')

@section('subtitle', 'PHY — ' . ($payload['session_type'] ?? ''))

@section('content')
@php
    $mobilityColors = [
        'Independent' => 'bg-green-100 text-green-800',
        'Supervision' => 'bg-yellow-100 text-yellow-800',
        'Assisted'    => 'bg-amber-100 text-amber-800',
        'Dependent'   => 'bg-red-100 text-red-800',
    ];
    $prognosisColors = [
        'Good'     => 'bg-green-100 text-green-800',
        'Fair'     => 'bg-amber-100 text-amber-800',
        'Guarded'  => 'bg-red-100 text-red-800',
    ];
    $sessionTypeColors = [
        'Initial Assessment'    => 'bg-teal-700 text-white',
        'Progress Review'       => 'bg-blue-700 text-white',
        'Discharge Assessment'  => 'bg-green-700 text-white',
    ];

    $funcAss  = $payload['functional_assessment'] ?? [];
    $objMeas  = $payload['objective_measures']    ?? [];
    $rom      = $payload['range_of_motion']        ?? [];
    $strength = $payload['muscle_strength']        ?? [];
    $problems = $payload['problems_identified']    ?? [];
    $stGoals  = $payload['short_term_goals']       ?? [];
    $ltGoals  = $payload['long_term_goals']        ?? [];
    $txPlan   = $payload['treatment_plan']         ?? [];
    $notes    = $payload['session_notes']          ?? [];
    $hep      = $payload['home_exercise_programme'] ?? [];
    $equipment = $payload['equipment_prescribed']  ?? [];

    $vasScore = $payload['pain_assessment']['severity_vas'] ?? null;

    $mrcColor = function ($grade) {
        $g = (int) $grade;
        if ($g <= 2) return 'bg-red-100 text-red-800';
        if ($g === 3) return 'bg-amber-100 text-amber-800';
        return 'bg-green-100 text-green-800';
    };
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — ASSESSMENT HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-teal-200 bg-teal-50 p-5 mb-6">
    <div class="flex flex-wrap gap-2 mb-3">
        <span class="inline-flex items-center rounded-full {{ $sessionTypeColors[$payload['session_type'] ?? ''] ?? 'bg-teal-700 text-white' }} px-3 py-1 text-xs font-semibold uppercase tracking-wide">
            {{ $payload['session_type'] ?? '—' }}
        </span>
    </div>
    <div class="text-lg font-bold text-teal-900 mb-3">{{ $payload['referral_diagnosis'] ?? '—' }}</div>
    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-teal-900">
        <div><span class="font-semibold">Assessment Date:</span> {{ $payload['assessment_date'] ?? '—' }}</div>
        <div><span class="font-semibold">Physiotherapist:</span> {{ $payload['physiotherapist'] ?? '—' }}
            @if (!empty($payload['physiotherapist_reg']))
                <span class="text-teal-600 text-xs ml-1">({{ $payload['physiotherapist_reg'] }})</span>
            @endif
        </div>
        <div><span class="font-semibold">Referring Physician:</span> {{ $payload['referring_physician'] ?? '—' }}</div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — PAIN ASSESSMENT
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['pain_assessment']))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Pain Assessment</h3>
        @php $pain = $payload['pain_assessment']; @endphp
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div><span class="font-semibold text-gray-700">Location:</span> <span class="text-gray-800">{{ $pain['location'] ?? '—' }}</span></div>
                    <div><span class="font-semibold text-gray-700">Character:</span> <span class="text-gray-800">{{ $pain['character'] ?? '—' }}</span></div>
                    <div><span class="font-semibold text-gray-700">Aggravating:</span> <span class="text-gray-800">{{ $pain['aggravating'] ?? '—' }}</span></div>
                    <div><span class="font-semibold text-gray-700">Relieving:</span> <span class="text-gray-800">{{ $pain['relieving'] ?? '—' }}</span></div>
                </div>
            </div>
            @if ($vasScore !== null)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 flex flex-col items-center justify-center">
                    <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">VAS Pain Score</div>
                    <div class="text-4xl font-bold {{ $vasScore >= 7 ? 'text-red-600' : ($vasScore >= 4 ? 'text-amber-600' : 'text-green-600') }}">
                        {{ $vasScore }}<span class="text-lg font-normal text-gray-400">/10</span>
                    </div>
                    {{-- Visual scale bar --}}
                    <div class="mt-3 w-full">
                        <div class="h-3 rounded-full bg-gradient-to-r from-green-400 via-yellow-400 to-red-500 relative">
                            @php $pct = min(100, max(0, ($vasScore / 10) * 100)); @endphp
                            <div class="absolute top-1/2 -translate-y-1/2 h-5 w-5 rounded-full bg-white border-2 border-gray-700 shadow" style="left: {{ $pct }}%;"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>No pain</span><span>Worst</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — FUNCTIONAL ASSESSMENT
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($funcAss))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Functional Assessment</h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
            @foreach ([
                ['Mobility',   $funcAss['mobility']  ?? null, true],
                ['Transfers',  $funcAss['transfers']  ?? null, false],
                ['Gait',       $funcAss['gait']       ?? null, false],
                ['Stairs',     $funcAss['stairs']     ?? null, false],
                ['ADL',        $funcAss['adl']        ?? null, false],
            ] as [$label, $val, $badge])
                @if ($val !== null)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="text-xs text-gray-500 mb-1 uppercase tracking-wide">{{ $label }}</div>
                        @if ($badge)
                            <span class="inline-block rounded-full {{ $mobilityColors[$val] ?? 'bg-gray-100 text-gray-800' }} px-2 py-0.5 text-xs font-semibold">
                                {{ $val }}
                            </span>
                        @else
                            <div class="text-xs text-gray-800">{{ $val }}</div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — OBJECTIVE OUTCOME MEASURES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($objMeas))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Objective Outcome Measures</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-teal-700 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold">Measure</th>
                    <th class="py-2 px-3 text-center font-semibold">Score</th>
                    <th class="py-2 px-3 text-center font-semibold">Normal</th>
                    <th class="py-2 px-3 text-left font-semibold">Interpretation</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($objMeas as $om)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $om['measure'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center font-semibold text-gray-900">{{ $om['value'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-500 text-xs">{{ $om['normal_value'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-600 text-xs italic">{{ $om['interpretation'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — RANGE OF MOTION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($rom))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Range of Motion</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-teal-700 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold">Joint</th>
                    <th class="py-2 px-3 text-left font-semibold">Movement</th>
                    <th class="py-2 px-3 text-center font-semibold">Active (°)</th>
                    <th class="py-2 px-3 text-center font-semibold">Passive (°)</th>
                    <th class="py-2 px-3 text-center font-semibold">Normal (°)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($rom as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $r['joint'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-800">{{ $r['movement'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-900">{{ $r['active_degrees'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-900">{{ $r['passive_degrees'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-500 text-xs">{{ $r['normal'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — MUSCLE STRENGTH (MRC)
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($strength))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Muscle Strength (MRC Scale)</h3>
        <div class="grid grid-cols-3 gap-2">
            @foreach ($strength as $s)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold text-gray-700">{{ $s['muscle_group'] ?? '—' }}</div>
                        @if (!empty($s['side']))
                            <div class="text-xs text-gray-400">{{ $s['side'] }}</div>
                        @endif
                    </div>
                    @if (isset($s['mrc_grade']))
                        <span class="inline-block rounded-full {{ $mrcColor($s['mrc_grade']) }} px-3 py-1 text-sm font-bold ml-2">
                            {{ $s['mrc_grade'] }}/5
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-2 text-xs text-gray-400">MRC Scale: 0=No contraction, 1=Flicker, 2=Active against gravity, 3=Active against gravity, 4=Active against some resistance, 5=Normal power</div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — PROBLEMS IDENTIFIED
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($problems))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Problems Identified</h3>
        <ul class="space-y-1">
            @foreach ($problems as $p)
                <li class="flex items-start gap-2 text-sm text-gray-800">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-600 flex-shrink-0"></span>
                    <span>{{ $p }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — GOALS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($stGoals) || !empty($ltGoals))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Rehabilitation Goals</h3>
        <div class="grid grid-cols-2 gap-4">
            @if (!empty($stGoals))
                <div>
                    <div class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-2">Short-Term Goals</div>
                    <ul class="space-y-2">
                        @foreach ($stGoals as $g)
                            <li class="rounded-lg border border-blue-100 bg-blue-50 p-2 text-xs text-blue-900">
                                <div class="font-medium">{{ $g['goal'] ?? '—' }}</div>
                                @if (!empty($g['target_date']))
                                    <div class="text-blue-500 mt-0.5">Target: {{ $g['target_date'] }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (!empty($ltGoals))
                <div>
                    <div class="text-xs font-semibold text-teal-700 uppercase tracking-wide mb-2">Long-Term Goals</div>
                    <ul class="space-y-2">
                        @foreach ($ltGoals as $g)
                            <li class="rounded-lg border border-teal-100 bg-teal-50 p-2 text-xs text-teal-900">
                                <div class="font-medium">{{ $g['goal'] ?? '—' }}</div>
                                @if (!empty($g['target_date']))
                                    <div class="text-teal-500 mt-0.5">Target: {{ $g['target_date'] }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — TREATMENT PLAN
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($txPlan))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Treatment Plan</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-teal-700 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold">Intervention</th>
                    <th class="py-2 px-3 text-center font-semibold">Frequency</th>
                    <th class="py-2 px-3 text-center font-semibold">Duration</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($txPlan as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $t['intervention'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-800">{{ $t['frequency'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-800">{{ $t['duration'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — SESSION NOTES (most recent 3)
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($notes))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Session Progress Notes</h3>
        @foreach (array_slice($notes, -3) as $note)
            <div class="mb-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                <div class="font-semibold text-teal-800 mb-1">{{ $note['date'] ?? '—' }}</div>
                <div class="grid grid-cols-2 gap-2 text-xs text-gray-700">
                    <div><span class="font-medium">Treatment:</span> {{ $note['treatment_given'] ?? '—' }}</div>
                    <div><span class="font-medium">Response:</span> {{ $note['patient_response'] ?? '—' }}</div>
                    <div class="col-span-2"><span class="font-medium">Progress:</span> {{ $note['progress'] ?? '—' }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — HOME EXERCISE PROGRAMME
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($hep))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Home Exercise Programme</h3>
        <ul class="space-y-1">
            @foreach ($hep as $ex)
                <li class="flex items-start gap-2 text-sm text-gray-800">
                    <span class="mt-0.5 flex-shrink-0 h-4 w-4 rounded border border-gray-400 bg-white inline-block"></span>
                    <span>{{ $ex }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 12 — EQUIPMENT, NEXT REVIEW, PROGNOSIS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-3 gap-4">
    @if (!empty($equipment))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Equipment Prescribed</div>
            <ul class="space-y-1">
                @foreach ($equipment as $eq)
                    <li class="text-xs text-gray-800">&#8226; {{ $eq }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (!empty($payload['next_review']))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Next Review</div>
            <div class="text-sm font-bold text-gray-800">{{ $payload['next_review'] }}</div>
        </div>
    @endif
    @if (!empty($payload['prognosis']))
        @php $prog = $payload['prognosis']; @endphp
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Prognosis</div>
            <span class="inline-block rounded-full {{ $prognosisColors[$prog] ?? 'bg-gray-100 text-gray-800' }} px-3 py-1 text-sm font-bold">
                {{ $prog }}
            </span>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 13 — PHYSIOTHERAPIST SIGNATURE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t pt-5">
    <div class="flex justify-end">
        <div class="text-right text-sm">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Reporting Physiotherapist</div>
            <div class="font-bold text-gray-900">{{ $payload['physiotherapist'] ?? $issuer_name }}</div>
            @if (!empty($payload['physiotherapist_reg']))
                <div class="text-gray-500 text-xs">Reg: {{ $payload['physiotherapist_reg'] }}</div>
            @endif
            <div class="text-gray-500">{{ $issued_at }}</div>
        </div>
    </div>
</div>
@endsection
