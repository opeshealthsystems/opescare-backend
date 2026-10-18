@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipt_title', ['number' => $receipt->receipt_number]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_receipts'))

@section('content')

<div class="page-head">
    <h2 class="fs-mono">{{ __('pharmacy_ops.receipt_title', ['number' => $receipt->receipt_number]) }}</h2>
    <p class="page-subtitle">
        {{ __('pharmacy_ops.lbl_received_date') }} {{ $receipt->received_date?->translatedFormat('d M Y') }}
        · {{ $receipt->supplier?->name ?? __('pharmacy_ops.lbl_no_supplier') }}
        @if($receipt->purchaseOrder) · {{ __('pharmacy_ops.lbl_order') }} {{ $receipt->purchaseOrder->po_number ?? $receipt->purchaseOrder->id }} @endif
    </p>
    <div class="page-head__spacer"></div>
    <span class="badge {{ $receipt->status === 'verified' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_ops.receipt_status.' . $receipt->status) }}</span>
    <a href="{{ route('portals.pharmacy.stock.receipts') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> {{ __('pharmacy_ops.back_to_receipts') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

@if($receipt->notes)
<div class="fs-note"><i data-lucide="sticky-note"></i><div>{{ $receipt->notes }}</div></div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="package-plus"></i> {{ __('pharmacy_ops.lines_title') }} <span class="ph-count-inline">({{ $receipt->items->count() }})</span></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_line_qty') }}</th>
                <th>{{ __('pharmacy_ops.col_line_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_line_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_line_cost') }}</th>
                <th>{{ __('pharmacy_ops.col_batch_created') }}</th>
            </tr></thead>
            <tbody>
            @foreach($receipt->items as $line)
                @php $batch = $batches->get($line->inventory_item_id); $lName = $line->item?->medicine?->name ?? $line->item?->name; @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $lName, 'tone' => $batch ? 'green' : ''])
                            <div>
                                <div class="ph-cell__title">{{ $lName }}</div>
                                <div class="ph-cell__sub">{{ $line->item?->medicine?->strength }} {{ $line->item?->medicine?->form }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_line_qty') }}" class="td-num">{{ $line->quantity_received }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_line_lot') }}" class="fs-mono">{{ $line->batch_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_line_expiry') }}">
                        @if($line->expiry_date) {{ $line->expiry_date->translatedFormat('d M Y') }} @else <span class="badge badge-neutral">{{ __('pharmacy_ops.expiry_unknown') }}</span> @endif
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_line_cost') }}" class="fs-mono">{{ $line->unit_cost !== null ? number_format((int) $line->unit_cost, 0, ',', ' ') . ' ' . __('pharmacy_ops.xaf') : '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_batch_created') }}">
                        @if($batch) <span class="badge badge-success">{{ __('pharmacy_ops.batch_status.' . $batch->status) }}</span> @else — @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
