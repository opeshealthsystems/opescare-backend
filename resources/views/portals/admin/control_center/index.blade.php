@extends('layouts.portal')
@section('title', __('public.adm_cc_idx_title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.adm_cc_idx_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_cc_idx_breadcrumb_section'))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.adm_cc_idx_heading') }}</h1>
        <p class="page-subtitle">{{ __('public.adm_cc_idx_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

{{-- System Health Banner --}}
@php
    $anyError = collect($health)->whereIn('status', ['error'])->count() > 0;
    $anyWarn  = collect($health)->whereIn('status', ['warning'])->count() > 0 || ($health['failed_jobs']['count'] ?? 0) > 0;
@endphp
@if($anyError)
<div class="banner banner--danger">
    <i data-lucide="alert-triangle"></i>
    <div><strong>{{ __('public.adm_cc_idx_banner_error_msg') }}</strong> {{ __('public.adm_cc_idx_banner_error_detail') }}</div>
    <div class="banner__spacer"></div>
    <a href="{{ route('portals.admin.cc.health') }}" class="btn btn-danger btn-sm">{{ __('public.adm_cc_idx_banner_error_btn') }}</a>
</div>
@elseif($anyWarn)
<div class="banner banner--warning">
    <i data-lucide="alert-circle"></i>
    <div><strong>{{ __('public.adm_cc_idx_banner_warn_label') }}</strong> {{ __('public.adm_cc_idx_banner_warn_detail') }}</div>
    <div class="banner__spacer"></div>
    <a href="{{ route('portals.admin.cc.health') }}" class="btn btn-secondary btn-sm">{{ __('public.adm_cc_idx_banner_warn_btn') }}</a>
</div>
@endif

{{-- God Mode — Platform Management --}}
<div class="mb-6">
    <div class="section-head section-head--danger">
        <i data-lucide="zap"></i>
        <h2>{{ __('public.adm_cc_idx_god_mode_heading') }}</h2>
    </div>
    <div class="card-grid">
        @php $godCards = [
            [__('public.adm_cc_idx_god_users'),        'users',        '/portals/admin/users',         __('public.adm_cc_idx_god_users_desc')],
            [__('public.adm_cc_idx_god_facilities'),   'building',     '/portals/admin/facilities',     __('public.adm_cc_idx_god_facilities_desc')],
            [__('public.adm_cc_idx_god_patients'),     'heart-pulse',  '/portals/admin/patients',       __('public.adm_cc_idx_god_patients_desc')],
            [__('public.adm_cc_idx_god_staff'),        'user-check',   '/portals/admin/staff',          __('public.adm_cc_idx_god_staff_desc')],
            [__('public.adm_cc_idx_god_financial'),    'banknote',     '/portals/admin/financial',      __('public.adm_cc_idx_god_financial_desc')],
            [__('public.adm_cc_idx_god_appointments'), 'calendar',     '/portals/admin/appointments',   __('public.adm_cc_idx_god_appointments_desc')],
            [__('public.adm_cc_idx_god_support'),      'headphones',   '/portals/admin/support',        __('public.adm_cc_idx_god_support_desc')],
            [__('public.adm_cc_idx_god_cdss'),         'activity',     '/portals/admin/cdss',           __('public.adm_cc_idx_god_cdss_desc')],
            [__('public.adm_cc_idx_god_roles'),        'shield',       '/portals/admin/roles',          __('public.adm_cc_idx_god_roles_desc')],
            [__('public.adm_cc_idx_god_orgs'),         'landmark',     '/portals/admin/organizations',  __('public.adm_cc_idx_god_orgs_desc')],
        ]; @endphp
        @foreach($godCards as [$title, $icon, $url, $desc])
        <a href="{{ $url }}" class="nav-card nav-card--danger">
            <div class="nav-card__head">
                <i data-lucide="{{ $icon }}"></i>
                <span class="nav-card__title">{{ $title }}</span>
            </div>
            <p class="nav-card__desc">{{ $desc }}</p>
        </a>
        @endforeach
    </div>
</div>

{{-- Quick nav cards --}}
<div class="card-grid mb-6">
    @php $cards = [
        [__('public.adm_cc_idx_nav_settings'),    'sliders-horizontal', route('portals.admin.cc.settings'),      __('public.adm_cc_idx_nav_settings_desc')],
        [__('public.adm_cc_idx_nav_flags'),       'toggle-right',       route('portals.admin.cc.feature_flags'), __('public.adm_cc_idx_nav_flags_desc')],
        [__('public.adm_cc_idx_nav_modules'),     'puzzle',             route('portals.admin.cc.modules'),       __('public.adm_cc_idx_nav_modules_desc')],
        [__('public.adm_cc_idx_nav_maintenance'), 'wrench',             route('portals.admin.cc.maintenance'),   __('public.adm_cc_idx_nav_maintenance_desc')],
        [__('public.adm_cc_idx_nav_health'),      'activity',           route('portals.admin.cc.health'),        __('public.adm_cc_idx_nav_health_desc')],
        [__('public.adm_cc_idx_nav_audit'),       'scroll-text',        route('portals.admin.cc.audit'),         __('public.adm_cc_idx_nav_audit_desc')],
    ]; @endphp
    @foreach($cards as [$title, $icon, $url, $desc])
    <a href="{{ $url }}" class="nav-card">
        <div class="nav-card__head">
            <i data-lucide="{{ $icon }}"></i>
            <span class="nav-card__title">{{ $title }}</span>
        </div>
        <p class="nav-card__desc">{{ $desc }}</p>
    </a>
    @endforeach
</div>

{{-- Recent Admin Actions --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="scroll-text"></i> {{ __('public.adm_cc_idx_recent_heading') }}</h3>
        <a href="{{ route('portals.admin.cc.audit') }}" class="btn btn-ghost btn-sm">{{ __('public.adm_cc_idx_recent_view_all') }}</a>
    </div>
    <div class="panel-body panel-body--flush">
        @if($actions->count() === 0)
            <div class="td-muted empty-cell">{{ __('public.adm_cc_idx_recent_empty') }}</div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('public.adm_cc_idx_col_action') }}</th><th>{{ __('public.adm_cc_idx_col_resource') }}</th><th>{{ __('public.adm_cc_idx_col_actor') }}</th><th>{{ __('public.adm_cc_idx_col_when') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($actions as $a)
                    <tr>
                        <td data-label="Action"><span class="code-token">{{ $a->action }}</span></td>
                        <td data-label="Resource"><span class="badge badge-neutral badge-sm">{{ $a->resource_type ?? '—' }}</span></td>
                        <td data-label="Actor">{{ $a->actor_id }}</td>
                        <td data-label="When" class="td-muted">{{ \Carbon\Carbon::parse($a->occurred_at)->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
