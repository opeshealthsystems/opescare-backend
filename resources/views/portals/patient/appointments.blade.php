@extends('layouts.portal')

@section('title', __('public.portal.nav_appointments', [], app()->getLocale()) . ' — OpesCare Patient Portal')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments')


@section('patient_banner')
    @include('partials.guardian-context-banner')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'My Appointments' }}</h1>
        <p class="page-subtitle">{{ __('public.portal.appointments_subtitle', [], app()->getLocale()) ?: 'View your upcoming and past appointments.' }}</p>
    </div>
    <a href="{{ route('portals.patient.appointments.book') }}" class="btn btn-primary">
        <i data-lucide="calendar-plus"></i> {{ __('public.appt_book_btn') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-info mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

@php $apptCount = method_exists($appointments, 'total') ? $appointments->total() : $appointments->count(); @endphp

@if($apptCount === 0)
<div class="panel">
    <div class="empty-state">
        <div class="empty-state-icon"><i data-lucide="calendar-check-2"></i></div>
        <h3>{{ __('public.portal.no_appointments_title', [], app()->getLocale()) ?: 'No Appointments' }}</h3>
        <p>{{ __('public.portal.no_appointments_desc', [], app()->getLocale()) ?: 'You don\'t have any appointments scheduled at this time.' }}</p>
        <a href="{{ route('public.care-map') }}" class="btn btn-primary">
            <i data-lucide="map-pin"></i>
            {{ __('public.portal.nav_care_map', [], app()->getLocale()) ?: 'Find a Provider' }}
        </a>
    </div>
</div>
@else
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="calendar-check-2"></i> {{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</h2>
        <span class="badge badge-primary">{{ $apptCount }}</span>
    </div>
    <div class="table-wrapper">
        <table class="data-table" aria-label="{{ __('public.aria_appointments_list') }}">
            <thead>
                <tr>
                    <th>{{ __('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time' }}</th>
                    <th>{{ __('public.portal.provider', [], app()->getLocale()) ?: 'Provider' }}</th>
                    <th>{{ __('public.portal.appointment_type', [], app()->getLocale()) ?: 'Type' }}</th>
                    <th>{{ __('public.portal.facility', [], app()->getLocale()) ?: 'Facility' }}</th>
                    <th>{{ __('public.portal.status', [], app()->getLocale()) ?: 'Status' }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($appointments as $appt)
                <tr>
                    <td data-label="{{ __('public.portal.date_time', [], app()->getLocale()) ?: 'Date & Time' }}">
                        <span class="td-strong">
                            {{ $appt->scheduled_at?->format('d M Y') ?? '—' }}
                        </span>
                        @if($appt->scheduled_at)
                        <div class="td-muted">{{ $appt->scheduled_at->format('H:i') }}</div>
                        @endif
                    </td>
                    <td data-label="{{ __('public.portal.provider', [], app()->getLocale()) ?: 'Provider' }}">
                        @php
                            $providerName = $appt->provider?->name
                                ?? (($appt->provider?->first_name ?? '') . ' ' . ($appt->provider?->last_name ?? ''))
                                ?: '—';
                        @endphp
                        <span class="td-strong">{{ trim($providerName) ?: '—' }}</span>
                    </td>
                    <td data-label="{{ __('public.portal.appointment_type', [], app()->getLocale()) ?: 'Type' }}">
                        <span class="badge badge-primary">@enum($appt->appointment_type ?? 'General')</span>
                    </td>
                    <td data-label="{{ __('public.portal.facility', [], app()->getLocale()) ?: 'Facility' }}">
                        <span class="td-muted">{{ $appt->facility?->name ?? __('public.portal.unknown_facility', [], app()->getLocale()) ?: 'Unknown Facility' }}</span>
                    </td>
                    <td data-label="{{ __('public.portal.status', [], app()->getLocale()) ?: 'Status' }}">
                        @php
                            $stCls = match($appt->status ?? 'scheduled') {
                                'completed'  => 'badge-success',
                                'cancelled'  => 'badge-danger',
                                'no_show'    => 'badge-warning',
                                'checked_in' => 'badge-teal',
                                default      => 'badge-primary',
                            };
                        @endphp
                        <span class="badge {{ $stCls }}">@enum($appt->status ?? 'Scheduled')</span>
                    </td>
                    <td class="row-actions">
                        @if(in_array($appt->status, ['requested', 'scheduled', 'confirmed']))
                        <a href="{{ route('portals.patient.appointments.reschedule', $appt->id) }}" class="btn btn-secondary btn-sm">
                            <i data-lucide="calendar-clock"></i> {{ __('public.appt_reschedule_btn') }}
                        </a>
                        <button type="button" class="btn btn-danger btn-sm" onclick="opOpenModal('cancel-appt-{{ $appt->id }}')">
                            <i data-lucide="x-circle"></i> {{ __('public.portal.btn_cancel_appointment', [], app()->getLocale()) ?: 'Cancel' }}
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(method_exists($appointments, 'links'))
    <div class="panel-body">
        {{ $appointments->links() }}
    </div>
    @endif
</div>

@foreach($appointments as $appt)
    @if(in_array($appt->status, ['requested', 'scheduled', 'confirmed']))
    <div id="cancel-appt-{{ $appt->id }}" class="modal-backdrop mt-6" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancel-appt-{{ $appt->id }}-title">
            <h3 class="modal__title" id="cancel-appt-{{ $appt->id }}-title"><i data-lucide="x-circle"></i> {{ __('public.portal.modal_cancel_appt_title', [], app()->getLocale()) ?: 'Cancel appointment' }}</h3>
            <form method="POST" action="{{ route('portals.patient.appointments.cancel', $appt->id) }}">
                @csrf
                <div class="modal__body">
                    <p>{{ __('public.portal.modal_cancel_appt_body', [], app()->getLocale()) ?: 'Cancel this appointment? This action cannot be undone.' }}</p>
                </div>
                <div class="modal__footer">
                    <button type="button" class="btn btn-ghost" onclick="opCloseModal('cancel-appt-{{ $appt->id }}')">{{ __('public.portal.btn_keep_appointment', [], app()->getLocale()) ?: 'Keep appointment' }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('public.portal.btn_cancel_appointment', [], app()->getLocale()) ?: 'Cancel appointment' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endforeach
@endif

@endsection

@section('scripts')
<script>
function opOpenModal(id){ document.getElementById(id).removeAttribute('hidden'); }
function opCloseModal(id){ document.getElementById(id).setAttribute('hidden',''); }
document.addEventListener('keydown', function(e){
    if(e.key==='Escape'){ document.querySelectorAll('.modal-backdrop').forEach(function(m){ m.setAttribute('hidden',''); }); }
});
</script>
@endsection
