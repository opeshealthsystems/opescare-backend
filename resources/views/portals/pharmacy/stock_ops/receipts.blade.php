@extends('layouts.portal')
@section('title', __('pharmacy_ops.receipts_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_receipts'))
@section('head')@include('portals.pharmacy.stock_ops._styles')@endsection

@section('content')
<div class="os-page">

<header class="os-head">
    <div class="os-head__text">
        <h1 class="os-head__title">{{ __('pharmacy_ops.receipts_title') }}</h1>
        <p class="os-head__sub">{{ __('pharmacy_ops.receipts_subtitle') }}</p>
    </div>
</header>

@include('portals.pharmacy.stock_ops._nav')
@include('portals.pharmacy.stock_ops._alerts')

<section class="od-card os-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pharmacy_ops.nav_receipts') }} <span class="od-card__count">({{ $receipts->total() }})</span></h2>
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
    </div>
    <div class="os-table-wrap">
        <table class="od-table os-table">
            <thead><tr>
                <th class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_all') }}"></th>
                <th class="os-col-num">{{ __('pharmacy_stock_desk.col_num') }}</th>
                <th>{{ __('pharmacy_ops.col_number') }}</th>
                <th>{{ __('pharmacy_ops.col_date') }}</th>
                <th>{{ __('pharmacy_ops.col_supplier') }}</th>
                <th>{{ __('pharmacy_ops.col_lines') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th class="os-col-act">{{ __('public.pharmacy_portal.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($receipts as $r)
                <tr>
                    <td class="os-col-check"><input type="checkbox" class="os-check" aria-label="{{ __('pharmacy_stock_desk.select_row') }}"></td>
                    <td class="os-col-num">{{ ($receipts->firstItem() ?? 1) + $loop->index }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_number') }}">
                        <div class="os-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['icon' => 'truck', 'tone' => $r->status === 'verified' ? 'green' : 'blue'])
                            <div class="os-cell__text">
                                <a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="os-cell__title">{{ $r->receipt_number ?? '—' }}</a>
                                <span class="os-cell__sub">{{ __('pharmacy_ops.sum_lots', ['count' => $r->items_count]) }}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_date') }}">{{ $r->received_date?->translatedFormat('d M Y') }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_supplier') }}">
                        @if($r->supplier)
                            <a href="{{ route('portals.pharmacy.stock.suppliers.show', $r->supplier->id) }}">{{ $r->supplier->name }}</a>
                        @else
                            <span class="os-cell__sub">{{ __('pharmacy_ops.lbl_no_supplier') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_lines') }}" class="os-num">{{ $r->items_count }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_status') }}">@include('portals.pharmacy.stock_ops._pill', ['tone' => $r->status === 'verified' ? 'ok' : 'neutral', 'label' => __('pharmacy_ops.receipt_status.' . $r->status)])</td>
                    <td class="os-col-act">
                        <div class="os-rowact">
                            <a href="{{ route('portals.pharmacy.stock.receipts.show', $r->id) }}" class="od-btn od-btn--row">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                            @include('portals.pharmacy.stock_ops._kebab', ['links' => array_values(array_filter([
                                [route('portals.pharmacy.stock.receipts.show', $r->id), __('pharmacy_stock_desk.receipt_details'), 'file-text'],
                                $r->supplier ? [route('portals.pharmacy.stock.suppliers.show', $r->supplier->id), $r->supplier->name, 'building-2'] : null,
                            ]))])
                        </div>
                    </td>
                </tr>
            @empty
                @include('portals.pharmacy.stock_ops._empty', ['cols' => 8, 'icon' => 'truck',
                    'text' => __('pharmacy_ops.receipts_empty'),
                    'href' => route('portals.pharmacy.stock.receipts.create'), 'btnIcon' => 'plus', 'btn' => __('pharmacy_ops.btn_new_receipt')])
            @endforelse
            </tbody>
        </table>
    </div>
    @if($receipts->hasPages())<div class="os-card__foot">{{ $receipts->links() }}</div>@endif
</section>

</div>
@endsection
