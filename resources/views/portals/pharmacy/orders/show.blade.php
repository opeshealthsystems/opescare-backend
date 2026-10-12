@extends('layouts.portal')
@section('title', __('pharmacy_orders.show_title', ['reference' => $reservation->reference]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_orders.title'))

@section('head')
@include('portals.pharmacy._supply_styles')
<style>
.po-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--p-space-4,1rem)}
.po-dl{display:grid;grid-template-columns:minmax(120px,40%) 1fr;gap:.45rem .9rem;margin:0}
.po-dl dt{font-size:.78rem;font-weight:650;letter-spacing:.03em;text-transform:uppercase;color:var(--p-text-muted,#64748B)}
.po-dl dd{margin:0;color:var(--p-text,#0F172A);overflow-wrap:anywhere}
.po-inline{display:inline-block;margin:0 .5rem .5rem 0}
.po-badge--pending{background:#FEF3C7;color:#92400E}
.po-badge--accepted,.po-badge--ready,.po-badge--dispatched{background:var(--f-wash,#E3EDF6);color:var(--f-brand,#0F4C81)}
.po-badge--collected,.po-badge--delivered{background:var(--f-good-wash,#E7F4EE);color:var(--f-good,#15803D)}
.po-badge--cancelled{background:var(--p-surface-2,#F1F5F9);color:var(--p-text-muted,#64748B)}
.po-timeline{list-style:none;margin:0;padding:0}
.po-timeline li{display:flex;justify-content:space-between;gap:1rem;padding:.45rem 0;border-bottom:1px solid var(--p-border,#E2E8F0)}
.po-timeline li:last-child{border-bottom:0}
.po-cancel{margin-top:var(--p-space-4,1rem);padding-top:var(--p-space-4,1rem);border-top:1px solid var(--p-border,#E2E8F0)}
</style>
@endsection

@php
    $patient = $reservation->patient;
    $name    = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
    $closed  = in_array($desk, [
        \App\Models\MedicineReservation::DESK_COLLECTED,
        \App\Models\MedicineReservation::DESK_DELIVERED,
        \App\Models\MedicineReservation::DESK_CANCELLED,
    ], true);
    $timeline = [
        'placed'     => $reservation->created_at,
        'accepted'   => $reservation->accepted_at,
        'ready'      => $reservation->ready_at,
        'dispatched' => $reservation->dispatched_at,
        'delivered'  => $reservation->delivered_at,
        'collected'  => $reservation->isDelivery() ? null : $reservation->collected_at,
        'cancelled'  => $reservation->cancelled_at,
    ];
@endphp

@section('content')
@if(session('warning'))
<div class="banner banner--warning" role="status"><i data-lucide="alert-triangle"></i><div>{{ session('warning') }}</div></div>
@endif


<div class="page-head">
    <h2>{{ __('pharmacy_orders.show_title', ['reference' => $reservation->reference]) }}
        <span class="badge po-badge--{{ $desk }}">@enum($desk, 'reservation_status')</span>
    </h2>
    <p class="page-subtitle">
        <a href="{{ route('portals.pharmacy.orders.index') }}"><i data-lucide="arrow-left"></i> {{ __('pharmacy_orders.btn_back') }}</a>
    </p>
</div>

<div class="po-grid">

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="pill"></i> {{ __('pharmacy_orders.section_order') }}</h3></div>
        <div class="panel-body">
            <dl class="po-dl">
                <dt>{{ __('pharmacy_orders.col_medicine') }}</dt>
                <dd class="td-strong">{{ $reservation->medicine?->name ?? '—' }}
                    <div class="td-muted">{{ $reservation->medicine?->strength }} {{ $reservation->medicine?->form }}</div></dd>
                <dt>{{ __('pharmacy_orders.col_qty') }}</dt>
                <dd class="fs-mono">{{ $reservation->quantity }}</dd>
                @if(in_array($desk, [\App\Models\MedicineReservation::DESK_COLLECTED, \App\Models\MedicineReservation::DESK_DELIVERED], true))
                <dt>{{ __('pharmacy_orders.lbl_stock_ledger') }}</dt>
                <dd>@if(($stockRecorded ?? 0) > 0){{ __('pharmacy_orders.stock_recorded', ['count' => $stockRecorded]) }} <a href="{{ route('portals.pharmacy.stock.adjustments') }}">{{ __('pharmacy_orders.stock_view_log') }}</a>@else<span class="td-muted">{{ __('pharmacy_orders.stock_not_recorded') }}</span> <a href="{{ route('portals.pharmacy.stock.adjustments') }}">{{ __('pharmacy_orders.stock_record_by_hand') }}</a>@endif</dd>
                @endif
                <dt>{{ __('pharmacy_orders.col_pack') }}</dt>
                <dd>{{ $reservation->pack_size ?? '—' }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_unit_price') }}</dt>
                <dd class="fs-mono">@if($reservation->unit_price !== null){{ number_format((float) $reservation->unit_price, 0, ',', ' ') }} FCFA @else <span class="td-muted">{{ __('pharmacy_orders.no_price') }}</span> @endif</dd>
                <dt>{{ __('pharmacy_orders.col_total') }}</dt>
                <dd class="fs-mono td-strong">@if($reservation->total_price !== null){{ number_format((float) $reservation->total_price, 0, ',', ' ') }} FCFA @else <span class="td-muted">{{ __('pharmacy_orders.no_price') }}</span> @endif</dd>
                <dt>{{ __('pharmacy_orders.lbl_fulfilment') }}</dt>
                <dd>{{ $reservation->isDelivery() ? __('pharmacy_orders.mode_delivery') : __('pharmacy_orders.mode_pickup') }}</dd>
                <dt>{{ __('pharmacy_orders.col_expires') }}</dt>
                <dd>{{ $reservation->expires_at?->translatedFormat('d M Y H:i') ?? __('pharmacy_orders.no_expiry') }}</dd>
                @if($reservation->prescription_id)
                <dt>{{ __('pharmacy_orders.lbl_prescription') }}</dt>
                <dd class="fs-mono">{{ $reservation->prescription?->reference ?? '—' }}</dd>
                @endif
                <dt>{{ __('pharmacy_orders.lbl_patient_note') }}</dt>
                <dd>{{ $reservation->patient_note ?: __('pharmacy_orders.not_provided') }}</dd>
                @if($reservation->pharmacy_note)
                <dt>{{ __('pharmacy_orders.lbl_pharmacy_note') }}</dt>
                <dd>{{ $reservation->pharmacy_note }}</dd>
                @endif
                @if($reservation->cancelled_reason)
                <dt>{{ __('pharmacy_orders.lbl_cancel_reason') }}</dt>
                <dd>{{ $reservation->cancelled_reason }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="user"></i> {{ __('pharmacy_orders.section_patient') }}</h3></div>
        <div class="panel-body">
            <dl class="po-dl">
                <dt>{{ __('pharmacy_orders.col_patient') }}</dt>
                <dd class="td-strong">{{ $name !== '' ? $name : '—' }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_health_id') }}</dt>
                <dd class="fs-mono">{{ $patient?->health_id ?? '—' }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_phone') }}</dt>
                <dd>{{ $patient?->phone_number ?: __('pharmacy_orders.not_provided') }}</dd>
            </dl>
        </div>
    </div>

    @if($reservation->isDelivery())
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="truck"></i> {{ __('pharmacy_orders.section_delivery') }}</h3></div>
        <div class="panel-body">
            <dl class="po-dl">
                <dt>{{ __('pharmacy_orders.lbl_delivery_address') }}</dt>
                <dd>{{ $reservation->delivery_address ?: __('pharmacy_orders.not_provided') }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_delivery_phone') }}</dt>
                <dd>{{ $reservation->delivery_phone ?: __('pharmacy_orders.not_provided') }}</dd>
            </dl>
        </div>
    </div>
    @endif

    <div class="panel">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="history"></i> {{ __('pharmacy_orders.section_timeline') }}</h3></div>
        <div class="panel-body">
            <ul class="po-timeline">
                @foreach($timeline as $key => $at)
                    @if($at)
                        <li><span>{{ __('pharmacy_orders.ts_' . $key) }}</span><span class="td-muted fs-mono">{{ $at->translatedFormat('d M Y H:i') }}</span></li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title"><i data-lucide="arrow-right-circle"></i> {{ __('pharmacy_orders.section_actions') }}</h3></div>
    <div class="panel-body">
        @if($closed)
            <p class="td-muted">{{ __('pharmacy_orders.closed_hint') }}</p>
        @else
            @include('portals.pharmacy.orders._actions', ['reservation' => $reservation, 'desk' => $desk, 'compact' => false])

            <div class="po-cancel">
                <form method="POST" action="{{ route('portals.pharmacy.orders.cancel', $reservation->id) }}" data-confirm="{{ __('pharmacy_orders.confirm_cancel') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="po-reason">{{ __('pharmacy_orders.lbl_reason') }}</label>
                        <input type="text" name="reason" id="po-reason" class="form-control" minlength="3" maxlength="300" required placeholder="{{ __('pharmacy_orders.reason_placeholder') }}" value="{{ old('reason') }}">
                        @error('reason')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-danger fs-tap"><i data-lucide="x-circle"></i> {{ __('pharmacy_orders.btn_cancel') }}</button>
                </form>
            </div>
        @endif
    </div>
</div>

@endsection
