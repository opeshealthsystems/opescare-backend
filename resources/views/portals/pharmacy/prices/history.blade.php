@extends('layouts.portal')
@section('title', __('pharmacy_prices.history_title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_prices.nav_prices'))

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@section('content')

@php $name = $item->medicine?->name ?? $item->name; @endphp

<div class="page-head">
    <h2>{{ __('pharmacy_prices.history_title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_prices.history_subtitle', ['name' => $name]) }}</p>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.pharmacy.prices.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> {{ __('pharmacy_prices.btn_back') }}</a>
</div>

@include('portals.pharmacy.stock_ops._nav')

<div class="fs-kpis">
    <div class="fs-kpi fs-kpi--neutral">
        <div class="fs-kpi__label">{{ __('pharmacy_prices.col_price') }}</div>
        <div class="fs-kpi__value fs-mono">
            @if($item->selling_price_xaf !== null)
                {{ number_format($item->selling_price_xaf, 0, ',', ' ') }} FCFA
            @else
                —
            @endif
        </div>
        <div class="fs-kpi__sub">{{ $name }}</div>
    </div>
    <div class="fs-kpi fs-kpi--neutral">
        <div class="fs-kpi__label">{{ __('pharmacy_prices.sum_changes') }}</div>
        <div class="fs-kpi__value">{{ $changes->total() }}</div>
    </div>
</div>

<div class="fs-note"><i data-lucide="lock"></i><div>{{ __('pharmacy_prices.history_append_only') }}</div></div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="history"></i> {{ __('pharmacy_prices.col_history') }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_prices.col_when') }}</th>
                <th>{{ __('pharmacy_prices.col_old') }}</th>
                <th>{{ __('pharmacy_prices.col_new') }}</th>
                <th>{{ __('pharmacy_prices.col_by') }}</th>
                <th>{{ __('pharmacy_prices.lbl_reason') }}</th>
            </tr></thead>
            <tbody>
            @forelse($changes as $c)
                @php $delta = $c->old_price_xaf === null ? null : $c->new_price_xaf - $c->old_price_xaf; @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_prices.col_when') }}" class="td-muted">{{ $c->created_at?->format('d M Y H:i') }}</td>
                    <td data-label="{{ __('pharmacy_prices.col_old') }}" class="fs-mono">
                        @if($c->old_price_xaf === null)
                            <span class="badge badge-neutral">{{ __('pharmacy_prices.no_price_yet') }}</span>
                        @else
                            {{ number_format($c->old_price_xaf, 0, ',', ' ') }} FCFA
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_new') }}" class="fs-mono td-strong">
                        {{ number_format($c->new_price_xaf, 0, ',', ' ') }} FCFA
                        @if($delta !== null && $delta !== 0)
                            <span class="{{ $delta > 0 ? 'fs-delta--out' : 'fs-delta--in' }}">{{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0, ',', ' ') }}</span>
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_prices.col_by') }}">{{ $actors[$c->changed_by] ?? __('pharmacy_prices.actor_unknown') }}</td>
                    <td data-label="{{ __('pharmacy_prices.lbl_reason') }}" class="td-muted">{{ $c->reason ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="td-muted empty-cell">{{ __('pharmacy_prices.history_empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($changes->hasPages())
    <div class="panel-footer">{{ $changes->links() }}</div>
    @endif
</div>

@endsection
