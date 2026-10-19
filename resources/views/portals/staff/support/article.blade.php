@extends('layouts.portal')

@section('title', $article->title . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ $article->title }}</h1>
        <p class="od-page__sub">{{ $article->published_at?->isoFormat('ll') }}</p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.staff.support') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('support.back') }}</a>
    </div>
</header>

<section class="od-card">
    <div class="od-card__body odc-prose odc-preline">{{ $article->body }}</div>
</section>

</div>
@endsection
