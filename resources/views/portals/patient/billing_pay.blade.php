@extends('layouts.portal')

@section('title', __('billing.pay_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('billing.pay_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('billing.pay_title') }}</h1>
        <p class="page-subtitle">{{ __('billing.pay_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.patient.billing') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('billing.title') }}
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="panel" style="max-width:560px;">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="receipt"></i> {{ $invoice->invoice_number }}</h3>
    </div>
    <div class="panel-body">
        <p style="font-size:1.5rem;font-weight:700;margin:0 0 .25rem;">XAF {{ number_format($amount, 0) }}</p>
        <p class="page-subtitle" style="margin:0 0 1.25rem;">{{ __('billing.pay_momo_hint') }}</p>

        <form method="POST" action="{{ route('portals.patient.billing.pay.store', $invoice->id) }}">
            @csrf
            <div style="margin-bottom:1rem;">
                <label class="form-label" for="phone">{{ __('billing.phone_label') }}</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="6XX XXX XXX"
                       value="{{ old('phone', $patient->phone) }}" inputmode="tel" required style="max-width:280px;">
                <p class="page-subtitle" style="margin:.4rem 0 0;font-size:.82rem;">{{ __('billing.phone_hint') }}</p>
            </div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="smartphone"></i> {{ __('billing.pay_submit', ['amount' => 'XAF ' . number_format($amount, 0)]) }}
                </button>
                <a href="{{ route('portals.patient.billing') }}" class="btn btn-ghost">{{ __('billing.cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection
