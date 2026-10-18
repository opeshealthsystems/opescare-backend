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
    $odInitials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $odTone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp
<div class="od-table-wrap odc-gap">
    <table class="od-table odc-stack">
        <thead>
            <tr>
                <th class="od-table__num">#</th>
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
            @foreach($rows as $i => $appointment)
            @php
                $when = \Carbon\Carbon::parse($appointment->scheduled_at ?? $appointment->created_at);
                $status = $appointment->status ?? '';
                $statusPill = match($status) {
                    'completed'  => 'ready',
                    'confirmed'  => 'ready',
                    'cancelled'  => 'urgent',
                    'no_show'    => 'pending',
                    'checked_in' => 'teal',
                    default      => 'neutral',
                };
                $patientLabel = $appointment->patient?->health_id
                    ?? ($appointment->patient_id ? '#'.$appointment->patient_id : '—');
                $patientName = trim(($appointment->patient?->first_name ?? '') . ' ' . ($appointment->patient?->last_name ?? ''));
                $phone   = $appointment->patient?->phone_number;
                $dial    = $phone ? preg_replace('/[^0-9+]/', '', $phone) : null;
                $canConfirm = $canAct && in_array($status, $pendingStatuses, true);
                $canNoShow  = $canAct && in_array($status, $noShowFrom, true);
                $canCancel  = $canAct && in_array($status, $cancelFrom, true);
                $canCheckIn = $canQueue && in_array($status, $checkInFrom, true);
            @endphp
            <tr>
                <td class="od-table__num">{{ $i + 1 }}</td>
                <td data-label="{{ __('public.staff_portal.col_datetime', [], $lApp) }}">
                    <span class="od-table__name">{{ ($timeOnly ?? false) ? $when->format('H:i') : $when->translatedFormat('M d, Y H:i') }}</span>
                </td>
                <td data-label="{{ __('public.staff_portal.col_patient_id', [], $lApp) }}">
                    <div class="od-table__person">
                        <span class="od-avatar od-tint--{{ $odTone($patientName !== '' ? $patientName : (string) $patientLabel) }}" aria-hidden="true">{{ $odInitials($appointment->patient?->first_name, $appointment->patient?->last_name) }}</span>
                        <div>
                            <span class="od-table__name">{{ $patientName !== '' ? $patientName : $patientLabel }}</span>
                            @if($patientName !== '')<span class="od-table__sub odc-mono">{{ $patientLabel }}</span>@endif
                            @if($dial)
                                {{-- The number a desk actually needs: tap to call. --}}
                                <a class="odc-tel" href="tel:{{ $dial }}" aria-label="{{ __('finder_supply.call_patient') }}">
                                    <i data-lucide="phone"></i>
                                    {{ $phone }}
                                </a>
                            @endif
                        </div>
                    </div>
                </td>
                <td data-label="{{ __('staff.desk.col_clinician') }}">
                    {{-- The clinician's name (eager-loaded), never a raw uuid. --}}
                    {{ $appointment->provider?->name ?? __('staff.desk.unassigned') }}
                </td>
                <td data-label="{{ __('public.staff_portal.col_type', [], $lApp) }}">
                    <span class="od-pill od-pill--normal">@enum($appointment->appointment_type ?? 'General')</span>
                </td>
                <td data-label="{{ __('public.staff_portal.col_facility', [], $lApp) }}">
                    {{-- Always the session's facility (the query is scoped to it);
                         its name, not the uuid this column used to print. --}}
                    {{ $facilityName ?? '—' }}
                </td>
                <td data-label="{{ __('public.staff_portal.col_status', [], $lApp) }}">
                    <span class="od-pill od-pill--dot od-pill--{{ $statusPill }}">@enum($appointment->status ?? 'Unknown')</span>
                </td>
                <td class="od-table__actions" data-label="{{ __('public.staff_portal.col_actions', [], $lApp) }}">
                    @if($canConfirm || $canNoShow || $canCancel || $canCheckIn)
                    <div class="odc-rowacts">
                        @if($canCheckIn)
                        <form method="POST" action="{{ route('portals.staff.appointments.check-in', $appointment->id) }}" data-confirm="{{ __('hospital_desk.confirm_appt_check_in') }}">
                            @csrf
                            <button type="submit" class="od-btn od-btn--row">
                                <i data-lucide="log-in"></i>
                                {{ __('staff.desk.btn_check_in') }}
                            </button>
                        </form>
                        @endif
                        @if($canConfirm)
                        <form method="POST" action="{{ route('portals.staff.appointments.confirm', $appointment->id) }}" data-confirm="{{ __('hospital_desk.confirm_appt_confirm') }}">
                            @csrf
                            <button type="submit" class="od-btn od-btn--row odc-btn-ok">
                                <i data-lucide="check"></i>
                                {{ __('finder_supply.btn_confirm') }}
                            </button>
                        </form>
                        @endif
                        @if($canNoShow)
                        <form method="POST" action="{{ route('portals.staff.appointments.no-show', $appointment->id) }}" data-confirm="{{ __('hospital_desk.confirm_appt_no_show') }}">
                            @csrf
                            <button type="submit" class="od-btn od-btn--sm odc-btn-quiet">
                                <i data-lucide="clock"></i>
                                {{ __('finder_supply.btn_no_show') }}
                            </button>
                        </form>
                        @endif
                        @if($canCancel)
                        <button type="button" class="od-btn od-btn--sm odc-btn-danger"
                                data-call="openApptCancelModal" data-args="{{ json_encode([$appointment->id, $patientLabel . ' · ' . $when->translatedFormat('M d, H:i')]) }}">
                            <i data-lucide="x"></i>
                            {{ __('finder_supply.btn_cancel') }}
                        </button>
                        @endif
                    </div>
                    @else
                        <span class="od-muted">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
