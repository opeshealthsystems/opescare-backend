@extends('layouts.portal')
@section('title', __('pharmacy_orders.title'))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_orders.title'))

@section('head')
@include('portals.pharmacy._supply_styles')
<style>
.po-tabs{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:var(--p-space-4,1rem)}
.po-tab{display:inline-flex;align-items:center;gap:.4rem;min-height:44px;padding:.4rem .9rem;border:1.5px solid var(--p-border,#E2E8F0);border-radius:999px;font-size:.85rem;font-weight:650;color:var(--p-text-2,#334155);background:var(--p-surface,#fff);text-decoration:none}
.po-tab--active{background:var(--f-brand,var(--p-primary,#0F4C81));border-color:var(--f-brand,var(--p-primary,#0F4C81));color:#fff}
.po-tab__count{display:inline-flex;min-width:1.5rem;justify-content:center;padding:0 .4rem;border-radius:999px;font-size:.75rem;background:rgba(255,255,255,.25)}
.po-tab:not(.po-tab--active) .po-tab__count{background:var(--p-surface-2,#F1F5F9)}
.po-inline{display:inline-block;margin:0}
.po-badge--pending{background:#FEF3C7;color:#92400E}
.po-badge--accepted,.po-badge--ready,.po-badge--dispatched{background:var(--f-wash,#E3EDF6);color:var(--f-brand,#0F4C81)}
.po-badge--collected,.po-badge--delivered{background:var(--f-good-wash,#E7F4EE);color:var(--f-good,#15803D)}
.po-badge--cancelled{background:var(--p-surface-2,#F1F5F9);color:var(--p-text-muted,#64748B)}
</style>
@endsection

@php
    $now = \Illuminate\Support\Carbon::now();
@endphp

@section('content')

<div class="page-head">
    <h2>{{ __('pharmacy_orders.title') }}</h2>
    <p class="page-subtitle">{{ __('pharmacy_orders.subtitle') }}</p>
</div>

@if(isset($hasListing) && ! $hasListing)
<div class="banner banner--warning" role="status">
    <i data-lucide="link-2-off"></i>
    <div>{{ __('pharmacy_orders.no_listing') }}</div>
</div>
@endif

<nav class="po-tabs" aria-label="{{ __('pharmacy_orders.title') }}">
    @foreach($tabs as $t)
        <a href="{{ route('portals.pharmacy.orders.index', ['tab' => $t]) }}" class="po-tab {{ $t === $tab ? 'po-tab--active' : '' }}" @if($t === $tab) aria-current="page" @endif>
            {{ __('pharmacy_orders.tab_' . $t) }}
            <span class="po-tab__count">{{ $counts[$t] ?? 0 }}</span>
        </a>
    @endforeach
</nav>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="shopping-bag"></i> {{ __('pharmacy_orders.tab_' . $tab) }}</h3>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('pharmacy_orders.col_reference') }}</th>
                <th>{{ __('pharmacy_orders.col_patient') }}</th>
                <th>{{ __('pharmacy_orders.col_medicine') }}</th>
                <th>{{ __('pharmacy_orders.col_qty') }}</th>
                <th>{{ __('pharmacy_orders.col_pack') }}</th>
                <th>{{ __('pharmacy_orders.col_total') }}</th>
                <th>{{ __('pharmacy_orders.col_status') }}</th>
                <th>{{ __('pharmacy_orders.col_placed') }}</th>
                <th>{{ __('pharmacy_orders.col_expires') }}</th>
                <th>{{ __('pharmacy_orders.col_action') }}</th>
            </tr></thead>
            <tbody>
            @forelse($reservations as $r)
                @php
                    $desk    = $r->deskStatus();
                    $patient = $r->patient;
                    $name    = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
                @endphp
                <tr>
                    <td data-label="{{ __('pharmacy_orders.col_reference') }}" class="fs-mono td-strong">
                        <a href="{{ route('portals.pharmacy.orders.show', $r->id) }}">{{ $r->reference }}</a>
                        <div class="td-muted">{{ $r->isDelivery() ? __('pharmacy_orders.mode_delivery') : __('pharmacy_orders.mode_pickup') }}</div>
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_patient') }}">
                        {{ $name !== '' ? $name : '—' }}
                        @if($patient?->health_id)<div class="td-muted fs-mono">{{ $patient->health_id }}</div>@endif
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_medicine') }}" class="td-strong">
                        {{ $r->medicine?->name ?? '—' }}
                        <div class="td-muted">{{ $r->medicine?->strength }} {{ $r->medicine?->form }}</div>
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_qty') }}" class="fs-mono">{{ $r->quantity }}</td>
                    <td data-label="{{ __('pharmacy_orders.col_pack') }}">{{ $r->pack_size ?? '—' }}</td>
                    <td data-label="{{ __('pharmacy_orders.col_total') }}" class="fs-mono">
                        @if($r->total_price !== null){{ number_format((float) $r->total_price, 0, ',', ' ') }} FCFA @else <span class="td-muted">{{ __('pharmacy_orders.no_price') }}</span> @endif
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_status') }}">
                        <span class="badge po-badge--{{ $desk }}">@enum($desk, 'reservation_status')</span>
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_placed') }}" class="td-muted">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</td>
                    <td data-label="{{ __('pharmacy_orders.col_expires') }}" class="td-muted">
                        @if($r->expires_at)
                            {{ $r->expires_at->translatedFormat('d M Y H:i') }}
                            @if($desk === \App\Models\MedicineReservation::DESK_PENDING && $r->expires_at->lt($now))
                                <span class="badge badge-danger">{{ __('pharmacy_orders.expired') }}</span>
                            @endif
                        @else
                            {{ __('pharmacy_orders.no_expiry') }}
                        @endif
                    </td>
                    <td data-label="{{ __('pharmacy_orders.col_action') }}">
                        @include('portals.pharmacy.orders._actions', ['reservation' => $r, 'desk' => $desk, 'compact' => true])
                        <a href="{{ route('portals.pharmacy.orders.show', $r->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> {{ __('pharmacy_orders.btn_open') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="td-muted empty-cell">{{ __('pharmacy_orders.empty_' . $tab) }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
