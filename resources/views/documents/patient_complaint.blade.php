@extends('documents.base')

@section('title', 'Patient Complaint / Feedback Form')

@section('subtitle', 'PCF — GOVERNANCE RECORD / DOSSIER DE GOUVERNANCE')

@section('content')
@php
    $categoryColors = [
        'Clinical care quality'  => 'bg-red-100 text-red-800',
        'Communication'          => 'bg-blue-100 text-blue-800',
        'Staff attitude'         => 'bg-orange-100 text-orange-800',
        'Waiting time'           => 'bg-yellow-100 text-yellow-800',
        'Privacy/Dignity'        => 'bg-purple-100 text-purple-800',
        'Billing/Financial'      => 'bg-green-100 text-green-800',
        'Facilities/Environment' => 'bg-teal-100 text-teal-800',
        'Medication error'       => 'bg-red-200 text-red-900',
        'Other'                  => 'bg-gray-100 text-gray-600',
    ];

    $complainantType = $payload['complainant_type']    ?? '';
    $categories      = $payload['complaint_category']  ?? [];
    $anonymous       = !empty($payload['anonymous']);
    $resolved        = !empty($payload['resolution']);
    $escalated       = !empty($payload['escalated']);
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — AMBER HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl p-4" style="background-color:#FFFBEB;border:2px solid #F59E0B;">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-sm font-black uppercase tracking-widest" style="color:#B45309;">Patient Complaint / Feedback Form</div>
            <div class="text-xs font-semibold mt-0.5 uppercase tracking-wider text-amber-700">Formulaire de plainte / retour d'information du patient</div>
            <div class="text-xs text-amber-700 mt-1 space-x-3">
                <span><span class="font-semibold">Ref:</span> {{ $payload['complaint_ref'] ?? '—' }}</span>
                <span><span class="font-semibold">Date:</span> {{ $payload['complaint_date'] ?? '—' }}</span>
                <span><span class="font-semibold">Time:</span> {{ $payload['complaint_time'] ?? '—' }}</span>
            </div>
        </div>
        <span class="inline-block rounded-full text-white text-xs font-bold px-3 py-1 uppercase tracking-wide" style="background-color:#F59E0B;">PCF</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — COMPLAINANT
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Complainant Details</h3>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="flex flex-wrap items-center gap-3 mb-3">
            <span class="inline-block rounded-full bg-amber-100 text-amber-800 px-3 py-0.5 text-xs font-bold uppercase">{{ $complainantType ?: '—' }}</span>
            @if ($anonymous)
                <span class="inline-block rounded-full bg-gray-700 text-white px-3 py-0.5 text-xs font-bold uppercase tracking-wide">Anonymous</span>
            @endif
        </div>
        @if (!$anonymous)
            <div class="grid grid-cols-3 gap-3 text-xs text-gray-700">
                @if (!empty($payload['complainant_name']))
                    <div><span class="font-semibold">Name:</span> {{ $payload['complainant_name'] }}</div>
                @endif
                @if (!empty($payload['complainant_relationship']))
                    <div><span class="font-semibold">Relationship:</span> {{ $payload['complainant_relationship'] }}</div>
                @endif
                @if (!empty($payload['complainant_contact']))
                    <div><span class="font-semibold">Contact:</span> {{ $payload['complainant_contact'] }}</div>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — COMPLAINT CATEGORIES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($categories))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Complaint Categories</h3>
        <div class="flex flex-wrap gap-2">
            @foreach ($categories as $cat)
                <span class="inline-block rounded-full {{ $categoryColors[$cat] ?? 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-semibold">{{ $cat }}</span>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — COMPLAINT DESCRIPTION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['complaint_description']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Complaint Description</h3>
        <div class="rounded-lg border-2 border-amber-300 bg-amber-50 p-4 text-sm text-gray-800 leading-relaxed">
            {{ $payload['complaint_description'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — DATE / DEPARTMENT / STAFF
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-3 gap-4">
    @if (!empty($payload['incident_date']))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Incident Date</div>
            <div class="text-sm font-semibold text-gray-800">{{ $payload['incident_date'] }}</div>
        </div>
    @endif
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
        <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Department</div>
        <div class="text-sm font-semibold text-gray-800">{{ $payload['department_involved'] ?? '—' }}</div>
    </div>
    @if (!empty($payload['staff_involved']))
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Staff Involved</div>
            <div class="text-sm text-gray-700">{{ $payload['staff_involved'] }}</div>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — DESIRED OUTCOME
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['outcome_desired']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Desired Outcome (from Complainant)</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-800">
            {{ $payload['outcome_desired'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — IMMEDIATE RESPONSE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Immediate Response</h3>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
        <div class="flex items-center gap-2 mb-2">
            <span class="inline-block rounded-full {{ !empty($payload['immediate_response_given']) ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }} px-3 py-0.5 text-xs font-bold">
                {{ !empty($payload['immediate_response_given']) ? 'Response Given' : 'No Immediate Response' }}
            </span>
        </div>
        @if (!empty($payload['immediate_response_details']))
            <div class="text-xs text-gray-700">{{ $payload['immediate_response_details'] }}</div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — INVESTIGATION
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Investigation</h3>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
        <div class="flex items-center gap-3 flex-wrap mb-2">
            <span class="inline-block rounded-full {{ !empty($payload['investigation_required']) ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }} px-3 py-0.5 text-xs font-bold">
                {{ !empty($payload['investigation_required']) ? 'Investigation Required' : 'No Formal Investigation' }}
            </span>
        </div>
        <div class="grid grid-cols-2 gap-3 text-xs text-gray-700">
            @if (!empty($payload['assigned_to']))
                <div><span class="font-semibold">Assigned To:</span> {{ $payload['assigned_to'] }}</div>
            @endif
            @if (!empty($payload['target_resolution_date']))
                <div><span class="font-semibold">Target Resolution:</span> {{ $payload['target_resolution_date'] }}</div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — RESOLUTION
     ═══════════════════════════════════════════════════════════════════ --}}
@if ($resolved)
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Resolution</h3>
        <div class="rounded-lg border border-green-300 bg-green-50 p-4">
            <div class="flex items-center gap-3 flex-wrap mb-2">
                <span class="inline-block rounded-full bg-green-600 text-white px-3 py-0.5 text-xs font-bold">Resolved</span>
                @if (!empty($payload['resolved_date']))
                    <span class="text-xs text-green-700">{{ $payload['resolved_date'] }}</span>
                @endif
                @if (isset($payload['complainant_satisfied']))
                    @if ($payload['complainant_satisfied'])
                        <span class="inline-block rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-semibold">Complainant Satisfied</span>
                    @else
                        <span class="inline-block rounded-full bg-red-100 text-red-800 px-2 py-0.5 text-xs font-semibold">Complainant Not Satisfied</span>
                    @endif
                @endif
            </div>
            <div class="text-sm text-gray-800">{{ $payload['resolution'] }}</div>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — ESCALATION
     ═══════════════════════════════════════════════════════════════════ --}}
@if ($escalated)
    <div class="mb-6">
        <div class="rounded-lg border border-red-300 bg-red-50 p-3 flex items-center gap-3">
            <span class="inline-block rounded-full bg-red-600 text-white px-3 py-0.5 text-xs font-bold">Escalated</span>
            @if (!empty($payload['escalated_to']))
                <span class="text-xs text-red-800"><span class="font-semibold">To:</span> {{ $payload['escalated_to'] }}</span>
            @endif
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — RECEIVED BY SIGNATURE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Received By</h3>
    <div class="grid grid-cols-2 gap-6 text-xs text-gray-700">
        <div>
            <div class="mt-4 border-b border-gray-400 w-40"></div>
            <div class="mt-1 font-semibold text-gray-800">{{ $payload['received_by'] ?? $issuer_name ?? '—' }}</div>
            <div class="text-gray-500">{{ $payload['received_by_designation'] ?? $issuer_role ?? '—' }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">Document No.</div>
            <div class="font-mono font-semibold text-gray-700">{{ $document_number ?? '—' }}</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 12 — PATIENT RIGHTS BOX
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-4 rounded-xl border-2 border-amber-400 bg-amber-50 p-4">
    <div class="text-xs font-black text-amber-900 uppercase tracking-wide mb-1">Patient Rights / Droits du patient</div>
    <div class="text-xs text-amber-800 leading-relaxed">
        Every patient and family member has the right to raise concerns without fear of negative consequences to their care.
    </div>
    <div class="text-xs text-amber-700 mt-1 italic">
        Tout patient et membre de sa famille a le droit de soulever des préoccupations sans craindre de conséquences négatives pour ses soins.
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 13 — RESPONSE COMMITMENT
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600 text-center">
    We aim to acknowledge within <strong>24 hours</strong> and resolve within <strong>10 working days</strong> for formal complaints.
</div>
@endsection
