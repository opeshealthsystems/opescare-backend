@extends('documents.base')

@section('title', 'Resuscitation / CPR Record')

@section('subtitle', 'CPR — CLINICAL EMERGENCY RECORD / DOSSIER D\'URGENCE CLINIQUE')

@section('content')
@php
    $rhythmColors = [
        'Ventricular Fibrillation (VF)' => 'bg-red-600 text-white',
        'Pulseless VT'                  => 'bg-red-500 text-white',
        'PEA'                           => 'bg-orange-500 text-white',
        'Asystole'                      => 'bg-gray-900 text-white',
        'Unknown'                       => 'bg-gray-400 text-white',
    ];

    $shocks         = $payload['shocks']                       ?? [];
    $drugsGiven     = $payload['drugs_given']                  ?? [];
    $interventions  = $payload['interventions']                ?? [];
    $ivioAccess     = $payload['iv_io_access']                 ?? [];
    $bloodGas       = $payload['blood_gas']                    ?? null;
    $teamMembers    = $payload['team_members']                 ?? [];
    $reversible     = $payload['reversible_causes_considered'] ?? [];
    $airway         = $payload['airway_management']            ?? [];
    $arrestRhythm   = $payload['arrest_rhythm']                ?? '';
    $outcome        = $payload['outcome']                      ?? '';
    $roscAchieved   = !empty($payload['rosc_achieved']);
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — RED HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl p-4" style="background-color:#FEF2F2;border:2px solid #DC2626;">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-sm font-black uppercase tracking-widest" style="color:#DC2626;">Resuscitation / CPR Record</div>
            <div class="text-xs font-semibold mt-0.5 uppercase tracking-wider text-red-700">Dossier de réanimation cardio-pulmonaire</div>
            <div class="text-xs text-red-700 mt-1 space-x-3">
                <span><span class="font-semibold">Location:</span> {{ $payload['arrest_location'] ?? '—' }}</span>
                <span><span class="font-semibold">Date:</span> {{ $payload['arrest_date'] ?? '—' }}</span>
                <span><span class="font-semibold">Time:</span> {{ $payload['arrest_time'] ?? '—' }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block rounded-full {{ $rhythmColors[$arrestRhythm] ?? 'bg-gray-500 text-white' }} px-3 py-1 text-xs font-bold uppercase">
                {{ $arrestRhythm ?: '—' }}
            </span>
            <span class="inline-block rounded-full bg-red-600 text-white text-xs font-bold px-3 py-1 uppercase tracking-wide">CPR</span>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — ARREST DETAILS: WITNESSED, TEAM
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-2 gap-4">
    <div class="rounded-lg border border-red-200 bg-red-50 p-4">
        <div class="text-xs font-bold text-red-700 uppercase tracking-wider mb-2">Arrest Details</div>
        <div class="space-y-1 text-xs text-gray-800">
            <div class="flex items-center gap-2">
                <span class="font-semibold">Witnessed:</span>
                @if (!empty($payload['witnessed']))
                    <span class="inline-block rounded-full bg-amber-100 text-amber-800 px-2 py-0.5 text-xs font-semibold">Witnessed</span>
                    @if (!empty($payload['witness_name']))
                        <span class="text-gray-600">— {{ $payload['witness_name'] }}</span>
                    @endif
                @else
                    <span class="inline-block rounded-full bg-gray-100 text-gray-700 px-2 py-0.5 text-xs font-semibold">Unwitnessed</span>
                @endif
            </div>
            <div><span class="font-semibold">CPR Started:</span> {{ $payload['cpr_started_time'] ?? '—' }}</div>
            <div><span class="font-semibold">First Responder:</span> {{ $payload['first_responder'] ?? '—' }}</div>
            <div><span class="font-semibold">Team Leader:</span> {{ $payload['team_leader'] ?? '—' }}</div>
        </div>
    </div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Team Members</div>
        @if (!empty($teamMembers))
            <ul class="space-y-0.5">
                @foreach ($teamMembers as $member)
                    <li class="text-xs text-gray-800 flex items-center gap-1">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                        {{ $member }}
                    </li>
                @endforeach
            </ul>
        @else
            <div class="text-xs text-gray-400">Not recorded</div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — TIMELINE STRIP
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Event Timeline</h3>
    <div class="flex items-center flex-wrap gap-0">
        <div class="flex items-center">
            <div class="rounded-lg bg-red-600 text-white text-xs font-bold px-3 py-1.5 text-center">
                <div>Arrest</div>
                <div class="text-[10px] font-normal">{{ $payload['arrest_time'] ?? '—' }}</div>
            </div>
            <div class="h-0.5 w-6 bg-gray-400"></div>
        </div>
        <div class="flex items-center">
            <div class="rounded-lg bg-amber-500 text-white text-xs font-bold px-3 py-1.5 text-center">
                <div>CPR Started</div>
                <div class="text-[10px] font-normal">{{ $payload['cpr_started_time'] ?? '—' }}</div>
            </div>
        </div>
        @foreach ($shocks as $shock)
            @if (!empty($shock['rosc']))
                <div class="flex items-center">
                    <div class="h-0.5 w-6 bg-gray-400"></div>
                    <div class="rounded-lg bg-yellow-500 text-white text-xs font-bold px-2 py-1.5 text-center">
                        <div>Shock #{{ $shock['shock_number'] ?? '?' }}</div>
                        <div class="text-[10px] font-normal">{{ $shock['time'] ?? '—' }}</div>
                    </div>
                </div>
            @endif
        @endforeach
        @if ($roscAchieved)
            <div class="flex items-center">
                <div class="h-0.5 w-6 bg-gray-400"></div>
                <div class="rounded-lg bg-green-600 text-white text-xs font-bold px-3 py-1.5 text-center">
                    <div>ROSC</div>
                    <div class="text-[10px] font-normal">{{ $payload['rosc_time'] ?? '—' }}</div>
                </div>
            </div>
        @elseif (!empty($payload['death_time']))
            <div class="flex items-center">
                <div class="h-0.5 w-6 bg-gray-400"></div>
                <div class="rounded-lg bg-gray-900 text-white text-xs font-bold px-3 py-1.5 text-center">
                    <div>Death Pronounced</div>
                    <div class="text-[10px] font-normal">{{ $payload['death_time'] }}</div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — AIRWAY MANAGEMENT
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Airway Management</h3>
    @if (!empty($airway))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 grid grid-cols-3 gap-3 text-xs text-gray-700">
            <div>
                <span class="font-semibold">Method: </span>
                <span class="inline-block rounded bg-gray-700 text-white px-2 py-0.5 text-xs font-bold">{{ $airway['method'] ?? '—' }}</span>
            </div>
            @if (!empty($airway['intubation_time']))
                <div><span class="font-semibold">Intubated:</span> {{ $airway['intubation_time'] }} by {{ $airway['intubated_by'] ?? '—' }}</div>
            @endif
            @if (!empty($airway['tube_size']))
                <div><span class="font-semibold">Tube Size:</span> {{ $airway['tube_size'] }}</div>
            @endif
            @if (!empty($airway['confirmed_by']))
                <div><span class="font-semibold">Confirmed By:</span> {{ $airway['confirmed_by'] }}</div>
            @endif
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — IV/IO ACCESS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($ivioAccess))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">IV / IO Access</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Type</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Site</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Time</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Gauge</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ivioAccess as $access)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5">
                            <span class="inline-block rounded bg-gray-700 text-white text-xs font-bold px-2 py-0.5">{{ $access['type'] ?? '—' }}</span>
                        </td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-800">{{ $access['site'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $access['time'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $access['gauge'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — SHOCKS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($shocks))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Defibrillation / Shocks</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-red-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">#</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Time</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Joules</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Rhythm Before</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Rhythm After</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">ROSC?</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($shocks as $shock)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 font-semibold text-gray-700">{{ $shock['shock_number'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $shock['time'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 font-semibold text-gray-800">{{ $shock['joules'] ?? '—' }}J</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $shock['rhythm_before'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $shock['rhythm_after'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5">
                            @if (!empty($shock['rosc']))
                                <span class="text-green-700 font-bold">Yes</span>
                            @else
                                <span class="text-gray-400">No</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — DRUGS GIVEN
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($drugsGiven))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Drugs Given</h3>
        <div class="space-y-1">
            @foreach ($drugsGiven as $drug)
                <div class="flex items-center gap-3 rounded bg-gray-50 border border-gray-200 px-3 py-1.5 text-xs">
                    <span class="font-mono text-gray-500 w-14 flex-shrink-0">{{ $drug['time'] ?? '—' }}</span>
                    <span class="font-bold text-gray-900">{{ $drug['drug'] ?? '—' }}</span>
                    <span class="text-gray-700">{{ $drug['dose'] ?? '—' }}</span>
                    <span class="text-gray-500">{{ $drug['route'] ?? '—' }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — OTHER INTERVENTIONS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($interventions))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Interventions</h3>
        <div class="space-y-1">
            @foreach ($interventions as $intervention)
                <div class="flex items-center gap-3 rounded bg-gray-50 border border-gray-200 px-3 py-1.5 text-xs">
                    <span class="font-mono text-gray-500 w-14 flex-shrink-0">{{ $intervention['time'] ?? '—' }}</span>
                    <span class="text-gray-800">{{ $intervention['intervention'] ?? '—' }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — BLOOD GAS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($bloodGas))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Blood Gas Results</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Time</th>
                    <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">pH</th>
                    <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">pCO2</th>
                    <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">pO2</th>
                    <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">HCO3</th>
                    <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">Lactate</th>
                    <th class="border border-gray-200 px-2 py-2 text-left font-semibold text-gray-700">K+</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bloodGas as $bg)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-2 py-1.5 font-mono text-gray-700">{{ $bg['time'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1.5 text-gray-800">{{ $bg['ph'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $bg['pco2'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $bg['po2'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $bg['hco3'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $bg['lactate'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-2 py-1.5 text-gray-700">{{ $bg['k'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — 4Hs AND 4Ts
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($reversible))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Reversible Causes Considered (4Hs &amp; 4Ts)</h3>
        <div class="grid grid-cols-4 gap-2">
            @foreach ($reversible as $cause)
                <div class="rounded border {{ !empty($cause['addressed']) ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50' }} p-2 text-xs text-center">
                    <div class="font-semibold {{ !empty($cause['addressed']) ? 'text-green-800' : 'text-gray-700' }}">{{ $cause['cause'] ?? '—' }}</div>
                    <div class="{{ !empty($cause['addressed']) ? 'text-green-600' : 'text-gray-400' }} mt-0.5">
                        {{ !empty($cause['addressed']) ? 'Addressed' : 'Not addressed' }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — OUTCOME (PROMINENT)
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Outcome</h3>
    <div class="rounded-xl p-4 {{ $roscAchieved ? 'bg-green-50 border-2 border-green-500' : 'bg-gray-900 border-2 border-gray-700' }}">
        <div class="text-center">
            <div class="text-lg font-black uppercase tracking-widest {{ $roscAchieved ? 'text-green-800' : 'text-white' }}">
                {{ $roscAchieved ? 'ROSC Achieved' : 'Resuscitation Ceased' }}
            </div>
            @if ($roscAchieved && !empty($payload['rosc_time']))
                <div class="text-sm font-semibold text-green-700 mt-1">Time: {{ $payload['rosc_time'] }}</div>
                @if (!empty($payload['rosc_rhythm']))
                    <div class="text-xs text-green-600 mt-0.5">Rhythm: {{ $payload['rosc_rhythm'] }}</div>
                @endif
            @elseif (!empty($payload['death_time']))
                <div class="text-sm font-semibold text-gray-300 mt-1">Death Pronounced: {{ $payload['death_time'] }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Durations --}}
<div class="mb-6 grid grid-cols-2 gap-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Total CPR Duration</div>
        <div class="text-2xl font-black text-gray-800 mt-1">{{ $payload['total_cpr_duration_min'] ?? '—' }}<span class="text-xs font-normal text-gray-500 ml-1">min</span></div>
    </div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Total Resuscitation Duration</div>
        <div class="text-2xl font-black text-gray-800 mt-1">{{ $payload['total_resuscitation_duration_min'] ?? '—' }}<span class="text-xs font-normal text-gray-500 ml-1">min</span></div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 13 — TEAM LEADER SIGNATURE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t border-gray-200 pt-4">
    <div class="grid grid-cols-3 gap-6 text-xs text-gray-700">
        <div>
            <div class="font-semibold text-gray-800">Team Leader</div>
            <div class="mt-4 border-b border-gray-400 w-40"></div>
            <div class="mt-1">{{ $payload['team_leader_sig'] ?? $payload['team_leader'] ?? $issuer_name ?? '—' }}</div>
        </div>
        <div>
            <div class="font-semibold text-gray-800">Timestamp</div>
            <div class="mt-4 border-b border-gray-400 w-32"></div>
            <div class="mt-1">{{ $issued_at ?? '—' }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">Document No.</div>
            <div class="font-mono font-semibold text-gray-700">{{ $document_number ?? '—' }}</div>
        </div>
    </div>
</div>
@endsection
