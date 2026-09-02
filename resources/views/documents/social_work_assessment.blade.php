@extends('documents.base')

@section('title', 'Social Work Assessment')

@section('subtitle', 'SWA — CONFIDENTIAL / CONFIDENTIEL')

@section('content')
@php
    $livingColors = [
        'Alone'        => 'bg-yellow-100 text-yellow-800',
        'With family'  => 'bg-green-100 text-green-800',
        'With carer'   => 'bg-blue-100 text-blue-800',
        'Homeless'     => 'bg-red-100 text-red-800',
        'Unknown'      => 'bg-gray-100 text-gray-600',
    ];
    $riskColors = [
        'Low'    => 'bg-green-100 text-green-800',
        'Medium' => 'bg-amber-100 text-amber-800',
        'High'   => 'bg-red-100 text-red-800',
    ];
    $statusColors = [
        'Referred'  => 'bg-blue-100 text-blue-800',
        'In place'  => 'bg-green-100 text-green-800',
        'Pending'   => 'bg-gray-100 text-gray-600',
    ];

    $referralReasons   = $payload['referral_reason']        ?? [];
    $livingInfo        = $payload['living_situation']        ?? [];
    $familyInfo        = $payload['family_support']          ?? [];
    $financialInfo     = $payload['financial_assessment']    ?? [];
    $safeguarding      = $payload['safeguarding']            ?? [];
    $mentalHealth      = $payload['mental_health_social']    ?? [];
    $dischargePlan     = $payload['discharge_planning']      ?? [];
    $communityResources= $payload['community_resources']     ?? [];
    $actionsTaken      = $payload['actions_taken']           ?? [];
    $riskSummary       = $payload['risk_summary']            ?? '';
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — HEADER: REFERRAL REASONS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl p-4" style="background-color:#EFF6FF;border:2px solid #0369A1;">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-sm font-black uppercase tracking-widest" style="color:#0369A1;">Social Work Assessment</div>
            <div class="text-xs font-semibold mt-0.5 uppercase tracking-wider text-blue-700">Évaluation du travail social</div>
            <div class="text-xs text-blue-700 mt-1">
                Assessment Date:
                <span class="font-semibold">{{ $payload['assessment_date'] ?? '—' }}</span>
                &nbsp;&bull;&nbsp;
                Social Worker:
                <span class="font-semibold">{{ $payload['social_worker'] ?? '—' }}</span>
                @if (!empty($payload['sw_reg']))
                    &nbsp;&bull;&nbsp; Reg: <span class="font-semibold">{{ $payload['sw_reg'] }}</span>
                @endif
            </div>
        </div>
        <span class="inline-block rounded-full text-white text-xs font-bold px-3 py-1 uppercase tracking-wide" style="background-color:#0369A1;">SWA</span>
    </div>
    @if (!empty($referralReasons))
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($referralReasons as $reason)
                <span class="inline-block rounded-full bg-blue-200 text-blue-900 text-xs font-semibold px-2 py-0.5">{{ $reason }}</span>
            @endforeach
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — PRESENTING SOCIAL CONCERNS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['presenting_social_concerns']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Presenting Social Concerns</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 leading-relaxed">
            {{ $payload['presenting_social_concerns'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — LIVING SITUATION + FAMILY SUPPORT
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-2 gap-4">
    {{-- Living Situation --}}
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Living Situation</div>
        @if (!empty($livingInfo))
            <div class="mb-2">
                <span class="inline-block rounded-full {{ $livingColors[$livingInfo['type'] ?? ''] ?? 'bg-gray-100 text-gray-700' }} px-2 py-0.5 text-xs font-semibold">
                    {{ $livingInfo['type'] ?? '—' }}
                </span>
            </div>
            <div class="space-y-1 text-xs text-gray-700">
                <div><span class="font-semibold">Location:</span> {{ $livingInfo['location'] ?? '—' }}</div>
                <div><span class="font-semibold">Housing:</span> {{ $livingInfo['housing_type'] ?? '—' }}</div>
                @if (!empty($livingInfo['accessibility']))
                    <div><span class="font-semibold">Accessibility:</span> {{ $livingInfo['accessibility'] }}</div>
                @endif
            </div>
        @else
            <div class="text-xs text-gray-400">Not recorded</div>
        @endif
    </div>

    {{-- Family Support --}}
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Family Support</div>
        @if (!empty($familyInfo))
            <div class="space-y-1 text-xs text-gray-700">
                @if (!empty($familyInfo['primary_carer']))
                    <div><span class="font-semibold">Primary Carer:</span> {{ $familyInfo['primary_carer'] }}</div>
                @endif
                @if (!empty($familyInfo['carer_capacity']))
                    <div><span class="font-semibold">Carer Capacity:</span> {{ $familyInfo['carer_capacity'] }}</div>
                @endif
                <div><span class="font-semibold">Support Network:</span> {{ $familyInfo['support_network'] ?? '—' }}</div>
                @if (!empty($familyInfo['family_dynamics']))
                    <div><span class="font-semibold">Family Dynamics:</span> {{ $familyInfo['family_dynamics'] }}</div>
                @endif
            </div>
        @else
            <div class="text-xs text-gray-400">Not recorded</div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — FINANCIAL ASSESSMENT
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Financial Assessment</h3>
    @if (!empty($financialInfo))
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-1 text-xs text-gray-700">
                <div><span class="font-semibold">Employment:</span> {{ $financialInfo['employment'] ?? '—' }}</div>
                <div><span class="font-semibold">Income Source:</span> {{ $financialInfo['income_source'] ?? '—' }}</div>
                @if (!empty($financialInfo['social_protection']))
                    <div><span class="font-semibold">Social Protection:</span> {{ $financialInfo['social_protection'] }}</div>
                @endif
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-1 text-xs text-gray-700">
                <div class="flex items-center gap-2">
                    <span class="font-semibold">Health Insurance:</span>
                    @if (!empty($financialInfo['health_insurance']))
                        <span class="inline-block rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-semibold">Yes</span>
                        @if (!empty($financialInfo['insurer']))
                            <span class="text-gray-600">— {{ $financialInfo['insurer'] }}</span>
                        @endif
                    @else
                        <span class="inline-block rounded-full bg-red-100 text-red-800 px-2 py-0.5 text-xs font-semibold">No</span>
                    @endif
                </div>
                @if (!empty($financialInfo['financial_hardship']))
                    <div class="mt-1 rounded border border-amber-300 bg-amber-50 p-2">
                        <div class="text-xs font-bold text-amber-800 uppercase mb-1">Financial Hardship Identified</div>
                        @if (!empty($financialInfo['hardship_details']))
                            <div class="text-xs text-amber-700">{{ $financialInfo['hardship_details'] }}</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — SAFEGUARDING
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Safeguarding</h3>
    @if (!empty($safeguarding))
        @php $concernIdentified = !empty($safeguarding['concern_identified']); @endphp
        <div class="rounded-lg border {{ $concernIdentified ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} p-4">
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="inline-block rounded-full {{ $concernIdentified ? 'bg-red-600 text-white' : 'bg-green-100 text-green-800' }} px-3 py-0.5 text-xs font-bold">
                    {{ $concernIdentified ? 'Concern Identified' : 'No Concern Identified' }}
                </span>
                @if (!empty($safeguarding['vulnerable_adult']))
                    <span class="inline-block rounded-full bg-orange-100 text-orange-800 px-2 py-0.5 text-xs font-semibold">Vulnerable Adult</span>
                @endif
                @if (!empty($safeguarding['child_in_household']))
                    <span class="inline-block rounded-full bg-purple-100 text-purple-800 px-2 py-0.5 text-xs font-semibold">Child in Household</span>
                @endif
            </div>
            @if ($concernIdentified)
                <div class="space-y-1 text-xs text-red-800">
                    @if (!empty($safeguarding['concern_type']))
                        <div><span class="font-semibold">Concern Type:</span> {{ $safeguarding['concern_type'] }}</div>
                    @endif
                    @if (!empty($safeguarding['referral_made']))
                        <div class="font-semibold">Referral made to authorities</div>
                        @if (!empty($safeguarding['referral_details']))
                            <div>{{ $safeguarding['referral_details'] }}</div>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5B — MENTAL HEALTH (SOCIAL)
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($mentalHealth))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Mental Health (Social Context)</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-1 text-xs text-gray-700">
            <div><span class="font-semibold">Current Status:</span> {{ $mentalHealth['current_status'] ?? '—' }}</div>
            @if (!empty($mentalHealth['previous_admissions']))
                <div><span class="font-semibold">Previous Admissions:</span> {{ $mentalHealth['previous_admissions'] }}</div>
            @endif
            @if (!empty($mentalHealth['compliance_issues']))
                <div><span class="font-semibold">Compliance Issues:</span> {{ $mentalHealth['compliance_issues'] }}</div>
            @endif
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — DISCHARGE PLANNING
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Discharge Planning</h3>
    @if (!empty($dischargePlan))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="inline-block rounded-full {{ !empty($dischargePlan['ready_for_discharge']) ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }} px-3 py-0.5 text-xs font-bold">
                    {{ !empty($dischargePlan['ready_for_discharge']) ? 'Ready for Discharge' : 'Not Yet Ready' }}
                </span>
            </div>
            <div class="grid grid-cols-2 gap-4 text-xs text-gray-700">
                <div class="space-y-1">
                    @if (!empty($dischargePlan['anticipated_date']))
                        <div><span class="font-semibold">Anticipated Date:</span> {{ $dischargePlan['anticipated_date'] }}</div>
                    @endif
                    @if (!empty($dischargePlan['destination']))
                        <div><span class="font-semibold">Destination:</span> {{ $dischargePlan['destination'] }}</div>
                    @endif
                </div>
                <div class="space-y-1">
                    @if (!empty($dischargePlan['barriers']))
                        <div class="font-semibold text-red-700">Barriers:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($dischargePlan['barriers'] as $barrier)
                                <li class="text-red-700">{{ $barrier }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (!empty($dischargePlan['arrangements_made']))
                        <div class="font-semibold text-green-700 mt-2">Arrangements Made:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($dischargePlan['arrangements_made'] as $arr)
                                <li class="text-green-700">{{ $arr }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="text-xs text-gray-400">Not recorded</div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — COMMUNITY RESOURCES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($communityResources))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Community Resources</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Resource</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Contact</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($communityResources as $resource)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-800">{{ $resource['resource'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $resource['contact'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5">
                            <span class="inline-block rounded-full {{ $statusColors[$resource['status'] ?? ''] ?? 'bg-gray-100 text-gray-600' }} px-2 py-0.5 text-xs font-semibold">
                                {{ $resource['status'] ?? '—' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — FOLLOW-UP PLAN
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['follow_up_plan']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Follow-up Plan</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 leading-relaxed">
            {{ $payload['follow_up_plan'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — ACTIONS TAKEN LOG
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($actionsTaken))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Actions Taken</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Action</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Date</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Outcome</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($actionsTaken as $action)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-800">{{ $action['action'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-600">{{ $action['date'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $action['outcome'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — RISK SUMMARY
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($riskSummary))
    <div class="mb-6 flex items-center gap-3">
        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Overall Risk Summary:</span>
        <span class="inline-block rounded-full {{ $riskColors[$riskSummary] ?? 'bg-gray-100 text-gray-700' }} px-4 py-1 text-sm font-black uppercase tracking-wide">
            {{ $riskSummary }}
        </span>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — SOCIAL WORKER SIGNATURE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t border-gray-200 pt-4">
    <div class="grid grid-cols-3 gap-6 text-xs text-gray-700">
        <div>
            <div class="font-semibold text-gray-800">Social Worker</div>
            <div class="mt-4 border-b border-gray-400 w-40"></div>
            <div class="mt-1">{{ $payload['social_worker'] ?? $issuer_name ?? '—' }}</div>
            @if (!empty($payload['sw_reg']))
                <div class="text-gray-500">Reg: {{ $payload['sw_reg'] }}</div>
            @endif
        </div>
        <div>
            <div class="font-semibold text-gray-800">Date</div>
            <div class="mt-4 border-b border-gray-400 w-32"></div>
            <div class="mt-1">{{ $payload['assessment_date'] ?? $issued_at ?? '—' }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">Document No.</div>
            <div class="font-mono font-semibold text-gray-700">{{ $document_number ?? '—' }}</div>
            @if (!empty($verification_code))
                <div class="mt-1 text-xs text-gray-400">Verify: {{ $verification_code }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
