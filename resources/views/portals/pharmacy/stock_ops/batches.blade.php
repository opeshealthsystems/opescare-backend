@extends('layouts.portal')
@section('title', __('pharmacy_ops.batches_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_ops.nav_batches'))

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@php
    $today = \Illuminate\Support\Carbon::today();
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_ops.batches_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_ops.batches_subtitle') }}</p>
</div>

@include('portals.pharmacy.stock_ops._nav')

<div class="fs-kpis">
    <div class="fs-kpi fs-kpi--warn">
        <div class="fs-kpi__label">{{ __('pharmacy_ops.sum_expiring', ['days' => $windowDays]) }}</div>
        <div class="fs-kpi__value">{{ $expiring->count() }}</div>
        <div class="fs-kpi__sub">{{ __('pharmacy_ops.sum_packs', ['count' => $expiring->sum('quantity_available')]) }}</div>
    </div>
    <div class="fs-kpi fs-kpi--danger">
        <div class="fs-kpi__label">{{ __('pharmacy_ops.sum_expired') }}</div>
        <div class="fs-kpi__value">{{ $expired->count() }}</div>
        <div class="fs-kpi__sub">{{ __('pharmacy_ops.sum_packs', ['count' => $expired->sum('quantity_available')]) }}</div>
    </div>
    <div class="fs-kpi fs-kpi--neutral">
        <div class="fs-kpi__label">{{ __('pharmacy_ops.sum_undated') }}</div>
        <div class="fs-kpi__value">{{ $undated->count() }}</div>
        <div class="fs-kpi__sub">{{ __('pharmacy_ops.sum_packs', ['count' => $undated->sum('quantity_available')]) }}</div>
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
        <h3 class="panel-title"><i data-lucide="calendar-clock"></i> {{ __('pharmacy_ops.sum_expiring', ['days' => $windowDays]) }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_ops.col_medicine') }}</th>
                <th>{{ __('pharmacy_ops.col_lot') }}</th>
                <th>{{ __('pharmacy_ops.col_expiry') }}</th>
                <th>{{ __('pharmacy_ops.col_packs') }}</th>
            </tr></thead>
            <tbody>
            @foreach($expiring as $b)
                <tr>
                    <td data-label="{{ __('pharmacy_ops.col_medicine') }}" class="td-strong">{{ $b->item?->medicine?->name ?? $b->item?->name }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="fs-mono">{{ $b->lot_number ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_ops.col_expiry') }}">
                        {{ $b->expiry_date->format('d M Y') }}
                        <span class="badge badge-warning">{{ __('pharmacy_ops.expiring_badge', ['days' => (int) $today->diffInDays($b->expiry_date, false)]) }}</span>
                    </td>
                    <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="fs-mono">{{ $b->quantity_available }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="layers"></i> {{ __('pharmacy_ops.nav_batches') }}</h3>
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
            </tr></thead>
            <tbody>
            @forelse($items as $item)
                @php $med = $item->medicine; $code = $med?->atc_code; @endphp
                <tr>
                    <td colspan="3" class="td-strong">
                        {{ $med?->name ?? $item->name }}
                        <div class="td-muted">
                            {{ $med?->strength }} {{ $med?->form }}
                            @if($code)
                                · <span class="fs-mono">{{ $code }}</span>@if(isset($atc[$code])) — {{ $atc[$code] }}@endif
                            @endif
                        </div>
                    </td>
                    <td class="fs-mono td-strong" data-label="{{ __('pharmacy_ops.col_usable') }}">
                        {{ __('pharmacy_ops.col_usable') }}: {{ $usable[$item->id] ?? 0 }}
                    </td>
                    <td colspan="2" class="td-muted">
                        @if($item->batches->isEmpty()) {{ __('pharmacy_ops.no_lots') }} @else {{ __('pharmacy_ops.sum_lots', ['count' => $item->batches->count()]) }} @endif
                    </td>
                </tr>
                @foreach($item->batches as $b)
                    @php
                        $isExpired = $b->expiry_date && $b->expiry_date->lt($today);
                        $daysLeft  = $b->expiry_date ? (int) $today->diffInDays($b->expiry_date, false) : null;
                    @endphp
                    <tr>
                        <td data-label="{{ __('pharmacy_ops.col_medicine') }}" class="td-muted">↳ {{ $med?->name ?? $item->name }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_lot') }}" class="fs-mono">{{ $b->lot_number ?? '—' }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_expiry') }}">
                            @if(! $b->expiry_date)
                                <span class="badge badge-neutral" title="{{ __('pharmacy_ops.expiry_unknown_hint') }}">{{ __('pharmacy_ops.expiry_unknown') }}</span>
                            @else
                                {{ $b->expiry_date->format('d M Y') }}
                                @if($isExpired)
                                    <span class="badge badge-danger">{{ __('pharmacy_ops.expired_badge') }}</span>
                                @elseif($daysLeft !== null && $daysLeft <= $windowDays)
                                    <span class="badge badge-warning">{{ __('pharmacy_ops.expiring_badge', ['days' => $daysLeft]) }}</span>
                                @endif
                            @endif
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_packs') }}" class="fs-mono">{{ $b->quantity_available }}</td>
                        <td data-label="{{ __('pharmacy_ops.col_status') }}">
                            <span class="badge {{ $b->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ __('pharmacy_ops.batch_status.' . $b->status) }}</span>
                        </td>
                        <td data-label="{{ __('pharmacy_ops.col_received') }}" class="td-muted">{{ $b->created_at?->format('d M Y') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="6" class="td-muted empty-cell">{{ __('pharmacy_ops.batches_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
