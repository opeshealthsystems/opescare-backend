{{-- 30 Blood request slip. Not a crossmatch record. docs/specs/documents/30-blood-request-slip.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.bld_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $val   = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
    $r = $bloodRequest;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.bld_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.bld_subtitle') }}</p>
<table class="kv"><tr><td class="k">{{ __('doc_new_docs.bld_reference') }}</td><td class="v id">{{ $r->reference ?: $dash }}</td></tr></table>
<h2>{{ __('doc_new_docs.section_patient') }}</h2>
@include('documents.new._patient', ['patient' => $patient])
<h2>{{ __('doc_new_docs.bld_bank') }}</h2>
@include('pdf.layouts.secure-facility', ['facility' => $r->careFacility ?? $facility])
<h2>{{ __('doc_new_docs.bld_request') }}</h2>
<table class="kv">
    <tr><td class="k">{{ __('doc_new_docs.bld_group') }}</td><td class="v"><strong>{{ $val($r->blood_group) ?: $dash }}</strong></td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_component') }}</td><td class="v">{{ $val($r->component_type) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_quantity') }}</td><td class="v">{{ $r->quantity ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.urgency') }}</td><td class="v">{{ $val($r->urgency) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.status') }}</td><td class="v">{{ $val($r->status) ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_needed_by') }}</td><td class="v">{{ $stamp($r->needed_by) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_raised') }}</td><td class="v">{{ $stamp($r->created_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_decided') }}</td><td class="v">{{ $stamp($r->decided_at ?? $r->confirmed_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_fulfilled') }}</td><td class="v">{{ $stamp($r->fulfilled_at) ?? $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_patient_note') }}</td><td class="v">{{ $r->patient_note ?: $dash }}</td></tr>
    <tr><td class="k">{{ __('doc_new_docs.bld_facility_note') }}</td><td class="v">{{ $r->facility_note ?: $dash }}</td></tr>
</table>
<p class="note"><strong>{{ __('doc_new_docs.bld_not_crossmatch') }}</strong></p>
@include('pdf.layouts.secure-signature', [])
@endsection
