{{-- 31 Consent record. docs/specs/documents/31-consent-record.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.con_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
    $val   = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.con_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.con_subtitle') }}</p>
@include('documents.new._patient', ['patient' => $patient])

<h2>{{ __('doc_new_docs.con_grants') }}</h2>
@if($grants->isEmpty())
<p class="empty">{{ __('doc_new_docs.none_recorded') }}</p>
@else
<table class="data">
    <thead><tr><th>{{ __('doc_new_docs.facility') }}</th><th>{{ __('doc_new_docs.con_scope') }}</th><th>{{ __('doc_new_docs.status') }}</th><th>{{ __('doc_new_docs.con_by') }}</th><th>{{ __('doc_new_docs.con_granted') }}</th><th>{{ __('doc_new_docs.con_expires') }}</th></tr></thead>
    @foreach($grants as $g)
    <tr><td>{{ $facilities[$g->facility_id] ?? $dash }}</td><td>{{ is_array($g->scope) ? implode(', ', array_filter($g->scope, 'is_scalar')) : ($g->scope ?: $dash) }}</td><td>{{ $val($g->status) }}</td><td>{{ $g->authorizing_actor ?: $dash }}</td><td>{{ $stamp($g->created_at) }}</td><td>{{ $stamp($g->expires_at) ?? $dash }}</td></tr>
    @endforeach
</table>
@endif

<h2>{{ __('doc_new_docs.con_requests') }}</h2>
@if($requests->isEmpty())
<p class="empty">{{ __('doc_new_docs.none_recorded') }}</p>
@else
<table class="data">
    <thead><tr><th>{{ __('doc_new_docs.facility') }}</th><th>{{ __('doc_new_docs.con_purpose') }}</th><th>{{ __('doc_new_docs.status') }}</th><th>{{ __('doc_new_docs.con_asked') }}</th><th>{{ __('doc_new_docs.con_decided') }}</th></tr></thead>
    @foreach($requests as $r)
    <tr><td>{{ $facilities[$r->requesting_facility_id] ?? $dash }}</td><td>{{ $r->purpose ?: $dash }}</td><td>{{ $val($r->status) }}</td><td>{{ $stamp($r->created_at) }}</td><td>{{ $stamp($r->decided_at) ?? $dash }}</td></tr>
    @endforeach
</table>
@endif
@endsection
