@extends('layouts.portal')

@section('title', __('public.portal.insurance_page_title', [], app()->getLocale()) ?: 'Health Insurance — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.insurance_breadcrumb', [], app()->getLocale()) ?: 'Health Insurance')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success mb-6">
    <i data-lucide="check-circle-2"></i>
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('warning'))
<div class="alert alert-warning mb-6">
    <i data-lucide="alert-triangle"></i>
    <span>{{ session('warning') }}</span>
</div>
@endif

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.insurance_page_heading', [], $l) ?: 'Health Insurance' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.insurance_page_subtitle', [], $l) ?: 'Browse and purchase health insurance plans from top providers.' }}</p>
    </div>
</div>

{{-- ── My Policies ──────────────────────────────────────────────────────────── --}}
@if($myPolicies->isNotEmpty())
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="shield-check"></i> {{ __('public.portal.insurance_my_policies', [], $l) ?: 'My Active Policies' }}</h2>
        <span class="badge badge-primary">{{ $myPolicies->count() }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.portal.insurance_my_policies', [], $l) ?: 'My insurance policies' }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.insurance_col_provider', [], $l) ?: 'Provider' }}</th>
                    <th>{{ __('public.portal.insurance_col_plan', [], $l) ?: 'Plan' }}</th>
                    <th>{{ __('public.portal.insurance_col_policy_no', [], $l) ?: 'Policy Number' }}</th>
                    <th>{{ __('public.portal.insurance_col_valid_until', [], $l) ?: 'Valid Until' }}</th>
                    <th>{{ __('public.portal.insurance_col_status', [], $l) ?: 'Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($myPolicies as $policy)
                <tr>
                    <td data-label="{{ __('public.portal.insurance_col_provider', [], $l) ?: 'Provider' }}">
                        <span class="td-strong">{{ $policy->plan?->provider?->name ?? '—' }}</span>
                    </td>
                    <td data-label="{{ __('public.portal.insurance_col_plan', [], $l) ?: 'Plan' }}">{{ $policy->plan?->name ?? '—' }}</td>
                    <td data-label="{{ __('public.portal.insurance_col_policy_no', [], $l) ?: 'Policy Number' }}">
                        <span class="td-mono">{{ $policy->policy_number }}</span>
                    </td>
                    <td data-label="{{ __('public.portal.insurance_col_valid_until', [], $l) ?: 'Valid Until' }}">
                        {{ $policy->expiry_date ? $policy->expiry_date->format('d M Y') : '—' }}
                    </td>
                    <td data-label="{{ __('public.portal.insurance_col_status', [], $l) ?: 'Status' }}">
                        @php $s = $policy->status; @endphp
                        <span class="badge {{ $s === 'active' ? 'badge-success' : ($s === 'pending' ? 'badge-warning' : 'badge-neutral') }}">
                            @enum($s)
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── Marketplace ───────────────────────────────────────────────────────────── --}}
<div class="page-head">
    <h2>{{ __('public.portal.insurance_available_plans', [], $l) ?: 'Available Plans' }}</h2>
</div>

@if($providers->isEmpty())
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="shield-off"></i></div>
        <h3>{{ __('public.portal.insurance_no_plans_title', [], $l) ?: 'No Plans Available' }}</h3>
        <p>{{ __('public.portal.insurance_no_plans_desc', [], $l) ?: 'There are no purchasable insurance plans listed at this time. Please check back later.' }}</p>
    </div>
</div>
@else

@foreach($providers as $provider)
<div class="panel mb-6">
    {{-- Provider header --}}
    <div class="panel-header">
        <div class="entity-head">
            <div class="entity-head__icon">
                @if($provider->logo_url)
                    <img src="{{ $provider->logo_url }}" alt="{{ $provider->name }}" style="width:28px;height:28px;object-fit:contain;border-radius:6px;">
                @else
                    <i data-lucide="building-2"></i>
                @endif
            </div>
            <div>
                <div class="entity-head__title">{{ $provider->name }}</div>
                @if($provider->contact_phone)
                <div class="entity-head__sub">
                    <i data-lucide="phone"></i>
                    {{ $provider->contact_phone }}
                </div>
                @endif
            </div>
        </div>
        <span class="badge badge-neutral">{{ $provider->activePlans->count() }} {{ Str::plural('plan', $provider->activePlans->count()) }}</span>
    </div>

    {{-- Plan cards grid --}}
    <div class="panel-body">
    <div class="card-grid">
        @foreach($provider->activePlans as $plan)
        <div class="nav-card">
            <div class="nav-card__head">
                <div class="nav-card__title">{{ $plan->name }}</div>
                @if($plan->plan_type)
                <span class="badge badge-info">{{ strtoupper($plan->plan_type) }}</span>
                @endif
            </div>

            @if($plan->description)
            <p class="nav-card__desc">{{ Str::limit($plan->description, 100) }}</p>
            @endif

            {{-- Pricing --}}
            <div class="stat-grid">
                @if($plan->monthly_premium)
                <div class="stat-card stat-card--primary">
                    <div class="stat-card__label">{{ __('public.portal.insurance_lbl_monthly', [], $l) ?: 'Monthly' }}</div>
                    <div class="stat-card__value">XAF {{ number_format($plan->monthly_premium, 0) }}</div>
                </div>
                @endif
                @if($plan->annual_premium)
                <div class="stat-card">
                    <div class="stat-card__label">{{ __('public.portal.insurance_lbl_annual', [], $l) ?: 'Annual' }}</div>
                    <div class="stat-card__value">XAF {{ number_format($plan->annual_premium, 0) }}</div>
                </div>
                @endif
            </div>

            {{-- Quick benefits --}}
            <div class="gap-2" style="display:flex;flex-wrap:wrap;">
                @if($plan->cashless_available)
                <span class="badge badge-success"><i data-lucide="check"></i> {{ __('public.portal.insurance_badge_cashless', [], $l) ?: 'Cashless' }}</span>
                @endif
                @if($plan->copay_percentage)
                <span class="badge badge-neutral"><i data-lucide="percent"></i> {{ number_format($plan->copay_percentage, 0) }}{{ __('public.portal.insurance_badge_copay', [], $l) ?: '% co-pay' }}</span>
                @endif
            </div>

            <a href="{{ route('portals.patient.insurance.plan', $plan->id) }}" class="btn btn-primary btn-sm btn-block mt-3">
                <i data-lucide="info"></i> {{ __('public.portal.insurance_btn_view_enroll', [], $l) ?: 'View & Enroll' }}
            </a>
        </div>
        @endforeach
    </div>
    </div>
</div>
@endforeach

@endif

@endsection
