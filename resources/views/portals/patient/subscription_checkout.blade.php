@extends('layouts.portal')

@section('title', __('public.pat_sub_checkout_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_sub_checkout_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.pat_sub_checkout_title') }}</h1>
        <p class="page-subtitle">{{ __('public.pat_sub_checkout_subtitle') }}</p>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="panel mb-4" style="max-width:560px;">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="sparkles"></i> {{ $plan->name }}</h3>
        <span class="badge badge-primary">{{ $interval === 'annual' ? __('public.pat_sub_annual_opt') : __('public.pat_sub_monthly_opt') }}</span>
    </div>
    <div class="panel-body">
        <p style="font-size:1.5rem;font-weight:700;margin:0 0 .25rem;">
            {{ $currency }} {{ number_format($amount, 0) }}
            <span class="page-subtitle" style="font-size:.9rem;">
                / {{ $interval === 'annual' ? __('public.pat_sub_per_year') : __('public.pat_sub_per_month') }}
            </span>
        </p>
        <p class="page-subtitle" style="margin:0 0 1.25rem;">{{ __('public.pat_sub_checkout_momo_hint') }}</p>

        <form method="POST" action="{{ route('portals.patient.subscription.checkout.pay') }}">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
            <input type="hidden" name="interval" value="{{ $interval }}">

            <div style="margin-bottom:1rem;">
                <label class="form-label" for="phone">{{ __('public.pat_sub_checkout_phone_label') }}</label>
                <input type="tel" id="phone" name="phone" class="form-control"
                       placeholder="6XX XXX XXX" value="{{ old('phone', $patient->phone) }}"
                       inputmode="tel" required style="max-width:280px;">
                <p class="page-subtitle" style="margin:.4rem 0 0;font-size:.82rem;">
                    {{ __('public.pat_sub_checkout_phone_hint') }}
                </p>
            </div>

            <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="smartphone"></i> {{ __('public.pat_sub_checkout_pay_now', ['amount' => $currency . ' ' . number_format($amount, 0)]) }}
                </button>
                <a href="{{ route('portals.patient.subscription') }}" class="btn btn-ghost">
                    {{ __('public.pat_sub_checkout_cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
