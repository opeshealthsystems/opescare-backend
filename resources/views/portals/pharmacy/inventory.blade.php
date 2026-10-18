@extends('layouts.portal')
@section('title', __('public.pharmacy_portal.page_title', [], app()->getLocale()))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label', [], app()->getLocale()))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('public.pharmacy_portal.breadcrumb_section_stock', [], app()->getLocale()))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@php
    $l = app()->getLocale();
    // Colour only ever means state: green in stock, red low/out, neutral unknown.
    $toneFor = fn (string $v) => match ($v) {
        'in_stock'     => 'ok',
        'low_stock'    => 'low',
        'out_of_stock' => 'out',
        default        => 'neutral',
    };
    $avFor = fn (string $v) => match ($v) {
        'in_stock' => 'green', 'low_stock', 'out_of_stock' => 'red', default => 'grey',
    };
    $stockCount = $stocks ? (method_exists($stocks, 'total') ? $stocks->total() : $stocks->count()) : 0;
    // Per-status counts for the filter pills and KPI tiles: one grouped read of
    // the same table the list reads, for this pharmacy's own listing only.
    $byStatus = $listing
        ? \App\Models\MedicinePharmacyStock::query()
            ->where('care_facility_id', $listing->id)
            ->toBase()
            ->selectRaw('stock_status, count(*) as n')
            ->groupBy('stock_status')
            ->pluck('n', 'stock_status')
            ->map(fn ($n) => (int) $n)
        : collect();
    $allCount = (int) $byStatus->sum();
    $active   = (string) request('stock_status', '');
@endphp

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('public.pharmacy_portal.page_heading_stock', [], $l) }}</h1>
        <p class="os-head__sub">{{ __('public.pharmacy_portal.page_subtitle_stock', [], $l) }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

{{-- Why a pharmacy may be invisible to patients, in the order worth fixing. --}}
@foreach($issues as $issue)
    @if($issue === 'unlinked')
    <div class="os-banner os-banner--danger">
        <i data-lucide="unlink"></i>
        <div><strong>{{ __('public.pharmacy_portal.stock_warn_unlinked_title', [], $l) }}</strong><br>{{ __('public.pharmacy_portal.stock_warn_unlinked_body', [], $l) }}</div>
    </div>
    @elseif($issue === 'no_coordinates')
    <div class="os-banner os-banner--warn">
        <i data-lucide="map-pin-off"></i>
        <div><strong>{{ __('public.pharmacy_portal.stock_warn_no_coords_title', [], $l) }}</strong><br>{{ __('public.pharmacy_portal.stock_warn_no_coords_body', [], $l) }}</div>
    </div>
    @elseif($issue === 'not_listed')
    <div class="os-banner os-banner--warn">
        <i data-lucide="eye-off"></i>
        <div><strong>{{ __('public.pharmacy_portal.stock_warn_not_listed_title', [], $l) }}</strong><br>{{ __('public.pharmacy_portal.stock_warn_not_listed_body', [], $l) }}</div>
    </div>
    @endif
@endforeach

@if($listing)

@php $covPct = $coverage['total'] > 0 ? (int) round($coverage['reported'] / $coverage['total'] * 100) : 0; @endphp
<div class="os-kpis">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'pack',
        'label' => __('pharmacy_stock_desk.kpi_reported'), 'value' => $coverage['reported'],
        'sub' => __('public.pharmacy_portal.stock_cov_summary', ['reported' => $coverage['reported'], 'total' => $coverage['total']], $l)])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'green', 'icon' => 'check-circle-2',
        'label' => __('pharmacy_stock_desk.kpi_in_stock'), 'value' => $byStatus->get('in_stock', 0),
        'sub' => $listing->facility_name])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'amber', 'icon' => 'box',
        'label' => __('pharmacy_stock_desk.kpi_low'), 'value' => $byStatus->get('low_stock', 0),
        'href' => route('portals.pharmacy.stock', ['stock_status' => 'low_stock'])])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'red', 'icon' => 'circle-slash',
        'label' => __('pharmacy_stock_desk.kpi_out'), 'value' => $byStatus->get('out_of_stock', 0),
        'href' => route('portals.pharmacy.stock', ['stock_status' => 'out_of_stock'])])
</div>

{{-- The list: what this pharmacy tells patients it has, one row per medicine. --}}
<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.nav_public_stock') }} <span class="od-card__count">({{ $stockCount }})</span></h2>
        @if(! $catalog->isEmpty())
        <a href="#stock-add" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('public.pharmacy_portal.stock_add_title', [], $l) }}</a>
        @endif
    </div>

    <nav class="od-tabs os-filter" aria-label="{{ __('public.aria_stock_status') }}">
        <a href="{{ route('portals.pharmacy.stock', array_filter(['search' => request('search')])) }}" class="od-tab{{ $active === '' ? ' is-active' : '' }}">{{ __('public.pharmacy_portal.filter_all_stock', [], $l) }} <span class="od-tab__count">({{ $allCount }})</span></a>
        @foreach($statuses as $s)
        <a href="{{ route('portals.pharmacy.stock', array_filter(['search' => request('search'), 'stock_status' => $s->value])) }}" class="od-tab{{ $active === $s->value ? ' is-active' : '' }}"><span class="od-tab__dot os-dot--{{ $toneFor($s->value) }}"></span>@enum($s->value, 'stock_status') <span class="od-tab__count">({{ $byStatus->get($s->value, 0) }})</span></a>
        @endforeach
    </nav>

    {{-- Search YOUR catalog first: "do we already report this one?" --}}
    <form method="GET" class="os-tools" role="search">
        @if($active !== '')<input type="hidden" name="stock_status" value="{{ $active }}">@endif
        <label class="os-search">
            <i data-lucide="search"></i>
            <input type="search" name="search" inputmode="search" autocomplete="off"
                   placeholder="{{ __('public.pharmacy_portal.ph_search_drug', [], $l) }}"
                   value="{{ request('search') }}" aria-label="{{ __('public.aria_search_drugs') }}">
        </label>
        <button type="submit" class="od-btn od-btn--secondary os-filter-btn"><i data-lucide="filter"></i> {{ __('public.pharmacy_portal.btn_filter', [], $l) }}</button>
        @if(request()->hasAny(['stock_status','search']))
            <a href="{{ route('portals.pharmacy.stock') }}" class="od-btn od-btn--ghost">{{ __('public.pharmacy_portal.btn_clear', [], $l) }}</a>
        @endif
    </form>

    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead>
                <tr>
                    <th class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_all') }}"></th>
                    <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_medicine', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}</th>
                    <th>{{ __('public.pharmacy_portal.stock_col_reported', [], $l) }}</th>
                    <th class="os-col-act">{{ __('public.pharmacy_portal.col_action', [], $l) }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                @php
                    $med    = $stock->medicine;
                    $ledger = isset($ledgerItems) && $ledgerItems->has($stock->medicine_id) ? $ledgerItems->get($stock->medicine_id) : null;
                    $stVal  = $stock->stock_status instanceof \BackedEnum ? $stock->stock_status->value : (string) $stock->stock_status;
                    $fid    = 'stock-' . $stock->id;
                @endphp
                <tr>
                    <td class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_row') }}"></td>
                    <td class="os-col-num">{{ ($stocks->firstItem() ?? 1) + $loop->index }}</td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_medicine', [], $l) }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $avFor($stVal)])
                            <div class="os-cell__text">
                                @if($ledger)
                                    <a href="{{ route('portals.pharmacy.stock.items.show', $ledger) }}" class="os-cell__title">{{ $med?->name ?? '—' }}</a>
                                @else
                                    <span class="os-cell__title">{{ $med?->name ?? '—' }}</span>
                                @endif
                                <span class="os-cell__sub">{{ trim(($med?->generic_name ?? '') . ' ' . ($med?->strength ?? '')) ?: ($med?->form ?? '—') }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}">
                        {{-- The status pill is also the control: same `stock_status` field on form stock-{id}. --}}
                        <label class="os-pillselect os-pill--{{ $toneFor($stVal) }}">
                            <span class="os-sr">{{ __('finder_supply.quick_status_label') }}</span>
                            <select name="stock_status" form="{{ $fid }}" required>
                                @foreach($statuses as $s)
                                <option value="{{ $s->value }}" @selected($stock->stock_status === $s)>@enum($s->value, 'stock_status')</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down"></i>
                        </label>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}">
                        <input type="number" name="packs_available" form="{{ $fid }}" class="os-input os-input--num" min="0" max="1000000" inputmode="numeric" value="{{ $stock->packs_available }}" aria-label="{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}">
                        <input type="text" name="pack_size" form="{{ $fid }}" class="os-input os-input--sub" maxlength="60" value="{{ $stock->pack_size }}" placeholder="{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}" aria-label="{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}">
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}">
                        <div class="os-money">
                            <input type="number" name="unit_price" form="{{ $fid }}" class="os-input os-input--num" min="0" step="1" inputmode="numeric" value="{{ $stock->unit_price !== null ? (int) round($stock->unit_price) : '' }}" aria-label="{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}">
                            <span class="os-money__unit">{{ $stock->currency ?: 'XAF' }}</span>
                        </div>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}">
                        <label class="os-switch" title="{{ __('public.pharmacy_portal.stock_reserve_hint', [], $l) }}">
                            <input type="checkbox" name="reservation_enabled" form="{{ $fid }}" value="1" {{ $stock->reservation_enabled ? 'checked' : '' }}>
                            <span class="os-switch__track" aria-hidden="true"></span>
                            <span class="os-sr">{{ __('public.pharmacy_portal.stock_reserve_hint', [], $l) }}</span>
                        </label>
                    </td>
                    <td data-label="{{ __('public.pharmacy_portal.stock_col_reported', [], $l) }}">
                        <span class="os-cell__main">{{ $stock->last_reported_at?->diffForHumans() ?? __('public.pharmacy_portal.stock_never', [], $l) }}</span>
                        @if($stock->source_system === $portalSource)
                            <span class="os-cell__sub os-src os-src--ok">{{ __('public.pharmacy_portal.stock_src_portal', [], $l) }}</span>
                        @else
                            <span class="os-cell__sub os-src">{{ __('public.pharmacy_portal.stock_src_other', ['source' => $stock->source_system ?: '—'], $l) }}</span>
                        @endif
                    </td>
                    <td class="os-col-act" data-label="{{ __('public.pharmacy_portal.col_action', [], $l) }}">
                        <div class="os-rowact">
                            <form method="POST" action="{{ route('portals.pharmacy.stock.report') }}" id="{{ $fid }}" class="os-inline">
                                @csrf
                                <input type="hidden" name="medicine_id" value="{{ $stock->medicine_id }}">
                                <input type="hidden" name="search" value="{{ request('search') }}">
                                <input type="hidden" name="stock_status_filter" value="{{ request('stock_status') }}">
                                <button type="submit" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.row_save') }}</button>
                            </form>
                            @php
                                $menu = [];
                                if ($ledger) {
                                    $menu[] = [route('portals.pharmacy.stock.items.show', $ledger), __('pharmacy_stock_desk.link_ledger'), 'book-open'];
                                }
                                $menu[] = [route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $stock->medicine_id]), __('pharmacy_stock_desk.btn_receive'), 'truck'];
                                if ($ledger) {
                                    $menu[] = [route('portals.pharmacy.prices.history', $ledger), __('pharmacy_stock_desk.price_history'), 'history'];
                                }
                            @endphp
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => $menu])
                        </div>
                    </td>
                </tr>
                @empty
                    @include('portals.pharmacy.stock_ops._empty', ['cols' => 9, 'icon' => 'package-search',
                        'text' => __('public.pharmacy_portal.stock_none', [], $l),
                        'href' => $catalog->isEmpty() ? null : '#stock-add', 'btnIcon' => 'plus',
                        'btn' => __('public.pharmacy_portal.stock_add_title', [], $l)])
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks && $stocks->hasPages())<div class="os-card__foot">{{ $stocks->links() }}</div>@endif
</section>

{{-- Report a medicine this pharmacy has never published availability for. --}}
<details class="od-card os-card os-fold" id="stock-add" @if(old('medicine_id')) open @endif>
    <summary class="od-card__head os-fold__head">
        <h2 class="od-card__title"><span class="os-fold__icon"><i data-lucide="plus"></i></span>{{ __('public.pharmacy_portal.stock_add_title', [], $l) }}</h2>
        <i data-lucide="chevron-down" class="os-fold__chev"></i>
    </summary>
    <div class="os-card__body">
        @if($catalog->isEmpty())
            <p class="os-muted">{{ __('public.pharmacy_portal.stock_catalog_empty', [], $l) }}</p>
        @else
        <form method="POST" action="{{ route('portals.pharmacy.stock.report') }}" class="os-form">
            @csrf
            <div class="os-grid os-grid--2">
                <div class="os-field">
                    <label class="os-label" for="add-medicine">{{ __('public.pharmacy_portal.stock_add_medicine', [], $l) }}</label>
                    <select name="medicine_id" id="add-medicine" class="os-input" required>
                        <option value="">{{ __('public.pharmacy_portal.stock_add_choose', [], $l) }}</option>
                        @foreach($catalog as $m)
                        <option value="{{ $m->id }}" {{ old('medicine_id') === $m->id ? 'selected' : '' }}>{{ $m->name }}@if($m->form) · {{ $m->form }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="add-status">{{ __('public.pharmacy_portal.stock_col_status', [], $l) }}</label>
                    <select name="stock_status" id="add-status" class="os-input" required>
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ $s->value === 'in_stock' ? 'selected' : '' }}>@enum($s->value, 'stock_status')</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="os-grid os-grid--3">
                <div class="os-field">
                    <label class="os-label" for="add-packs">{{ __('public.pharmacy_portal.stock_col_packs', [], $l) }}</label>
                    <input type="number" name="packs_available" id="add-packs" class="os-input" min="0" max="1000000" inputmode="numeric" value="{{ old('packs_available') }}">
                </div>
                <div class="os-field">
                    <label class="os-label" for="add-pack-size">{{ __('public.pharmacy_portal.stock_lbl_pack_size', [], $l) }}</label>
                    <input type="text" name="pack_size" id="add-pack-size" class="os-input" maxlength="60" value="{{ old('pack_size') }}">
                </div>
                <div class="os-field">
                    <label class="os-label" for="add-price">{{ __('public.pharmacy_portal.stock_col_price', [], $l) }}</label>
                    <input type="number" name="unit_price" id="add-price" class="os-input" min="0" step="1" inputmode="numeric" value="{{ old('unit_price') }}">
                    <span class="os-hint">{{ __('pharmacy_prices.whole_francs') }}</span>
                </div>
            </div>
            <label class="os-checkline">
                <input type="checkbox" name="reservation_enabled" value="1" checked>
                {{ __('public.pharmacy_portal.stock_col_reserve', [], $l) }}
            </label>
            <div class="os-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="megaphone"></i> {{ __('public.pharmacy_portal.stock_btn_report', [], $l) }}</button>
            </div>
        </form>
        @endif
    </div>
</details>

<p class="os-note"><i data-lucide="info"></i> {{ __('finder_supply.cov_hint') }}</p>
@endif

</div>
@endsection
