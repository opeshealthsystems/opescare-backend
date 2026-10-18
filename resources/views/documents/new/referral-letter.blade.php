{{-- 20 Referral letter / counter-referral. docs/specs/documents/20-referral-letter.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.ref_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $urg   = in_array($referral->urgency, ['routine', 'urgent', 'emergency'], true) ? __('doc_new_docs.urgency_' . $referral->urgency) : ($referral->urgency ?: $dash);
@endphp
@section('content')
<h1>{{ __('doc_new_docs.ref_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.ref_subtitle') }}</p>

<h2>{{ __('doc_new_docs.section_patient') }}</h2>
@include('documents.new._patient', ['patient' => $patient])

<h2>{{ __('doc_new_docs.ref_from') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $from])

<h2>{{ __('doc_new_docs.ref_to') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $to])
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.ref_specialty') }}</td><td class="v">{{ $referral->receiving_specialty ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.ref_receiving_clinician') }}</td><td class="v">{{ $referral->receiving_provider_name ?: $dash }}</td></tr>
</table>

<h2>{{ __('doc_new_docs.ref_referral') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.urgency') }}</td><td class="v"><strong>{{ $urg }}</strong></td></tr>
    <tr><td class="k">{{ __('doc_new_docs.status') }}</td><td class="v">{{ $referral->status ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.ref_raised') }}</td><td class="v">{{ $stamp($referral->created_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.ref_reason') }}</td><td class="v">{{ $referral->reason ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.ref_summary') }}</td><td class="v">{{ $referral->clinical_summary ?: $dash }}</td></tr>
    @if($referral->expires_at)<tr><td class="k">{{ __('doc_new_docs.ref_expires') }}</td><td class="v">{{ $stamp($referral->expires_at) }}</td></tr>@endif
</table>
@include('pdf.layouts.secure-signature', ['name' => $referrer?->name, 'role' => __('doc_new_docs.ref_referring_clinician'), 'date' => $referral->created_at])

<h2>{{ __('doc_new_docs.ref_counter') }}</h2>
@if($referral->completed_at || filled($referral->feedback) || $referral->rejected_at)
<table class="kv">
    @if($referral->accepted_at)<tr><td class="k">{{ __('doc_new_docs.ref_accepted') }}</td><td class="v">{{ $stamp($referral->accepted_at) }}</td></tr>@endif
    @if($referral->rejected_at)<tr><td class="k">{{ __('doc_new_docs.ref_rejected') }}</td><td class="v">{{ $stamp($referral->rejected_at) }} — {{ $referral->rejection_reason ?: $dash }}</td></tr>@endif
    @if($referral->completed_at)<tr><td class="k">{{ __('doc_new_docs.ref_completed') }}</td><td class="v">{{ $stamp($referral->completed_at) }}</td></tr>@endif
    <tr><td class="k">{{ __('doc_new_docs.ref_feedback') }}</td><td class="v">{{ $referral->feedback ?: $dash }}</td></tr>
</table>
@else
<div class="box"><p class="empty">{{ __('doc_new_docs.ref_counter_pending') }}</p><br><br></div>
@endif
@include('pdf.layouts.secure-signature', ['label' => __('doc_new_docs.ref_receiving_signature')])
@endsection
