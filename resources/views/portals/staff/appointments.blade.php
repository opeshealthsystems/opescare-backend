@extends('layouts.portal')

@section('title', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments')

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@php
    // Desk view: today's list first (soonest first), everything else after,
    // in the order the controller delivered it. Presentation-only grouping.
    $todayAppointments = $appointments
        ->filter(fn ($a) => \Carbon\Carbon::parse($a->scheduled_at ?? $a->created_at)->isToday())
        ->sortBy(fn ($a) => \Carbon\Carbon::parse($a->scheduled_at ?? $a->created_at))
        ->values();
    $otherAppointments = $appointments
        ->reject(fn ($a) => \Carbon\Carbon::parse($a->scheduled_at ?? $a->created_at)->isToday())
        ->values();
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.appointments_subtitle', [], app()->getLocale()) ?: 'View and manage scheduled patient appointments.' }}</p>
    </div>
    <div>
        <a href="{{ route('portals.staff.appointments.create') }}" class="btn btn-primary fs-tap">
            <i data-lucide="calendar-plus"></i>
            {{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment' }}
        </a>
    </div>
</div>

{{-- Confirm / cancel / no-show land back here with a flash. --}}
@if(session('success'))
    <div class="alert alert-success mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>
@endif

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
    <button type="submit" class="btn btn-primary btn-sm fs-tap">
        <i data-lucide="filter"></i>
        {{ __('public.staff_portal.filter_apply', [], app()->getLocale()) ?: 'Filter' }}
    </button>
    <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost btn-sm fs-tap">
        {{ __('public.staff_portal.filter_clear', [], app()->getLocale()) ?: 'Clear' }}
    </a>
</form>

@if(count($appointments) === 0)
    <div class="panel">
        <div class="panel-body panel-body--flush">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="calendar-x-2"></i>
                </div>
                <h3>{{ __('public.staff_portal.no_appointments_title', [], app()->getLocale()) ?: 'No Appointments Found' }}</h3>
                <p>{{ __('public.staff_portal.no_appointments_desc', [], app()->getLocale()) ?: 'There are no appointments matching your current filters.' }}</p>
            </div>
        </div>
    </div>
@else

    <div class="fs-day-head">
        @include('partials.finder.icon', ['name' => 'calendar', 'size' => 15])
        {{ __('finder_supply.appt_today') }}
        <span class="fs-day-count">{{ $todayAppointments->count() }}</span>
    </div>

    @if($todayAppointments->isEmpty())
        <p class="page-subtitle mb-6">{{ __('finder_supply.appt_today_empty') }}</p>
    @else
        @include('portals.staff._appointments_table', ['rows' => $todayAppointments, 'timeOnly' => true])
    @endif

    @if($otherAppointments->isNotEmpty())
        <div class="fs-day-head">
            @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
            {{ __('finder_supply.appt_other') }}
            <span class="fs-day-count">{{ $otherAppointments->count() }}</span>
        </div>
        @include('portals.staff._appointments_table', ['rows' => $otherAppointments, 'timeOnly' => false])
    @endif

@endif

{{-- Cancel needs a reason (the controller validates it); everything else is one tap. --}}
<div id="appt-cancel-modal" class="modal-backdrop mt-6" hidden>
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="appt-cancel-title">
        <h3 id="appt-cancel-title" class="modal__title">
            @include('partials.finder.icon', ['name' => 'x', 'size' => 18])
            {{ __('finder_supply.cancel_title') }}
        </h3>
        <form id="appt-cancel-form" method="POST" action="">
            @csrf
            <div class="modal__body">
                <p id="appt-cancel-label" class="td-muted"></p>
                <div class="form-group">
                    <label class="form-label" for="appt-cancel-reason">{{ __('finder_supply.cancel_reason_label') }}</label>
                    <textarea id="appt-cancel-reason" name="reason" class="form-control" rows="3"
                              required minlength="5" maxlength="500"
                              placeholder="{{ __('finder_supply.cancel_reason_ph') }}"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn-ghost btn-sm fs-tap" onclick="closeApptCancelModal()">{{ __('finder_supply.btn_keep') }}</button>
                <button type="submit" class="fs-act-btn fs-act-btn--danger">
                    @include('partials.finder.icon', ['name' => 'x', 'size' => 15])
                    {{ __('finder_supply.btn_cancel_confirm') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    var apptBase = '{{ url('/portals/staff/appointments') }}';

    function openApptCancelModal(id, label) {
        document.getElementById('appt-cancel-label').textContent = label;
        document.getElementById('appt-cancel-form').action = apptBase + '/' + id + '/cancel';
        document.getElementById('appt-cancel-modal').removeAttribute('hidden');
        document.getElementById('appt-cancel-reason').focus();
    }
    function closeApptCancelModal() { document.getElementById('appt-cancel-modal').setAttribute('hidden',''); }
    document.getElementById('appt-cancel-modal').addEventListener('click', function(e) { if (e.target === this) closeApptCancelModal(); });
</script>
@endsection
