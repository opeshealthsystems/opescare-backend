{{-- 29 Imaging request form. docs/specs/documents/29-imaging-request.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.img_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $val   = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.img_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.img_subtitle') }}</p>
<h2>{{ __('doc_new_docs.section_patient') }}</h2>
@include('documents.new._patient', ['patient' => $patient])
<h2>{{ __('doc_new_docs.req_requester') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $facility])
<h2>{{ __('doc_new_docs.req_examination') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.img_accession') }}</td><td class="v id">{{ $order->accession_number ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.img_modality') }}</td><td class="v"><strong>{{ $order->modalityLabel() ?: $dash }}</strong></td></tr>
    <tr><td class="k">{{ __('doc_new_docs.img_region') }}</td><td class="v">{{ $order->body_part ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.urgency') }}</td><td class="v">{{ $val($order->urgency) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.status') }}</td><td class="v">{{ $val($order->status) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.req_ordered') }}</td><td class="v">{{ $stamp($order->ordered_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.img_scheduled') }}</td><td class="v">{{ $stamp($order->scheduled_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.req_indication') }}</td><td class="v">{{ $order->clinical_indication ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.img_referring') }}</td><td class="v">{{ $order->referring_physician ?: ($requester?->name ?? $dash) }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.req_notes') }}</td><td class="v">{{ $order->notes ?: $dash }}</td></tr>
</table>
<h2>{{ __('doc_new_docs.img_safety') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.img_pregnancy') }}</td><td class="v">[ ] {{ __('doc_new_docs.yes') }} &nbsp; [ ] {{ __('doc_new_docs.no') }} &nbsp; [ ] {{ __('doc_new_docs.na') }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.img_contrast') }}</td><td class="v">[ ] {{ __('doc_new_docs.yes') }} &nbsp; [ ] {{ __('doc_new_docs.no') }}</td></tr>
</table>
@include('pdf.layouts.secure-signature', ['name' => $requester?->name, 'role' => __('doc_new_docs.req_requesting_clinician'), 'date' => $order->ordered_at])
@endsection
