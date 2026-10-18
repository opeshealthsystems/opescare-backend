@extends('layouts.portal')
@section('title', __('plan_pricing.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.portal.nav_dashboard'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('plan_pricing.title'))

@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection

@section('content')
@php $gbTints = ['blue', 'amber', 'teal', 'rose', 'sky', 'green']; @endphp
<div class="od-page">
<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('plan_pricing.title') }}</h1>
        <p class="od-page__sub">{{ __('plan_pricing.subtitle', ['count' => $plans->count()]) }}</p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.cc.audit') }}" class="od-btn od-btn--secondary od-btn--sm">
            <i data-lucide="scroll-text"></i> {{ __('plan_pricing.btn_audit') }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="oda-gb-alert oda-gb-alert--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if($errors->any())
    <div class="oda-gb-alert oda-gb-alert--bad">
        <i data-lucide="triangle-alert"></i>
        <div>
            @foreach($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="oda-gb-alert oda-gb-alert--info">
    <i data-lucide="banknote"></i>
    <div>{{ __('plan_pricing.francs_note') }}</div>
</div>

<section class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('plan_pricing.title') }}</h2>
        <span class="od-card__count">{{ $plans->count() }}</span>
    </div>
    <div class="od-card__body od-card__body--flush">
        @if($plans->isEmpty())
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="tag"></i></div>
                <h3 class="od-empty__title">{{ __('plan_pricing.empty_heading') }}</h3>
                <p class="od-empty__text">{{ __('plan_pricing.empty_body') }}</p>
            </div>
        @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead><tr>
                    <th>{{ __('plan_pricing.col_plan') }}</th>
                    <th>{{ __('plan_pricing.col_audience') }}</th>
                    <th>{{ __('plan_pricing.col_daily') }}</th>
                    <th>{{ __('plan_pricing.col_monthly') }}</th>
                    <th>{{ __('plan_pricing.col_annual') }}</th>
                    <th>{{ __('plan_pricing.col_status') }}</th>
                    <th>{{ __('plan_pricing.col_last_change') }}</th>
                    <th>{{ __('plan_pricing.col_actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($plans as $plan)
                        @php
                            $audienceKey  = 'plan_pricing.audience_' . $plan->audience;
                            $audience     = __($audienceKey) === $audienceKey ? $plan->audience : __($audienceKey);
                            $lastChange   = $lastChanges[$plan->id] ?? null;
                            $actorName    = $lastChange
                                ? ($actors[$lastChange->actor_id] ?? $lastChange->actor_id ?? __('plan_pricing.actor_unknown'))
                                : null;
                            $defaultOne   = $plan->defaultInterval();
                        @endphp
                    <tr>
                        <td data-label="{{ __('plan_pricing.col_plan') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $gbTints[crc32((string) $plan->slug) % 6] }}"><i data-lucide="tag"></i></span>
                                <div>
                                    <div class="od-table__name">{{ $plan->name }}</div>
                                    <div class="od-table__sub oda-mono">{{ $plan->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('plan_pricing.col_audience') }}">
                            <span class="od-pill od-pill--neutral">{{ $audience }}</span>
                        </td>

                        @foreach($intervals as $interval)
                            @php $minor = $plan->{$interval . '_price_minor'}; @endphp
                            <td data-label="{{ __('plan_pricing.col_' . $interval) }}">
                                @if($minor === null)
                                    <span class="oda-gb-price oda-gb-price--none">{{ __('plan_pricing.not_sold') }}</span>
                                @else
                                    <span class="oda-gb-price">{{ $plan->priceFormatted($interval) }}</span>
                                @endif
                                @if($interval === $defaultOne && $minor !== null)
                                    <div class="od-table__sub oda-gb-default">
                                        <i data-lucide="star"></i>
                                        {{ __('plan_pricing.default_interval', ['interval' => __('plan_pricing.interval_' . $interval)]) }}
                                    </div>
                                @endif
                            </td>
                        @endforeach

                        <td data-label="{{ __('plan_pricing.col_status') }}">
                            <span class="od-pill od-pill--{{ $plan->is_active ? 'ready' : 'neutral' }}">
                                {{ $plan->is_active ? __('plan_pricing.badge_active') : __('plan_pricing.badge_inactive') }}
                            </span>
                        </td>

                        <td data-label="{{ __('plan_pricing.col_last_change') }}">
                            @if($lastChange)
                                <div class="od-table__sub">{{ __('plan_pricing.last_change_by', [
                                    'name' => $actorName,
                                    'when' => \Carbon\Carbon::parse($lastChange->occurred_at)->diffForHumans(),
                                ]) }}</div>
                                <div class="od-table__sub oda-mono">{{ \Carbon\Carbon::parse($lastChange->occurred_at)->translatedFormat('M d, Y H:i') }}</div>
                            @else
                                <span class="od-table__sub">{{ __('plan_pricing.last_change_never') }}</span>
                            @endif
                        </td>

                        <td data-label="{{ __('plan_pricing.col_actions') }}">
                            <div class="od-table__actions">
                                <button type="button" class="od-btn od-btn--row" data-show="pp-modal-{{ $plan->id }}" data-display="flex">
                                    <i data-lucide="pencil"></i> {{ __('plan_pricing.btn_edit') }}
                                </button>
                                <form method="POST" action="{{ route('portals.admin.plans.toggle', $plan) }}" class="oda-inline-form">
                                    @csrf
                                    <input type="hidden" name="active" value="{{ $plan->is_active ? '0' : '1' }}">
                                    <button type="submit" class="od-btn od-btn--ghost od-btn--sm">
                                        <i data-lucide="{{ $plan->is_active ? 'circle-slash' : 'circle-check' }}"></i>
                                        {{ $plan->is_active ? __('plan_pricing.btn_deactivate') : __('plan_pricing.btn_activate') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>
</div>

{{-- One edit modal per plan, rendered server-side. The inputs a plan does not
     offer are not inputs at all: an interval a plan is not sold on cannot be
     given a price from here, and the controller rejects it even if the markup
     is bypassed. --}}
@foreach($plans as $plan)
    @php $failed = session('price_error_plan') === $plan->id; @endphp
    <div id="pp-modal-{{ $plan->id }}" class="modal-fixed {{ $failed ? 'open' : '' }}">
        <div class="od-card oda-modal">
            <div class="od-card__head">
                <h2 class="od-card__title">{{ $plan->name }}</h2>
                <button type="button" class="od-btn od-btn--icon od-btn--ghost oda-modal__close" aria-label="{{ __('public.aria_close') }}" data-hide="pp-modal-{{ $plan->id }}"><i data-lucide="x"></i></button>
            </div>
            <div class="od-card__body">
            <form method="POST" action="{{ route('portals.admin.plans.update', $plan) }}" class="od-stack">
                @csrf
                @foreach($intervals as $interval)
                    @php
                        $offered = $plan->offersInterval($interval);
                        $stored  = $plan->{$interval . '_price_minor'};
                        $value   = $failed ? old('prices.' . $interval, $stored) : $stored;
                    @endphp
                    <div class="oda-gb-field-row">
                        @if($offered)
                            <label class="oda-label__text oda-gb-field-row__label" for="pp-{{ $plan->id }}-{{ $interval }}">
                                {{ __('plan_pricing.col_' . $interval) }}
                            </label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                   id="pp-{{ $plan->id }}-{{ $interval }}"
                                   name="prices[{{ $interval }}]"
                                   class="od-input"
                                   value="{{ $value }}"
                                   autocomplete="off">
                            <span class="od-table__sub">FCFA</span>
                        @else
                            {{-- No input at all, and no `for` pointing at a
                                 non-control: this interval is not something the
                                 pricing screen can fill in. --}}
                            <span class="oda-label__text oda-gb-field-row__label">{{ __('plan_pricing.col_' . $interval) }}</span>
                            <span class="oda-gb-price--none oda-gb-locked" title="{{ __('plan_pricing.not_sold_hint') }}">
                                {{ __('plan_pricing.not_sold') }}
                            </span>
                        @endif
                    </div>
                @endforeach

                <p class="oda-help">{{ __('plan_pricing.blank_means') }}</p>
                <p class="oda-help">{{ __('plan_pricing.not_sold_hint') }}</p>

                <div class="oda-form__actions">
                    <button type="button" class="od-btn od-btn--ghost od-btn--sm" data-hide="pp-modal-{{ $plan->id }}">{{ __('public.adm_cc_set_edit_modal_btn_cancel') }}</button>
                    <button type="submit" class="od-btn od-btn--primary od-btn--sm">{{ __('plan_pricing.btn_save') }}</button>
                </div>
            </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@section('scripts')
@include('portals.admin._modal')
@endsection
