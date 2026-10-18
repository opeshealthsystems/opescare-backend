@extends('layouts.portal')
@section('title', __('public.adm_cc_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_idx_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_idx_breadcrumb_section'))
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
<div class="od-page od-stack">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('public.adm_cc_idx_heading') }}</h1>
        <p class="od-page__sub">{{ __('public.adm_cc_idx_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="oda-ga-alert oda-ga-alert--ok"><i data-lucide="check-circle"></i><div class="oda-ga-alert__body">{{ session('success') }}</div></div>
@endif

{{-- System Health Banner --}}
@php
    $anyError = collect($health)->whereIn('status', ['error'])->count() > 0;
    $anyWarn  = collect($health)->whereIn('status', ['warning'])->count() > 0 || ($health['failed_jobs']['count'] ?? 0) > 0;
@endphp
@if($anyError)
<div class="oda-ga-alert oda-ga-alert--danger">
    <i data-lucide="alert-triangle"></i>
    <div class="oda-ga-alert__body"><strong>{{ __('public.adm_cc_idx_banner_error_msg') }}</strong> {{ __('public.adm_cc_idx_banner_error_detail') }}</div>
    <a href="{{ route('portals.admin.cc.health') }}" class="od-btn od-btn--danger od-btn--sm">{{ __('public.adm_cc_idx_banner_error_btn') }}</a>
</div>
@elseif($anyWarn)
<div class="oda-ga-alert oda-ga-alert--warn">
    <i data-lucide="alert-circle"></i>
    <div class="oda-ga-alert__body"><strong>{{ __('public.adm_cc_idx_banner_warn_label') }}</strong> {{ __('public.adm_cc_idx_banner_warn_detail') }}</div>
    <a href="{{ route('portals.admin.cc.health') }}" class="od-btn od-btn--secondary od-btn--sm">{{ __('public.adm_cc_idx_banner_warn_btn') }}</a>
</div>
@endif

{{-- God Mode — Platform Management --}}
<section>
    <h2 class="oda-ga-section"><i data-lucide="zap"></i> {{ __('public.adm_cc_idx_god_mode_heading') }}</h2>
    <div class="oda-ga-tiles">
        {{-- Only desks this app actually serves. The tiles are built from
             route() so a renamed route breaks the build instead of shipping a
             404: the six hand-written paths that used to sit here (patients,
             staff, financial, support, cdss, organizations) were platform-only
             desks that never existed in Finder and 404'd on every click. --}}
        @php $godCards = [
            [__('public.adm_cc_idx_god_users'),        'users',    route('portals.admin.users.index'),        __('public.adm_cc_idx_god_users_desc')],
            [__('public.adm_cc_idx_god_facilities'),   'building', route('portals.admin.facilities.index'),   __('public.adm_cc_idx_god_facilities_desc')],
            [__('public.adm_cc_idx_god_appointments'), 'calendar', route('portals.admin.appointments.index'), __('public.adm_cc_idx_god_appointments_desc')],
            [__('public.adm_cc_idx_god_roles'),        'shield',   route('portals.admin.roles.index'),        __('public.adm_cc_idx_god_roles_desc')],
        ]; @endphp
        @foreach($godCards as [$title, $icon, $url, $desc])
        <a href="{{ $url }}" class="oda-ga-tile oda-ga-tile--danger">
            <span class="oda-ga-tile__head"><i data-lucide="{{ $icon }}"></i> {{ $title }}</span>
            <p class="oda-ga-tile__desc">{{ $desc }}</p>
        </a>
        @endforeach
    </div>
</section>

{{-- Quick nav cards --}}
<div class="oda-ga-tiles">
    @php $cards = [
        [__('public.adm_cc_idx_nav_settings'),    'sliders-horizontal', route('portals.admin.cc.settings'),      __('public.adm_cc_idx_nav_settings_desc')],
        [__('public.adm_cc_idx_nav_flags'),       'toggle-right',       route('portals.admin.cc.feature_flags'), __('public.adm_cc_idx_nav_flags_desc')],
        [__('public.adm_cc_idx_nav_modules'),     'puzzle',             route('portals.admin.cc.modules'),       __('public.adm_cc_idx_nav_modules_desc')],
        [__('public.adm_cc_idx_nav_maintenance'), 'wrench',             route('portals.admin.cc.maintenance'),   __('public.adm_cc_idx_nav_maintenance_desc')],
        [__('public.adm_cc_idx_nav_health'),      'activity',           route('portals.admin.cc.health'),        __('public.adm_cc_idx_nav_health_desc')],
        [__('public.adm_cc_idx_nav_audit'),       'scroll-text',        route('portals.admin.cc.audit'),         __('public.adm_cc_idx_nav_audit_desc')],
    ]; @endphp
    @foreach($cards as [$title, $icon, $url, $desc])
    <a href="{{ $url }}" class="oda-ga-tile">
        <span class="oda-ga-tile__head"><i data-lucide="{{ $icon }}"></i> {{ $title }}</span>
        <p class="oda-ga-tile__desc">{{ $desc }}</p>
    </a>
    @endforeach
</div>

{{-- Recent Admin Actions --}}
<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('public.adm_cc_idx_recent_heading') }} <span class="od-card__count">({{ $actions->count() }})</span></h2>
        <a href="{{ route('portals.admin.cc.audit') }}" class="od-card__link">{{ __('public.adm_cc_idx_recent_view_all') }}</a>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($actions->count() === 0)
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="scroll-text"></i></span>
                <p class="od-empty__text">{{ __('public.adm_cc_idx_recent_empty') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_idx_col_action') }}</th><th>{{ __('public.adm_cc_idx_col_resource') }}</th><th>{{ __('public.adm_cc_idx_col_actor') }}</th><th>{{ __('public.adm_cc_idx_col_when') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($actions as $a)
                    <tr>
                        <td data-label="Action"><span class="oda-mono">{{ $a->action }}</span></td>
                        <td data-label="Resource"><span class="od-pill od-pill--neutral">{{ $a->resource_type ?? '—' }}</span></td>
                        <td data-label="Actor"><span class="oda-mono">{{ $a->actor_id }}</span></td>
                        <td data-label="When" class="oda-ga-muted">{{ \Carbon\Carbon::parse($a->occurred_at)->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>
</div>
@endsection
