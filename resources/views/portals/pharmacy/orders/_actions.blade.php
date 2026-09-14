{{--
    The one forward step a hold admits from where it is, as a POST form with
    a confirm(). $reservation, $desk (deskStatus()) and $compact (index row:
    primary step only) are passed in. The state machine is the service's;
    this only picks which button to draw.
--}}
@php
    $step = match ($desk) {
        \App\Models\MedicineReservation::DESK_PENDING    => 'accept',
        \App\Models\MedicineReservation::DESK_ACCEPTED   => 'ready',
        \App\Models\MedicineReservation::DESK_READY      => $reservation->isDelivery() ? 'dispatch' : 'collected',
        \App\Models\MedicineReservation::DESK_DISPATCHED => 'delivered',
        default => null,
    };
    $icon = match ($step) {
        'accept'    => 'check',
        'ready'     => 'package-check',
        'collected' => 'hand-coins',
        'dispatch'  => 'truck',
        'delivered' => 'package-open',
        default     => null,
    };
@endphp

@if($step)
    <form method="POST" action="{{ route('portals.pharmacy.orders.' . $step, $reservation->id) }}" class="po-inline" onsubmit="return confirm(@json(__('pharmacy_orders.confirm_' . $step)));">
        @csrf
        <button type="submit" class="btn btn-primary {{ $compact ? 'btn-sm' : 'fs-tap' }}"><i data-lucide="{{ $icon }}"></i> {{ __('pharmacy_orders.btn_' . $step) }}</button>
    </form>
@endif
