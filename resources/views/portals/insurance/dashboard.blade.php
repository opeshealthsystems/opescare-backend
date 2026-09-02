@extends('layouts.portal')

@section('title', __('public.insurance_portal.page_title', [], app()->getLocale()) ?: 'Insurance Portal')

@section('sidebar_role_badge')
<div class="sidebar-role-badge sidebar-role-badge--primary">
    <i data-lucide="shield-check"></i>
    {{ __('public.insurance_portal.role_badge', [], app()->getLocale()) ?: 'Insurance' }}
</div>
@endsection
@section('sidebar_user_role', __('public.insurance_portal.role_label', [], app()->getLocale()) ?: 'Insurance Admin')

@section('sidebar_nav')
<div class="sidebar-nav-section">
    <div class="sidebar-nav-label">{{ __('public.insurance_portal.nav_section', [], app()->getLocale()) ?: 'Insurance' }}</div>
    <a href="{{ route('portals.insurance.dashboard') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i><span>{{ __('public.insurance_portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard' }}</span>
    </a>
    <a href="{{ route('portals.insurance.providers') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.providers') ? 'active' : '' }}">
        <i data-lucide="building-2"></i><span>{{ __('public.insurance_portal.nav_providers', [], app()->getLocale()) ?: 'Providers & Plans' }}</span>
    </a>
    <a href="{{ route('portals.insurance.policies') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.policies') ? 'active' : '' }}">
        <i data-lucide="shield-check"></i><span>{{ __('public.insurance_portal.nav_policies', [], app()->getLocale()) ?: 'Patient Policies' }}</span>
    </a>
    <a href="{{ route('portals.insurance.preauths') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.preauths') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i><span>{{ __('public.insurance_portal.nav_preauths', [], app()->getLocale()) ?: 'Preauthorization' }}</span>
    </a>
    <a href="{{ route('portals.insurance.claims') }}" class="sidebar-link {{ request()->routeIs('portals.insurance.claims') ? 'active' : '' }}">
        <i data-lucide="file-text"></i><span>{{ __('public.insurance_portal.nav_claims', [], app()->getLocale()) ?: 'Claims' }}</span>
    </a>
</div>
@endsection

@section('breadcrumb_home', __('public.insurance_portal.page_title', [], app()->getLocale()) ?: 'Insurance Portal')
@section('breadcrumb_home_url', route('portals.insurance.dashboard'))
@section('breadcrumb_section', __('public.insurance_portal.nav_dashboard', [], app()->getLocale()) ?: 'Dashboard')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.insurance_portal.page_title', [], app()->getLocale()) ?: 'Insurance Portal' }}</h1>
        <p class="page-subtitle">{{ __('public.insurance_portal.page_subtitle', [], app()->getLocale()) ?: 'Overview of providers, policies, authorizations and claims.' }}</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4">
        <i data-lucide="check-circle"></i><div>{{ session('success') }}</div>
    </div>
@endif

{{-- Stat cards --}}
<div class="stat-grid mb-6">
    <a href="{{ route('portals.insurance.providers') }}" class="stat-card stat-card--primary">
        <div class="stat-card__label">{{ __('public.insurance_portal.stat_providers', [], app()->getLocale()) ?: 'Providers' }}</div>
        <div class="stat-card__value">{{ $stats['providers'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.insurance.providers') }}" class="stat-card stat-card--primary">
        <div class="stat-card__label">{{ __('public.insurance_portal.stat_active_plans', [], app()->getLocale()) ?: 'Active Plans' }}</div>
        <div class="stat-card__value">{{ $stats['active_plans'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.insurance.policies') }}" class="stat-card stat-card--success">
        <div class="stat-card__label">{{ __('public.insurance_portal.stat_policies', [], app()->getLocale()) ?: 'Policies' }}</div>
        <div class="stat-card__value">{{ $stats['policies'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.insurance.preauths') }}" class="stat-card stat-card--warning">
        <div class="stat-card__label">{{ __('public.insurance_portal.stat_pending_preauth', [], app()->getLocale()) ?: 'Pending Preauth' }}</div>
        <div class="stat-card__value">{{ $stats['pending_auth'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.insurance.claims') }}" class="stat-card stat-card--danger">
        <div class="stat-card__label">{{ __('public.insurance_portal.stat_open_claims', [], app()->getLocale()) ?: 'Open Claims' }}</div>
        <div class="stat-card__value">{{ $stats['open_claims'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.insurance.claims') }}" class="stat-card stat-card--success">
        <div class="stat-card__label">{{ __('public.insurance_portal.stat_paid_claims', [], app()->getLocale()) ?: 'Paid Claims' }}</div>
        <div class="stat-card__value">{{ $stats['paid_claims'] ?? 0 }}</div>
    </a>
    <a href="{{ route('portals.insurance.claims') }}" class="stat-card stat-card--teal">
        <div class="stat-card__icon"><i data-lucide="banknote"></i></div>
        <div class="stat-card__label">{{ __('public.insurance_portal.stat_total_value', [], app()->getLocale()) ?: 'Total Claims Value' }}</div>
        <div class="stat-card__value">{{ number_format($stats['total_claim_value'] ?? 0) }} XAF</div>
    </a>
</div>

<div class="grid-2">

    {{-- Recent Claims --}}
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">{{ __('public.insurance_portal.panel_recent_claims', [], app()->getLocale()) ?: 'Recent Claims' }}</h2>
            <a href="{{ route('portals.insurance.claims') }}" class="btn btn-secondary btn-sm">{{ __('public.portal.view_all', [], app()->getLocale()) ?: 'View all' }}</a>
        </div>
        <div class="panel-body--flush">
            @forelse($recentClaims as $claim)
            <div class="list-row">
                <div class="list-row__main">
                    <div class="td-strong">{{ $claim->patient?->full_name ?? __('public.portal.unknown_patient', [], app()->getLocale()) ?: 'Unknown Patient' }}</div>
                    <div class="td-muted">{{ $claim->created_at?->format('d M Y') }}</div>
                </div>
                <span class="badge badge-{{ match($claim->status) { 'paid' => 'success', 'submitted' => 'info', 'rejected' => 'danger', 'cancelled' => 'neutral', default => 'warning' } }}">
                    @enum($claim->status)
                </span>
            </div>
            @empty
            <div class="empty-state"><p>{{ __('public.insurance_portal.no_claims', [], app()->getLocale()) ?: 'No claims yet.' }}</p></div>
            @endforelse
        </div>
    </div>

    {{-- Recent Preauths --}}
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">{{ __('public.insurance_portal.panel_recent_preauths', [], app()->getLocale()) ?: 'Recent Preauthorizations' }}</h2>
            <a href="{{ route('portals.insurance.preauths') }}" class="btn btn-secondary btn-sm">{{ __('public.portal.view_all', [], app()->getLocale()) ?: 'View all' }}</a>
        </div>
        <div class="panel-body--flush">
            @forelse($recentPreauths as $auth)
            <div class="list-row">
                <div class="list-row__main">
                    <div class="td-strong">{{ $auth->patient?->full_name ?? __('public.portal.unknown_patient', [], app()->getLocale()) ?: 'Unknown Patient' }}</div>
                    <div class="td-muted">{{ $auth->created_at?->format('d M Y') }}</div>
                </div>
                <span class="badge badge-{{ match($auth->status) { 'approved' => 'success', 'submitted' => 'info', 'rejected' => 'danger', 'cancelled' => 'neutral', default => 'warning' } }}">
                    @enum($auth->status)
                </span>
            </div>
            @empty
            <div class="empty-state"><p>{{ __('public.insurance_portal.no_preauths', [], app()->getLocale()) ?: 'No preauth requests yet.' }}</p></div>
            @endforelse
        </div>
    </div>

</div>

@endsection
