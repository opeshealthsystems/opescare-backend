@extends('documents.base')

@section('title', 'Endoscopy Report')

@section('subtitle', 'ENDO — ' . ($payload['procedure_type'] ?? ''))

@section('content')
@php
    $sedationColors = [
        'Conscious sedation (Midazolam + Fentanyl)' => 'bg-blue-100 text-blue-800',
        'General Anaesthesia'                        => 'bg-purple-100 text-purple-800',
        'Topical only'                               => 'bg-gray-100 text-gray-700',
    ];
    $sedColor = $sedationColors[$payload['sedation'] ?? ''] ?? 'bg-gray-100 text-gray-700';

    $findings      = $payload['findings']      ?? [];
    $biopsies      = $payload['biopsies']      ?? [];
    $polypectomies = $payload['polypectomy']   ?? [];
    $recs          = $payload['recommendations'] ?? [];
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 1 — PROCEDURE HEADER
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border border-green-200 bg-green-50 p-5 mb-6">
    <div class="flex flex-wrap gap-2 mb-3">
        <span class="inline-flex items-center rounded-full bg-green-700 px-3 py-1 text-xs font-semibold text-white uppercase tracking-wide">
            {{ $payload['procedure_type'] ?? '—' }}
        </span>
        <span class="inline-flex items-center rounded-full {{ $sedColor }} px-3 py-1 text-xs font-semibold">
            {{ $payload['sedation'] ?? '—' }}
        </span>
        @if (!empty($payload['consent_obtained']))
            <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 px-3 py-1 text-xs font-semibold">
                Consent Obtained
            </span>
        @endif
    </div>
    <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-green-900">
        <div><span class="font-semibold">Procedure Date:</span> {{ $payload['procedure_date'] ?? '—' }}</div>
        <div><span class="font-semibold">Endoscopist:</span> {{ $payload['endoscopist'] ?? '—' }}
            @if (!empty($payload['endoscopist_reg']))
                <span class="text-green-600 text-xs ml-1">({{ $payload['endoscopist_reg'] }})</span>
            @endif
        </div>
        <div><span class="font-semibold">Scope Used:</span> {{ $payload['scope_used'] ?? '—' }}</div>
    </div>
    @if (!empty($payload['indication']))
        <div class="mt-3 pt-3 border-t border-green-200 text-sm text-green-900">
            <span class="font-semibold">Indication:</span> {{ $payload['indication'] }}
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 2 — PROCEDURAL DETAILS
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Procedural Details</h3>
    <div class="grid grid-cols-1 gap-2 text-sm">
        @if (!empty($payload['extent_of_examination']))
            <div><span class="font-semibold text-gray-700">Extent of Examination:</span> <span class="text-gray-800">{{ $payload['extent_of_examination'] }}</span></div>
        @endif
        @if (!empty($payload['bowel_prep_quality']))
            <div><span class="font-semibold text-gray-700">Bowel Preparation Quality:</span> <span class="text-gray-800">{{ $payload['bowel_prep_quality'] }}</span></div>
        @endif
        @if (!empty($payload['recovery_time_min']))
            <div><span class="font-semibold text-gray-700">Recovery Time:</span> <span class="text-gray-800">{{ $payload['recovery_time_min'] }} minutes</span></div>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 3 — FINDINGS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($findings))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Endoscopic Findings</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-green-700 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold w-1/4">Location</th>
                    <th class="py-2 px-3 text-left font-semibold w-1/2">Description</th>
                    <th class="py-2 px-3 text-left font-semibold w-1/4">Impression</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($findings as $finding)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $finding['location'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-800">{{ $finding['description'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-600 text-xs italic">{{ $finding['impression'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 4 — BIOPSY DETAILS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($biopsies))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Biopsy Details</h3>
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-2 mb-2 text-xs text-yellow-800">
            Biopsy specimens have been labelled, numbered, and sent to the laboratory. Please correlate with histopathology results.
        </div>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-green-700 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold">Site</th>
                    <th class="py-2 px-3 text-center font-semibold">No. of Pieces</th>
                    <th class="py-2 px-3 text-left font-semibold">Sent For</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($biopsies as $biopsy)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $biopsy['site'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-800">{{ $biopsy['number_of_pieces'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-800">{{ $biopsy['sent_for'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 5 — POLYPECTOMY
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($polypectomies))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Polypectomy Details</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-green-700 text-white text-xs uppercase tracking-wider">
                    <th class="py-2 px-3 text-left font-semibold">Site</th>
                    <th class="py-2 px-3 text-center font-semibold">Size (mm)</th>
                    <th class="py-2 px-3 text-left font-semibold">Morphology</th>
                    <th class="py-2 px-3 text-left font-semibold">Method</th>
                    <th class="py-2 px-3 text-center font-semibold">Retrieved</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($polypectomies as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 font-medium text-gray-700">{{ $p['site'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center text-gray-800">{{ $p['size_mm'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-800">{{ $p['morphology'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-gray-800">{{ $p['method'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-center">
                            @if (!empty($p['retrieved']))
                                <span class="inline-block rounded-full bg-green-100 text-green-800 px-2 py-0.5 text-xs font-semibold">Yes</span>
                            @else
                                <span class="inline-block rounded-full bg-red-100 text-red-800 px-2 py-0.5 text-xs font-semibold">No</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 6 — H. PYLORI RAPID UREASE TEST
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['hp_rapid_urease_test']))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">H. pylori Rapid Urease Test</h3>
        @php $hpResult = $payload['hp_rapid_urease_test']; @endphp
        <span class="inline-block rounded-full {{ $hpResult === 'Positive' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }} px-4 py-1 text-sm font-bold">
            {{ $hpResult }}
        </span>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 7 — HAEMOSTASIS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['haemostasis']))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Haemostasis</h3>
        <p class="text-sm text-gray-800">{{ $payload['haemostasis'] }}</p>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 8 — IMPRESSION
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($payload['impression']))
    <div class="mb-6 rounded-xl border-2 border-green-700 bg-green-50 p-5">
        <h3 class="text-sm font-bold text-green-900 uppercase tracking-wider mb-2">Impression</h3>
        <p class="text-sm text-green-900 leading-relaxed">{{ $payload['impression'] }}</p>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 9 — RECOMMENDATIONS
     ═══════════════════════════════════════════════════════════════════ --}}
@if (!empty($recs))
    <div class="mb-6">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3 border-b pb-1">Recommendations</h3>
        <ul class="space-y-1">
            @foreach ($recs as $rec)
                <li class="flex items-start gap-2 text-sm text-gray-800">
                    <span class="mt-1 h-2 w-2 rounded-full bg-green-600 flex-shrink-0"></span>
                    <span>{{ $rec }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 10 — COMPLICATIONS & RECOVERY
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex flex-wrap gap-4 items-center">
    @if (!empty($payload['complications']))
        @php $comp = $payload['complications']; @endphp
        <div>
            <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Complications</span>
            <span class="inline-block rounded-full {{ $comp === 'None' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} px-3 py-1 text-xs font-semibold">
                {{ $comp }}
            </span>
        </div>
    @endif
    @if (!empty($payload['recovery_time_min']))
        <div>
            <span class="text-xs text-gray-500 uppercase tracking-wide block mb-1">Recovery Time</span>
            <span class="inline-block rounded-full bg-blue-100 text-blue-800 px-3 py-1 text-xs font-semibold">
                {{ $payload['recovery_time_min'] }} minutes
            </span>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SECTION 11 — ENDOSCOPIST SIGNATURE
     ═══════════════════════════════════════════════════════════════════ --}}
<div class="mt-8 border-t pt-5">
    <div class="flex justify-end">
        <div class="text-right text-sm">
            <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Reporting Endoscopist</div>
            <div class="font-bold text-gray-900">{{ $payload['endoscopist'] ?? $issuer_name }}</div>
            @if (!empty($payload['endoscopist_reg']))
                <div class="text-gray-500 text-xs">Reg: {{ $payload['endoscopist_reg'] }}</div>
            @endif
            <div class="text-gray-500">{{ $issued_at }}</div>
        </div>
    </div>
</div>
@endsection
