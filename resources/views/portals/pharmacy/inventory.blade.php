@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()) ?: 'Drug Inventory')
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()) ?: 'Pharmacist')
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()) ?: 'Pharmacy Portal')
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_stock', [], app()->getLocale()) ?: 'Public Availability')

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

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
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('public.pharmacy_portal.page_heading_stock', [], $l) }}</h2>
    <p class="page-subtitle">{{ __('public.pharmacy_portal.page_subtitle_stock', [], $l) }}</p>
    <div class="page-head__spacer"></div>
    @feature('inventory_ops')
        @endfeature
</div>

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

{{-- Search YOUR catalog first: behind a counter the question is always
     "do we already report this one?" — so the search leads the screen. --}}
<form method="GET" class="fs-search" role="search">
    <label class="fs-search__box">
        @include('partials.finder.icon', ['name' => 'search', 'size' => 18])
        <input type="text" name="search" class="fs-search__input" inputmode="search" autocomplete="off"
               placeholder="{{ __('public.pharmacy_portal.ph_search_drug', [], $l) ?: 'Drug or generic name…' }}"
               value="{{ request('search') }}" aria-label="{{ __('public.aria_search_drugs') }}">
    </label>
    <select name="stock_status" class="filter-select" aria-label="{{ __('public.aria_stock_status') }}" onchange="this.form.submit()">
        <option value="">{{ __('public.pharmacy_portal.filter_all_stock', [], $l) ?: 'All stock' }}</option>
        @foreach($statuses as $s)
        <option value="{{ $s->value }}" {{ request('stock_status') === $s->value ? 'selected' : '' }}>@enum($s->value, 'stock_status')</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm fs-tap"><i data-lucide="filter"></i> {{ __('public.pharmacy_portal.btn_filter', [], $l) ?: 'Filter' }}</button>
    @if(request()->hasAny(['stock_status','search']))
        <a href="{{ route('portals.pharmacy.stock') }}" class="btn btn-ghost btn-sm fs-tap">{{ __('public.pharmacy_portal.btn_clear', [], $l) ?: 'Clear' }}</a>
    @endif
</form>

{{-- Coverage: the number a pharmacist reads. Same corrected semantics
     (`coverage['reported']` of `coverage['total']`), restyled only. --}}
@php $covPct = $coverage['total'] > 0 ? (int) round($coverage['reported'] / $coverage['total'] * 100) : 0; @endphp
<div class="fs-cov">
    <div class="fs-cov__head">
        @include('partials.finder.icon', ['name' => 'pill', 'size' => 14])
        {{ __('finder_supply.cov_title') }}
    </div>
    <div class="fs-cov__count">
        <strong>{{ $listing->facility_name }}</strong> —
        {{ __('public.pharmacy_portal.stock_cov_summary', [
            'reported' => $coverage['reported'],
            'total'    => $coverage['total'],
        ], $l) }}
    </div>
    <div class="fs-cov__bar" role="img" aria-label="{{ $covPct }}%">
        <div class="fs-cov__fill" style="width:{{ $covPct }}%"></div>
    </div>
    <p class="fs-cov__hint">{{ __('finder_supply.cov_hint') }}</p>
</div>

{{-- Report a medicine this pharmacy has never published availability for. --}}
<details class="panel" @if(old('medicine_id')) open @endif>
    <summary class="panel-header" style="cursor:pointer;">
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
                    <input type="number" name="unit_price" id="add-price" class="form-control fs-num" min="0" step="0.01" inputmode="decimal" value="{{ old('unit_price') }}">
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

<div class="panel">
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
                    <th>{{ __('public.pharmacy_portal.col_action', [], $l) ?: 'Action' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                @php $med = $stock->medicine; @endphp
                <tr>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_medicine', [], $l) }}">
                        <span class="td-strong">{{ $med?->name ?? '—' }}</span>
                        <div class="td-muted">{{ $med?->generic_name }} {{ $med?->strength }}</div>
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
                        <input type="number" name="unit_price" form="stock-{{ $stock->id }}" class="form-control fs-num" min="0" step="0.01" inputmode="decimal" value="{{ $stock->unit_price }}" aria-label="{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}">
                        <div class="td-muted">{{ $stock->currency }}</div>
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
                    <td data-label="{{ __('public.pharmacy_portal.col_action', [], $l) ?: 'Action' }}">
                        <form method="POST" action="{{ route('portals.pharmacy.stock.report') }}" id="stock-{{ $stock->id }}" class="inline-form">
                            @csrf
                            <input type="hidden" name="medicine_id" value="{{ $stock->medicine_id }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="stock_status_filter" value="{{ request('stock_status') }}">
                            <button type="submit" class="btn btn-primary fs-tap">
                                @include('partials.finder.icon', ['name' => 'check', 'size' => 16]) {{ __('public.pharmacy_portal.stock_btn_save', [], $l) }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="td-muted empty-cell">{{ __('public.pharmacy_portal.stock_none', [], $l) }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $stocks?->links() }}</div>
</div>
@endif

@endsection
