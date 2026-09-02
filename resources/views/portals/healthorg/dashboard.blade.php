@extends('layouts.portal')

@section('title', __('public.healthorg_portal.page_title', [], app()->getLocale()) ?: 'Health Organization Portal')

@section('sidebar_role_badge')
<div class="sidebar-role-badge">
    <i data-lucide="heart-handshake"></i>
    {{ __('public.healthorg_portal.role_badge', [], app()->getLocale()) ?: 'Health Org' }}
</div>
@endsection
@section('sidebar_user_role', __('public.healthorg_portal.role_label', [], app()->getLocale()) ?: 'Health Org Admin')

@section('sidebar_nav')
@include('portals.healthorg._sidebar')
@endsection

@section('breadcrumb_home', __('public.healthorg_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Health Org Portal')
@section('breadcrumb_home_url', route('portals.healthorg.dashboard'))
@section('breadcrumb_section', __('public.healthorg_portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.healthorg_portal.page_title', [], app()->getLocale()) ?: 'Health Organization Portal' }}</h1>
        <p class="page-subtitle">{{ __('public.healthorg_portal.page_subtitle', [], app()->getLocale()) ?: 'Programs, outreach coordination, and public health reporting.' }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4">
        <i data-lucide="check-circle"></i><div>{{ session('success') }}</div>
    </div>
@endif

{{-- Stat Cards --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__label">{{ __('public.healthorg_portal.stat_patients', [], app()->getLocale()) ?: 'Registered Patients' }}</div>
        <div class="stat-card__value">{{ number_format($stats['patients'] ?? 0) }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__label">{{ __('public.healthorg_portal.stat_facilities', [], app()->getLocale()) ?: 'Active Facilities' }}</div>
        <div class="stat-card__value">{{ $stats['facilities'] ?? 0 }}</div>
    </div>
    <a href="{{ route('portals.healthorg.programs') }}" class="stat-card stat-card--info">
        <div class="stat-card__label">{{ __('public.healthorg_portal.stat_programs', [], app()->getLocale()) ?: 'Active Programs' }}</div>
        <div class="stat-card__value">{{ $stats['programs'] ?? '—' }}</div>
    </a>
    <a href="{{ route('portals.healthorg.reports') }}" class="stat-card stat-card--warning">
        <div class="stat-card__label">{{ __('public.healthorg_portal.stat_draft_reports', [], app()->getLocale()) ?: 'Draft Reports' }}</div>
        <div class="stat-card__value">{{ $stats['reports_draft'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.healthorg.reports') }}" class="stat-card stat-card--success">
        <div class="stat-card__label">{{ __('public.healthorg_portal.stat_submitted_reports', [], app()->getLocale()) ?: 'Submitted Reports' }}</div>
        <div class="stat-card__value">{{ $stats['reports_sent'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.healthorg.outreach') }}" class="stat-card stat-card--info">
        <div class="stat-card__label">{{ __('public.healthorg_portal.stat_outreach', [], app()->getLocale()) ?: 'Active Outreach' }}</div>
        <div class="stat-card__value">{{ $stats['outreach'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.healthorg.signals') }}" class="stat-card stat-card--danger">
        <div class="stat-card__label">{{ __('public.healthorg_portal.stat_signals_open', [], app()->getLocale()) ?: 'Open Signals' }}</div>
        <div class="stat-card__value">{{ $stats['signals_open'] ?? 0 }}</div>
    </a>
</div>

{{-- Quick Actions --}}
<div class="panel mb-6">
    <div class="panel-header"><h2 class="panel-title">{{ __('public.healthorg_portal.panel_quick_actions', [], app()->getLocale()) ?: 'Quick Actions' }}</h2></div>
    <div class="panel-body">
        <div class="card-grid">
            <a href="{{ route('portals.healthorg.programs') }}" class="nav-card">
                <div class="nav-card__head">
                    <i data-lucide="folder-open"></i>
                    <span class="nav-card__title">{{ __('public.healthorg_portal.action_programs', [], app()->getLocale()) ?: 'View Programs' }}</span>
                </div>
            </a>
            <a href="{{ route('portals.healthorg.outreach') }}" class="nav-card">
                <div class="nav-card__head">
                    <i data-lucide="map-pin"></i>
                    <span class="nav-card__title">{{ __('public.healthorg_portal.action_outreach', [], app()->getLocale()) ?: 'Outreach Sites' }}</span>
                </div>
            </a>
            <a href="{{ route('portals.healthorg.reports') }}" class="nav-card">
                <div class="nav-card__head">
                    <i data-lucide="file-bar-chart-2"></i>
                    <span class="nav-card__title">{{ __('public.healthorg_portal.action_reports', [], app()->getLocale()) ?: 'Public Health Reports' }}</span>
                </div>
            </a>
            <a href="{{ route('portals.healthorg.signals') }}" class="nav-card nav-card--danger">
                <div class="nav-card__head">
                    <i data-lucide="activity"></i>
                    <span class="nav-card__title">{{ __('public.healthorg_portal.action_signals', [], app()->getLocale()) ?: 'Outbreak Signals' }}</span>
                </div>
            </a>
            <a href="{{ route('public.care-map') }}" target="_blank" class="nav-card">
                <div class="nav-card__head">
                    <i data-lucide="map"></i>
                    <span class="nav-card__title">{{ __('public.portal.nav_care_map', [], app()->getLocale()) ?: 'Care Map' }}</span>
                </div>
            </a>
            <a href="{{ route('portals.healthorg.signals') }}" class="nav-card nav-card--warning">
                <div class="nav-card__head">
                    <i data-lucide="radar"></i>
                    <span class="nav-card__title">{{ __('public.healthorg_portal.action_surveillance', [], app()->getLocale()) ?: 'Disease Surveillance' }}</span>
                </div>
            </a>
        </div>
    </div>
</div>

{{-- Info banner --}}
<div class="alert alert-info">
    <i data-lucide="info"></i>
    <div>
        {{ __('public.healthorg_portal.api_info', [], app()->getLocale()) ?: 'Advanced public health reporting, disease surveillance, and outbreak intelligence are available via the' }}
        <strong>{{ __('public.healthorg_portal.api_name', [], app()->getLocale()) ?: 'Public Health API' }}</strong>
        {{ __('public.healthorg_portal.api_at', [], app()->getLocale()) ?: 'at' }} <code class="mono">/api/v1/public-health</code>.
        {{ __('public.healthorg_portal.api_cta_prefix', [], app()->getLocale()) ?: 'Use the' }}
        <a href="{{ route('portals.developer.dashboard') }}">{{ __('public.healthorg_portal.api_cta_link', [], app()->getLocale()) ?: 'Developer Portal' }}</a>
        {{ __('public.healthorg_portal.api_cta_suffix', [], app()->getLocale()) ?: 'to obtain API credentials.' }}
    </div>
</div>

@endsection
