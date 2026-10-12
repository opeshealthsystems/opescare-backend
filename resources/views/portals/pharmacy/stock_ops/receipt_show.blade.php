@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipt_title', ['number' => $receipt->receipt_number]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_receipts'))

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

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
                @php $batch = $batches->get($line->inventory_item_id); @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}" class="td-strong">{{ $line->item?->medicine?->name ?? $line->item?->name }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_line_qty') }}" class="fs-mono">{{ $line->quantity_received }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_line_lot') }}" class="fs-mono">{{ $line->batch_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_line_expiry') }}">
                        @if($line->expiry_date) {{ $line->expiry_date->translatedFormat('d M Y') }} @else <span class="badge badge-neutral">{{ __('pharmacy_ops.expiry_unknown') }}</span> @endif
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_line_cost') }}" class="fs-mono">{{ $line->unit_cost !== null ? number_format((int) $line->unit_cost, 0, ',', ' ') . ' ' . __('pharmacy_ops.xaf') : '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_batch_created') }}">
                        @if($batch) <span class="badge badge-success"><i data-lucide="check"></i> {{ __('pharmacy_ops.batch_status.' . $batch->status) }}</span> @else — @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
