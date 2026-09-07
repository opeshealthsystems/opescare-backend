@extends('layouts.portal')
@section('title', __('plan_pricing.title'))
@include('portals.admin.control_center._sidebar')
@section('breadcrumb_home', __('public.portal.nav_dashboard'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('plan_pricing.title'))

@section('head')
<style>
  .pp-price{font-variant-numeric:tabular-nums;font-weight:600;white-space:nowrap}
  .pp-price--none{font-weight:400;font-style:italic;color:var(--p-text-muted)}
  .pp-default{display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;color:var(--p-text-muted);margin-top:.15rem}
  .pp-slug{font-size:.72rem;color:var(--p-text-muted)}
  .pp-field-row{display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem}
  .pp-field-row .form-label{flex:0 0 6.5rem;margin:0;font-size:.8rem}
  .pp-field-row .form-control{flex:1}
  .pp-unit{font-size:.75rem;color:var(--p-text-muted);flex:0 0 auto}
  .pp-locked{flex:1;font-size:.8rem;font-style:italic;color:var(--p-text-muted)}
  .pp-actions{display:flex;gap:.4rem;flex-wrap:wrap;justify-content:flex-end}
</style>
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('plan_pricing.title') }}</h1>
        <p class="page-subtitle">{{ __('plan_pricing.subtitle', ['count' => $plans->count()]) }}</p>
    </div>
    <a href="{{ route('portals.admin.cc.audit') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="scroll-text"></i> {{ __('plan_pricing.btn_audit') }}
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-6">
        <i data-lucide="triangle-alert"></i>
        <div>
            @foreach($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="alert alert-info mb-6">
    <i data-lucide="banknote"></i>
    <div>{{ __('plan_pricing.francs_note') }}</div>
</div>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($plans->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="tag"></i></div>
                <h3>{{ __('plan_pricing.empty_heading') }}</h3>
                <p>{{ __('plan_pricing.empty_body') }}</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('plan_pricing.col_plan') }}</th>
                    <th>{{ __('plan_pricing.col_audience') }}</th>
                    <th>{{ __('plan_pricing.col_daily') }}</th>
                    <th>{{ __('plan_pricing.col_monthly') }}</th>
                    <th>{{ __('plan_pricing.col_annual') }}</th>
                    <th>{{ __('plan_pricing.col_status') }}</th>
                    <th>{{ __('plan_pricing.col_last_change') }}</th>
                    <th class="row-actions">{{ __('plan_pricing.col_actions') }}</th>
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
                            <div class="td-strong">{{ $plan->name }}</div>
                            <div class="pp-slug">{{ $plan->slug }}</div>
                        </td>
                        <td data-label="{{ __('plan_pricing.col_audience') }}">
                            <span class="badge badge-neutral badge-sm">{{ $audience }}</span>
                        </td>

                        @foreach($intervals as $interval)
                            @php $minor = $plan->{$interval . '_price_minor'}; @endphp
                            <td data-label="{{ __('plan_pricing.col_' . $interval) }}">
                                @if($minor === null)
                                    <span class="pp-price pp-price--none">{{ __('plan_pricing.not_sold') }}</span>
                                @else
                                    <span class="pp-price">{{ $plan->priceFormatted($interval) }}</span>
                                @endif
                                @if($interval === $defaultOne && $minor !== null)
                                    <div class="pp-default">
                                        <i data-lucide="star"></i>
                                        {{ __('plan_pricing.default_interval', ['interval' => __('plan_pricing.interval_' . $interval)]) }}
                                    </div>
                                @endif
                            </td>
                        @endforeach

                        <td data-label="{{ __('plan_pricing.col_status') }}">
                            <span class="badge {{ $plan->is_active ? 'badge-success' : 'badge-neutral' }} badge-sm">
                                {{ $plan->is_active ? __('plan_pricing.badge_active') : __('plan_pricing.badge_inactive') }}
                            </span>
                        </td>

                        <td data-label="{{ __('plan_pricing.col_last_change') }}" class="td-muted">
                            @if($lastChange)
                                {{ __('plan_pricing.last_change_by', [
                                    'name' => $actorName,
                                    'when' => \Carbon\Carbon::parse($lastChange->occurred_at)->diffForHumans(),
                                ]) }}
                                <div class="code-muted">{{ \Carbon\Carbon::parse($lastChange->occurred_at)->format('M d, Y H:i') }}</div>
                            @else
                                {{ __('plan_pricing.last_change_never') }}
                            @endif
                        </td>

                        <td class="row-actions">
                            <div class="pp-actions">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="ppOpen('{{ $plan->id }}')">
                                    <i data-lucide="pencil"></i> {{ __('plan_pricing.btn_edit') }}
                                </button>
                                <form method="POST" action="{{ route('portals.admin.plans.toggle', $plan) }}" class="inline-form">
                                    @csrf
                                    <input type="hidden" name="active" value="{{ $plan->is_active ? '0' : '1' }}">
                                    <button type="submit" class="btn btn-ghost btn-sm">
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
</div>

{{-- One edit modal per plan, rendered server-side. The inputs a plan does not
     offer are not inputs at all: an interval a plan is not sold on cannot be
     given a price from here, and the controller rejects it even if the markup
     is bypassed. --}}
@foreach($plans as $plan)
    @php $failed = session('price_error_plan') === $plan->id; @endphp
    <div id="pp-modal-{{ $plan->id }}" class="modal-fixed {{ $failed ? 'open' : '' }}">
        <div class="modal-fixed__panel modal-fixed__panel--md">
            <div class="modal-fixed__head">
                <h3 class="modal-fixed__title">{{ $plan->name }}</h3>
                <button type="button" class="icon-btn" aria-label="{{ __('public.aria_close') }}" onclick="ppClose('{{ $plan->id }}')"><i data-lucide="x"></i></button>
            </div>
            <form method="POST" action="{{ route('portals.admin.plans.update', $plan) }}">
                @csrf
                @foreach($intervals as $interval)
                    @php
                        $offered = $plan->offersInterval($interval);
                        $stored  = $plan->{$interval . '_price_minor'};
                        $value   = $failed ? old('prices.' . $interval, $stored) : $stored;
                    @endphp
                    <div class="pp-field-row">
                        @if($offered)
                            <label class="form-label" for="pp-{{ $plan->id }}-{{ $interval }}">
                                {{ __('plan_pricing.col_' . $interval) }}
                            </label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                   id="pp-{{ $plan->id }}-{{ $interval }}"
                                   name="prices[{{ $interval }}]"
                                   class="form-control"
                                   value="{{ $value }}"
                                   autocomplete="off">
                            <span class="pp-unit">FCFA</span>
                        @else
                            {{-- No input at all, and no `for` pointing at a
                                 non-control: this interval is not something the
                                 pricing screen can fill in. --}}
                            <span class="form-label">{{ __('plan_pricing.col_' . $interval) }}</span>
                            <span class="pp-locked" title="{{ __('plan_pricing.not_sold_hint') }}">
                                {{ __('plan_pricing.not_sold') }}
                            </span>
                        @endif
                    </div>
                @endforeach

                <p class="form-hint">{{ __('plan_pricing.blank_means') }}</p>
                <p class="form-hint">{{ __('plan_pricing.not_sold_hint') }}</p>

                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="ppClose('{{ $plan->id }}')">{{ __('public.adm_cc_set_edit_modal_btn_cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('plan_pricing.btn_save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection

@section('scripts')
<script>
    function ppOpen(id)  { document.getElementById('pp-modal-' + id).classList.add('open'); }
    function ppClose(id) { document.getElementById('pp-modal-' + id).classList.remove('open'); }
    document.querySelectorAll('.modal-fixed[id^="pp-modal-"]').forEach(function (modal) {
        modal.addEventListener('click', function (e) { if (e.target === this) this.classList.remove('open'); });
    });
</script>
@endsection
