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
@endphp

@section('content')

<h1>{{ __('document.lab_title') }}</h1>
<p class="subtitle">{{ __('document.lab_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.lab_test') }}</td><td class="v">{{ $order->test_name ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.lab_ordered') }}</td><td class="v">{{ $date($order->ordered_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.lab_reported') }}</td><td class="v">{{ $date($order->resulted_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.facility') }}</td><td class="v">{{ $order->facility->name ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.issued') }}</td><td class="v">{{ $stamp($issuedAt) }}</td></tr>
</table>

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
        <td>{{ $result->parameter_name ?: $dash }}</td>
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

@if(filled($order->clinical_indication))
<h2>{{ __('document.lab_interpretation') }}</h2>
<p>{{ $order->clinical_indication }}</p>
@endif

<p class="note">{{ __('document.lab_advice') }}</p>
<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
