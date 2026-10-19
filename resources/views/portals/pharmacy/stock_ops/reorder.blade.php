@extends('layouts.portal')
@section('title', __('pharmacy_ops.reorder_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@php
    $withRule = $items->filter(fn ($i) => $i->reorderRule->first() !== null)->count();
    $outCount = $below->filter(fn ($r) => (int) $r->usable_quantity <= 0)->count();
@endphp

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_ops.reorder_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_ops.reorder_subtitle') }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<div class="os-kpis">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => $below->isEmpty() ? 'green' : 'amber', 'icon' => 'trending-down',
        'label' => __('pharmacy_ops.below_title'), 'value' => $below->count(), 'sub' => __('pharmacy_ops.no_auto_order')])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'red', 'icon' => 'circle-slash',
        'label' => __('pharmacy_stock_desk.kpi_out'), 'value' => $outCount, 'sub' => __('pharmacy_ops.below_title')])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'list-checks',
        'label' => __('pharmacy_ops.rules_title'), 'value' => $withRule, 'sub' => __('pharmacy_stock_desk.kpi_rules_sub')])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'teal', 'icon' => 'pack',
        'label' => __('pharmacy_stock_desk.all_items'), 'value' => $items->count(), 'sub' => __('pharmacy_ops.nav_batches')])
</div>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.below_title') }} <span class="od-card__count">({{ $below->count() }})</span></h2>
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="od-btn od-btn--primary"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_all') }}"></th>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_usable') }}</th>
                <th>{{ __('pharmacy_ops.col_minimum') }}</th>
                <th>{{ __('pharmacy_ops.col_suggested') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($below as $row)
                @php $rName = $row->medicine?->name ?? $row->name; $isOut = (int) $row->usable_quantity <= 0; @endphp
                <tr>
                    <td class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_row') }}"></td>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $isOut ? 'red' : 'amber'])
                            <div class="os-cell__text">
                                <a href="{{ route('portals.pharmacy.stock.items.show', $row->id) }}" class="os-cell__title">{{ $rName }}</a>
                                <span class="os-cell__sub">{{ $row->medicine?->strength }} {{ $row->medicine?->form }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_usable') }}" class="os-out">{{ (int) $row->usable_quantity }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_minimum') }}" class="os-num">{{ (int) $row->rule_level }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_suggested') }}" class="os-num">{{ (int) $row->rule_quantity ?: '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">
                        @if($isOut)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'low', 'label' => __('pharmacy_stock_desk.pill_low')])
                        @else
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => __('pharmacy_stock_desk.pill_reorder')])
                        @endif
                    </td>
                    <td class="os-col-act">
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $row->medicine_id]) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.row_receive') }}</a>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => [
                                [route('portals.pharmacy.stock.items.show', $row->id), __('pharmacy_stock_desk.link_ledger'), 'book-open'],
                                [route('portals.pharmacy.stock.items.show', $row->id) . '#reorder', __('pharmacy_stock_desk.btn_reorder_rule'), 'trending-down'],
                            ]])
                        </div>
                    </td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 8, 'icon' => 'check-circle-2', 'text' => __('pharmacy_ops.below_empty')])
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="os-note"><i data-lucide="info"></i> {{ __('pharmacy_ops.no_auto_order') }}</p>
</section>

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.rules_title') }} <span class="od-card__count">({{ $items->count() }})</span></h2>
        <a href="#rule-form" class="od-btn od-btn--secondary"><i data-lucide="plus"></i> {{ __('pharmacy_ops.rule_form_title') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_usable') }}</th>
                <th>{{ __('pharmacy_ops.col_minimum') }}</th>
                <th>{{ __('pharmacy_ops.col_suggested') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php
                    $rule  = $item->reorderRule->first();
                    $iName = $item->medicine?->name ?? $item->name;
                    $iUse  = $usable[$item->id] ?? 0;
                    $under = $rule && $iUse < $rule->reorder_level;
                @endphp
                <tr>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $iUse <= 0 ? 'red' : ($under ? 'amber' : ($rule ? 'green' : 'grey'))])
                            <div class="os-cell__text">
                                <a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}" class="os-cell__title">{{ $iName }}</a>
                                <span class="os-cell__sub">{{ $item->medicine?->strength }} {{ $item->medicine?->form }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_usable') }}" class="os-num">{{ $iUse }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_minimum') }}" class="os-num">{{ $rule ? $rule->reorder_level : '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_suggested') }}" class="os-num">{{ $rule && $rule->reorder_quantity ? $rule->reorder_quantity : '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">
                        @if(! $rule)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.no_rule')])
                        @elseif($iUse <= 0)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'low', 'label' => __('pharmacy_stock_desk.pill_low')])
                        @elseif($under)
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => __('pharmacy_stock_desk.pill_reorder')])
                        @else
                            @include('portals.pharmacy.stock_ops._pill', ['tone' => 'ok', 'label' => __('pharmacy_stock_desk.pill_in_stock')])
                        @endif
                    </td>
                    <td class="os-col-act">
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}#reorder" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => array_values(array_filter([
                                [route('portals.pharmacy.stock.items.show', $item->id), __('pharmacy_stock_desk.link_ledger'), 'book-open'],
                                $item->medicine_id ? [route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $item->medicine_id]), __('pharmacy_stock_desk.btn_receive'), 'truck'] : null,
                            ]))])
                        </div>
                    </td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 7, 'icon' => 'list', 'text' => __('pharmacy_ops.rules_empty'),
                    'href' => '#rule-form', 'btnIcon' => 'plus', 'btn' => __('pharmacy_ops.rule_form_title')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<details class="od-card os-card os-fold" id="rule-form" @if($errors->any() || old('medicine_id')) open @endif>
    <summary class="od-card__head os-fold__head">
        <h2 class="od-card__title"><span class="os-fold__icon"><i data-lucide="plus"></i></span>{{ __('pharmacy_ops.rule_form_title') }}</h2>
        <i data-lucide="chevron-down" class="os-fold__chev"></i>
    </summary>
    <div class="os-card__body">
        <form method="POST" action="{{ route('portals.pharmacy.stock.reorder.store') }}" class="os-form">
            @csrf
            <div class="os-grid os-grid--3">
                <div class="os-field">
                    <label class="os-label" for="rr-medicine">{{ __('pharmacy_ops.lbl_medicine') }}</label>
                    <select name="medicine_id" id="rr-medicine" class="os-input" required>
                        <option value="">{{ __('pharmacy_ops.choose_medicine') }}</option>
                        @foreach($catalog as $m)
                        <option value="{{ $m->id }}" {{ old('medicine_id') === $m->id ? 'selected' : '' }}>{{ $m->name }}@if($m->form) · {{ $m->form }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="rr-level">{{ __('pharmacy_ops.lbl_min_level') }}</label>
                    <input type="number" name="reorder_level" id="rr-level" class="os-input" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_level') }}" required>
                </div>
                <div class="os-field">
                    <label class="os-label" for="rr-qty">{{ __('pharmacy_ops.lbl_suggest_qty') }}</label>
                    <input type="number" name="reorder_quantity" id="rr-qty" class="os-input" min="0" max="1000000" inputmode="numeric" value="{{ old('reorder_quantity') }}">
                </div>
            </div>
            <div class="os-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_save_rule') }}</button>
            </div>
        </form>
    </div>
</details>

</div>
@endsection
