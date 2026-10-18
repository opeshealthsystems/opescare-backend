{{-- 26 Appointment slip. docs/specs/documents/26-appointment-slip.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.apt_title'))
@php
    $dash = __('doc_new_docs.not_recorded');
    $val  = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.apt_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.apt_subtitle') }}</p>
@include('documents.new._patient', ['patient' => $patient])

<h2>{{ __('doc_new_docs.apt_details') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.apt_when') }}</td><td class="v"><strong>{{ $appointment->scheduled_at ? $appointment->scheduled_at->translatedFormat('l d M Y, H:i') : $dash }}</strong></td></tr>
    <tr><td class="k">{{ __('doc_new_docs.apt_type') }}</td><td class="v">{{ $val($appointment->appointment_type) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.status') }}</td><td class="v">{{ $val($appointment->status) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.apt_provider') }}</td><td class="v">{{ $provider?->name ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.apt_reason') }}</td><td class="v">{{ $appointment->reason ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.apt_booking_ref') }}</td><td class="v id">{{ strtoupper(substr((string) $appointment->id, 0, 8)) }}</td></tr>
</table>

<h2>{{ __('doc_new_docs.apt_where') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $facility])
<p class="note">{{ __('doc_new_docs.apt_bring') }}</p>
@endsection
