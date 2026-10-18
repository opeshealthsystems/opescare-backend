@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipts_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_receipts'))

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.receipts_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.receipts_subtitle') }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="truck"></i> {{ __('pharmacy_ops.nav_receipts') }} <span class="ph-count-inline">({{ $receipts->total() }})</span></h3>
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_number') }}</th>
                <th>{{ __('pharmacy_ops.col_date') }}</th>
                <th>{{ __('pharmacy_ops.col_supplier') }}</th>
                <th>{{ __('pharmacy_ops.col_lines') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($receipts as $r)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_number') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'truck', 'tone' => $r->status === 'verified' ? 'green' : ''])
                            <div>
                                <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="fs-mono">{{ $r->receipt_number ?? '—' }}</a></div>
                                <div class="ph-cell__sub">{{ __('pharmacy_ops.sum_lots', ['count' => $r->items_count]) }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_date') }}">{{ $r->received_date?->translatedFormat('d M Y') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_supplier') }}">{{ $r->supplier?->name ?? __('pharmacy_ops.lbl_no_supplier') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_lines') }}" class="td-num">{{ $r->items_count }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}"><span class="badge {{ $r->status === 'verified' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_ops.receipt_status.' . $r->status) }}</span></td>
                    <td class="row-actions"><a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="truck"></i>
                        <p>{{ __('pharmacy_ops.receipts_empty') }}</p>
                        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $receipts->links() }}</div>
</div>

@endsection
