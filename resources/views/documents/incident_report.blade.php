@extends('documents.base')

@section('title', 'Clinical Incident Report')

@section('subtitle', 'INC — INTERNAL CONFIDENTIAL / CONFIDENTIEL INTERNE')

@section('content')
@php
    $severityColors = [
        'Near Miss'     => 'bg-gray-200 text-gray-700',
        'No Harm'       => 'bg-green-100 text-green-800',
        'Minor Harm'    => 'bg-yellow-100 text-yellow-800',
        'Moderate Harm' => 'bg-amber-100 text-amber-800',
        'Severe Harm'   => 'bg-red-100 text-red-800',
        'Death'         => 'bg-gray-900 text-white',
    ];
    $incidentTypeColors = [
        'Medication Error'         => 'bg-red-100 text-red-800',
        'Patient Fall'             => 'bg-orange-100 text-orange-800',
        'Wrong Patient'            => 'bg-red-200 text-red-900',
        'Wrong Site'               => 'bg-red-200 text-red-900',
        'Equipment Failure'        => 'bg-yellow-100 text-yellow-800',
        'Near Miss'                => 'bg-gray-100 text-gray-700',
        'Adverse Drug Reaction'    => 'bg-purple-100 text-purple-800',
        'Procedure Complication'   => 'bg-orange-100 text-orange-800',
        'Breach of Confidentiality'=> 'bg-blue-100 text-blue-800',
        'Other'                    => 'bg-gray-100 text-gray-700',
    ];
    $rootCauseColors = [
        'Communication'          => 'bg-blue-100 text-blue-800',
        'Training'               => 'bg-purple-100 text-purple-800',
        'Equipment'              => 'bg-yellow-100 text-yellow-800',
        'Protocol not followed'  => 'bg-red-100 text-red-800',
        'Staffing'               => 'bg-amber-100 text-amber-800',
        'Environment'            => 'bg-teal-100 text-teal-800',
        'Patient factors'        => 'bg-gray-100 text-gray-700',
        'Unknown'                => 'bg-gray-100 text-gray-500',
    ];
    $statusColors = [
        'Completed'   => 'bg-green-100 text-green-800',
        'In progress' => 'bg-amber-100 text-amber-800',
        'Pending'     => 'bg-gray-100 text-gray-600',
    ];

    $incType    = $payload['incident_type']     ?? '';
    $severity   = $payload['incident_severity'] ?? '';
    $staff      = $payload['staff_involved']    ?? [];
    $witnesses  = $payload['witnesses']         ?? [];
    $factors    = $payload['contributing_factors'] ?? [];
    $actions    = $payload['corrective_actions'] ?? [];
    $immediates = $payload['immediate_actions_taken'] ?? [];
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     CONFIDENTIAL BANNER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl border-2 border-amber-500 bg-amber-50 p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <div class="text-sm font-black text-amber-900 uppercase tracking-widest">
                Clinical Incident Report &mdash; Internal Confidential
            </div>
            <div class="text-xs text-amber-700 font-semibold mt-0.5 uppercase tracking-wider">
                Rapport d&rsquo;incident clinique &mdash; Confidentiel interne
            </div>
        </div>
        <span class="inline-block rounded-full bg-amber-500 text-white px-3 py-1 text-xs font-bold uppercase tracking-wide">
            NOT FOR EXTERNAL RELEASE
        </span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — INCIDENT TYPE, SEVERITY, LOCATION, DATE/TIME
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-2 gap-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Incident Type</div>
        <span class="inline-block rounded-full {{ $incidentTypeColors[$incType] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-semibold">
            {{ $incType ?: '—' }}
        </span>
    </div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-2">Severity</div>
        <span class="inline-block rounded-full {{ $severityColors[$severity] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-bold">
            {{ $severity ?: '—' }}
        </span>
    </div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Location</div>
        <div class="text-sm font-semibold text-gray-800">{{ $payload['location'] ?? '—' }}</div>
    </div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Date &amp; Time</div>
        <div class="text-sm font-semibold text-gray-800">
            {{ $payload['incident_date'] ?? '—' }}
            @if (!empty($payload['incident_time']))
                at {{ $payload['incident_time'] }}
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — INCIDENT DESCRIPTION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['description']))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Incident Description</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 leading-relaxed">
            {{ $payload['description'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — IMMEDIATE ACTIONS TAKEN
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($immediates))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Immediate Actions Taken</h3>
        <ul class="space-y-1">
            @foreach ($immediates as $action)
                <li class="flex items-start gap-2 text-sm text-gray-800">
                    <span class="mt-0.5 flex-shrink-0 inline-flex h-4 w-4 items-center justify-center rounded bg-amber-500 text-white text-[10px] font-bold">&#10003;</span>
                    <span>{{ $action }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — PATIENT OUTCOME & INFORMED STATUS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Patient Outcome &amp; Notification</h3>
    <div class="grid grid-cols-3 gap-4">
        @if (!empty($payload['patient_outcome']))
            <div class="col-span-1 rounded-lg border border-gray-200 bg-gray-50 p-3">
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Outcome</div>
                <div class="text-sm text-gray-800">{{ $payload['patient_outcome'] }}</div>
            </div>
        @endif
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Patient Informed</div>
            @php $pi = $payload['patient_informed'] ?? false; @endphp
            <span class="inline-block rounded-full {{ $pi ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} px-2 py-0.5 text-xs font-semibold">
                {{ $pi ? 'Yes' : 'No' }}
            </span>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Family Informed</div>
            @php $fi = $payload['family_informed'] ?? false; @endphp
            <span class="inline-block rounded-full {{ $fi ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} px-2 py-0.5 text-xs font-semibold">
                {{ $fi ? 'Yes' : 'No' }}
            </span>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — STAFF INVOLVED & WITNESSES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($staff))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Staff Involved</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-amber-600 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold">Name</th>
                    <th class="py-2 px-3 text-left font-semibold">Designation</th>
                    <th class="py-2 px-3 text-left font-semibold">Role in Incident</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($staff as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $s['name'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-700">{{ $s['designation'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-600 italic">{{ $s['role_in_incident'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (!empty($witnesses))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-2 border-b pb-1">Witnesses</h3>
        <div class="flex flex-wrap gap-3">
            @foreach ($witnesses as $w)
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs">
                    <div class="font-semibold text-gray-700">{{ $w['name'] ?? '—' }}</div>
                    <div class="text-gray-500">{{ $w['designation'] ?? '' }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — ROOT CAUSE & CONTRIBUTING FACTORS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Root Cause Analysis</h3>
    @if (!empty($payload['root_cause_category']))
        @php $rc = $payload['root_cause_category']; @endphp
        <div class="flex items-center gap-3 mb-3">
            <span class="text-sm font-semibold text-gray-700">Root Cause Category:</span>
            <span class="inline-block rounded-full {{ $rootCauseColors[$rc] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-semibold">
                {{ $rc }}
            </span>
        </div>
    @endif
    @if (!empty($factors))
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Contributing Factors</div>
        <ul class="space-y-1">
            @foreach ($factors as $f)
                <li class="flex items-start gap-2 text-sm text-gray-800">
                    <span class="mt-1 h-2 w-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                    <span>{{ $f }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — PREVENTABLE BADGE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex items-center gap-3">
    <span class="text-sm font-semibold text-gray-700">Incident Preventable:</span>
    @php $prev = $payload['preventable'] ?? null; @endphp
    @if ($prev !== null)
        <span class="inline-block rounded-full {{ $prev ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }} px-3 py-1 text-xs font-bold">
            {{ $prev ? 'Yes — Preventable' : 'No — Not Preventable' }}
        </span>
    @else
        <span class="text-gray-400 text-sm">—</span>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — CORRECTIVE ACTIONS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($actions))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Corrective Actions</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-amber-600 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold">Action</th>
                    <th class="py-2 px-3 text-left font-semibold">Responsible</th>
                    <th class="py-2 px-3 text-center font-semibold">Target Date</th>
                    <th class="py-2 px-3 text-center font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($actions as $a)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 text-gray-800">{{ $a['action'] ?? '—' }}</td>
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $a['responsible_person'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-700">{{ $a['target_date'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center">
                            @if (!empty($a['status']))
                                <span class="inline-block rounded-full {{ $statusColors[$a['status']] ?? 'bg-gray-100 text-gray-600' }} px-2 py-0.5 text-xs font-semibold">
                                    {{ $a['status'] }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — REPORTED BY → REPORTED TO CHAIN
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Reporting Chain</h3>
    <div class="flex items-center gap-4">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center flex-1">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Reported By</div>
            <div class="text-sm font-bold text-gray-800">{{ $payload['reported_by'] ?? '—' }}</div>
        </div>
        <div class="text-gray-400 font-bold text-xl">&rarr;</div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center flex-1">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Reported To</div>
            <div class="text-sm font-bold text-gray-800">{{ $payload['reported_to'] ?? '—' }}</div>
        </div>
        @if (!empty($payload['report_datetime']))
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center flex-1">
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Report Date/Time</div>
                <div class="text-sm font-semibold text-gray-800">{{ $payload['report_datetime'] }}</div>
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — QUALITY REVIEW BADGE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex items-center gap-3">
    <span class="text-sm font-semibold text-gray-700">Quality Review Required:</span>
    @php $qr = $payload['quality_review_required'] ?? false; @endphp
    <span class="inline-block rounded-full {{ $qr ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600' }} px-3 py-1 text-xs font-bold">
        {{ $qr ? 'Yes — Escalate to Quality Department' : 'No' }}
    </span>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 12 — CONFIDENTIALITY NOTICE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 rounded-lg border border-gray-300 bg-gray-100 p-4 text-xs text-gray-600">
    <div class="font-bold text-gray-700 uppercase tracking-wide mb-1">Confidentiality Notice / Avis de confidentialit&eacute;</div>
    <p class="leading-relaxed">
        This report is strictly confidential and intended solely for quality improvement and patient safety purposes within
        {{ $facility_name }}. It is not admissible as evidence of negligence in any legal proceeding and must not be
        disclosed to unauthorised parties. Ce rapport est strictement confidentiel et destin&eacute; uniquement &agrave;
        des fins d&rsquo;am&eacute;lioration de la qualit&eacute; et de la s&eacute;curit&eacute; des patients. Il n&rsquo;est
        pas admissible comme preuve de n&eacute;gligence et ne doit pas &ecirc;tre divulgu&eacute; &agrave; des tiers non autoris&eacute;s.
    </p>
</div>
@endsection
