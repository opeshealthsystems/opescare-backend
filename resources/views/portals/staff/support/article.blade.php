@extends('layouts.portal')

@section('title', $article->title . ' — OpesCare')

@section('breadcrumb_home', __('support.title'))
@section('breadcrumb_home_url', route('portals.staff.support'))
@section('breadcrumb_section', __('support.kb_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $article->title }}</h1>
        <p class="page-subtitle">{{ $article->published_at?->isoFormat('ll') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.staff.support') }}" class="btn btn-ghost"><i data-lucide="arrow-left"></i> {{ __('support.back') }}</a>
    </div>
</div>

<div class="panel">
    <div class="panel-body" style="white-space:pre-line;">{{ $article->body }}</div>
</div>

@endsection
