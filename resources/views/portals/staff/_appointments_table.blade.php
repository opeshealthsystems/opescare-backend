{{--
    Appointment desk table — one group of rows (today / other days).

    Props:
        $rows      Collection of appointments to render
        $timeOnly  bool — today's rows show just the time, others full date

    Presentation only: Confirm / No-show post to their existing routes,
    Cancel opens the shared reason modal on the parent screen (the
    controller validates the reason). Colors are semantic: good = confirm,
    muted = no-show, danger = cancel.
--}}
@php
    $lApp = app()->getLocale();
    // The action buttons post to routes behind capability:front_desk,schedule
    // (routes/web.php); a role without either sees the row read-only instead of
    // a button that 403s.
    $canAct = \App\Support\StaffCapabilities::can(auth()->user(), 'front_desk', 'schedule');
    // Check-in posts to portals.staff.appointments.check-in, which now issues
    // the queue ticket through PatientQueueService — the same act as the queue
    // desk's "Check in booking". Offered only to roles that could also open the
    // queue, so the button never lands a patient in a room the clerk cannot see.
    $canQueue = $canAct && \App\Support\StaffCapabilities::can(auth()->user(), 'queue');
    // Status vocabulary from the one service that owns it. `booked` is what a
    // patient's own request from the app is written as; the old hand-written
    // lists knew only `scheduled`, so those bookings showed no action at all.
    $pendingStatuses = \App\Services\FrontDesk\FrontDeskService::PENDING_STATUSES;
    $noShowFrom      = \App\Services\FrontDesk\FrontDeskService::NO_SHOW_FROM;
    $checkInFrom     = \App\Services\FrontDesk\FrontDeskService::CHECK_IN_FROM;
    $cancelFrom      = array_merge($pendingStatuses, ['confirmed']);
@endphp
<div class="panel mb-6">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.staff_portal.col_datetime', [], $lApp) }}</th>
                        <th>{{ __('public.staff_portal.col_patient_id', [], $lApp) }}</th>
                        <th>{{ __('staff.desk.col_clinician') }}</th>
                        <th>{{ __('public.staff_portal.col_type', [], $lApp) }}</th>
                        <th>{{ __('public.staff_portal.col_facility', [], $lApp) }}</th>
                        <th>{{ __('public.staff_portal.col_status', [], $lApp) }}</th>
                        <th>{{ __('public.staff_portal.col_actions', [], $lApp) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $appointment)
                    @php
                        $when = \Carbon\Carbon::parse($appointment->scheduled_at ?? $appointment->created_at);
                        $status = $appointment->status ?? '';
                        $statusBadge = match($status) {
                            'completed'  => 'badge-success',
                            'confirmed'  => 'badge-success',
                            'cancelled'  => 'badge-danger',
                            'no_show'    => 'badge-warning',
                            'checked_in' => 'badge-teal',
                            default      => 'badge-neutral',
                        };
                        $patientLabel = $appointment->patient?->health_id
                            ?? ($appointment->patient_id ? '#'.$appointment->patient_id : '—');
                        $phone   = $appointment->patient?->phone_number;
                        $dial    = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
                        $canConfirm = $canAct && in_array($status, $pendingStatuses, true);
                        $canNoShow  = $canAct && in_array($status, $noShowFrom, true);
                        $canCancel  = $canAct && in_array($status, $cancelFrom, true);
                        $canCheckIn = $canQueue && in_array($status, $checkInFrom, true);
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.staff_portal.col_datetime', [], $lApp) }}">
                            <span class="td-strong">{{ ($timeOnly ?? false) ? $when->format('H:i') : $when->translatedFormat('M d, Y H:i') }}</span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_patient_id', [], $lApp) }}">
                            <span class="mono">{{ $patientLabel }}</span>
                            @if($dial)
                                {{-- The number a desk actually needs: tap to call. --}}
                                <div>
                                    <a class="fs-tel" href="tel:{{ $dial }}" aria-label="{{ __('finder_supply.call_patient') }}">
                                        @include('partials.finder.icon', ['name' => 'phone', 'size' => 14])
                                        {{ $phone }}
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td data-label="{{ __('staff.desk.col_clinician') }}">
                            {{-- The clinician's name (eager-loaded), never a raw uuid. --}}
                            {{ $appointment->provider?->name ?? __('staff.desk.unassigned') }}
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_type', [], $lApp) }}">
                            <span class="badge badge-primary">@enum($appointment->appointment_type ?? 'General')</span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_facility', [], $lApp) }}">
                            {{-- Always the session's facility (the query is scoped to it);
                                 its name, not the uuid this column used to print. --}}
                            {{ $facilityName ?? '—' }}
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_status', [], $lApp) }}">
                            <span class="badge {{ $statusBadge }}">@enum($appointment->status ?? 'Unknown')</span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_actions', [], $lApp) }}">
                            @if($canConfirm || $canNoShow || $canCancel || $canCheckIn)
                            <div class="fs-act">
                                @if($canCheckIn)
                                <form method="POST" action="{{ route('portals.staff.appointments.check-in', $appointment->id) }}" data-confirm="{{ __('hospital_desk.confirm_appt_check_in') }}">
                                    @csrf
                                    <button type="submit" class="fs-act-btn fs-act-btn--good">
                                        @include('partials.finder.icon', ['name' => 'log-in', 'size' => 15])
                                        {{ __('staff.desk.btn_check_in') }}
                                    </button>
                                </form>
                                @endif
                                @if($canConfirm)
                                <form method="POST" action="{{ route('portals.staff.appointments.confirm', $appointment->id) }}" data-confirm="{{ __('hospital_desk.confirm_appt_confirm') }}">
                                    @csrf
                                    <button type="submit" class="fs-act-btn fs-act-btn--good">
                                        @include('partials.finder.icon', ['name' => 'check', 'size' => 15])
                                        {{ __('finder_supply.btn_confirm') }}
                                    </button>
                                </form>
                                @endif
                                @if($canNoShow)
                                <form method="POST" action="{{ route('portals.staff.appointments.no-show', $appointment->id) }}" data-confirm="{{ __('hospital_desk.confirm_appt_no_show') }}">
                                    @csrf
                                    <button type="submit" class="fs-act-btn fs-act-btn--muted">
                                        @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                                        {{ __('finder_supply.btn_no_show') }}
                                    </button>
                                </form>
                                @endif
                                @if($canCancel)
                                <button type="button" class="fs-act-btn fs-act-btn--danger"
                                        data-call="openApptCancelModal" data-args="{{ json_encode([$appointment->id, $patientLabel . ' · ' . $when->translatedFormat('M d, H:i')]) }}">
                                    @include('partials.finder.icon', ['name' => 'x', 'size' => 15])
                                    {{ __('finder_supply.btn_cancel') }}
                                </button>
                                @endif
                            </div>
                            @else
                                <span class="td-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
