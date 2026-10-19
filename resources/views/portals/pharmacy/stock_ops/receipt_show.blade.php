@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipt_title', ['number' => $receipt->receipt_number]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_ops.receipt_title', ['number' => $receipt->receipt_number]) }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_ops.receipts_subtitle') }}</p>
    </div>
    <div class="os-head__actions">
        <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_ops.back_to_receipts') }}</a>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')

<div class="os-split">
<div class="os-stack">
<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.lines_title') }} <span class="od-card__count">({{ $receipt->items->count() }})</span></h2>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_line_qty') }}</th>
                <th>{{ __('pharmacy_ops.col_line_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_line_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_line_cost') }}</th>
                <th>{{ __('pharmacy_ops.col_batch_created') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($receipt->items as $line)
                @php $batch = $batches->get($line->inventory_item_id); $lName = $line->item?->medicine?->name ?? $line->item?->name; @endphp
                <tr>
                    <td class="os-col-num">{{ $loop->iteration }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['pack' => true, 'tone' => $batch ? 'green' : 'grey'])
                            <div class="os-cell__text">
                                <span class="os-cell__title">{{ $lName }}</span>
                                <span class="os-cell__sub">{{ $line->item?->medicine?->strength }} {{ $line->item?->medicine?->form }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_line_qty') }}" class="os-in">+{{ $line->quantity_received }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_line_lot') }}" class="os-lot">{{ $line->batch_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_line_expiry') }}">
                        @if($line->expiry_date) {{ $line->expiry_date->translatedFormat('d M Y') }} @else @include('portals.pharmacy.stock_ops._pill', ['tone' => 'neutral', 'label' => __('pharmacy_ops.expiry_unknown')]) @endif
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_line_cost') }}" class="os-num">{{ $line->unit_cost !== null ? number_format((int) $line->unit_cost, 0, ',', ' ') . ' ' . __('pharmacy_ops.xaf') : '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_batch_created') }}">
                        @if($batch) @include('portals.pharmacy.stock_ops._pill', ['tone' => 'ok', 'label' => __('pharmacy_ops.batch_status.' . $batch->status)]) @else — @endif
                    </td>
                    <td class="os-col-act">
                        @if($line->inventory_item_id)
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.items.show', $line->inventory_item_id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                        </div>
                        @endif
                    </td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 8, 'icon' => 'package-plus', 'text' => __('pharmacy_ops.receipts_empty')])
            @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>

<aside class="od-card os-card os-detail od-detail" aria-label="{{ __('pharmacy_stock_desk.receipt_details') }}">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_stock_desk.receipt_details') }}</h2>
        @include('portals.pharmacy.stock_ops._pill', ['tone' => $receipt->status === 'verified' ? 'ok' : 'neutral', 'label' => __('pharmacy_ops.receipt_status.' . $receipt->status)])
    </div>
    <div class="os-detail__who">
        @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'truck', 'tone' => $receipt->status === 'verified' ? 'green' : 'blue', 'big' => true])
        <div>
            <div class="os-detail__name">{{ $receipt->receipt_number ?? '—' }}</div>
            <div class="os-detail__ref">{{ __('pharmacy_ops.lbl_received_date') }} {{ $receipt->received_date?->translatedFormat('d M Y') }}</div>
        </div>
    </div>
    <div class="os-infos">
        <div class="os-info">
            <span class="os-info__icon"><i data-lucide="building-2"></i></span>
            <div>
                <div class="os-info__k">{{ __('pharmacy_ops.lbl_supplier') }}</div>
                <div class="os-info__v">
                    @if($receipt->supplier)
                        <a href="{{ route('portals.pharmacy.stock.suppliers.show', $receipt->supplier->id) }}">{{ $receipt->supplier->name }}</a>
                    @else
                        {{ __('pharmacy_ops.lbl_no_supplier') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="os-info">
            <span class="os-info__icon"><i data-lucide="clipboard-list"></i></span>
            <div>
                <div class="os-info__k">{{ __('pharmacy_ops.lbl_order') }}</div>
                <div class="os-info__v">{{ $receipt->purchaseOrder ? ($receipt->purchaseOrder->po_number ?? $receipt->purchaseOrder->id) : __('pharmacy_ops.lbl_no_order') }}</div>
            </div>
        </div>
        <div class="os-info">
            <span class="os-info__icon os-info__icon--green"><i data-lucide="package-plus"></i></span>
            <div>
                <div class="os-info__k">{{ __('pharmacy_stock_desk.kpi_packs') }}</div>
                <div class="os-info__v">{{ (int) $receipt->items->sum('quantity_received') }}</div>
                <div class="os-info__note">{{ __('pharmacy_stock_desk.kpi_lines') }}: {{ $receipt->items->count() }}</div>
            </div>
        </div>
        @if($receipt->notes)
        <div class="os-info">
            <span class="os-info__icon os-info__icon--grey"><i data-lucide="sticky-note"></i></span>
            <div>
                <div class="os-info__k">{{ __('pharmacy_ops.lbl_notes') }}</div>
                <div class="os-info__note">{{ $receipt->notes }}</div>
            </div>
        </div>
        @endif
    </div>
    <div class="os-actions">
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="od-btn od-btn--primary"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
        <a href="{{ route('portals.pharmacy.stock.batches') }}" class="od-btn od-btn--secondary"><i data-lucide="layers"></i> {{ __('pharmacy_ops.nav_batches') }}</a>
        <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="od-btn od-btn--secondary"><i data-lucide="list"></i> {{ __('pharmacy_ops.nav_receipts') }}</a>
    </div>
</aside>
</div>

</div>
@endsection
