@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_stock', [], app()->getLocale()))

@php
    $l = app()->getLocale();
    // Availability semantics on the segmented control — colors only ever mean
    // state: good = in stock, warn = low, danger = out, neutral = unknown.
    $segFor = fn (string $v) => match ($v) {
        'in_stock'     => 'fs-seg__opt--good',
        'low_stock'    => 'fs-seg__opt--warn',
        'out_of_stock' => 'fs-seg__opt--danger',
        default        => '',
    };
    $stockCount = $stocks ? (method_exists($stocks, 'total') ? $stocks->total() : $stocks->count()) : 0;
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading_stock', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle_stock', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    @feature('inventory_ops')
        @endfeature
</div>

@include('portals.pharmacy.stock_ops._nav')

@if($errors->any())
<div class="banner banner--danger">
    <i data-lucide="alert-octagon"></i>
    <div>
        <ul class="alert-list">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

{{-- Why a pharmacy may be invisible to patients, in the order worth fixing. --}}
@foreach($issues as $issue)
    @if($issue === 'unlinked')
    <div class="banner banner--danger">
        <i data-lucide="unlink"></i>
        <div>
            <strong>{{ __('public.pharmacy_portal.stock_warn_unlinked_title', [], $l) }}</strong><br>
            {{ __('public.pharmacy_portal.stock_warn_unlinked_body', [], $l) }}
        </div>
    </div>
    @elseif($issue === 'no_coordinates')
    <div class="banner banner--warning">
        <i data-lucide="map-pin-off"></i>
        <div>
            <strong>{{ __('public.pharmacy_portal.stock_warn_no_coords_title', [], $l) }}</strong><br>
            {{ __('public.pharmacy_portal.stock_warn_no_coords_body', [], $l) }}
        </div>
    </div>
    @elseif($issue === 'not_listed')
    <div class="banner banner--warning">
        <i data-lucide="eye-off"></i>
        <div>
            <strong>{{ __('public.pharmacy_portal.stock_warn_not_listed_title', [], $l) }}</strong><br>
            {{ __('public.pharmacy_portal.stock_warn_not_listed_body', [], $l) }}
        </div>
    </div>
    @endif
@endforeach

@if($listing)

{{-- Coverage: the number a pharmacist reads. Same corrected semantics
     (`coverage['reported']` of `coverage['total']`), restyled only. --}}
@php $covPct = $coverage['total'] > 0 ? (int) round($coverage['reported'] / $coverage['total'] * 100) : 0; @endphp
<div class="fs-cov">
    <div class="fs-cov__icon"><i data-lucide="pill"></i></div>
    <div class="fs-cov__head">{{ __('finder_supply.cov_title') }}</div>
    <div class="fs-cov__count">
        <strong>{{ $listing->facility_name }}</strong> —
        {{ __('public.pharmacy_portal.stock_cov_summary', [
            'reported' => $coverage['reported'],
            'total'    => $coverage['total'],
        ], $l) }}
    </div>
    <progress class="fs-cov__bar" value="{{ $coverage['reported'] }}" max="{{ max(1, $coverage['total']) }}" aria-label="{{ $covPct }}%"></progress>
    <p class="fs-cov__hint">{{ __('finder_supply.cov_hint') }}</p>
</div>

{{-- Report a medicine this pharmacy has never published availability for. --}}
<details class="panel" id="stock-add" @if(old('medicine_id')) open @endif>
    <summary class="panel-header">
        <i data-lucide="plus-circle"></i>
        {{ __('public.pharmacy_portal.stock_add_title', [], $l) }}
    </summary>
    <div class="panel-body">
        @if($catalog->isEmpty())
            <p class="td-muted">{{ __('public.pharmacy_portal.stock_catalog_empty', [], $l) }}</p>
        @else
        <form method="POST" action="{{ route('portals.pharmacy.stock.report') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add-medicine">{{ __('public.pharmacy_portal.stock_add_medicine', [], $l) }}</label>
                    <select name="medicine_id" id="add-medicine" class="form-control" required>
                        <option value="">{{ __('public.pharmacy_portal.stock_add_choose', [], $l) }}</option>
                        @foreach($catalog as $m)
                        <option value="{{ $m->id }}" {{ old('medicine_id') === $m->id ? 'selected' : '' }}>
                            {{ $m->name }}@if($m->form) · {{ $m->form }}@endif
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-status">{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}</label>
                    <select name="stock_status" id="add-status" class="form-control" required>
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ $s->value === 'in_stock' ? 'selected' : '' }}>@enum($s->value, 'stock_status')</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="add-packs">{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}</label>
                    <input type="number" name="packs_available" id="add-packs" class="form-control fs-num" min="0" max="1000000" inputmode="numeric" value="{{ old('packs_available') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-pack-size">{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}</label>
                    <input type="text" name="pack_size" id="add-pack-size" class="form-control" maxlength="60" value="{{ old('pack_size') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="add-price">{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}</label>
                    <input type="number" name="unit_price" id="add-price" class="form-control fs-num" min="0" step="1" inputmode="numeric" value="{{ old('unit_price') }}">
                    <div class="td-muted">{{ __('pharmacy_prices.whole_francs') }}</div>
                </div>
            </div>
            <label class="form-check">
                <input type="checkbox" name="reservation_enabled" value="1" checked>
                {{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}
            </label>
            <div class="mt-6">
                <button type="submit" class="btn btn-primary fs-tap">
                    <i data-lucide="megaphone"></i> {{ __('public.pharmacy_portal.stock_btn_report', [], $l) }}
                </button>
            </div>
        </form>
        @endif
    </div>
</details>

{{-- The queue: what this pharmacy tells patients it has, one row per medicine. --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="megaphone"></i> {{ __('pharmacy_ops.nav_public_stock') }} <span class="ph-count-inline">({{ $stockCount }})</span></h3>
        <a href="#stock-add" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('public.pharmacy_portal.stock_add_title', [], $l) }}</a>
    </div>
    <div class="ph-toolbar">
        <div class="ph-pills" role="tablist">
            <a href="{{ route('portals.pharmacy.stock', array_filter(['search' => request('search')])) }}" class="ph-pill{{ request('stock_status') === null || request('stock_status') === '' ? ' active' : '' }}" role="tab">{{ __('public.pharmacy_portal.filter_all_stock', [], $l) }}</a>
            @foreach($statuses as $s)
            <a href="{{ route('portals.pharmacy.stock', array_filter(['search' => request('search'), 'stock_status' => $s->value])) }}" class="ph-pill{{ request('stock_status') === $s->value ? ' active' : '' }}" role="tab"><span class="ph-dot"></span>@enum($s->value, 'stock_status')</a>
            @endforeach
        </div>
        {{-- Search YOUR catalog first: behind a counter the question is always
             "do we already report this one?" — so the search leads the list. --}}
        <form method="GET" class="fs-search" role="search">
            <label class="fs-search__box">
                @include('partials.finder.icon', ['name' => 'search', 'size' => 18])
                <input type="text" name="search" class="fs-search__input" inputmode="search" autocomplete="off"
                       placeholder="{{ __('public.pharmacy_portal.ph_search_drug', [], $l) }}"
                       value="{{ request('search') }}" aria-label="{{ __('public.aria_search_drugs') }}">
            </label>
            <select name="stock_status" class="filter-select" aria-label="{{ __('public.aria_stock_status') }}" data-autosubmit>
                <option value="">{{ __('public.pharmacy_portal.filter_all_stock', [], $l) }}</option>
                @foreach($statuses as $s)
                <option value="{{ $s->value }}" {{ request('stock_status') === $s->value ? 'selected' : '' }}>@enum($s->value, 'stock_status')</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm fs-tap"><i data-lucide="filter"></i> {{ __('public.pharmacy_portal.btn_filter', [], $l) }}</button>
            @if(request()->hasAny(['stock_status','search']))
                <a href="{{ route('portals.pharmacy.stock') }}" class="btn btn-ghost btn-sm fs-tap">{{ __('public.pharmacy_portal.btn_clear', [], $l) }}</a>
            @endif
        </form>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('public.pharmacy_portal.stock_col_medicine', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_source', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_reported', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.col_action', [], $l) }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                @php
                    $med     = $stock->medicine;
                    $ledger  = isset($ledgerItems) && $ledgerItems->has($stock->medicine_id) ? $ledgerItems->get($stock->medicine_id) : null;
                    $stVal   = $stock->stock_status instanceof \BackedEnum ? $stock->stock_status->value : (string) $stock->stock_status;
                    $tone    = match ($stVal) { 'in_stock' => 'green', 'low_stock' => 'amber', 'out_of_stock' => 'red', default => 'grey' };
                @endphp
                <tr>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_medicine', [], $l) }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $med?->name, 'tone' => $tone])
                            <div>
                                @if($ledger)
                                    <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.items.show', $ledger) }}" class="td-strong">{{ $med?->name ?? '—' }}</a></div>
                                    <div class="ph-cell__sub">{{ $med?->generic_name }} {{ $med?->strength }} · <a href="{{ route('portals.pharmacy.stock.items.show', $ledger) }}">{{ __('pharmacy_stock_desk.link_ledger') }}</a></div>
                                @else
                                    <div class="ph-cell__title"><span class="td-strong">{{ $med?->name ?? '—' }}</span></div>
                                    <div class="ph-cell__sub">{{ $med?->generic_name }} {{ $med?->strength }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}">
                        {{-- Tap 1: pick the availability. Tap 2: Save. Same field
                             (`stock_status` on form stock-{id}), bigger targets. --}}
                        <div class="fs-seg" role="radiogroup" aria-label="{{ __('finder_supply.quick_status_label') }}">
                            @foreach($statuses as $s)
                            <label class="fs-seg__opt {{ $segFor($s->value) }}">
                                <input type="radio" name="stock_status" form="stock-{{ $stock->id }}" value="{{ $s->value }}"
                                       {{ $stock->stock_status === $s ? 'checked' : '' }} required>
                                <span>@enum($s->value, 'stock_status')</span>
                            </label>
                            @endforeach
                        </div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}">
                        <input type="number" name="packs_available" form="stock-{{ $stock->id }}" class="form-control fs-num" min="0" max="1000000" inputmode="numeric" value="{{ $stock->packs_available }}" aria-label="{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}">
                        <input type="text" name="pack_size" form="stock-{{ $stock->id }}" class="form-control mt-6" maxlength="60" value="{{ $stock->pack_size }}" placeholder="{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}" aria-label="{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}">
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}">
                        <input type="number" name="unit_price" form="stock-{{ $stock->id }}" class="form-control fs-num" min="0" step="1" inputmode="numeric" value="{{ $stock->unit_price !== null ? (int) round($stock->unit_price) : '' }}" aria-label="{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}">
                        <div class="td-muted">{{ $stock->currency }} · {{ __('pharmacy_prices.whole_francs') }}</div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}">
                        <label class="form-check">
                            <input type="checkbox" name="reservation_enabled" form="stock-{{ $stock->id }}" value="1" {{ $stock->reservation_enabled ? 'checked' : '' }}>
                            <span class="td-muted">{{ __('public.pharmacy_portal.stock_reserve_hint', [], $l) }}</span>
                        </label>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_source', [], $l) }}">
                        @if($stock->source_system === $portalSource)
                            <span class="badge badge-success">{{ __('public.pharmacy_portal.stock_src_portal', [], $l) }}</span>
                        @else
                            <span class="badge badge-neutral">{{ __('public.pharmacy_portal.stock_src_other', ['source' => $stock->source_system ?: '—'], $l) }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_reported', [], $l) }}" class="td-muted">
                        @include('partials.finder.icon', ['name' => 'clock', 'size' => 13])
                        {{ $stock->last_reported_at?->diffForHumans() ?? __('public.pharmacy_portal.stock_never', [], $l) }}
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.col_action', [], $l) }}">
                        <form method="POST" action="{{ route('portals.pharmacy.stock.report') }}" id="stock-{{ $stock->id }}" class="inline-form">
                            @csrf
                            <input type="hidden" name="medicine_id" value="{{ $stock->medicine_id }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="stock_status_filter" value="{{ request('stock_status') }}">
                            <button type="submit" class="btn btn-primary btn-sm fs-tap">
                                @include('partials.finder.icon', ['name' => 'check', 'size' => 16]) {{ __('public.pharmacy_portal.stock_btn_save', [], $l) }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="package-search"></i>
                        <p>{{ __('public.pharmacy_portal.stock_none', [], $l) }}</p>
                        @if(! $catalog->isEmpty())
                        <a href="#stock-add" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('public.pharmacy_portal.stock_add_title', [], $l) }}</a>
                        @endif
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $stocks?->links() }}</div>
</div>
@endif

@endsection
