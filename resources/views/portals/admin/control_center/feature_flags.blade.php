@extends('layouts.portal')
@section('title', __('public.adm_cc_ff_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cc_ff_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cc_ff_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="oda-ga-alert oda-ga-alert--danger"><i data-lucide="triangle-alert"></i><div class="oda-ga-alert__body">{{ session('error') }}</div></div>
@endif

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_cc_ff_heading') }} <span class="od-card__count">({{ $flags->count() }})</span></h2>
    </div>
    <div class="od-card__body od-card__body--flush">
    @if($flags->count() === 0)
        <div class="od-empty">
            <span class="od-empty__icon"><i data-lucide="toggle-right"></i></span>
            <h3 class="od-empty__title">{{ __('public.adm_cc_ff_empty_heading') }}</h3>
            <p class="od-empty__text">{{ __('public.adm_cc_ff_empty_body') }}</p>
        </div>
    @else
        @foreach($flags as $flag)
        <div class="oda-ga-toggle">
            <div class="oda-ga-toggle__text">
                <span class="od-table__name">{{ $flag->label }}</span>
                <div class="oda-ga-toggle__meta">
                    <span class="oda-mono">{{ $flag->key }}</span>
                    <span class="od-pill od-pill--neutral">{{ $flag->scope }}</span>
                    @if($flag->updated_by)<span>· {{ __('public.adm_cc_ff_updated_by') }} {{ $flag->updated_by }}</span>@endif
                </div>
            </div>
            <span class="od-pill {{ $flag->enabled ? 'od-pill--ready' : 'od-pill--neutral' }}">
                {{ $flag->enabled ? __('public.adm_cc_ff_badge_enabled') : __('public.adm_cc_ff_badge_disabled') }}
            </span>
            <form method="POST" action="{{ route('portals.admin.cc.feature_flags.toggle', urlencode($flag->key)) }}" class="oda-inline-form">
                @csrf
                <input type="hidden" name="enabled" value="{{ $flag->enabled ? '0' : '1' }}">
                <label class="oda-ga-switch">
                    <input type="checkbox" {{ $flag->enabled ? 'checked' : '' }} data-autosubmit aria-label="{{ __('admin_desk.ga_toggle', ['label' => $flag->label]) }}">
                    <span class="oda-ga-switch__track"></span>
                </label>
            </form>
        </div>
        @endforeach
    @endif
    </div>
</section>
</div>
@endsection
