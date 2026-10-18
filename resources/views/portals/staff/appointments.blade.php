@extends('layouts.portal')

@section('title', __('public.portal.nav_appointments', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()))

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
        <h1 class="page-title">{{ __('public.portal.nav_appointments', [], app()->getLocale()) }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.appointments_subtitle', [], app()->getLocale()) }}</p>
    </div>
    <div>
        {{-- Mirrors the gate on portals.staff.appointments.create (routes/web.php). --}}
        @capability('front_desk', 'schedule')
        <a href="{{ route('portals.staff.appointments.waitlist') }}" class="btn btn-ghost fs-tap">
            <i data-lucide="list-ordered"></i>
            {{ __('waitlist.staff_title') }}
        </a>
        <a href="{{ route('portals.staff.appointments.create') }}" class="btn btn-primary fs-tap">
            <i data-lucide="calendar-plus"></i>
            {{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) }}
        </a>
        @endcapability
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
            placeholder="{{ __('public.staff_portal.filter_patient_id', [], app()->getLocale()) }}"
            value="{{ request('patient_id') }}"
        >
    </div>
    {{-- No facility filter: the facility a desk acts for is the session's
         active facility (RequireFacilityContext), never a typed id. --}}
    {{-- The clinicians posted here, by name. A clerk cannot know a uuid, and
         the controller still filters on the exact id it sends. --}}
    <select name="provider_id" class="form-control" aria-label="{{ __('staff.desk.col_clinician') }}">
        <option value="">{{ __('staff.desk.filter_any_clinician') }}</option>
        @foreach($clinicians ?? [] as $clinician)
            <option value="{{ $clinician->id }}" @selected(request('provider_id') === (string) $clinician->id)>{{ $clinician->name }}</option>
        @endforeach
    </select>
    {{-- The controller always honoured ?status=…; the form never offered it. --}}
    <select name="status" class="form-control" aria-label="{{ __('public.staff_portal.col_status', [], app()->getLocale()) }}">
        <option value="">{{ __('staff.desk.filter_any_status') }}</option>
        @foreach($statuses ?? [] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>@enum($s)</option>
        @endforeach
    </select>
    <input
        type="date"
        name="date"
        class="form-control"
        value="{{ request('date') }}"
    >
    <button type="submit" class="btn btn-primary btn-sm fs-tap">
        <i data-lucide="filter"></i>
        {{ __('public.staff_portal.filter_apply', [], app()->getLocale()) }}
    </button>
    <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost btn-sm fs-tap">
        {{ __('public.staff_portal.filter_clear', [], app()->getLocale()) }}
    </a>
</form>

@if(count($appointments) === 0)
    <div class="panel">
        <div class="panel-body panel-body--flush">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="calendar-x-2"></i>
                </div>
                <h3>{{ __('public.staff_portal.no_appointments_title', [], app()->getLocale()) }}</h3>
                <p>{{ __('public.staff_portal.no_appointments_desc', [], app()->getLocale()) }}</p>
                <a href="{{ route('portals.staff.appointments.create') }}" class="btn btn-primary btn-sm mt-6"><i data-lucide="calendar-plus"></i> {{ __('hospital_desk.empty_appointments_action') }}</a>
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
        @include('portals.staff._appointments_table', ['rows' => $todayAppointments, 'timeOnly' => true, 'facilityName' => $facilityName ?? null])
    @endif

    @if($otherAppointments->isNotEmpty())
        <div class="fs-day-head">
            @include('partials.finder.icon', ['name' => 'clock', 'size' => 15])
            {{ __('finder_supply.appt_other') }}
            <span class="fs-day-count">{{ $otherAppointments->count() }}</span>
        </div>
        @include('portals.staff._appointments_table', ['rows' => $otherAppointments, 'timeOnly' => false, 'facilityName' => $facilityName ?? null])
    @endif

    {{-- 50 per page: the book of a hospital is thousands of rows, and the
         previous limit(100) simply dropped the rest with no page 2. --}}
    @if(method_exists($appointments, 'hasPages') && $appointments->hasPages())
        <p class="td-muted mb-2">{{ __('staff.desk.showing_page', ['page' => $appointments->currentPage(), 'last' => $appointments->lastPage(), 'total' => $appointments->total()]) }}</p>
        <div class="mb-6">{{ $appointments->links() }}</div>
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
                <button type="button" class="btn btn-ghost btn-sm fs-tap" data-call="closeApptCancelModal">{{ __('finder_supply.btn_keep') }}</button>
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
<script @cspNonce>
    // Built from the named route, with the id substituted client-side. The old
    // hand-written '/portals/staff/appointments' prefix is a GET-only legacy
    // redirect, so posting the cancel form to it answered 405 and the modal's
    // Confirm button did nothing.
    var apptCancelUrl = '{{ route('portals.staff.appointments.cancel', ['id' => '__ID__']) }}';

    function openApptCancelModal(id, label) {
        document.getElementById('appt-cancel-label').textContent = label;
        document.getElementById('appt-cancel-form').action = apptCancelUrl.replace('__ID__', encodeURIComponent(id));
        document.getElementById('appt-cancel-modal').removeAttribute('hidden');
        document.getElementById('appt-cancel-reason').focus();
    }
    function closeApptCancelModal() { document.getElementById('appt-cancel-modal').setAttribute('hidden',''); }
    document.getElementById('appt-cancel-modal').addEventListener('click', function(e) { if (e.target === this) closeApptCancelModal(); });
</script>
@endsection
