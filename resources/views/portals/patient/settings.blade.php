@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('public.pat_settings_title', [], $l) . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_settings_breadcrumb', [], $l))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.pat_settings_title', [], $l) }}</h1>
            <p class="pf-sub">{{ __('public.pat_settings_subtitle', [], $l) }}</p>
        </div>
    </div>

    @if(!$patient)
    <div class="f-card" style="text-align:center;padding:40px 20px;">
        <div class="pf-qa__icon" style="margin:0 auto 12px;width:48px;height:48px;">
            @include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 24])
        </div>
        <h2 style="font-size:1.125rem;font-weight:750;margin:0 0 6px;">{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h2>
        <p class="f-muted" style="margin:0;">{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
    @else
    <form method="POST" action="{{ route('portals.patient.settings.update') }}">
        @csrf

        @php
            $prefs = [
                'push_appointments'     => ['icon' => 'calendar',     'label' => __('public.pat_settings_appointments', [], $l)],
                'push_lab_results'      => ['icon' => 'droplet',      'label' => __('public.pat_settings_lab_results', [], $l)],
                'push_prescriptions'    => ['icon' => 'pill',         'label' => __('public.pat_settings_prescriptions', [], $l)],
                'push_billing'          => ['icon' => 'info',         'label' => __('public.pat_settings_billing', [], $l)],
                'push_consent_requests' => ['icon' => 'shield-check', 'label' => __('public.pat_settings_consent', [], $l)],
            ];
        @endphp

        <div class="f-card" style="margin-bottom:12px;">
            <h2 class="pf-card-head">
                @include('partials.finder.icon', ['name' => 'bell', 'size' => 18])
                {{ __('public.pat_settings_notif_title', [], $l) }}
            </h2>
            <div>
                @foreach($prefs as $field => $meta)
                <div class="pf-setting">
                    <span class="pf-setting__label">
                        @include('partials.finder.icon', ['name' => $meta['icon'], 'size' => 18])
                        {{ $meta['label'] }}
                    </span>
                    <label class="pf-switch">
                        <input type="checkbox" name="{{ $field }}" value="1" {{ $settings?->{$field} ? 'checked' : '' }} aria-label="{{ $meta['label'] }}">
                        <i></i>
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <div class="f-card" style="margin-bottom:16px;">
            <h2 class="pf-card-head">{{ __('public.pat_settings_appearance_title', [], $l) }}</h2>
            <div class="f-field" style="margin-bottom:0;">
                <label class="f-label" for="preferred_theme">{{ __('public.pat_settings_theme', [], $l) }}</label>
                <select id="preferred_theme" name="preferred_theme" class="f-input" style="max-width:280px;">
                    <option value="system" {{ ($settings?->preferred_theme ?? 'system') === 'system' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_system', [], $l) }}</option>
                    <option value="light"  {{ $settings?->preferred_theme === 'light' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_light', [], $l) }}</option>
                    <option value="dark"   {{ $settings?->preferred_theme === 'dark' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_dark', [], $l) }}</option>
                </select>
            </div>
        </div>

        <div class="pf-actions">
            <button type="submit" class="f-btn f-btn--primary" style="min-height:48px;">
                @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                {{ __('public.pat_settings_save', [], $l) }}
            </button>
            <span class="f-small f-muted">{{ __('public.pat_settings_save_hint', [], $l) }}</span>
        </div>
    </form>
    @endif
</div>

@endsection
