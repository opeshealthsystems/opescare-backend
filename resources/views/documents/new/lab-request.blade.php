{{-- 28 Laboratory request form (ISO 15189 §7.2). docs/specs/documents/28-lab-request.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.lab_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $blank = '______________________________';
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $val   = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.lab_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.lab_subtitle') }}</p>
<h2>{{ __('doc_new_docs.section_patient') }}</h2>
@include('documents.new._patient', ['patient' => $patient])
<h2>{{ __('doc_new_docs.req_requester') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $facility])
<h2>{{ __('doc_new_docs.req_examination') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.lab_test') }}</td><td class="v"><strong>{{ $order->test_name ?: $dash }}</strong>@if($order->test_code) ({{ $order->test_code }})@endif</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.urgency') }}</td><td class="v">{{ $val($order->urgency) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.status') }}</td><td class="v">{{ $val($order->status) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.req_ordered') }}</td><td class="v">{{ $stamp($order->ordered_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.req_indication') }}</td><td class="v">{{ $order->clinical_indication ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.req_notes') }}</td><td class="v">{{ $order->notes ?: $dash }}</td></tr>
</table>
<h2>{{ __('doc_new_docs.lab_collection') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.lab_sample_type') }}</td><td class="v">{{ $blank }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.lab_collected') }}</td><td class="v">{{ $stamp($order->collected_at) ?? $blank }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.lab_collector') }}</td><td class="v">{{ $blank }}</td></tr>
</table>
@include('pdf.layouts.secure-signature', ['name' => $requester?->name, 'role' => __('doc_new_docs.req_requesting_clinician'), 'date' => $order->ordered_at])
@endsection
