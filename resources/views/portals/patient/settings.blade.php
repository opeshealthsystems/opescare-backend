@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('public.pat_settings_title', [], $l) . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], $l))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_settings_breadcrumb', [], $l))

@section('head')
    @include('portals.patient._finder_head')
    <link rel="stylesheet" href="{{ asset('css/od-patient.css') }}?v={{ @filemtime(public_path('css/od-patient.css')) ?: '1' }}">
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
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
    <div class="f-card ods-99e8a36">
        <div class="pf-qa__icon ods-4ef0061">
            @include('partials.finder.icon', ['name' => 'alert-triangle', 'size' => 24])
        </div>
        <h2 class="ods-40b57ba">{{ __('public.portal.no_profile_found_title', [], $l) }}</h2>
        <p class="f-muted ods-1386d50">{{ __('public.portal.no_profile_found_desc', [], $l) }}</p>
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
                'push_messages' => ['icon' => 'message-circle', 'label' => __('public.pat_settings_messages', [], $l)],
            ];
        @endphp

        <div class="f-card ods-fa3e1c7">
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

        <div class="f-card ods-8e86974">
            <h2 class="pf-card-head">{{ __('public.pat_settings_appearance_title', [], $l) }}</h2>
            <div class="f-field ods-e7ec540">
                <label class="f-label" for="preferred_theme">{{ __('public.pat_settings_theme', [], $l) }}</label>
                <select id="preferred_theme" name="preferred_theme" class="f-input ods-015ccf9">
                    <option value="system" {{ ($settings?->preferred_theme ?? 'system') === 'system' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_system', [], $l) }}</option>
                    <option value="light"  {{ $settings?->preferred_theme === 'light' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_light', [], $l) }}</option>
                    <option value="dark"   {{ $settings?->preferred_theme === 'dark' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_dark', [], $l) }}</option>
                </select>
            </div>
        </div>

        <div class="pf-actions">
            <button type="submit" class="f-btn f-btn--primary ods-a27b458">
                @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                {{ __('public.pat_settings_save', [], $l) }}
            </button>
            <span class="f-small f-muted">{{ __('public.pat_settings_save_hint', [], $l) }}</span>
        </div>
    </form>

    {{-- Lost or stolen card. One POST revokes every active QR token; the Health
         ID number itself stays valid, so nothing here can lock a patient out. --}}
    <form method="POST" action="{{ route('portals.patient.lost-card') }}" class="f-card ods-8e86974">
        @csrf
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'shield-alert', 'size' => 18])
            {{ __('public.pat_lost_card_title', [], $l) }}
        </h2>
        <p class="f-muted ods-c9d4377">{{ __('public.pat_lost_card_desc', [], $l) }}</p>
        <div class="f-field">
            <label class="f-label" for="report_reason">{{ __('public.pat_lost_card_reason', [], $l) }}</label>
            <textarea id="report_reason" name="report_reason" class="f-input" rows="2" maxlength="500" required>{{ old('report_reason') }}</textarea>
            @error('report_reason')<p class="f-small ods-a945a59">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="f-btn f-btn--danger" data-confirm="{{ __('public.pat_lost_card_confirm', [], $l) }}">
            @include('partials.finder.icon', ['name' => 'shield-alert', 'size' => 18])
            {{ __('public.pat_lost_card_btn', [], $l) }}
        </button>
    </form>
    @endif
</div>

@endsection
