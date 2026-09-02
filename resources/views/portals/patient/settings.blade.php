@extends('layouts.portal')

@php $l = app()->getLocale(); @endphp

@section('title', __('public.pat_settings_title', [], $l) . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], $l) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_settings_breadcrumb', [], $l))

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.pat_settings_title', [], $l) }}</h1>
        <p class="page-subtitle">{{ __('public.pat_settings_subtitle', [], $l) }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

@if(!$patient)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="alert-circle"></i></div>
        <h3>{{ __('public.portal.no_profile_found_title', [], $l) ?: 'No Patient Profile Found' }}</h3>
        <p>{{ __('public.portal.no_profile_found_desc', [], $l) ?: 'Your patient profile could not be loaded. Please contact support.' }}</p>
    </div>
</div>
@else
<form method="POST" action="{{ route('portals.patient.settings.update') }}">
    @csrf

    @php
        $prefs = [
            'push_appointments'     => ['icon' => 'calendar-check-2', 'label' => __('public.pat_settings_appointments', [], $l)],
            'push_lab_results'      => ['icon' => 'flask-conical',    'label' => __('public.pat_settings_lab_results', [], $l)],
            'push_prescriptions'    => ['icon' => 'pill',             'label' => __('public.pat_settings_prescriptions', [], $l)],
            'push_billing'          => ['icon' => 'credit-card',      'label' => __('public.pat_settings_billing', [], $l)],
            'push_consent_requests' => ['icon' => 'shield-check',     'label' => __('public.pat_settings_consent', [], $l)],
        ];
    @endphp

    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title"><i data-lucide="bell"></i> {{ __('public.pat_settings_notif_title', [], $l) }}</h2>
        </div>
        <div class="panel-body">
            <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.25rem;">
                @foreach($prefs as $field => $meta)
                <li style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--p-border, #e5e7eb);">
                    <span style="display:flex;align-items:center;gap:.6rem;">
                        <i data-lucide="{{ $meta['icon'] }}" style="width:1.1rem;height:1.1rem;color:#0F4C81;"></i>
                        <span class="td-strong">{{ $meta['label'] }}</span>
                    </span>
                    <label class="switch">
                        <input type="checkbox" name="{{ $field }}" value="1" {{ $settings?->{$field} ? 'checked' : '' }} aria-label="{{ $meta['label'] }}">
                        <span class="switch__track"></span>
                    </label>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title"><i data-lucide="palette"></i> {{ __('public.pat_settings_appearance_title', [], $l) }}</h2>
        </div>
        <div class="panel-body">
            <label class="td-strong" for="preferred_theme" style="display:block;margin-bottom:.4rem;">{{ __('public.pat_settings_theme', [], $l) }}</label>
            <select id="preferred_theme" name="preferred_theme" class="form-control" style="max-width:260px;">
                <option value="system" {{ ($settings?->preferred_theme ?? 'system') === 'system' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_system', [], $l) }}</option>
                <option value="light"  {{ $settings?->preferred_theme === 'light' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_light', [], $l) }}</option>
                <option value="dark"   {{ $settings?->preferred_theme === 'dark' ? 'selected' : '' }}>{{ __('public.pat_settings_theme_dark', [], $l) }}</option>
            </select>
        </div>
    </div>

    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:1rem;">
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i> {{ __('public.pat_settings_save', [], $l) }}
        </button>
        <span class="page-subtitle" style="margin:0;">{{ __('public.pat_settings_save_hint', [], $l) }}</span>
    </div>
</form>
@endif

@endsection
