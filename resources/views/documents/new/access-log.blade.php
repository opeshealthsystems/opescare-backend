{{-- 32 Patient access log. IP and user agent are not printed. docs/specs/documents/32-access-log.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.acl_title'))
@php
    $dash  = __('doc_new_docs.not_recorded');
    $stamp = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->translatedFormat('d M Y, H:i') : null;
@endphp
@section('content')
<h1>{{ __('doc_new_docs.acl_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.acl_subtitle', ['count' => $limit]) }}</p>
@include('documents.new._patient', ['patient' => $patient])

<h2>{{ __('doc_new_docs.acl_entries') }}</h2>
@if($logs->isEmpty())
<p class="empty">{{ __('doc_new_docs.none_recorded') }}</p>
@else
<table class="data">
    <thead><tr><th>{{ __('doc_new_docs.acl_when') }}</th><th>{{ __('doc_new_docs.acl_who') }}</th><th>{{ __('doc_new_docs.facility') }}</th><th>{{ __('doc_new_docs.acl_what') }}</th><th>{{ __('doc_new_docs.acl_why') }}</th></tr></thead>
    @foreach($logs as $log)
    <tr>
        <td>{{ $stamp($log->created_at) }}</td>
        <td>{{ $actors[$log->actor_id] ?? ($log->actor_type ?: $dash) }}</td>
        <td>{{ $facilities[$log->facility_id] ?? $dash }}</td>
        <td>{{ $log->access_type }} · {{ $log->data_category }}</td>
        <td>{{ $log->purpose }}@if($log->emergency_access) <span class="pill bad">{{ __('doc_new_docs.acl_emergency') }}</span>@endif</td>
    </tr>
    @endforeach
</table>
@endif
@endsection
