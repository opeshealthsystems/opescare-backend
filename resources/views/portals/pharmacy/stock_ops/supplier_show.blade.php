@extends('layouts.portal')
@section('title', $supplier->name)
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@php
    $stTone = match($supplier->status) { 'active' => 'ok', 'blacklisted' => 'low', default => 'neutral' };
@endphp

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ $supplier->name }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_stock_desk.supplier_subtitle') }}</p>
    </div>
    <div class="os-head__actions">
        <a href="{{ route('portals.pharmacy.stock.suppliers') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_stock_desk.back_to_suppliers') }}</a>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<div class="os-kpis os-kpis--3">
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'blue', 'icon' => 'truck',
        'label' => __('pharmacy_stock_desk.col_deliveries'), 'value' => $deliveries->count(),
        'sub' => $deliveries->first()?->received_date?->translatedFormat('d M Y') ?? __('pharmacy_stock_desk.no_delivery_yet')])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => $openOrders->isEmpty() ? 'green' : 'amber', 'icon' => 'clipboard-list',
        'label' => __('pharmacy_stock_desk.col_open_orders'), 'value' => $openOrders->count()])
    @include('portals.pharmacy.stock_ops._kpi', ['tone' => 'teal', 'icon' => 'pack',
        'label' => __('pharmacy_stock_desk.items_supplied'), 'value' => $items->count()])
</div>

<div class="os-split">
<div class="os-stack">

<section class="od-card os-card" id="deliveries">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.deliveries_title') }} <span class="od-card__count">({{ $deliveries->count() }})</span></h2>
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="od-btn od-btn--primary"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_number') }}</th>
                <th>{{ __('pharmacy_ops.col_date') }}</th>
                <th>{{ __('pharmacy_ops.col_lines') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($deliveries as $r)
                <tr>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_number') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'truck', 'tone' => $r->status === 'verified' ? 'green' : 'blue'])
                            <div class="os-cell__text"><a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="os-cell__title">{{ $r->receipt_number ?? '—' }}</a></div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_date') }}">{{ $r->received_date?->translatedFormat('d M Y') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_lines') }}" class="os-num">{{ $r->items_count }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => $r->status === 'verified' ? 'ok' : 'neutral', 'label' => __('pharmacy_ops.receipt_status.' . $r->status)])</td>
                    <td class="os-col-act"><div class="os-rowact"><a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a></div></td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 6, 'icon' => 'truck', 'text' => __('pharmacy_stock_desk.deliveries_empty'),
                    'href' => route('portals.pharmacy.stock.receipts.create'), 'btnIcon' => 'plus', 'btn' => __('pharmacy_ops.btn_new_receipt')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="od-card os-card" id="items">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.items_supplied') }} <span class="od-card__count">({{ $items->count() }})</span></h2>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_last_delivery') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($items as $row)
                @php $rName = $row->item->medicine?->name ?? $row->item->name; @endphp
                <tr>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => 'blue'])
                            <div class="os-cell__text">
                                <a href="{{ route('portals.pharmacy.stock.items.show', $row->item->id) }}" class="os-cell__title">{{ $rName }}</a>
                                <span class="os-cell__sub">{{ $row->item->medicine?->strength }} {{ $row->item->medicine?->form }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="os-num">{{ $row->packs }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_last_delivery') }}">{{ $row->last_delivery?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td class="os-col-act"><div class="os-rowact"><a href="{{ route('portals.pharmacy.stock.items.show', $row->item->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a></div></td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 5, 'icon' => 'package', 'text' => __('pharmacy_stock_desk.items_supplied_empty')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="od-card os-card" id="orders">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.open_orders_title') }} <span class="od-card__count">({{ $openOrders->count() }})</span></h2>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_number') }}</th>
                <th>{{ __('pharmacy_ops.col_date') }}</th>
                <th>{{ __('pharmacy_stock_desk.col_expected') }}</th>
                <th>{{ __('pharmacy_ops.col_lines') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
            </tr></thead>
            <tbody>
            @forelse($openOrders as $o)
                <tr>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_number') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'clipboard-list', 'tone' => 'amber'])
                            <div class="os-cell__text"><span class="os-cell__title">{{ $o->po_number ?? '—' }}</span></div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_date') }}">{{ $o->order_date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_stock_desk.col_expected') }}">{{ $o->expected_delivery_date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_lines') }}" class="os-num">{{ $o->items_count }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => 'reorder', 'label' => __('pharmacy_stock_desk.order_status.' . $o->status)])</td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 6, 'icon' => 'clipboard-check', 'text' => __('pharmacy_stock_desk.open_orders_empty')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>

</div>

{{-- ── Contact card + edit (right-hand detail panel) ─────────────────── --}}
<aside class="od-card os-card os-detail od-detail" id="contact" aria-label="{{ __('pharmacy_stock_desk.supplier_details') }}">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.supplier_details') }}</h2>
        @include('portals.pharmacy.stock_ops._pill', ['tone' => $stTone, 'label' => __('pharmacy_stock_desk.supplier_status.' . $supplier->status)])
    </div>
    <div class="os-detail__who">
        @include('portals.pharmacy.stock_ops._avatar', ['name' => $supplier->name, 'tone' => $supplier->status === 'active' ? 'blue' : 'grey', 'big' => true])
        <div>
            <div class="os-detail__name">{{ $supplier->name }}</div>
            @if($supplier->contact_person)<div class="os-detail__ref">{{ $supplier->contact_person }}</div>@endif
            <div class="os-facts">
                @if($supplier->phone)<a href="tel:{{ $supplier->phone }}"><i data-lucide="phone"></i> {{ $supplier->phone }}</a>@endif
                @if($supplier->email)<span><i data-lucide="mail"></i> {{ $supplier->email }}</span>@endif
                @if($supplier->address)<span><i data-lucide="map-pin"></i> {{ $supplier->address }}</span>@endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('portals.pharmacy.stock.suppliers.update', $supplier->id) }}" class="os-form">
        @csrf
        <div class="os-grid os-grid--2">
            <div class="os-field">
                <label class="os-label" for="su-name">{{ __('pharmacy_ops.lbl_name') }}</label>
                <input type="text" name="name" id="su-name" class="os-input" maxlength="150" value="{{ old('name', $supplier->name) }}" required>
            </div>
            <div class="os-field">
                <label class="os-label" for="su-contact">{{ __('pharmacy_ops.lbl_contact') }}</label>
                <input type="text" name="contact_person" id="su-contact" class="os-input" maxlength="100" value="{{ old('contact_person', $supplier->contact_person) }}">
            </div>
            <div class="os-field">
                <label class="os-label" for="su-phone">{{ __('pharmacy_ops.lbl_phone') }}</label>
                <input type="text" name="phone" id="su-phone" class="os-input" maxlength="30" inputmode="tel" value="{{ old('phone', $supplier->phone) }}">
            </div>
            <div class="os-field">
                <label class="os-label" for="su-email">{{ __('pharmacy_ops.lbl_email') }}</label>
                <input type="email" name="email" id="su-email" class="os-input" maxlength="100" value="{{ old('email', $supplier->email) }}">
            </div>
            <div class="os-field">
                <label class="os-label" for="su-address">{{ __('pharmacy_stock_desk.lbl_address') }}</label>
                <input type="text" name="address" id="su-address" class="os-input" maxlength="500" value="{{ old('address', $supplier->address) }}">
            </div>
            <div class="os-field">
                <label class="os-label" for="su-status">{{ __('pharmacy_ops.col_status') }}</label>
                <select name="status" id="su-status" class="os-input">
                    @foreach($statuses as $st)
                    <option value="{{ $st }}" {{ old('status', $supplier->status) === $st ? 'selected' : '' }}>{{ __('pharmacy_stock_desk.supplier_status.' . $st) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="os-field">
            <label class="os-label" for="su-notes">{{ __('pharmacy_ops.lbl_notes') }}</label>
            <input type="text" name="notes" id="su-notes" class="os-input" maxlength="1000" value="{{ old('notes', $supplier->notes) }}">
        </div>
        <div class="os-form__actions">
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> {{ __('pharmacy_stock_desk.btn_save_supplier') }}</button>
        </div>
    </form>
</aside>

</div>

</div>
@endsection
