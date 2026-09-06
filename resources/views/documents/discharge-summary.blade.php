{{--
    Discharge summary — rendered when the patient asks to download it, never
    stored. See App\Services\Patient\PatientDocumentService.

    Only a discharged admission produces this sheet; the service refuses one
    that is still open. A "discharge summary" for a patient who has not been
    discharged would be read as a discharge.
--}}
@extends('pdf.layouts.secure')

@section('document_title', __('document.dis_title'))

@php
    $dash  = __('document.not_recorded');
    $date  = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y') : null;
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;

    $admitted   = $admission->admitted_at ? \Illuminate\Support\Carbon::parse($admission->admitted_at) : null;
    $discharged = $admission->discharged_at ? \Illuminate\Support\Carbon::parse($admission->discharged_at) : null;
    // Whole days between the two, only when the record carries both.
    $nights = $admitted && $discharged ? $admitted->diffInDays($discharged) : null;
@endphp

@section('content')

<h1>{{ __('document.dis_title') }}</h1>
<p class="subtitle">{{ __('document.dis_subtitle') }}</p>

<table class="kv">
    <tr><td class="k">{{ __('document.issued_to') }}</td><td class="v">{{ trim($patient->first_name . ' ' . $patient->last_name) }}</td></tr>
    <tr><td class="k">{{ __('document.health_id') }}</td><td class="v id">{{ $patient->health_id ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.date_of_birth') }}</td><td class="v">{{ $date($patient->date_of_birth) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.dis_admitted') }}</td><td class="v">{{ $date($admission->admitted_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('document.dis_discharged') }}</td><td class="v">{{ $date($admission->discharged_at) ?? $dash }}</td></tr>
    @if($nights !== null)
    <tr><td class="k">{{ __('document.dis_length') }}</td><td class="v">{{ __('document.rx_days', ['count' => $nights]) }}</td></tr>
    @endif
    <tr><td class="k">{{ __('document.issued') }}</td><td class="v">{{ $stamp($issuedAt) }}</td></tr>
</table>

<h2>{{ __('document.dis_reason') }}</h2>
<p>{{ $admission->admission_reason ?: $dash }}</p>

<h2>{{ __('document.dis_outcome') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('document.dis_outcome') }}</td><td class="v">{{ $admission->discharge_reason ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('document.dis_destination') }}</td><td class="v">{{ $admission->discharge_destination ?: $dash }}</td></tr>
</table>

<p class="note muted">{{ __('document.generated_note') }}</p>

@endsection
