@extends('layouts.portal')
@section('title', __('public.stf_supply_index_title') . ' — Dashboard')
@section('sidebar') @include('portals.staff.supply_chain._sidebar') @endsection

@section('content')
<div class="portal-content">

    <div class="portal-page-header">
        <div>
            <h1 class="portal-page-title">{{ __('public.stf_supply_index_title') }}</h1>
            <p class="portal-page-subtitle">{{ __('public.stf_supply_index_subtitle') }}</p>
        </div>
        <a href="{{ route('portals.staff.supply.stock.receive') }}" class="btn btn--primary" onclick="event.preventDefault();openModal('receiveModal')">
            <i data-lucide="plus"></i> {{ __('public.stf_supply_index_btn_receive') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
    @endif

    {{-- KPI Cards --}}
    <div class="stat-grid mb-6">
        <div class="stat-card stat-card--primary">
            <div class="stat-card__head"><i data-lucide="list"></i></div>
            <div class="stat-card__value">{{ $stats['items'] }}</div><div class="stat-card__label">{{ __('public.stf_supply_index_kpi_items') }}</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-card__head"><i data-lucide="triangle-alert"></i></div>
            <div class="stat-card__value">{{ $stats['lowStock'] }}</div><div class="stat-card__label">{{ __('public.stf_supply_index_kpi_low') }}</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-card__head"><i data-lucide="clock"></i></div>
            <div class="stat-card__value">{{ $stats['expiring'] }}</div><div class="stat-card__label">{{ __('public.stf_supply_index_kpi_expiring') }}</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-card__head"><i data-lucide="x-circle"></i></div>
            <div class="stat-card__value">{{ $stats['expired'] }}</div><div class="stat-card__label">{{ __('public.stf_supply_index_kpi_expired') }}</div>
        </div>
        <div class="stat-card stat-card--success">
            <div class="stat-card__head"><i data-lucide="truck"></i></div>
            <div class="stat-card__value">{{ $stats['suppliers'] }}</div><div class="stat-card__label">{{ __('public.stf_supply_index_kpi_suppliers') }}</div>
        </div>
        <div class="stat-card stat-card--teal">
            <div class="stat-card__head"><i data-lucide="file-text"></i></div>
            <div class="stat-card__value">{{ $stats['openPOs'] }}</div><div class="stat-card__label">{{ __('public.stf_supply_index_kpi_open_pos') }}</div>
        </div>
    </div>

    <div class="grid-2 mb-6">

        {{-- Low Stock Alerts --}}
        <div class="portal-card">
            <div class="portal-card__header">
                <h2 class="portal-card__title"><i data-lucide="triangle-alert"></i> {{ __('public.stf_supply_index_card_low_stock') }}</h2>
                <a href="{{ route('portals.staff.supply.items') }}" class="btn btn--sm btn--outline">{{ __('public.stf_supply_index_btn_all_items') }}</a>
            </div>
            <div class="portal-card__body panel-body--flush">
                @forelse($lowStock as $item)
                    <div class="list-row">
                        <div>
                            <div class="td-strong">{{ $item->name }}</div>
                            <div class="td-muted">{{ $item->code }} · @enum($item->category)</div>
                        </div>
                        <div>
                            <span class="badge badge-danger">{{ $item->totalStock(request()->facilityId ?? \App\Models\Facility::value('id')) }} {{ $item->unit }}</span>
                            <div class="td-muted">{{ __('public.stf_supply_index_reorder_at', ['level' => $item->reorder_level]) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
                        <p>{{ __('public.stf_supply_index_all_healthy') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Expiring Soon --}}
        <div class="portal-card">
            <div class="portal-card__header">
                <h2 class="portal-card__title"><i data-lucide="clock"></i> {{ __('public.stf_supply_index_card_expiring') }}</h2>
                <a href="{{ route('portals.staff.supply.stock') }}" class="btn btn--sm btn--outline">{{ __('public.stf_supply_index_btn_stock_view') }}</a>
            </div>
            <div class="portal-card__body panel-body--flush">
                @forelse($expiring as $batch)
                    <div class="list-row">
                        <div>
                            <div class="td-strong">{{ $batch->item->name ?? '—' }}</div>
                            <div class="td-muted">{{ __('public.stf_supply_index_batch_label', ['number' => $batch->batch_number ?: 'N/A']) }}</div>
                        </div>
                        <div>
                            <span class="badge badge-warning">{{ $batch->expiry_date?->format('d M Y') }}</span>
                            <div class="td-muted">{{ __('public.stf_supply_index_units', ['count' => $batch->availableQty()]) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <p>{{ __('public.stf_supply_index_no_expiring') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Recent Stock Movements --}}
    <div class="portal-card">
        <div class="portal-card__header">
            <h2 class="portal-card__title"><i data-lucide="arrow-left-right"></i> {{ __('public.stf_supply_index_card_movements') }}</h2>
            <a href="{{ route('portals.staff.supply.movements') }}" class="btn btn--sm btn--outline">{{ __('public.stf_supply_index_btn_all_movements') }}</a>
        </div>
        <div class="portal-card__body panel-body--flush">
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.stf_supply_index_col_item') }}</th>
                        <th>{{ __('public.stf_supply_index_col_type') }}</th>
                        <th>{{ __('public.stf_supply_index_col_qty') }}</th>
                        <th>{{ __('public.stf_supply_index_col_by') }}</th>
                        <th>{{ __('public.stf_supply_index_col_when') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMovements as $mv)
                        <tr>
                            <td data-label="{{ __('public.stf_supply_index_col_item') }}" class="td-strong">{{ $mv->item->name ?? '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_index_col_type') }}">
                                <span class="badge badge--{{
                                    $mv->movement_type === 'receipt' ? 'success' :
                                    ($mv->movement_type === 'dispense' ? 'info' :
                                    ($mv->movement_type === 'write_off' ? 'danger' :
                                    ($mv->movement_type === 'adjustment' ? 'warning' : 'default')))
                                }}">@enum($mv->movement_type)</span>
                            </td>
                            <td data-label="{{ __('public.stf_supply_index_col_qty') }}" class="td-strong">{{ $mv->quantity }}</td>
                            <td data-label="{{ __('public.stf_supply_index_col_by') }}" class="td-muted">{{ $mv->performed_by ?: '—' }}</td>
                            <td data-label="{{ __('public.stf_supply_index_col_when') }}" class="td-muted">{{ $mv->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell td-muted">{{ __('public.stf_supply_index_no_movements') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>
@endsection
