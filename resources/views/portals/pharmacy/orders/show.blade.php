@extends('layouts.portal')
@section('title', __('pharmacy_orders.show_title', ['reference' => $reservation->reference]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))
@section('breadcrumb_home', __('public.pharmacy_portal.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.pharmacy.dashboard'))
@section('breadcrumb_section', __('pharmacy_orders.title'))

@php
    $patient = $reservation->patient;
    $name    = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
    $initials = $patient ? mb_strtoupper(mb_substr((string) ($patient->first_name ?? ''), 0, 1) . mb_substr((string) ($patient->last_name ?? ''), 0, 1)) : '';
    $closed  = in_array($desk, [
        \App\Models\MedicineReservation::DESK_COLLECTED,
        \App\Models\MedicineReservation::DESK_DELIVERED,
        \App\Models\MedicineReservation::DESK_CANCELLED,
    ], true);
    $deskBadge = match ($desk) {
        \App\Models\MedicineReservation::DESK_PENDING   => 'badge-warning',
        \App\Models\MedicineReservation::DESK_CANCELLED => 'badge-neutral',
        \App\Models\MedicineReservation::DESK_COLLECTED,
        \App\Models\MedicineReservation::DESK_DELIVERED => 'badge-success',
        default                                          => 'badge-info',
    };
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
        <span class="badge {{ $deskBadge }}">@enum($desk, 'reservation_status')</span>
    </h2>
    <p class="page-subtitle">
        <a href="{{ route('portals.pharmacy.orders.index') }}"><i data-lucide="arrow-left"></i> {{ __('pharmacy_orders.btn_back') }}</a>
    </p>
</div>

<div class="ph-split">

    <div class="ph-stack">
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="pill"></i> {{ __('pharmacy_orders.section_order') }}</h3></div>
            <div class="panel-body">
                <dl class="ph-dl">
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
            <div class="panel-header"><h3 class="panel-title"><i data-lucide="arrow-right-circle"></i> {{ __('pharmacy_orders.section_actions') }}</h3></div>
            <div class="panel-body">
                @if($closed)
                    <p class="td-muted">{{ __('pharmacy_orders.closed_hint') }}</p>
                @else
                    @include('portals.pharmacy.orders._actions', ['reservation' => $reservation, 'desk' => $desk, 'compact' => false])

                    <div class="ph-cancel">
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
    </div>

    <div class="panel ph-detail">
        <div class="panel-header"><h3 class="panel-title"><i data-lucide="user"></i> {{ __('pharmacy_orders.section_patient') }}</h3></div>
        <div class="ph-detail__head">
            <span class="ph-avatar ph-detail__avatar" aria-hidden="true">{{ $initials !== '' ? $initials : '—' }}</span>
            <div>
                <div class="ph-detail__name">{{ $name !== '' ? $name : '—' }}</div>
                <div class="ph-detail__meta">
                    <span class="fs-mono">{{ $reservation->reference }}</span>
                    <span>&middot;</span>
                    <span>{{ $reservation->created_at?->translatedFormat('d M Y H:i') }}</span>
                </div>
            </div>
        </div>
        <div class="ph-chips">
            <span class="ph-chip fs-mono" title="{{ __('pharmacy_orders.lbl_health_id') }}"><i data-lucide="id-card"></i> {{ $patient?->health_id ?? '—' }}</span>
            <span class="ph-chip" title="{{ __('pharmacy_orders.lbl_phone') }}"><i data-lucide="phone"></i> {{ $patient?->phone_number ?: __('pharmacy_orders.not_provided') }}</span>
            <span class="ph-chip" title="{{ __('pharmacy_orders.lbl_fulfilment') }}"><i data-lucide="{{ $reservation->isDelivery() ? 'truck' : 'store' }}"></i> {{ $reservation->isDelivery() ? __('pharmacy_orders.mode_delivery') : __('pharmacy_orders.mode_pickup') }}</span>
        </div>

        <div class="ph-section">
            <div class="ph-section__title"><i data-lucide="user"></i> {{ __('pharmacy_orders.section_patient') }}</div>
            <dl class="ph-dl">
                <dt>{{ __('pharmacy_orders.col_patient') }}</dt>
                <dd class="td-strong">{{ $name !== '' ? $name : '—' }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_health_id') }}</dt>
                <dd class="fs-mono">{{ $patient?->health_id ?? '—' }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_phone') }}</dt>
                <dd>{{ $patient?->phone_number ?: __('pharmacy_orders.not_provided') }}</dd>
            </dl>
        </div>

        @if($reservation->isDelivery())
        <div class="ph-section">
            <div class="ph-section__title"><i data-lucide="truck"></i> {{ __('pharmacy_orders.section_delivery') }}</div>
            <dl class="ph-dl">
                <dt>{{ __('pharmacy_orders.lbl_delivery_address') }}</dt>
                <dd>{{ $reservation->delivery_address ?: __('pharmacy_orders.not_provided') }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_delivery_phone') }}</dt>
                <dd>{{ $reservation->delivery_phone ?: __('pharmacy_orders.not_provided') }}</dd>
            </dl>
        </div>
        @endif

        <div class="ph-section">
            <div class="ph-section__title"><i data-lucide="history"></i> {{ __('pharmacy_orders.section_timeline') }}</div>
            <ul class="ph-timeline">
                @foreach($timeline as $key => $at)
                    @if($at)
                        <li>
                            <span class="ph-timeline__dot {{ $key === 'cancelled' ? 'ph-timeline__dot--pending' : '' }}"></span>
                            <div>
                                <div class="ph-timeline__label">{{ __('pharmacy_orders.ts_' . $key) }}</div>
                                <div class="ph-timeline__when fs-mono">{{ $at->translatedFormat('d M Y H:i') }}</div>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

</div>

@endsection
