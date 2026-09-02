@extends('layouts.portal')

@section('title', __('public.pat_sub_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.pat_sub_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.pat_sub_title') }}</h1>
        <p class="page-subtitle">{{ __('public.pat_sub_subtitle') }}</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('info'))
<div class="alert alert-info mb-4"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>
@endif
@if(session('error'))
<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>
@endif

@php $premium = $plans->first(fn ($p) => ! $p->isFree()); @endphp

{{-- Current plan --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="badge-check"></i> {{ __('public.pat_sub_current_plan') }}</h3>
        @if($active)
            <span class="badge badge-{{ $active->statusColor() }}">@enum($active->status)</span>
        @endif
    </div>
    <div class="panel-body">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
            <div>
                <div style="font-size:1.5rem;font-weight:700;">{{ $currentPlan?->name ?? __('public.pat_sub_free_label') }}</div>
                @if($active && $active->current_period_end)
                    <p class="page-subtitle" style="margin:.25rem 0 0;">
                        @if(! $active->auto_renew)
                            {{ __('public.pat_sub_autorenew_off') }} {{ $active->current_period_end->isoFormat('LL') }}
                        @else
                            {{ __('public.pat_sub_renews') }} {{ $active->current_period_end->isoFormat('LL') }}
                        @endif
                    </p>
                @endif
            </div>

            @if($premium && (! $currentPlan || $currentPlan->isFree()))
                <form method="POST" action="{{ route('portals.patient.subscription.subscribe') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $premium->id }}">
                    <input type="hidden" name="interval" value="monthly">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="arrow-up-circle"></i> {{ __('public.pat_sub_upgrade') }}
                    </button>
                </form>
            @elseif($active && $active->auto_renew && ! $currentPlan?->isFree())
                <form method="POST" action="{{ route('portals.patient.subscription.cancel') }}"
                      onsubmit="return confirm('{{ __('public.pat_sub_cancel_confirm') }}');">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-danger">
                        <i data-lucide="x-circle"></i> {{ __('public.pat_sub_cancel') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- Value banner (shown on Free) — loss-aversion framing toward Premium --}}
@if($premium && (! $currentPlan || $currentPlan->isFree()))
<div class="panel mb-4" style="border-left:4px solid #0F4C81;">
    <div class="panel-body" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between;">
        <div style="max-width:580px;">
            <h3 style="margin:0 0 .35rem;font-size:1.15rem;font-weight:800;color:#0F4C81;display:flex;align-items:center;gap:.5rem;">
                <i data-lucide="sparkles"></i> {{ __('public.pat_sub_why_title') }}
            </h3>
            <p class="page-subtitle" style="margin:0;">{{ __('public.pat_sub_why_body') }}</p>
        </div>
        <form method="POST" action="{{ route('portals.patient.subscription.subscribe') }}">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $premium->id }}">
            <input type="hidden" name="interval" value="annual">
            <button type="submit" class="btn btn-primary"><i data-lucide="arrow-up-circle"></i> {{ __('public.pat_sub_upgrade') }}</button>
        </form>
    </div>
</div>
@endif

{{-- Available plans --}}
@foreach($plans as $plan)
    @php $isCurrent = $currentPlan && $currentPlan->id === $plan->id; @endphp
    <div class="panel mb-4">
        <div class="panel-header">
            <h3 class="panel-title">
                <i data-lucide="{{ $plan->isFree() ? 'circle' : 'sparkles' }}"></i> {{ $plan->name }}
            </h3>
            @if($isCurrent)
                <span class="badge badge-info">{{ __('public.pat_sub_current') }}</span>
            @elseif(! $plan->isFree())
                <span class="badge badge-primary">{{ __('public.pat_sub_most_popular') }}</span>
            @endif
        </div>
        <div class="panel-body">
            <p style="font-size:1.25rem;margin:0 0 .5rem;">
                @if($plan->isFree())
                    <strong>{{ __('public.pat_sub_free_label') }}</strong>
                @else
                    <strong>{{ $plan->priceFormatted() }}</strong><span class="page-subtitle">{{ __('public.pat_sub_monthly') }}</span>
                    @if($plan->annualPriceFormatted())
                        <span class="page-subtitle"> · {{ $plan->annualPriceFormatted() }}{{ __('public.pat_sub_annual') }}</span>
                    @endif
                @endif
            </p>
            @php
                $savePct = null; $monthsFree = null;
                if (! $plan->isFree() && $plan->annual_price_kobo && $plan->price_kobo) {
                    $yearly = $plan->price_kobo * 12;
                    if ($yearly > $plan->annual_price_kobo) {
                        $savePct    = (int) round((($yearly - $plan->annual_price_kobo) / $yearly) * 100);
                        $monthsFree = (int) round(($yearly - $plan->annual_price_kobo) / $plan->price_kobo);
                    }
                }
            @endphp
            @if($savePct)
                <div style="margin:0 0 .9rem;">
                    <span class="badge badge-success">
                        <i data-lucide="piggy-bank" style="width:.85rem;height:.85rem;"></i>
                        {{ __('public.pat_sub_save_annual', ['pct' => $savePct]) }}@if($monthsFree) · {{ __('public.pat_sub_months_free', ['n' => $monthsFree]) }}@endif
                    </span>
                </div>
            @endif

            @if($plan->description)
                <p class="page-subtitle" style="margin:0 0 .75rem;">{{ $plan->description }}</p>
            @endif

            <ul style="list-style:none;padding:0;margin:0 0 1rem;display:grid;gap:.4rem;">
                @foreach($plan->planFeatures as $feature)
                    <li style="display:flex;align-items:center;gap:.5rem;">
                        <i data-lucide="check" style="width:1rem;height:1rem;color:#0F4C81;"></i> {{ $feature->feature_label }}
                    </li>
                @endforeach
            </ul>

            @unless($isCurrent)
                <form method="POST" action="{{ route('portals.patient.subscription.subscribe') }}"
                      style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    @if($plan->isFree())
                        <input type="hidden" name="interval" value="monthly">
                        <button type="submit" class="btn btn-secondary">{{ __('public.pat_sub_choose') }}</button>
                    @else
                        <label class="sr-only" for="int-{{ $plan->id }}">{{ __('public.pat_sub_choose_cadence') }}</label>
                        <select id="int-{{ $plan->id }}" name="interval" class="form-control" style="max-width:170px;display:inline-block;width:auto;">
                            <option value="monthly">{{ __('public.pat_sub_monthly_opt') }}</option>
                            @if($plan->annual_price_kobo)
                                <option value="annual">{{ __('public.pat_sub_annual_opt') }}</option>
                            @endif
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="arrow-up-circle"></i> {{ __('public.pat_sub_upgrade') }}
                        </button>
                    @endif
                </form>
            @endunless
        </div>
    </div>
@endforeach

{{-- Invoices --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="receipt"></i> {{ __('public.pat_sub_invoices') }}</h3>
    </div>
    <div class="panel-body">
        @if($invoices->isEmpty())
            <p class="page-subtitle" style="margin:0;">{{ __('public.pat_sub_no_invoices') }}</p>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('public.pat_sub_invoice_number') }}</th>
                        <th>{{ __('public.pat_sub_invoice_date') }}</th>
                        <th>{{ __('public.pat_sub_invoice_amount') }}</th>
                        <th>{{ __('public.pat_sub_invoice_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->invoice_date?->isoFormat('LL') }}</td>
                            <td>{{ $invoice->currency }} {{ number_format($invoice->total_kobo / 100, 0) }}</td>
                            <td><span class="badge">@enum($invoice->status)</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
