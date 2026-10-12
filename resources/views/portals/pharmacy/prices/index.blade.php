@extends('layouts.portal')
@section('title', __('pharmacy_prices.index_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_prices.nav_prices'))

@section('head')
@include('portals.pharmacy._supply_styles')
<style>
.fs-price{display:flex;align-items:center;gap:.4rem;flex-wrap:wrap}
.fs-price .form-control{width:9.5rem}
.fs-price__unit{font-size:.8rem;font-weight:700;color:var(--p-text-muted,#64748B)}
.fs-price .fs-reason{width:11rem}
</style>
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_prices.index_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_prices.index_subtitle') }}</p>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if(session('success'))
<div class="banner banner--success"><i data-lucide="check-circle-2"></i><div>{{ session('success') }}</div></div>
@endif

@if($errors->any())
<div class="banner banner--danger"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>
@endif

@unless($hasListing)
<div class="banner banner--warning">
    <i data-lucide="link-2-off"></i>
    <div>{{ __('pharmacy_prices.no_listing') }}</div>
</div>
@endunless

<div class="fs-kpis">
    <div class="fs-kpi fs-kpi--neutral">
        <div class="fs-kpi__label">{{ __('pharmacy_prices.sum_items') }}</div>
        <div class="fs-kpi__value">{{ $items->count() }}</div>
    </div>
    <div class="fs-kpi {{ $unpriced > 0 ? 'fs-kpi--warn' : 'fs-kpi--neutral' }}">
        <div class="fs-kpi__label">{{ __('pharmacy_prices.sum_unpriced') }}</div>
        <div class="fs-kpi__value">{{ $unpriced }}</div>
        <div class="fs-kpi__sub">{{ __('pharmacy_prices.sum_unpriced_hint') }}</div>
    </div>
</div>

<div class="fs-note"><i data-lucide="coins"></i><div>{{ __('pharmacy_prices.whole_francs_hint') }}</div></div>

<form method="GET" class="fs-search" role="search" action="{{ route('portals.pharmacy.prices.index') }}">
    <div class="fs-search__box">
        <i data-lucide="search" class="f-icon"></i>
        <input type="search" name="q" class="form-control" value="{{ $search }}" placeholder="{{ __('pharmacy_prices.search_placeholder') }}" aria-label="{{ __('pharmacy_prices.search_placeholder') }}" maxlength="80">
    </div>
    <button type="submit" class="btn btn-secondary btn-sm fs-tap"><i data-lucide="filter"></i> {{ __('pharmacy_prices.btn_search') }}</button>
    @if($search !== '')
        <a href="{{ route('portals.pharmacy.prices.index') }}" class="btn btn-ghost btn-sm fs-tap">{{ __('pharmacy_prices.btn_clear') }}</a>
    @endif
</form>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="tags"></i> {{ __('pharmacy_prices.nav_prices') }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_prices.col_medicine') }}</th>
                <th>{{ __('pharmacy_prices.col_price') }}</th>
                <th>{{ __('pharmacy_prices.col_published') }}</th>
                <th>{{ __('pharmacy_prices.col_last_changed') }}</th>
                <th>{{ __('pharmacy_prices.col_history') }}</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php
                    $med    = $item->medicine;
                    $row    = $item->medicine_id ? ($published[$item->medicine_id] ?? null) : null;
                    $last   = $item->priceChanges->first();
                    $shown  = $row?->unit_price !== null ? (int) round($row->unit_price) : null;
                    $differs = $row && $item->selling_price_xaf !== null && $shown !== $item->selling_price_xaf;
                @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_prices.col_medicine') }}" class="td-strong">
                        {{ $med?->name ?? $item->name }}
                        <div class="td-muted">{{ $med?->generic_name }} {{ $med?->strength }} {{ $med?->form }}</div>
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_price') }}">
                        <form method="POST" action="{{ route('portals.pharmacy.prices.update', $item->id) }}" class="fs-price">
                            @csrf
                            @if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                            <input type="number" name="price_xaf" class="form-control fs-num" step="1" min="0" max="{{ $maxPrice }}" inputmode="numeric" required
                                   value="{{ old('price_xaf', $item->selling_price_xaf) }}" aria-label="{{ __('pharmacy_prices.col_price') }}">
                            <span class="fs-price__unit">FCFA</span>
                            <input type="text" name="reason" class="form-control fs-reason" maxlength="120" placeholder="{{ __('pharmacy_prices.lbl_reason') }}" aria-label="{{ __('pharmacy_prices.lbl_reason') }}">
                            <button type="submit" class="btn btn-primary btn-sm fs-tap"><i data-lucide="save"></i> {{ __('pharmacy_prices.btn_save') }}</button>
                        </form>
                        @if($item->selling_price_xaf === null)
                            <div class="td-muted">{{ __('pharmacy_prices.no_price_yet') }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_published') }}">
                        @if(! $item->medicine_id)
                            <span class="badge badge-neutral">{{ __('pharmacy_prices.not_a_medicine') }}</span>
                        @elseif(! $row)
                            <span class="badge badge-warning">{{ __('pharmacy_prices.not_published') }}</span>
                        @elseif($shown === null)
                            <span class="badge badge-warning">{{ __('pharmacy_prices.published_no_price') }}</span>
                        @elseif($differs)
                            <span class="fs-mono">{{ number_format($shown, 0, ',', ' ') }} {{ $row->currency ?: 'XAF' }}</span>
                            <span class="badge badge-warning">{{ __('pharmacy_prices.differs') }}</span>
                        @else
                            <span class="fs-mono">{{ number_format($shown, 0, ',', ' ') }} {{ $row->currency ?: 'XAF' }}</span>
                            <span class="badge badge-success">{{ __('pharmacy_prices.in_sync') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_last_changed') }}" class="td-muted">
                        @if($last)
                            {{ $last->created_at?->translatedFormat('d M Y H:i') }}
                            <div>{{ $actors[$last->changed_by] ?? __('pharmacy_prices.actor_unknown') }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_history') }}">
                        <a href="{{ route('portals.pharmacy.prices.history', $item->id) }}" class="btn btn-ghost btn-sm fs-tap"><i data-lucide="history"></i> {{ __('pharmacy_prices.btn_history') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="td-muted empty-cell">{{ $search !== '' ? __('pharmacy_prices.search_empty') : __('pharmacy_prices.index_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
