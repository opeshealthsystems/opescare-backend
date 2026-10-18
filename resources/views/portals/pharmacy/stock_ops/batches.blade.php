@extends('layouts.portal')
@section('title', __('pharmacy_ops.batches_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_batches'))

@php
    $today = \Illuminate\Support\Carbon::today();
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.batches_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.batches_subtitle') }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

<div class="ph-kpis">
    <div class="stat-card stat-card--warning">
        <div class="stat-card__icon"><i data-lucide="calendar-clock"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_ops.sum_expiring', ['days' => $windowDays]) }}</div>
        <div class="stat-card__value">{{ $expiring->count() }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_ops.sum_packs', ['count' => $expiring->sum('quantity_available')]) }}</div>
    </div>
    <div class="stat-card stat-card--danger">
        <div class="stat-card__icon"><i data-lucide="calendar-x"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_ops.sum_expired') }}</div>
        <div class="stat-card__value">{{ $expired->count() }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_ops.sum_packs', ['count' => $expired->sum('quantity_available')]) }}</div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-card__icon"><i data-lucide="calendar-off"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_ops.sum_undated') }}</div>
        <div class="stat-card__value">{{ $undated->count() }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_ops.sum_packs', ['count' => $undated->sum('quantity_available')]) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__icon"><i data-lucide="layers"></i></div>
        <div class="stat-card__label">{{ __('pharmacy_ops.nav_batches') }}</div>
        <div class="stat-card__value">{{ $items->count() }}</div>
        <div class="stat-card__hint">{{ __('pharmacy_ops.sum_lots', ['count' => $items->sum(fn ($i) => $i->batches->count())]) }}</div>
    </div>
</div>

<div class="fs-note"><i data-lucide="info"></i><div>{{ __('pharmacy_ops.usable_hint') }}</div></div>

@if($expired->isNotEmpty())
<div class="banner banner--danger">
    <i data-lucide="calendar-x"></i>
    <div>
        <strong>{{ __('pharmacy_ops.sum_expired') }}: {{ __('pharmacy_ops.sum_lots', ['count' => $expired->count()]) }}</strong><br>
        {{ __('pharmacy_ops.write_off_hint') }}
    </div>
</div>
@endif

@if($expiring->isNotEmpty())
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="calendar-clock"></i> {{ __('pharmacy_ops.sum_expiring', ['days' => $windowDays]) }} <span class="ph-count-inline">({{ $expiring->count() }})</span></h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @foreach($expiring as $b)
                @php $bName = $b->item?->medicine?->name ?? $b->item?->name; @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $bName, 'tone' => 'amber'])
                            <div>
                                <div class="ph-cell__title">{{ $bName }}</div>
                                <div class="ph-cell__sub">{{ $b->item?->medicine?->strength }} {{ $b->item?->medicine?->form }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="fs-mono">{{ $b->lot_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_expiry') }}">
                        {{ $b->expiry_date->translatedFormat('d M Y') }}
                        <span class="badge badge-warning">{{ __('pharmacy_ops.expiring_badge', ['days' => (int) $today->diffInDays($b->expiry_date, false)]) }}</span>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="td-num">{{ $b->quantity_available }}</td>
                    <td class="row-actions">
                        @if($b->item)
                        <a href="{{ route('portals.pharmacy.stock.items.show', $b->item->id) }}#batch-{{ $b->id }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="layers"></i> {{ __('pharmacy_ops.nav_batches') }} <span class="ph-count-inline">({{ $items->count() }})</span></h3>
        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
                <th>{{ __('pharmacy_ops.col_status') }}</th>
                <th>{{ __('pharmacy_ops.col_received') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php
                    $med   = $item->medicine;
                    $code  = $med?->atc_code;
                    $iName = $med?->name ?? $item->name;
                    $iUse  = $usable[$item->id] ?? 0;
                @endphp
                <tr>
                    <td colspan="3">
                        <div class="ph-cell">
                            @include('portals.pharmacy.stock_ops._avatar', ['name' => $iName, 'tone' => $iUse > 0 ? '' : 'red'])
                            <div>
                                <div class="ph-cell__title"><a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}">{{ $iName }}</a></div>
                                <div class="ph-cell__sub">
                                    {{ $med?->strength }} {{ $med?->form }}
                                    @if($code)
                                        · <span class="fs-mono">{{ $code }}</span>@if(isset($atc[$code])) — {{ $atc[$code] }}@endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="td-num" data-label="{{ __('pharmacy_ops.col_usable') }}">
                        {{ __('pharmacy_ops.col_usable') }}: {{ $iUse }}
                    </td>
                    <td colspan="2" class="td-muted">
                        @if($item->batches->isEmpty()) {{ __('pharmacy_ops.no_lots') }} @else {{ __('pharmacy_ops.sum_lots', ['count' => $item->batches->count()]) }} @endif
                    </td>
                    <td class="row-actions">
                        <a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}" class="btn btn-primary btn-sm">{{ __('pharmacy_stock_desk.btn_open') }}</a>
                    </td>
                </tr>
                @foreach($item->batches as $b)
                    @php
                        $isExpired = $b->expiry_date && $b->expiry_date->lt($today);
                        $daysLeft  = $b->expiry_date ? (int) $today->diffInDays($b->expiry_date, false) : null;
                    @endphp
                    <tr class="ph-row--child">
                        <td data-label="{{ __('pharmacy_ops.col_medicine') }}" class="td-muted">↳ {{ $iName }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="fs-mono"><a href="{{ route('portals.pharmacy.stock.items.show', $item->id) }}#batch-{{ $b->id }}">{{ $b->lot_number ?? '—' }}</a></td>
                        <td data-label="{{ __('pharmacy_ops.col_expiry') }}">
                            @if(! $b->expiry_date)
                                <span class="badge badge-neutral" title="{{ __('pharmacy_ops.expiry_unknown_hint') }}">{{ __('pharmacy_ops.expiry_unknown') }}</span>
                            @else
                                {{ $b->expiry_date->translatedFormat('d M Y') }}
                                @if($isExpired)
                                    <span class="badge badge-danger">{{ __('pharmacy_ops.expired_badge') }}</span>
                                @elseif($daysLeft !== null && $daysLeft <= $windowDays)
                                    <span class="badge badge-warning">{{ __('pharmacy_ops.expiring_badge', ['days' => $daysLeft]) }}</span>
                                @endif
                            @endif
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="td-num">{{ $b->quantity_available }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_status') }}">
                            <span class="badge {{ $b->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_ops.batch_status.' . $b->status) }}</span>
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_received') }}" class="td-muted">{{ $b->created_at?->translatedFormat('d M Y') }}</td>
                        <td></td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="7" class="empty-cell">
                    <div class="empty-state">
                        <i data-lucide="layers"></i>
                        <p>{{ __('pharmacy_ops.batches_empty') }}</p>
                        <a href="{{ route('portals.pharmacy.stock.receipts.create') }}" class="btn btn-primary btn-sm"><i data-lucide="truck"></i> {{ __('pharmacy_ops.btn_new_receipt') }}</a>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
