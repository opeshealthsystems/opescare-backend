@extends('layouts.portal')
@section('title', __('pharmacy_ops.adj_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@php
    // Reason → dot colour on the filter pills (state colours only).
    $reasonTone = fn (string $r) => match ($r) {
        'received', 'returned' => 'ok',
        'damaged', 'expired'   => 'low',
        'dispensed'            => 'info',
        'count_correction'     => 'reorder',
        default                => 'neutral',
    };
@endphp

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_ops.adj_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_ops.adj_subtitle') }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.nav_adjustments') }} <span class="od-card__count">({{ $movements->total() }})</span></h2>
        @if(! $batches->isEmpty())
        <a href="#adj-form" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_record') }}</a>
        @endif
    </div>

    <nav class="od-tabs os-filter" aria-label="{{ __('pharmacy_ops.lbl_reason') }}">
        <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="od-tab{{ $filter === '' ? ' is-active' : '' }}">{{ __('pharmacy_ops.filter_all') }}@if($filter === '') <span class="od-tab__count">({{ $movements->total() }})</span>@endif</a>
        @foreach($reasons as $r)
        <a href="{{ route('portals.pharmacy.stock.adjustments', ['reason' => $r]) }}" class="od-tab{{ $filter === $r ? ' is-active' : '' }}"><span class="od-tab__dot os-dot--{{ $reasonTone($r) }}"></span>{{ __('pharmacy_ops.reason.' . $r) }}@if($filter === $r) <span class="od-tab__count">({{ $movements->total() }})</span>@endif</a>
        @endforeach
    </nav>

    <p class="os-note"><i data-lucide="lock"></i> {{ __('pharmacy_ops.adj_append_only') }}</p>

    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_all') }}"></th>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_when') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_change') }}</th>
                <th>{{ __('pharmacy_ops.col_reason') }}</th>
                <th>{{ __('pharmacy_ops.col_by') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($movements as $m)
                @php
                    [$code, $note] = \App\Services\Pharmacy\PharmacyStockOpsService::decodeReason($m->reason);
                    $mName = $m->item?->medicine?->name ?? $m->item?->name ?? '—';
                    $when  = $m->performed_at ?? $m->created_at;
                @endphp
                <tr>
                    <td class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_row') }}"></td>
                    <td class="os-col-num">{{ ($movements->firstItem() ?? 1) + $loop->index }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_when') }}">
                        <span class="os-cell__main">{{ $when?->translatedFormat('H:i') }}</span>
                        <span class="os-cell__sub">{{ $when?->translatedFormat('d M Y') }}</span>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $m->quantity >= 0 ? 'green' : 'red'])
                            <div class="os-cell__text">
                                @if($m->inventory_item_id)
                                    <a href="{{ route('portals.pharmacy.stock.items.show', $m->inventory_item_id) }}" class="os-cell__title">{{ $mName }}</a>
                                @else
                                    <span class="os-cell__title">{{ $mName }}</span>
                                @endif
                                <span class="os-cell__sub">{{ __('pharmacy_stock_desk.movement_type.' . $m->movement_type) }}@if($note) · {{ $note }}@endif</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="os-lot">{{ $m->batch?->lot_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_change') }}" class="{{ $m->quantity >= 0 ? 'os-in' : 'os-out' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_reason') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => match($reasonTone($code)) { 'ok' => 'ok', 'low' => 'low', 'info' => 'info', 'reorder' => 'reorder', default => 'neutral' }, 'label' => __('pharmacy_ops.reason.' . $code)])</td>
                    <td data-label="{{ __('pharmacy_ops.col_by') }}"><span class="os-cell__sub">{{ $actors[$m->performed_by] ?? '—' }}</span></td>
                    <td class="os-col-act">
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.adjustments.show', $m->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => array_values(array_filter([
                                $m->inventory_item_id ? [route('portals.pharmacy.stock.items.show', $m->inventory_item_id), __('pharmacy_stock_desk.link_ledger'), 'book-open'] : null,
                                $m->inventory_item_id ? [route('portals.pharmacy.stock.items.show', $m->inventory_item_id) . '#adjust', __('pharmacy_stock_desk.btn_adjust'), 'arrow-left-right'] : null,
                                [route('portals.pharmacy.stock.adjustments.show', $m->id), __('pharmacy_stock_desk.adj_facts'), 'file-text'],
                            ]))])
                        </div>
                    </td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 9, 'icon' => 'arrow-left-right',
                    'text' => __('pharmacy_ops.adj_empty'),
                    'href' => $batches->isEmpty() ? null : '#adj-form', 'btnIcon' => 'plus', 'btn' => __('pharmacy_ops.btn_record')])
            @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())<div class="os-card__foot">{{ $movements->links() }}</div>@endif
</section>

<details class="od-card os-card os-fold" id="adj-form" @if($errors->any() || old('batch_id')) open @endif>
    <summary class="od-card__head os-fold__head">
        <h2 class="od-card__title"><span class="os-fold__icon"><i data-lucide="plus"></i></span>{{ __('pharmacy_ops.adj_form_title') }}</h2>
        <i data-lucide="chevron-down" class="os-fold__chev"></i>
    </summary>
    <div class="os-card__body">
        @if($batches->isEmpty())
            <p class="os-muted">{{ __('pharmacy_ops.batches_empty') }}</p>
        @else
        <form method="POST" action="{{ route('portals.pharmacy.stock.adjustments.store') }}" class="os-form">
            @csrf
            <div class="os-grid os-grid--2">
                <div class="os-field">
                    <label class="os-label" for="mv-batch">{{ __('pharmacy_ops.lbl_batch') }}</label>
                    <select name="batch_id" id="mv-batch" class="os-input" required>
                        <option value="">{{ __('pharmacy_ops.choose_batch') }}</option>
                        @foreach($batches as $b)
                        <option value="{{ $b->id }}" {{ old('batch_id') === $b->id ? 'selected' : '' }}>
                            {{ __('pharmacy_ops.lot_option', [
                                'medicine' => $b->item?->medicine?->name ?? $b->item?->name,
                                'lot'      => $b->lot_number ?: __('pharmacy_ops.lot_none'),
                                'expiry'   => $b->expiry_date?->format('Y-m-d') ?? __('pharmacy_ops.expiry_unknown'),
                                'packs'    => $b->quantity_available,
                            ]) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="os-field">
                    <label class="os-label" for="mv-reason">{{ __('pharmacy_ops.lbl_reason') }}</label>
                    <select name="reason" id="mv-reason" class="os-input" required>
                        @foreach($reasons as $r)
                        <option value="{{ $r }}" {{ old('reason') === $r ? 'selected' : '' }}>{{ __('pharmacy_ops.reason.' . $r) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="os-grid os-grid--2">
                <div class="os-field">
                    <label class="os-label" for="mv-qty">{{ __('pharmacy_ops.lbl_quantity') }}</label>
                    <input type="number" name="quantity" id="mv-qty" class="os-input" min="-1000000" max="1000000" step="1" inputmode="numeric" value="{{ old('quantity') }}" required>
                    <span class="os-hint">{{ __('pharmacy_ops.lbl_quantity_hint') }}</span>
                </div>
                <div class="os-field">
                    <label class="os-label" for="mv-note">{{ __('pharmacy_ops.lbl_note') }}</label>
                    <input type="text" name="note" id="mv-note" class="os-input" maxlength="500" value="{{ old('note') }}">
                </div>
            </div>
            <div class="os-form__actions">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> {{ __('pharmacy_ops.btn_record') }}</button>
            </div>
        </form>
        @endif
    </div>
</details>

</div>
@endsection
