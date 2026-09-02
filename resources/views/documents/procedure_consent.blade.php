@extends('documents.base')

@section('title', 'Procedure-Specific Consent Form')

@section('subtitle', 'PCS — LEGAL DOCUMENT / DOCUMENT LÉGAL')

@section('content')
@php
    $categoryColors = [
        'Surgical'                   => 'bg-red-100 text-red-800',
        'Obstetric'                  => 'bg-pink-100 text-pink-800',
        'Interventional Cardiology'  => 'bg-red-200 text-red-900',
        'Endoscopy'                  => 'bg-blue-100 text-blue-800',
        'Anaesthesia'                => 'bg-purple-100 text-purple-800',
        'Chemotherapy'               => 'bg-orange-100 text-orange-800',
        'Dialysis Access'            => 'bg-teal-100 text-teal-800',
    ];
    $anaesthesiaColors = [
        'General Anaesthesia'  => 'bg-gray-800 text-white',
        'Spinal'               => 'bg-blue-700 text-white',
        'Epidural'             => 'bg-blue-500 text-white',
        'Local'                => 'bg-green-100 text-green-800',
        'Conscious sedation'   => 'bg-purple-100 text-purple-800',
        'None'                 => 'bg-gray-100 text-gray-600',
    ];

    $benefits           = $payload['benefits']                   ?? [];
    $risksCommon        = $payload['risks_common']               ?? [];
    $risksSerious       = $payload['risks_serious']              ?? [];
    $risksSpecific      = $payload['risks_procedure_specific']   ?? [];
    $alternatives       = $payload['alternatives']               ?? [];
    $category           = $payload['procedure_category']         ?? '';
    $anaesthesia        = $payload['anaesthesia_type']           ?? '';
    $consentGivenBy     = $payload['consent_given_by']           ?? '';
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — INDIGO HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl p-4" style="background-color:#EEF2FF;border:2px solid #4F46E5;">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <div class="text-sm font-black uppercase tracking-widest" style="color:#4F46E5;">Procedure-Specific Consent Form</div>
            <div class="text-xs font-semibold mt-0.5 uppercase tracking-wider" style="color:#6366F1;">Formulaire de consentement éclairé — procédure spécifique</div>
            <div class="text-base font-black text-gray-900 mt-2">{{ $payload['procedure_name'] ?? '—' }}</div>
            <div class="mt-1 flex items-center gap-2 flex-wrap">
                <span class="inline-block rounded-full {{ $categoryColors[$category] ?? 'bg-gray-100 text-gray-700' }} px-3 py-0.5 text-xs font-bold">{{ $category ?: '—' }}</span>
                <span class="text-xs text-gray-500">Date: {{ $payload['consent_date'] ?? '—' }} at {{ $payload['consent_time'] ?? '—' }}</span>
            </div>
        </div>
        <span class="inline-block rounded-full text-white text-xs font-bold px-3 py-1 uppercase tracking-wide" style="background-color:#4F46E5;">PCS</span>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — INDICATION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['indication']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Indication</h3>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 leading-relaxed">
            {{ $payload['indication'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — PROCEDURE DESCRIPTION (PLAIN LANGUAGE)
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['procedure_description']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">What Will Happen (Plain Language)</h3>
        <div class="rounded-lg border-2 border-indigo-200 bg-indigo-50 p-4 text-sm text-gray-800 leading-relaxed">
            {{ $payload['procedure_description'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — ANAESTHESIA
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex items-center gap-3">
    <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Anaesthesia:</span>
    <span class="inline-block rounded-full {{ $anaesthesiaColors[$anaesthesia] ?? 'bg-gray-100 text-gray-700' }} px-4 py-1 text-sm font-bold">
        {{ $anaesthesia ?: '—' }}
    </span>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — BENEFITS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($benefits))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Benefits</h3>
        <ul class="space-y-1">
            @foreach ($benefits as $benefit)
                <li class="flex items-start gap-2 text-sm text-gray-800">
                    <span class="mt-1 flex-shrink-0 inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-green-500 text-white text-[9px] font-bold">&#10003;</span>
                    <span>{{ $benefit }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — RISKS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 grid grid-cols-2 gap-4">
    {{-- Common Risks --}}
    @if (!empty($risksCommon))
        <div>
            <h3 class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-2 border-b border-amber-200 pb-1">Common Risks</h3>
            <div class="space-y-1">
                @foreach ($risksCommon as $risk)
                    <div class="rounded border border-amber-200 bg-amber-50 px-3 py-1.5 flex justify-between items-center">
                        <span class="text-xs text-gray-800">{{ $risk['risk'] ?? '—' }}</span>
                        <span class="text-xs text-amber-700 font-semibold ml-2 flex-shrink-0">{{ $risk['frequency'] ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    {{-- Serious Risks --}}
    @if (!empty($risksSerious))
        <div>
            <h3 class="text-xs font-bold text-red-700 uppercase tracking-wider mb-2 border-b border-red-200 pb-1">Serious Risks</h3>
            <div class="space-y-1">
                @foreach ($risksSerious as $risk)
                    <div class="rounded border border-red-200 bg-red-50 px-3 py-1.5 flex justify-between items-center">
                        <span class="text-xs text-gray-800">{{ $risk['risk'] ?? '—' }}</span>
                        <span class="text-xs text-red-700 font-semibold ml-2 flex-shrink-0">{{ $risk['frequency'] ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6B — PROCEDURE-SPECIFIC RISKS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($risksSpecific))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-red-800 uppercase tracking-wider mb-2 border-b border-red-200 pb-1">Procedure-Specific Risks</h3>
        <div class="space-y-1">
            @foreach ($risksSpecific as $risk)
                <div class="rounded border border-red-300 bg-red-50 px-3 py-1.5">
                    <span class="text-xs font-semibold text-red-900">{{ $risk['risk'] ?? '—' }}</span>
                    @if (!empty($risk['detail']))
                        <span class="text-xs text-red-700 ml-2">— {{ $risk['detail'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — ALTERNATIVES TABLE
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($alternatives))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Alternatives</h3>
        <table class="w-full text-xs border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Alternative</th>
                    <th class="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-700">Why Not Chosen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($alternatives as $alt)
                    <tr class="even:bg-gray-50">
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-800">{{ $alt['alternative'] ?? '—' }}</td>
                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">{{ $alt['reason_not_chosen'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — NO TREATMENT CONSEQUENCES
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['no_treatment_consequences']))
    <div class="mb-6">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Consequences of No Treatment</h3>
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
            {{ $payload['no_treatment_consequences'] }}
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — SPECIAL CONSENTS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Special Consents</h3>
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Blood Products</div>
            @if (!empty($payload['blood_products_refused']))
                <span class="inline-block rounded-full bg-red-600 text-white px-2 py-0.5 text-xs font-bold">REFUSED</span>
            @elseif (!empty($payload['blood_products_consent']))
                <span class="inline-block rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-bold">Consent Given</span>
            @else
                <span class="inline-block rounded-full bg-gray-200 text-gray-600 px-2 py-0.5 text-xs font-semibold">Not Applicable</span>
            @endif
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Photography / Teaching</div>
            @if (!empty($payload['photography_consent']))
                <span class="inline-block rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-bold">Consent Given</span>
            @else
                <span class="inline-block rounded-full bg-gray-200 text-gray-600 px-2 py-0.5 text-xs font-semibold">Declined</span>
            @endif
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Trainee Involvement</div>
            @if (!empty($payload['trainee_involvement']))
                <div class="text-xs text-gray-800">{{ $payload['trainee_involvement'] }}</div>
            @else
                <span class="inline-block rounded-full bg-gray-200 text-gray-600 px-2 py-0.5 text-xs font-semibold">None</span>
            @endif
        </div>
    </div>
    @if (!empty($payload['interpreter_used']))
        <div class="mt-2 rounded border border-blue-200 bg-blue-50 p-2 text-xs text-blue-800">
            Interpreter used: {{ $payload['interpreter_name'] ?? 'Name not recorded' }}
        </div>
    @endif
    @if (!empty($payload['patient_questions_answered']))
        <div class="mt-2 rounded border border-green-200 bg-green-50 p-2 text-xs text-green-800 font-semibold">
            &#10003; Patient questions answered to satisfaction
        </div>
    @endif
    @if (!empty($payload['capacity_confirmed']))
        <div class="mt-2 rounded border border-indigo-200 bg-indigo-50 p-2 text-xs text-indigo-800 font-semibold">
            &#10003; Mental capacity to consent confirmed
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — PATIENT DECLARATION
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 rounded-xl border-2 border-indigo-400 bg-indigo-50 p-4">
    <div class="text-xs font-black text-indigo-900 uppercase tracking-wide mb-2">Patient Declaration / Déclaration du patient</div>
    <div class="text-sm text-indigo-900 leading-relaxed font-medium">
        {{ $payload['declaration'] ?? 'I confirm that I have been explained the nature, purpose, risks, benefits, and alternatives to this procedure in language I understand. I have had my questions answered. I give my voluntary consent.' }}
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — CONSENT GIVEN BY + GUARDIAN
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 border-b border-gray-200 pb-1">Consent Given By</h3>
    <div class="flex items-center gap-3 flex-wrap">
        <span class="inline-block rounded-full bg-indigo-100 text-indigo-800 px-4 py-1 text-sm font-bold">{{ $consentGivenBy ?: '—' }}</span>
        @if (!empty($payload['guardian_name']))
            <span class="text-xs text-gray-700"><span class="font-semibold">Name:</span> {{ $payload['guardian_name'] }}</span>
        @endif
        @if (!empty($payload['guardian_relationship']))
            <span class="text-xs text-gray-700"><span class="font-semibold">Relationship:</span> {{ $payload['guardian_relationship'] }}</span>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 12 — SIGNATURE BLOCK
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <div class="grid grid-cols-3 gap-6 text-xs text-gray-700">
        <div>
            <div class="font-semibold text-gray-800 mb-1">Consenting Doctor</div>
            <div class="mt-6 border-b border-gray-400 w-40"></div>
            <div class="mt-1 font-semibold">{{ $payload['consenting_doctor'] ?? $issuer_name ?? '—' }}</div>
            @if (!empty($payload['consenting_doctor_reg']))
                <div class="text-gray-500">Reg: {{ $payload['consenting_doctor_reg'] }}</div>
            @endif
        </div>
        <div>
            <div class="font-semibold text-gray-800 mb-1">
                @if ($consentGivenBy === 'Patient')
                    Patient Signature
                @else
                    {{ $consentGivenBy ?: 'Patient / Guardian' }} Signature
                @endif
            </div>
            <div class="mt-6 border-b border-gray-400 w-40"></div>
            <div class="mt-1 text-gray-500">
                @if (!empty($payload['guardian_name']))
                    {{ $payload['guardian_name'] }}
                @else
                    {{ $patient_name ?? '—' }}
                @endif
            </div>
        </div>
        <div>
            <div class="font-semibold text-gray-800 mb-1">Witness</div>
            <div class="mt-6 border-b border-gray-400 w-40"></div>
            <div class="mt-1 text-gray-400">Name &amp; Signature</div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 13 — LEGAL NOTICE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-500 text-center">
    This consent form is issued in accordance with
    <span class="font-semibold text-gray-700">{{ $payload['legal_reference'] ?? 'Cameroon Law No. 2003/004' }}</span>
    governing patient rights and informed consent.
    Document No: <span class="font-mono font-semibold text-gray-700">{{ $document_number ?? '—' }}</span>
</div>
@endsection
