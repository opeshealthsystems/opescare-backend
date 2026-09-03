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
@php $lApp = app()->getLocale(); @endphp
<div class="panel mb-6">
    <div class="panel-body panel-body--flush">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('public.staff_portal.col_datetime', [], $lApp) ?: 'Date / Time' }}</th>
                        <th>{{ __('public.staff_portal.col_patient_id', [], $lApp) ?: 'Patient ID' }}</th>
                        <th>{{ __('public.staff_portal.col_provider', [], $lApp) ?: 'Provider' }}</th>
                        <th>{{ __('public.staff_portal.col_type', [], $lApp) ?: 'Type' }}</th>
                        <th>{{ __('public.staff_portal.col_facility', [], $lApp) ?: 'Facility' }}</th>
                        <th>{{ __('public.staff_portal.col_status', [], $lApp) ?: 'Status' }}</th>
                        <th>{{ __('public.staff_portal.col_actions', [], $lApp) ?: 'Actions' }}</th>
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
                        $canConfirm = $status === 'scheduled';
                        $canNoShow  = in_array($status, ['scheduled', 'confirmed'], true);
                        $canCancel  = in_array($status, ['scheduled', 'confirmed'], true);
                    @endphp
                    <tr>
                        <td data-label="{{ __('public.staff_portal.col_datetime', [], $lApp) ?: 'Date / Time' }}">
                            <span class="td-strong">{{ ($timeOnly ?? false) ? $when->format('H:i') : $when->format('M d, Y H:i') }}</span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_patient_id', [], $lApp) ?: 'Patient ID' }}">
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
                        <td data-label="{{ __('public.staff_portal.col_provider', [], $lApp) ?: 'Provider' }}">
                            {{ $appointment->provider_name ?? $appointment->provider_id ?? '--' }}
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_type', [], $lApp) ?: 'Type' }}">
                            <span class="badge badge-primary">@enum($appointment->appointment_type ?? 'General')</span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_facility', [], $lApp) ?: 'Facility' }}">
                            {{ $appointment->facility_name ?? $appointment->facility_id ?? '--' }}
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_status', [], $lApp) ?: 'Status' }}">
                            <span class="badge {{ $statusBadge }}">@enum($appointment->status ?? 'Unknown')</span>
                        </td>
                        <td data-label="{{ __('public.staff_portal.col_actions', [], $lApp) ?: 'Actions' }}">
                            @if($canConfirm || $canNoShow || $canCancel)
                            <div class="fs-act">
                                @if($canConfirm)
                                <form method="POST" action="{{ route('portals.staff.appointments.confirm', $appointment->id) }}">
                                    @csrf
                                    <button type="submit" class="fs-act-btn fs-act-btn--good">
                                        @include('partials.finder.icon', ['name' => 'check', 'size' => 15])
                                        {{ __('finder_supply.btn_confirm') }}
                                    </button>
                                </form>
                                @endif
                                @if($canNoShow)
                                <form method="POST" action="{{ route('portals.staff.appointments.no-show', $appointment->id) }}">
                                    @csrf
                                    <button type="submit" class="fs-act-btn fs-act-btn--muted">
                                        @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
                                        {{ __('finder_supply.btn_no_show') }}
                                    </button>
                                </form>
                                @endif
                                @if($canCancel)
                                <button type="button" class="fs-act-btn fs-act-btn--danger"
                                        onclick="openApptCancelModal('{{ $appointment->id }}', '{{ addslashes($patientLabel) }} · {{ $when->format('M d, H:i') }}')">
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
