@extends('layouts.portal')

@section('title', $plan->name . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.insurance_breadcrumb', [], app()->getLocale()) ?: 'Health Insurance')

@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@php $l = app()->getLocale(); @endphp

@section('content')

<div class="breadcrumb">
    <a href="{{ route('portals.patient.insurance') }}">
        <i data-lucide="arrow-left"></i> {{ __('public.portal.insurance_back_link', [], $l) ?: 'Back to Insurance Plans' }}
    </a>
</div>

<div class="grid-main-side">

    {{-- ── Left: Plan details ─────────────────────────────────────────── --}}
    <div>

        {{-- Header banner --}}
        <div class="health-id-card mb-6" style="background:#0F2744;">
            <div class="health-id-label">{{ $plan->provider?->name }}</div>
            <div class="health-id-value">{{ $plan->name }}</div>
            @if($plan->plan_type)
            <span class="badge badge-info">{{ strtoupper($plan->plan_type) }}</span>
            @endif
        </div>

        {{-- Pricing --}}
        <div class="panel mb-4">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="receipt"></i> {{ __('public.portal.plan_panel_pricing', [], $l) ?: 'Pricing' }}</h2>
            </div>
            <div class="panel-body">
            <div class="stat-grid">
                @if($plan->monthly_premium)
                <div class="stat-card stat-card--primary">
                    <div class="stat-card__label">{{ __('public.portal.plan_lbl_monthly_premium', [], $l) ?: 'Monthly Premium' }}</div>
                    <div class="stat-card__value">XAF {{ number_format($plan->monthly_premium, 0) }}</div>
                </div>
                @endif
                @if($plan->annual_premium)
                <div class="stat-card">
                    <div class="stat-card__label">{{ __('public.portal.plan_lbl_annual_premium', [], $l) ?: 'Annual Premium' }}</div>
                    <div class="stat-card__value">XAF {{ number_format($plan->annual_premium, 0) }}</div>
                </div>
                @endif
                @if($plan->deductible)
                <div class="stat-card">
                    <div class="stat-card__label">{{ __('public.portal.plan_lbl_deductible', [], $l) ?: 'Deductible' }}</div>
                    <div class="stat-card__value">XAF {{ number_format($plan->deductible, 0) }}</div>
                </div>
                @endif
                @if($plan->copay_percentage)
                <div class="stat-card">
                    <div class="stat-card__label">{{ __('public.portal.plan_lbl_copay', [], $l) ?: 'Co-pay' }}</div>
                    <div class="stat-card__value">{{ number_format($plan->copay_percentage, 0) }}%</div>
                </div>
                @endif
            </div>
            </div>
        </div>

        {{-- Benefits --}}
        <div class="panel mb-4">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="shield-check"></i> {{ __('public.portal.plan_panel_benefits', [], $l) ?: 'Benefits' }}</h2>
            </div>
            <div class="panel-body">
                <div class="list-row">
                    <span class="list-row__main">
                        <i data-lucide="{{ $plan->cashless_available ? 'check-circle-2' : 'x-circle' }}"></i>
                        {{ __('public.portal.plan_lbl_cashless', [], $l) ?: 'Cashless Treatment' }}
                    </span>
                    <span class="badge {{ $plan->cashless_available ? 'badge-success' : 'badge-neutral' }}">{{ $plan->cashless_available ? (__('public.portal.plan_lbl_yes', [], $l) ?: 'Yes') : (__('public.portal.plan_lbl_no', [], $l) ?: 'No') }}</span>
                </div>
                <div class="list-row">
                    <span class="list-row__main">
                        <i data-lucide="{{ $plan->requires_preauthorization ? 'check-circle-2' : 'x-circle' }}"></i>
                        {{ __('public.portal.plan_lbl_preauth', [], $l) ?: 'Requires Pre-authorization' }}
                    </span>
                    <span class="badge {{ $plan->requires_preauthorization ? 'badge-warning' : 'badge-neutral' }}">{{ $plan->requires_preauthorization ? (__('public.portal.plan_lbl_yes', [], $l) ?: 'Yes') : (__('public.portal.plan_lbl_no', [], $l) ?: 'No') }}</span>
                </div>
            </div>
        </div>

        {{-- Description --}}
        @if($plan->description)
        <div class="panel mb-4">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="file-text"></i> {{ __('public.portal.plan_panel_about', [], $l) ?: 'About this Plan' }}</h2>
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ $plan->description }}</p>
            </div>
        </div>
        @endif

        {{-- Provider contact --}}
        @if($plan->provider && ($plan->provider->contact_phone || $plan->provider->contact_email))
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="phone"></i> {{ __('public.portal.plan_panel_contact', [], $l) ?: 'Provider Contact' }}</h2>
            </div>
            <div class="panel-body">
                @if($plan->provider->contact_phone)
                <div class="cell-with-icon mb-3">
                    <i data-lucide="phone"></i>
                    {{ $plan->provider->contact_phone }}
                </div>
                @endif
                @if($plan->provider->contact_email)
                <div class="cell-with-icon">
                    <i data-lucide="mail"></i>
                    <a href="mailto:{{ $plan->provider->contact_email }}" class="link-action">{{ $plan->provider->contact_email }}</a>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- ── Right: Enroll form ──────────────────────────────────────────── --}}
    <div>
        <div class="panel">
            @if($alreadyEnrolled)
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="shield-check"></i></div>
                <h3>{{ __('public.portal.plan_already_enrolled_h3', [], $l) ?: 'Already Enrolled' }}</h3>
                <p>{{ __('public.portal.plan_already_enrolled_p', [], $l) ?: 'You already have an active policy for this plan.' }}</p>
                <a href="{{ route('portals.patient.insurance') }}" class="btn btn-secondary btn-sm">{{ __('public.portal.plan_btn_view_policies', [], $l) ?: 'View My Policies' }}</a>
            </div>
            @else
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="shield-plus"></i> {{ __('public.portal.plan_panel_enroll', [], $l) ?: 'Enroll Now' }}</h2>
            </div>

            <div class="panel-body">
            {{-- Summary --}}
            <div class="kv-table mb-4">
                <div class="kv-strong">{{ __('public.portal.plan_kv_plan', [], $l) ?: 'Plan' }}</div>
                <div>{{ $plan->name }}</div>
                <div class="kv-strong">{{ __('public.portal.plan_kv_provider', [], $l) ?: 'Provider' }}</div>
                <div>{{ $plan->provider?->name ?? '—' }}</div>
                @if($plan->monthly_premium)
                <div class="kv-strong">{{ __('public.portal.plan_kv_monthly', [], $l) ?: 'Monthly' }}</div>
                <div>XAF {{ number_format($plan->monthly_premium, 0) }}</div>
                @endif
            </div>

            <form method="POST" action="{{ route('portals.patient.insurance.purchase', $plan->id) }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">{{ __('public.portal.plan_field_payment_method', [], $l) ?: 'Payment Method' }}</label>

                    @foreach([
                        ['mobile_money', __('public.portal.plan_pay_mobile_money', [], $l) ?: 'Mobile Money', 'smartphone'],
                        ['card', __('public.portal.plan_pay_card', [], $l) ?: 'Debit / Credit Card', 'credit-card'],
                        ['bank_transfer', __('public.portal.plan_pay_bank_transfer', [], $l) ?: 'Bank Transfer', 'landmark'],
                    ] as [$val, $label, $icon])
                    <label class="form-check">
                        <input type="radio" name="payment_method" value="{{ $val }}" {{ $val === 'mobile_money' ? 'checked' : '' }}>
                        <i data-lucide="{{ $icon }}"></i>
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i data-lucide="shield-plus"></i> {{ __('public.portal.plan_btn_confirm_enroll', [], $l) ?: 'Confirm Enrollment' }}
                </button>

                <p class="text-sm text-muted mt-3" style="text-align:center;">
                    {{ __('public.portal.plan_activation_notice', [], $l) ?: 'Your policy will be activated within 1–2 business days after verification.' }}
                </p>
            </form>
            </div>
            @endif
        </div>
    </div>

</div>

@endsection
