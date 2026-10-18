@extends('layouts.portal')
@section('title', __('pharmacy_ops.batches_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_batches'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@php
    $today = \Illuminate\Support\Carbon::today();
@endphp

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_ops.batches_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_ops.batches_subtitle') }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')

<div class="os-kpis">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'amber', 'icon' => 'calendar-clock',
        'label' => __('pharmacy_ops.sum_expiring', ['days' => $windowDays]), 'value' => $expiring->count(),
        'sub' => __('pharmacy_ops.sum_packs', ['count' => $expiring->sum('quantity_available')])])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'red', 'icon' => 'calendar-x',
        'label' => __('pharmacy_ops.sum_expired'), 'value' => $expired->count(),
        'sub' => __('pharmacy_ops.sum_packs', ['count' => $expired->sum('quantity_available')])])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'teal', 'icon' => 'calendar-off',
        'label' => __('pharmacy_ops.sum_undated'), 'value' => $undated->count(),
        'sub' => __('pharmacy_ops.sum_packs', ['count' => $undated->sum('quantity_available')])])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'pack',
        'label' => __('pharmacy_ops.nav_batches'), 'value' => $items->count(),
        'sub' => __('pharmacy_ops.sum_lots', ['count' => $items->sum(fn ($i) => $i->batches->count())])])
</div>

@if($expired->isNotEmpty())
<div class="os-banner os-banner--danger">
    <i data-lucide="calendar-x"></i>
    <div><strong>{{ __('pharmacy_ops.sum_expired') }}: {{ __('pharmacy_ops.sum_lots', ['count' => $expired->count()]) }}</strong><br>{{ __('pharmacy_ops.write_off_hint') }}</div>
</div>
@endif

@if($expiring->isNotEmpty())
<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.sum_expiring', ['days' => $windowDays]) }} <span class="od-card__count">({{ $expiring->count() }})</span></h2>
        <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left-right"></i> {{ __('pharmacy_ops.nav_adjustments') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @foreach($expiring as $b)
                @php $bName = $b->item?->medicine?->name ?? $b->item?->name; @endphp
                <tr>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => 'amber'])
                            <div class="os-cell__text">
                                <span class="os-cell__title">{{ $bName }}</span>
                                <span class="os-cell__sub">{{ $b->item?->medicine?->strength }} {{ $b->item?->medicine?->form }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="os-lot">{{ $b->lot_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_expiry') }}">{{ $b->expiry_date->translatedFormat('d M Y') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="os-num">{{ $b->quantity_available }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => __('pharmacy_ops.expiring_badge', ['days' => (int) $today->diffInDays($b->expiry_date, false)])])</td>
                    <td class="os-col-act">
                        @if($b->item)
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.items.show', $b->item->id) }}#batch-{{ $b->id }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => [
                                [route('portals.pharmacy.stock.items.show', $b->item->id) . '#adjust', __('pharmacy_stock_desk.btn_adjust'), 'arrow-left-right'],
                            ]])
                        </div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.nav_batches') }} <span class="od-card__count">({{ $items->count() }})</span></h2>
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="od-btn od-btn--primary"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
    </div>
    <p class="os-note"><i data-lucide="info"></i> {{ __('pharmacy_ops.usable_hint') }}</p>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th>{{ __('pharmacy_ops.col_received') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php
                    $med   = $item->medicine;
                    $code  = $med?->atc_code;
                    $iName = $med?->name ?? $item->name;
                    $iUse  = $usable[$item->id] ?? 0;
                @endphp
                <tr class="os-row--group">
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td colspan="3" data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $iUse > 0 ? 'green' : 'red'])
                            <div class="os-cell__text">
                                <a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}" class="os-cell__title">{{ $iName }}</a>
                                <span class="os-cell__sub">
                                    {{ $med?->strength }} {{ $med?->form }}
                                    @if($code) · {{ $code }}@if(isset($atc[$code])) — {{ $atc[$code] }}@endif @endif
                                    · @if($item->batches->isEmpty()){{ __('pharmacy_ops.no_lots') }}@else{{ __('pharmacy_ops.sum_lots', ['count' => $item->batches->count()]) }}@endif
                                </span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_usable') }}" class="os-num">{{ $iUse }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">
                        @if($iUse > 0)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'ok', 'label' => __('pharmacy_stock_desk.pill_in_stock')])
                        @else
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'low', 'label' => __('pharmacy_stock_desk.kpi_out')])
                        @endif
                    </td>
                    <td></td>
                    <td class="os-col-act">
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => array_values(array_filter([
                                $item->medicine_id ? [route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]), __('pharmacy_stock_desk.btn_receive'), 'truck'] : null,
                                [route('portals.pharmacy.stock.items.show', $item->id) . '#adjust', __('pharmacy_stock_desk.btn_adjust'), 'arrow-left-right'],
                                [route('portals.pharmacy.stock.items.show', $item->id) . '#reorder', __('pharmacy_stock_desk.btn_reorder_rule'), 'trending-down'],
                            ]))])
                        </div>
                    </td>
                </tr>
                @foreach($item->batches as $b)
                    @php
                        $isExpired = $b->expiry_date && $b->expiry_date->lt($today);
                        $daysLeft  = $b->expiry_date ? (int) $today->diffInDays($b->expiry_date, false) : null;
                    @endphp
                    <tr class="os-row--child">
                        <td class="os-col-num"></td>
                        <td data-label="{{ __('pharmacy_ops.col_medicine') }}"><span class="os-cell__sub">↳ {{ $iName }}</span></td>
                        <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="os-lot"><a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}#batch-{{ $b->id }}">{{ $b->lot_number ?? '—' }}</a></td>
                        <td data-label="{{ __('pharmacy_ops.col_expiry') }}">
                            @if(! $b->expiry_date)
                                <span title="{{ __('pharmacy_ops.expiry_unknown_hint') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.expiry_unknown')])</span>
                            @else
                                {{ $b->expiry_date->translatedFormat('d M Y') }}
                            @endif
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="os-num">{{ $b->quantity_available }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_status') }}">
                            @if($isExpired)
                                @include('portals.pharmacy.stock_ops._pill', ['tone' => 'expired', 'label' => __('pharmacy_ops.expired_badge')])
                            @elseif($daysLeft !== null && $daysLeft <= $windowDays)
                                @include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => __('pharmacy_ops.expiring_badge', ['days' => $daysLeft])])
                            @else
                                @include('portals.pharmacy.stock_ops._pill', ['tone' => $b->status === 'active' ? 'ok' : 'neutral', 'label' => __('pharmacy_ops.batch_status.' . $b->status)])
                            @endif
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_received') }}"><span class="os-cell__sub">{{ $b->created_at?->translatedFormat('d M Y') }}</span></td>
                        <td></td>
                    </tr>
                @endforeach
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 8, 'icon' => 'layers',
                    'text' => __('pharmacy_ops.batches_empty'),
                    'href' => route('portals.pharmacy.stock.receipts.create'), 'btnIcon' => 'truck', 'btn' => __('pharmacy_ops.btn_new_receipt')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

</div>
@endsection
