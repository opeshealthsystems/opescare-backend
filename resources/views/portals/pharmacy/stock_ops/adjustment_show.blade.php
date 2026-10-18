@extends('layouts.portal')
@section('title', __('pharmacy_stock_desk.adj_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_adjustments'))

@section('content')

@php
    $m    = $movement;
    $name = $m->item?->medicine?->name ?? $m->item?->name ?? '—';
    $when = $m->performed_at ?? $m->created_at;
@endphp

<div class="page-head">
    <h2>{{ __('pharmacy_stock_desk.adj_title') }}</h2>
    <p class="page-subtitle">{{ $name }} · {{ $when?->translatedFormat('d M Y H:i') }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.stock.adjustments') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> {{ __('pharmacy_stock_desk.back_to_log') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

<div class="fs-note"><i data-lucide="lock"></i><div>{{ __('pharmacy_ops.adj_append_only') }}</div></div>

<div class="ph-kpis">
    <div class="stat-card {{ $m->quantity >= 0 ? 'stat-card--success' : 'stat-card--warning' }}">
        <div class="stat-card__icon"><i data-lucide="{{ $m->quantity >= 0 ? 'arrow-down-to-line' : 'arrow-up-from-line' }}"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_ops.col_change') }}</div>
        <div class="stat-card__value fs-mono {{ $m->quantity >= 0 ? 'fs-delta--in' : 'fs-delta--out' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_stock_desk.movement_type.' . $m->movement_type) }}</div>
    </div>
    @if($beforeAfter)
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="package"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.col_before') }}</div>
        <div class="stat-card__value fs-mono">{{ $beforeAfter[0] }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_stock_desk.lot_packs') }}</div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="package-check"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_stock_desk.col_after') }}</div>
        <div class="stat-card__value fs-mono">{{ $beforeAfter[1] }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_stock_desk.lot_packs') }}</div>
    </div>
    @endif
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-text"></i> {{ __('pharmacy_stock_desk.adj_facts') }}</h3>
        <span class="badge badge-neutral">{{ __('pharmacy_ops.reason.' . $reasonCode) }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table data-table--facts">
            <tbody>
                <tr>
                    <th>{{ __('pharmacy_ops.col_by') }}</th>
                    <td>{{ $actor ?? '—' }}</td>
                </tr>
                <tr>
                    <th>{{ __('pharmacy_ops.col_when') }}</th>
                    <td>{{ $when?->translatedFormat('d M Y H:i') }}</td>
                </tr>
                <tr>
                    <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                    <td>
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $name])
                            <div class="ph-cell__title">
                                @if($m->inventory_item_id)
                                    <a href="{{ route('portals.pharmacy.stock.items.show', $m->inventory_item_id) }}" class="td-strong">{{ $name }}</a>
                                @else
                                    {{ $name }}
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('pharmacy_ops.col_lot') }}</th>
                    <td class="fs-mono">
                        @if($m->batch)
                            <a href="{{ route('portals.pharmacy.stock.items.show', $m->inventory_item_id) }}#batch-{{ $m->batch->id }}">{{ $m->batch->lot_number ?? '—' }}</a>
                            <span class="td-muted">· {{ $m->batch->expiry_date?->translatedFormat('d M Y') ?? __('pharmacy_ops.expiry_unknown') }}</span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>{{ __('pharmacy_stock_desk.col_location') }}</th>
                    <td>{{ $m->toLocation?->name ?? $m->fromLocation?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>{{ __('pharmacy_ops.col_reason') }}</th>
                    <td><span class="badge badge-neutral">{{ __('pharmacy_ops.reason.' . $reasonCode) }}</span></td>
                </tr>
                <tr>
                    <th>{{ __('pharmacy_ops.col_note') }}</th>
                    <td>{{ $note ?? '—' }}</td>
                </tr>
                <tr>
                    <th>{{ __('pharmacy_stock_desk.col_reference') }}</th>
                    <td>
                        @if($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RECEIPT && $m->reference_id)
                            <a href="{{ route('portals.pharmacy.stock.receipts.show', $m->reference_id) }}">{{ __('pharmacy_stock_desk.kind.receipt') }}</a>
                        @elseif($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_RESERVATION)
                            {{ __('pharmacy_stock_desk.kind.reservation') }}
                        @elseif($m->reference_type === \App\Services\Pharmacy\PharmacyStockOpsService::REFERENCE_ADJUSTMENT)
                            {{ __('pharmacy_stock_desk.kind.adjustment') }}
                        @else
                            {{ $m->reference_type ?? '—' }}
                        @endif
                    </td>
                </tr>
                @if($m->unit_cost !== null)
                <tr>
                    <th>{{ __('pharmacy_ops.col_line_cost') }}</th>
                    <td class="fs-mono">{{ number_format((int) $m->unit_cost, 0, ',', ' ') }} FCFA</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
