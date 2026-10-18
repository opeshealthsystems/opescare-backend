{{-- 24 Health Passport print. Exactly the builder's pages. docs/specs/documents/24-health-passport.md --}}
@extends('pdf.layouts.secure')
@section('document_title', __('doc_new_docs.hp_title'))
@section('head')
<style>.hp-page { page-break-before: always; } .hp-num { color: #0F4C81; }</style>
@endsection
@section('content')
<h1>{{ __('doc_new_docs.hp_title') }}</h1>
<p class="subtitle">{{ __('doc_new_docs.hp_subtitle', ['version' => $summary['passport_version'] ?? '']) }}</p>
@include('documents.new._patient', ['patient' => $patient])
<p class="note">{{ __('doc_new_docs.hp_never_invent') }}</p>

<h2>{{ __('doc_new_docs.hp_contents') }}</h2>
<table class="data">
    @foreach($pages as $page)<tr><td class="num" style="width:12mm;">{{ $page['number'] }}</td><td>{{ $page['title'] }}</td></tr>@endforeach
</table>

@foreach($pages as $page)
<div class="hp-page">
    <h1><span class="hp-num">{{ $page['number'] }}</span> {{ $page['title'] }}</h1>
    <p class="subtitle">{{ is_string($page['subtitle'] ?? null) ? $page['subtitle'] : '' }}</p>
    @if(empty($page['sections']))
        <p class="empty">{{ is_string($page['empty'] ?? null) ? $page['empty'] : __('doc_new_docs.none_recorded') }}</p>
    @else
        @include('documents.new._sections', ['sections' => $page['sections']])
    @endif
</div>
@endforeach
@endsection
