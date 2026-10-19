@extends('layouts.portal')
@section('title', __('pharmacy_prices.index_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_prices.index_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_prices.index_subtitle') }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')

@if(session('success'))
<div class="os-banner os-banner--ok" role="status"><i data-lucide="check-circle-2"></i><div>{{ session('success') }}</div></div>
@endif
@if($errors->any())
<div class="os-banner os-banner--danger" role="alert"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>
@endif
@unless($hasListing)
<div class="os-banner os-banner--warn"><i data-lucide="link-2-off"></i><div>{{ __('pharmacy_prices.no_listing') }}</div></div>
@endunless

<div class="os-kpis os-kpis--2">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'tags',
        'label' => __('pharmacy_prices.sum_items'), 'value' => $items->count()])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => $unpriced > 0 ? 'amber' : 'green', 'icon' => $unpriced > 0 ? 'alert-circle' : 'check-circle-2',
        'label' => __('pharmacy_prices.sum_unpriced'), 'value' => $unpriced, 'sub' => __('pharmacy_prices.sum_unpriced_hint')])
</div>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_prices.nav_prices') }} <span class="od-card__count">{{ __('pharmacy_desk.count_paren', ['count' => $items->count()]) }}</span></h2>
    </div>

    <form method="GET" class="os-tools" role="search" action="{{ route('portals.pharmacy.prices.index') }}">
        <label class="os-search">
            <i data-lucide="search"></i>
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('pharmacy_prices.search_placeholder') }}" aria-label="{{ __('pharmacy_prices.search_placeholder') }}" maxlength="80">
        </label>
        <button type="submit" class="od-btn od-btn--secondary os-filter-btn"><i data-lucide="filter"></i> {{ __('pharmacy_prices.btn_search') }}</button>
        @if($search !== '')
            <a href="{{ route('portals.pharmacy.prices.index') }}" class="od-btn od-btn--ghost">{{ __('pharmacy_prices.btn_clear') }}</a>
        @endif
    </form>

    <p class="os-note"><i data-lucide="coins"></i> {{ __('pharmacy_prices.whole_francs_hint') }}</p>

    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_prices.col_medicine') }}</th>
                <th>{{ __('pharmacy_prices.col_price') }}</th>
                <th>{{ __('pharmacy_prices.col_published') }}</th>
                <th>{{ __('pharmacy_prices.col_last_changed') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php
                    $med     = $item->medicine;
                    $row     = $item->medicine_id ? ($published[$item->medicine_id] ?? null) : null;
                    $last    = $item->priceChanges->first();
                    $shown   = $row?->unit_price !== null ? (int) round($row->unit_price) : null;
                    $differs = $row && $item->selling_price_xaf !== null && $shown !== $item->selling_price_xaf;
                    $fid     = 'price-' . $item->id;
                @endphp
                <tr>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_prices.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $item->selling_price_xaf === null ? 'amber' : 'teal'])
                            <div class="os-cell__text">
                                <span class="os-cell__title">{{ $med?->name ?? $item->name }}</span>
                                <span class="os-cell__sub">{{ $med?->generic_name }} {{ $med?->strength }} {{ $med?->form }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_price') }}">
                        <form method="POST" action="{{ route('portals.pharmacy.prices.update', $item->id) }}" class="os-price" id="{{ $fid }}">
                            @csrf
                            @if($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                            <div class="os-money">
                                <input type="number" name="price_xaf" class="os-input os-input--num" step="1" min="0" max="{{ $maxPrice }}" inputmode="numeric" required
                                       value="{{ old('price_xaf', $item->selling_price_xaf) }}" aria-label="{{ __('pharmacy_prices.col_price') }}">
                                <span class="os-money__unit">FCFA</span>
                            </div>
                            <input type="text" name="reason" class="os-input os-input--reason" maxlength="120" placeholder="{{ __('pharmacy_prices.lbl_reason') }}" aria-label="{{ __('pharmacy_prices.lbl_reason') }}">
                        </form>
                        @if($item->selling_price_xaf === null)
                            <span class="os-cell__sub">{{ __('pharmacy_prices.no_price_yet') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_published') }}">
                        @if(! $item->medicine_id)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_prices.not_a_medicine')])
                        @elseif(! $row)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => __('pharmacy_prices.not_published')])
                        @elseif($shown === null)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => __('pharmacy_prices.published_no_price')])
                        @else
                            <span class="os-cell__main os-num">{{ number_format($shown, 0, ',', ' ') }} {{ $row->currency ?: 'XAF' }}</span>
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => $differs ? 'reorder' : 'ok', 'label' => $differs ? __('pharmacy_prices.differs') : __('pharmacy_prices.in_sync')])
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_last_changed') }}">
                        @if($last)
                            <span class="os-cell__main">{{ $last->created_at?->translatedFormat('d M Y H:i') }}</span>
                            <span class="os-cell__sub">{{ $actors[$last->changed_by] ?? __('pharmacy_prices.actor_unknown') }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="os-col-act">
                        <div class="os-rowact">
                            <button type="submit" form="{{ $fid }}" class="od-btn od-btn--row">{{ __('pharmacy_prices.btn_save') }}</button>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => [
                                [route('portals.pharmacy.prices.history', $item->id), __('pharmacy_prices.btn_history'), 'history'],
                                [route('portals.pharmacy.stock.items.show', $item->id), __('pharmacy_stock_desk.link_ledger'), 'book-open'],
                            ]])
                        </div>
                    </td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 6, 'icon' => 'tags',
                    'text' => $search !== '' ? __('pharmacy_prices.search_empty') : __('pharmacy_prices.index_empty'),
                    'href' => $search !== '' ? route('portals.pharmacy.prices.index') : null, 'btn' => __('pharmacy_prices.btn_clear')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

</div>
@endsection
