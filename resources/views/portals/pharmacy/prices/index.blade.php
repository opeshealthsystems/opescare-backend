@extends('layouts.portal')
@section('title', __('pharmacy_prices.index_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_prices.nav_prices'))

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

<div class="ph-kpis">
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="tags"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_prices.sum_items') }}</div>
        <div class="stat-card__value">{{ $items->count() }}</div>
    </div>
    <div class="stat-card {{ $unpriced > 0 ? 'stat-card--warning' : 'stat-card--success' }}">
        <div class="stat-card__icon"><i data-lucide="{{ $unpriced > 0 ? 'alert-circle' : 'check' }}"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_prices.sum_unpriced') }}</div>
        <div class="stat-card__value">{{ $unpriced }}</div>
        <div class="stat-card__sub">{{ __('pharmacy_prices.sum_unpriced_hint') }}</div>
    </div>
</div>

<div class="fs-note"><i data-lucide="coins"></i><div>{{ __('pharmacy_prices.whole_francs_hint') }}</div></div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="tags"></i> {{ __('pharmacy_prices.nav_prices') }} <span class="ph-count-inline">{{ __('pharmacy_desk.count_paren', ['count' => $items->count()]) }}</span></h3>
    </div>

    <div class="ph-tools">
        <form method="GET" class="ph-toolbar" role="search" action="{{ route('portals.pharmacy.prices.index') }}">
            <label class="ph-search">
                <i data-lucide="search"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('pharmacy_prices.search_placeholder') }}" aria-label="{{ __('pharmacy_prices.search_placeholder') }}" maxlength="80">
            </label>
            <button type="submit" class="btn btn-secondary btn-sm fs-tap"><i data-lucide="filter"></i> {{ __('pharmacy_prices.btn_search') }}</button>
            @if($search !== '')
                <a href="{{ route('portals.pharmacy.prices.index') }}" class="btn btn-ghost btn-sm fs-tap">{{ __('pharmacy_prices.btn_clear') }}</a>
            @endif
        </form>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_prices.col_medicine') }}</th>
                <th>{{ __('pharmacy_prices.col_price') }}</th>
                <th>{{ __('pharmacy_prices.col_published') }}</th>
                <th>{{ __('pharmacy_prices.col_last_changed') }}</th>
                <th class="row-actions">{{ __('pharmacy_prices.col_history') }}</th>
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
                    <td data-label="{{ __('pharmacy_prices.col_medicine') }}">
                        <div class="ph-cell">
                            <span class="ph-avatar ph-avatar--teal" aria-hidden="true"><i data-lucide="pill"></i></span>
                            <div>
                                <span class="ph-cell__title">{{ $med?->name ?? $item->name }}</span>
                                <div class="ph-cell__sub">{{ $med?->generic_name }} {{ $med?->strength }} {{ $med?->form }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_price') }}">
                        <form method="POST" action="{{ route('portals.pharmacy.prices.update', $item->id) }}" class="ph-price">
                            @csrf
                            @if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                            <input type="number" name="price_xaf" class="form-control fs-num" step="1" min="0" max="{{ $maxPrice }}" inputmode="numeric" required
                                   value="{{ old('price_xaf', $item->selling_price_xaf) }}" aria-label="{{ __('pharmacy_prices.col_price') }}">
                            <span class="ph-price__unit">FCFA</span>
                            <input type="text" name="reason" class="form-control ph-reason" maxlength="120" placeholder="{{ __('pharmacy_prices.lbl_reason') }}" aria-label="{{ __('pharmacy_prices.lbl_reason') }}">
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
                    <td class="row-actions" data-label="{{ __('pharmacy_prices.col_history') }}">
                        <a href="{{ route('portals.pharmacy.prices.history', $item->id) }}" class="btn btn-ghost btn-sm fs-tap"><i data-lucide="history"></i> {{ __('pharmacy_prices.btn_history') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="tags"></i>
                        <p>{{ $search !== '' ? __('pharmacy_prices.search_empty') : __('pharmacy_prices.index_empty') }}</p>
                        @if($search !== '')
                            <a href="{{ route('portals.pharmacy.prices.index') }}" class="btn btn-secondary btn-sm">{{ __('pharmacy_prices.btn_clear') }}</a>
                        @endif
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
