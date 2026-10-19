@extends('layouts.portal')
@section('title', __('pharmacy_orders.show_title', ['reference' => $reservation->reference]))
@section('sidebar_user_role', __('public.pharmacy_portal.role_label'))

@php
    $patient = $reservation->patient;
    $name    = $patient ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) : '';
    $initials = $patient ? mb_strtoupper(mb_substr((string) ($patient->first_name ?? ''), 0, 1) . mb_substr((string) ($patient->last_name ?? ''), 0, 1)) : '';
    $closed  = in_array($desk, [
        \App\Models\MedicineReservation::DESK_COLLECTED,
        \App\Models\MedicineReservation::DESK_DELIVERED,
        \App\Models\MedicineReservation::DESK_CANCELLED,
    ], true);
    $deskPill = match ($desk) {
        \App\Models\MedicineReservation::DESK_PENDING   => 'pending',
        \App\Models\MedicineReservation::DESK_CANCELLED => 'neutral',
        \App\Models\MedicineReservation::DESK_COLLECTED,
        \App\Models\MedicineReservation::DESK_DELIVERED => 'ready',
        default                                          => 'verifying',
    };
    $tone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($name !== '' ? $name : (string) $reservation->id) % 6];
    $timeline = [
        'placed'     => $reservation->created_at,
        'accepted'   => $reservation->accepted_at,
        'ready'      => $reservation->ready_at,
        'dispatched' => $reservation->dispatched_at,
        'delivered'  => $reservation->delivered_at,
        'collected'  => $reservation->isDelivery() ? null : $reservation->collected_at,
        'cancelled'  => $reservation->cancelled_at,
    ];
    $money = fn ($v) => $v !== null ? \App\Services\Billing\Xaf::format(\App\Services\Billing\Xaf::int($v)) : null;
@endphp

@section('head')
    <link rel="stylesheet" href="{{ asset('css/od-pharmacy.css') }}?v={{ @filemtime(public_path('css/od-pharmacy.css')) ?: '1' }}">
@endsection

@section('content')
@if(session('warning'))
<div class="odp-notice od-tint--amber" role="status"><i data-lucide="alert-triangle"></i><div>{{ session('warning') }}</div></div>
@endif

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('pharmacy_orders.show_title', ['reference' => $reservation->reference]) }}</h1>
        <p class="od-page__sub"><span class="od-pill od-pill--{{ $deskPill }}">@enum($desk, 'reservation_status')</span></p>
    </div>
    <a href="{{ route('portals.pharmacy.orders.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('pharmacy_orders.btn_back') }}</a>
</div>

<div class="od-grid od-grid--split">

    <div class="od-stack">
        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_orders.section_order') }}</h2></header>
            <div class="od-card__body">
                <dl class="odp-dl">
                    <dt>{{ __('pharmacy_orders.col_medicine') }}</dt>
                    <dd><span class="od-strong">{{ $reservation->medicine?->name ?? '—' }}</span>
                        <span class="od-table__sub">{{ $reservation->medicine?->strength }} {{ $reservation->medicine?->form }}</span></dd>
                    <dt>{{ __('pharmacy_orders.col_qty') }}</dt>
                    <dd class="odp-mono">{{ $reservation->quantity }}</dd>
                    @if(in_array($desk, [\App\Models\MedicineReservation::DESK_COLLECTED, \App\Models\MedicineReservation::DESK_DELIVERED], true))
                    <dt>{{ __('pharmacy_orders.lbl_stock_ledger') }}</dt>
                    <dd>@if(($stockRecorded ?? 0) > 0){{ __('pharmacy_orders.stock_recorded', ['count' => $stockRecorded]) }} <a href="{{ route('portals.pharmacy.stock.adjustments') }}">{{ __('pharmacy_orders.stock_view_log') }}</a>@else<span class="od-muted">{{ __('pharmacy_orders.stock_not_recorded') }}</span> <a href="{{ route('portals.pharmacy.stock.adjustments') }}">{{ __('pharmacy_orders.stock_record_by_hand') }}</a>@endif</dd>
                    @endif
                    <dt>{{ __('pharmacy_orders.col_pack') }}</dt>
                    <dd>{{ $reservation->pack_size ?? '—' }}</dd>
                    <dt>{{ __('pharmacy_orders.lbl_unit_price') }}</dt>
                    <dd class="odp-mono">{!! $money($reservation->unit_price) !== null ? e($money($reservation->unit_price)) : '<span class="od-muted">' . e(__('pharmacy_orders.no_price')) . '</span>' !!}</dd>
                    <dt>{{ __('pharmacy_orders.col_total') }}</dt>
                    <dd class="odp-mono od-strong">{!! $money($reservation->total_price) !== null ? e($money($reservation->total_price)) : '<span class="od-muted">' . e(__('pharmacy_orders.no_price')) . '</span>' !!}</dd>
                    <dt>{{ __('pharmacy_orders.lbl_fulfilment') }}</dt>
                    <dd>{{ $reservation->isDelivery() ? __('pharmacy_orders.mode_delivery') : __('pharmacy_orders.mode_pickup') }}</dd>
                    <dt>{{ __('pharmacy_orders.col_expires') }}</dt>
                    <dd>{{ $reservation->expires_at?->translatedFormat('d M Y H:i') ?? __('pharmacy_orders.no_expiry') }}</dd>
                    @if($reservation->prescription_id)
                    <dt>{{ __('pharmacy_orders.lbl_prescription') }}</dt>
                    <dd class="odp-mono">{{ $reservation->prescription?->reference ?? '—' }}</dd>
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
        </section>

        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_orders.section_actions') }}</h2></header>
            <div class="od-card__body od-stack">
                @if($closed)
                    <p class="od-muted">{{ __('pharmacy_orders.closed_hint') }}</p>
                @else
                    @include('portals.pharmacy.orders._actions', ['reservation' => $reservation, 'desk' => $desk, 'compact' => false])

                    <form method="POST" action="{{ route('portals.pharmacy.orders.cancel', $reservation->id) }}" data-confirm="{{ __('pharmacy_orders.confirm_cancel') }}" class="odp-form-row">
                        @csrf
                        <label for="po-reason">{{ __('pharmacy_orders.lbl_reason') }}
                            <input type="text" name="reason" id="po-reason" class="od-input" minlength="3" maxlength="300" required placeholder="{{ __('pharmacy_orders.reason_placeholder') }}" value="{{ old('reason') }}">
                            @error('reason')<span class="od-kpi__sub--alert">{{ $message }}</span>@enderror
                        </label>
                        <button type="submit" class="od-btn od-btn--danger"><i data-lucide="x-circle"></i> {{ __('pharmacy_orders.btn_cancel') }}</button>
                    </form>
                @endif
            </div>
        </section>
    </div>

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('pharmacy_orders.section_patient') }}</h2></header>
        <div class="od-card__body od-detail">
            <div class="od-detail__head">
                <span class="od-avatar od-avatar--lg od-tint--{{ $tone }}" aria-hidden="true">{{ $initials !== '' ? $initials : '—' }}</span>
                <div class="od-detail__main">
                    <h3 class="od-detail__name">{{ $name !== '' ? $name : '—' }}</h3>
                    <div class="od-detail__ref">
                        <span>{{ $reservation->reference }}</span>
                        <span>{{ $reservation->created_at?->translatedFormat('d M Y H:i') }}</span>
                    </div>
                    <div class="od-detail__facts">
                        <span class="od-detail__fact" title="{{ __('pharmacy_orders.lbl_health_id') }}"><i data-lucide="id-card"></i> {{ $patient?->health_id ?? '—' }}</span>
                        <span class="od-detail__fact" title="{{ __('pharmacy_orders.lbl_phone') }}"><i data-lucide="phone"></i> {{ $patient?->phone_number ?: __('pharmacy_orders.not_provided') }}</span>
                        <span class="od-detail__fact" title="{{ __('pharmacy_orders.lbl_fulfilment') }}"><i data-lucide="{{ $reservation->isDelivery() ? 'truck' : 'store' }}"></i> {{ $reservation->isDelivery() ? __('pharmacy_orders.mode_delivery') : __('pharmacy_orders.mode_pickup') }}</span>
                    </div>
                </div>
            </div>

            <dl class="odp-dl">
                <dt>{{ __('pharmacy_orders.col_patient') }}</dt>
                <dd class="od-strong">{{ $name !== '' ? $name : '—' }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_health_id') }}</dt>
                <dd class="odp-mono">{{ $patient?->health_id ?? '—' }}</dd>
                <dt>{{ __('pharmacy_orders.lbl_phone') }}</dt>
                <dd>{{ $patient?->phone_number ?: __('pharmacy_orders.not_provided') }}</dd>
            </dl>

            @if($reservation->isDelivery())
            <div>
                <h3 class="od-card__title">{{ __('pharmacy_orders.section_delivery') }}</h3>
                <dl class="odp-dl">
                    <dt>{{ __('pharmacy_orders.lbl_delivery_address') }}</dt>
                    <dd>{{ $reservation->delivery_address ?: __('pharmacy_orders.not_provided') }}</dd>
                    <dt>{{ __('pharmacy_orders.lbl_delivery_phone') }}</dt>
                    <dd>{{ $reservation->delivery_phone ?: __('pharmacy_orders.not_provided') }}</dd>
                </dl>
            </div>
            @endif

            <div class="od-stack">
                <h3 class="od-card__title">{{ __('pharmacy_orders.section_timeline') }}</h3>
                <ul class="odp-timeline">
                    @foreach($timeline as $key => $at)
                        @if($at)
                            <li>
                                <span class="odp-timeline__dot"></span>
                                <div>
                                    <span class="od-list__title">{{ __('pharmacy_orders.ts_' . $key) }}</span>
                                    <span class="od-list__sub">{{ $at->translatedFormat('d M Y H:i') }}</span>
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

</div>

@endsection
