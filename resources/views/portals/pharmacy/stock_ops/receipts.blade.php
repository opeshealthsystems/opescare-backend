@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipts_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_receipts'))

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.receipts_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.receipts_subtitle') }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

<div class="panel">
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_number') }}</th>
                <th>{{ __('pharmacy_ops.col_date') }}</th>
                <th>{{ __('pharmacy_ops.col_supplier') }}</th>
                <th>{{ __('pharmacy_ops.col_lines') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
            </tr></thead>
            <tbody>
            @forelse($receipts as $r)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_number') }}"><a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="td-strong fs-mono">{{ $r->receipt_number ?? '—' }}</a></td>
                    <td data-label="{{ __('pharmacy_ops.col_date') }}">{{ $r->received_date?->translatedFormat('d M Y') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_supplier') }}">{{ $r->supplier?->name ?? __('pharmacy_ops.lbl_no_supplier') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_lines') }}" class="fs-mono">{{ $r->items_count }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}"><span class="badge {{ $r->status === 'verified' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_ops.receipt_status.' . $r->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="td-muted empty-cell">{{ __('pharmacy_ops.receipts_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $receipts->links() }}</div>
</div>

@endsection
