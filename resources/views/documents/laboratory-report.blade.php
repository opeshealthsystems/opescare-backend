{{--
    Laboratory report — rendered when the patient asks to download it, never
    stored. See App\Services\Patient\PatientDocumentService.

    Sits on the OpesCare Secure sheet; see pdf/layouts/secure.blade.php for the
    dompdf constraints (tables only, local images, DejaVu Sans).

    The flag beside a value is the one the laboratory released. It is NOT
    recomputed here: this platform has a single authority on whether a result is
    out of range, and a report that disagreed with the clinician's screen about
    a critical value is exactly the failure that authority exists to prevent.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('document.lab_title'))

@php
    $dash = __('document.not_recorded');
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;

    // The laboratory's own flag, mapped to the sheet's pill classes.
    $flagTone = fn (?string $flag) => match (strtoupper(trim((string) $flag))) {
        'HH', 'LL', 'C', 'CRITICAL', 'PANIC'      => 'bad',
        'H', 'L', 'A', 'ABNORMAL', 'HIGH', 'LOW'  => 'warn',
        ''                                        => 'mute',
        default                                   => 'info',
    };

    $results = $order->results->sortBy('parameter_name');

    // ISO 15189 report content: specimen, collection time, the person who
    // validated the release. The specimen comes from the LOINC dictionary's
    // system axis for this test (the order has no specimen column); null when
    // the dictionary does not name one, and then nothing is printed.
    $specimen = null;
    try {
        $specimen = app(\App\Services\Lab\LabDeskWebService::class)->dictionaryTerm($order)?->specimen;
    } catch (\Throwable) {
        $specimen = null;
    }
    $orderedBy   = $order->orderedBy?->name;
    $validatedBy = $order->verifiedBy?->name
        ?? $results->map(fn ($r) => $r->verifiedBy?->name)->filter()->first();
    $age = null;
    if ($patient->date_of_birth) {
        try { $age = (int) \Illuminate\Support\Carbon::parse($patient->date_of_birth)->diffInYears($order->collected_at ?? $order->ordered_at ?? now()); } catch (\Throwable) { $age = null; }
    }
    $hasNotes = $results->contains(fn ($r) => filled($r->notes));
@endphp

@section('content')

<h1>{{ __('document.lab_title') }}</h1>
<p class="subtitle">{{ __('document.lab_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}@if($age !== null)<span class="muted"> — {{ __('doc_ex_rx.age_years', ['count' => $age]) }}</span>@endif</td></tr>
    <tr><td class="k">{{ __('document.sex') }}</td><td class="v">@if(filled($patient->sex))@enum($patient->sex, 'sex')@else{{ $dash }}@endif</td></tr>
</table>

<h2>{{ __('doc_ex_lab.request') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('document.lab_test') }}</td><td class="v">{{ $order->test_name ?: $dash }}@if(filled($order->test_code))<span class="muted"> ({{ $order->test_code }})</span>@endif</td></tr>
    <tr><td class="k">{{ __('document.lab_ordered_by') }}</td><td class="v">{{ $orderedBy ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.lab_ordered') }}</td><td class="v">{{ $stamp($order->ordered_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_ex_lab.specimen') }}</td><td class="v">{{ $specimen ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_ex_lab.collected') }}</td><td class="v">{{ $stamp($order->collected_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.lab_reported') }}</td><td class="v">{{ $stamp($order->resulted_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_ex_lab.validated_by') }}</td><td class="v">{{ $validatedBy ?: $dash }}</td></tr>
</table>

<h2>{{ __('doc_ex_lab.laboratory') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $order->facility])

<h2>{{ __('document.lab_result') }}</h2>
@if($results->isNotEmpty())
<table class="data">
    <thead>
        <tr>
            <th>{{ __('document.lab_analyte') }}</th>
            <th>{{ __('document.lab_result') }}</th>
            <th>{{ __('document.lab_unit') }}</th>
            <th>{{ __('document.lab_range') }}</th>
            <th>{{ __('document.lab_flag') }}</th>
        </tr>
    </thead>
    @foreach($results as $result)
    <tr>
        <td>{{ $result->parameter_name ?: $dash }}@if(filled($result->loinc_code))<br><span class="muted">LOINC {{ $result->loinc_code }}</span>@endif</td>
        <td><strong>{{ $result->value !== null && $result->value !== '' ? $result->value : $dash }}</strong></td>
        <td class="muted">{{ $result->unit ?: '' }}</td>
        <td class="muted">{{ $result->reference_range ?: '' }}</td>
        <td>
            @if(filled($result->flag))
                <span class="pill {{ $flagTone($result->flag) }}">{{ $result->flag }}</span>
            @endif
        </td>
    </tr>
    @endforeach
</table>
@else
<p class="muted">{{ __('document.lab_no_results') }}</p>
@endif

@if($hasNotes)
<h2>{{ __('doc_ex_lab.comments') }}</h2>
<table class="kv">
    @foreach($results->filter(fn ($r) => filled($r->notes)) as $result)
    <tr><td class="k">{{ $result->parameter_name ?: $dash }}</td><td class="v">{{ $result->notes }}</td></tr>
    @endforeach
</table>
@endif

{{-- The order's clinical indication is the REASON the test was asked for.
     It used to print under "Interpretation", which it is not. --}}
@if(filled($order->clinical_indication))
<h2>{{ __('doc_ex_lab.clinical_indication') }}</h2>
<p>{{ $order->clinical_indication }}</p>
@endif

@include('pdf.layouts.secure-signature', [
    'name'  => $validatedBy,
    'role'  => __('doc_ex_lab.validator_role'),
    'date'  => $order->resulted_at,
    'label' => __('doc_ex_lab.validation_signature'),
])

<p class="note">{{ __('document.lab_advice') }}</p>
<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
