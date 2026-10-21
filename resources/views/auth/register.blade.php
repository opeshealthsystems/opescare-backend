@extends('layouts.auth')

@section('title', __('onboarding.selector.title'))

@push('head')
<link rel="stylesheet" href="{{ asset('css/onboarding.css') }}?v={{ @filemtime(public_path('css/onboarding.css')) ?: '1' }}">
@endpush

@php
    // Account-type selector, on the onboarding visual system (design 2026-10-21).
    $obGroups = [
        'people' => [
            ['route' => route('register.patient'),      'icon' => 'heart-pulse',   'key' => 'patient'],
            ['route' => route('register.professional'), 'icon' => 'stethoscope',   'key' => 'professional'],
            ['route' => route('register.guardian'),     'icon' => 'users',         'key' => 'guardian'],
        ],
        'facilities' => [
            ['route' => route('register.organization', ['type' => 'hospital']),      'icon' => 'building-2',    'key' => 'hospital'],
            ['route' => route('register.organization', ['type' => 'pharmacy']),      'icon' => 'pill',          'key' => 'pharmacy'],
            ['route' => route('register.organization', ['type' => 'laboratory']),    'icon' => 'flask-conical', 'key' => 'laboratory'],
            ['route' => route('register.organization', ['type' => 'imaging']),    'icon' => 'scan-line', 'title' => 'onboarding_flow.type.cards.imaging.title', 'desc' => 'onboarding_flow.type.cards.imaging.desc'],
            ['route' => route('register.organization', ['type' => 'blood_bank']), 'icon' => 'droplet',   'title' => 'onboarding_flow.type.cards.blood_bank.title', 'desc' => 'onboarding_flow.type.cards.blood_bank.desc'],
            ['route' => route('register.organization', ['type' => 'public_health']), 'icon' => 'globe',         'key' => 'public_health'],
        ],
        'partners' => [
            ['route' => route('register.developer'), 'icon' => 'braces', 'key' => 'developer'],
        ],
    ];
@endphp

@section('content')
<div class="ob-wrap">
    <div class="ob-panel">
        <div class="ob-head">
            <div class="ob-eyebrow">{{ __('onboarding_flow.selector.eyebrow') }}</div>
            <h1 class="ob-h1">{{ __('onboarding.selector.title') }}</h1>
            <p class="ob-sub">{{ __('onboarding.selector.subtitle') }}</p>
        </div>

        @foreach($obGroups as $group => $cards)
            <section class="ob-section" aria-labelledby="ob-g-{{ $group }}">
                <h2 id="ob-g-{{ $group }}">{{ __('onboarding_flow.selector.' . $group) }}</h2>
                @if($group === 'facilities')
                    <p class="ob-legend-sub">{{ __('onboarding_flow.selector.facilities_help') }}</p>
                @endif
                <div class="ob-choices">
                    @foreach($cards as $c)
                        <a href="{{ $c['route'] }}" class="ob-choice">
                            <span class="ob-ic">@include('partials.finder.icon', ['name' => $c['icon'], 'size' => 22])</span>
                            <span class="ob-choice__body">
                                <b>{{ __($c['title'] ?? 'onboarding.selector.cards.' . $c['key'] . '_title') }}</b>
                                <span class="ob-choice__desc">{{ __($c['desc'] ?? 'onboarding.selector.cards.' . $c['key'] . '_desc') }}</span>
                            </span>
                            <span class="ob-choice__go">@include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="ob-alt">
            <p>{{ __('onboarding.selector.already_have') }}
                <a href="{{ route('login') }}">{{ __('onboarding.selector.signin') }}</a>
            </p>
        </div>
    </div>
</div>
@endsection
