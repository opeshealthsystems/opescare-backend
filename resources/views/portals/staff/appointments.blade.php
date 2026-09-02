@extends('layouts.portal')

@section('title', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.appointments_subtitle', [], app()->getLocale()) ?: 'View and manage scheduled patient appointments.' }}</p>
    </div>
    <div>
        <a href="{{ route('portals.staff.appointments.create') }}" class="btn btn-primary">
            <i data-lucide="calendar-plus"></i>
            {{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment' }}
        </a>
    </div>
</div>

{{-- Filter Bar --}}
<form method="GET" action="{{ route('portals.staff.appointments') }}" class="filter-bar">
    <div class="form-search">
        <span class="search-icon">
            <i data-lucide="search"></i>
        </span>
        <input
            type="text"
            name="patient_id"
            class="form-control"
            placeholder="{{ __('public.staff_portal.filter_patient_id', [], app()->getLocale()) ?: 'Patient ID…' }}"
            value="{{ request('patient_id') }}"
        >
    </div>
    <input
        type="text"
        name="facility_id"
        class="form-control"
        placeholder="{{ __('public.staff_portal.filter_facility', [], app()->getLocale()) ?: 'Facility ID…' }}"
        value="{{ request('facility_id') }}"
    >
    <input
        type="text"
        name="provider_id"
        class="form-control"
        placeholder="{{ __('public.staff_portal.filter_provider', [], app()->getLocale()) ?: 'Provider ID…' }}"
        value="{{ request('provider_id') }}"
    >
    <input
        type="date"
        name="date"
        class="form-control"
        value="{{ request('date') }}"
    >
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i>
        {{ __('public.staff_portal.filter_apply', [], app()->getLocale()) ?: 'Filter' }}
    </button>
    <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost btn-sm">
        {{ __('public.staff_portal.filter_clear', [], app()->getLocale()) ?: 'Clear' }}
    </a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if(count($appointments) === 0)
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="calendar-x-2"></i>
                </div>
                <h3>{{ __('public.staff_portal.no_appointments_title', [], app()->getLocale()) ?: 'No Appointments Found' }}</h3>
                <p>{{ __('public.staff_portal.no_appointments_desc', [], app()->getLocale()) ?: 'There are no appointments matching your current filters.' }}</p>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('public.staff_portal.col_datetime', [], app()->getLocale()) ?: 'Date / Time' }}</th>
                            <th>{{ __('public.staff_portal.col_patient_id', [], app()->getLocale()) ?: 'Patient ID' }}</th>
                            <th>{{ __('public.staff_portal.col_provider', [], app()->getLocale()) ?: 'Provider' }}</th>
                            <th>{{ __('public.staff_portal.col_type', [], app()->getLocale()) ?: 'Type' }}</th>
                            <th>{{ __('public.staff_portal.col_facility', [], app()->getLocale()) ?: 'Facility' }}</th>
                            <th>{{ __('public.staff_portal.col_status', [], app()->getLocale()) ?: 'Status' }}</th>
                            <th>{{ __('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointments as $appointment)
                        @php
                            $statusBadge = match($appointment->status ?? '') {
                                'completed'  => 'badge-success',
                                'cancelled'  => 'badge-danger',
                                'no_show'    => 'badge-warning',
                                'checked_in' => 'badge-teal',
                                default      => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('public.staff_portal.col_datetime', [], app()->getLocale()) ?: 'Date / Time' }}">
                                {{ \Carbon\Carbon::parse($appointment->scheduled_at ?? $appointment->created_at)->format('M d, Y H:i') }}
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_patient_id', [], app()->getLocale()) ?: 'Patient ID' }}">
                                <span class="mono">{{ $appointment->patient?->health_id ?? ($appointment->patient_id ? '#'.$appointment->patient_id : '—') }}</span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_provider', [], app()->getLocale()) ?: 'Provider' }}">
                                {{ $appointment->provider_name ?? $appointment->provider_id ?? '--' }}
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_type', [], app()->getLocale()) ?: 'Type' }}">
                                <span class="badge badge-primary">@enum($appointment->appointment_type ?? 'General')</span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_facility', [], app()->getLocale()) ?: 'Facility' }}">
                                {{ $appointment->facility_name ?? $appointment->facility_id ?? '--' }}
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_status', [], app()->getLocale()) ?: 'Status' }}">
                                <span class="badge {{ $statusBadge }}">@enum($appointment->status ?? 'Unknown')</span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_actions', [], app()->getLocale()) ?: 'Actions' }}">
                                <a href="{{ route('portals.staff.appointments') }}?view={{ $appointment->id ?? $appointment->uuid ?? '' }}" class="btn btn-ghost btn-sm">
                                    <i data-lucide="eye"></i>
                                    {{ __('public.staff_portal.action_view', [], app()->getLocale()) ?: 'View' }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
