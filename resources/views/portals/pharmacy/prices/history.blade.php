@extends('layouts.portal')
@section('title', __('pharmacy_prices.history_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')

@php $name = $item->medicine?->name ?? $item->name; @endphp

<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_prices.history_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_prices.history_subtitle', ['name' => $name]) }}</p>
    </div>
    <div class="os-head__actions">
        <a href="{{ route('portals.pharmacy.prices.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_prices.btn_back') }}</a>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')

<div class="os-kpis os-kpis--2">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'coins',
        'label' => __('pharmacy_prices.col_price'),
        'value' => $item->selling_price_xaf !== null ? number_format($item->selling_price_xaf, 0, ',', ' ') . ' FCFA' : '—',
        'sub' => $name])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'teal', 'icon' => 'history',
        'label' => __('pharmacy_prices.sum_changes'), 'value' => $changes->total()])
</div>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_prices.col_history') }} <span class="od-card__count">{{ __('pharmacy_desk.count_paren', ['count' => $changes->total()]) }}</span></h2>
        <a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}" class="os-link">{{ __('pharmacy_stock_desk.link_ledger') }}</a>
    </div>
    <p class="os-note"><i data-lucide="lock"></i> {{ __('pharmacy_prices.history_append_only') }}</p>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_prices.col_when') }}</th>
                <th>{{ __('pharmacy_prices.col_old') }}</th>
                <th>{{ __('pharmacy_prices.col_new') }}</th>
                <th>{{ __('pharmacy_prices.col_by') }}</th>
                <th>{{ __('pharmacy_prices.lbl_reason') }}</th>
            </tr></thead>
            <tbody>
            @forelse($changes as $c)
                @php $delta = $c->old_price_xaf === null ? null : $c->new_price_xaf - $c->old_price_xaf; $who = $actors[$c->changed_by] ?? __('pharmacy_prices.actor_unknown'); @endphp
                <tr>
                    <td class="os-col-num">{{ ($changes->firstItem() ?? 1) + $loop->index }}</td>
                    <td data-label="{{ __('pharmacy_prices.col_when') }}">
                        <span class="os-cell__main">{{ $c->created_at?->translatedFormat('d M Y') }}</span>
                        <span class="os-cell__sub">{{ $c->created_at?->translatedFormat('H:i') }}</span>
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_old') }}" class="os-num">
                        @if($c->old_price_xaf === null)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_prices.no_price_yet')])
                        @else
                            {{ number_format($c->old_price_xaf, 0, ',', ' ') }} FCFA
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_new') }}" class="os-num">
                        {{ number_format($c->new_price_xaf, 0, ',', ' ') }} FCFA
                        @if($delta !== null && $delta !== 0)
                            <span class="os-cell__sub {{ $delta > 0 ? 'os-out' : 'os-in' }}">{{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0, ',', ' ') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_by') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $who, 'tone' => 'blue'])
                            <div class="os-cell__text"><span class="os-cell__title">{{ $who }}</span></div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_prices.lbl_reason') }}"><span class="os-cell__sub">{{ $c->reason ?? '—' }}</span></td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 6, 'icon' => 'history', 'text' => __('pharmacy_prices.history_empty')])
            @endforelse
            </tbody>
        </table>
    </div>
    @if($changes->hasPages())<div class="os-card__foot">{{ $changes->links() }}</div>@endif
</section>

</div>
@endsection
