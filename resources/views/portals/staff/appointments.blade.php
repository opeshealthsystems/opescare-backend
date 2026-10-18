@extends('layouts.portal')

@section('title', __('public.portal.nav_appointments', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.portal.nav_appointments', [], app()->getLocale()))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
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
    $apptTotal = method_exists($appointments, 'total') ? $appointments->total() : count($appointments);
@endphp

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('public.portal.nav_appointments', [], app()->getLocale()) }}</h1>
        <p class="od-page__sub">{{ __('public.staff_portal.appointments_subtitle', [], app()->getLocale()) }}</p>
    </div>
    <div class="odc-head__actions">
        {{-- Mirrors the gate on portals.staff.appointments.create (routes/web.php). --}}
        @capability('front_desk', 'schedule')
        <a href="{{ route('portals.staff.appointments.waitlist') }}" class="od-btn od-btn--secondary">
            <i data-lucide="list-ordered"></i>
            {{ __('waitlist.staff_title') }}
        </a>
        <a href="{{ route('portals.staff.appointments.create') }}" class="od-btn od-btn--primary">
            <i data-lucide="calendar-plus"></i>
            {{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) }}
        </a>
        @endcapability
    </div>
</header>

{{-- Confirm / cancel / no-show land back here with a flash. --}}
@if(session('success'))
    <div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>
@endif

<section class="od-card" aria-labelledby="odc-appt-title">
    <header class="od-card__head">
        <h2 class="od-card__title" id="odc-appt-title">{{ __('public.portal.nav_appointments', [], app()->getLocale()) }} <span class="od-card__count">({{ $apptTotal }})</span></h2>
    </header>
    <div class="od-card__body">
    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('portals.staff.appointments') }}" class="odc-toolbar">
        <label class="od-field">
            <i data-lucide="search"></i>
            <input
                type="text"
                name="patient_id"
                class="od-input"
                placeholder="{{ __('public.staff_portal.filter_patient_id', [], app()->getLocale()) }}"
                aria-label="{{ __('public.staff_portal.filter_patient_id', [], app()->getLocale()) }}"
                value="{{ request('patient_id') }}"
            >
        </label>
        {{-- No facility filter: the facility a desk acts for is the session's
             active facility (RequireFacilityContext), never a typed id. --}}
        {{-- The clinicians posted here, by name. A clerk cannot know a uuid, and
             the controller still filters on the exact id it sends. --}}
        <select name="provider_id" class="od-select" aria-label="{{ __('staff.desk.col_clinician') }}">
            <option value="">{{ __('staff.desk.filter_any_clinician') }}</option>
            @foreach($clinicians ?? [] as $clinician)
                <option value="{{ $clinician->id }}" @selected(request('provider_id') === (string) $clinician->id)>{{ $clinician->name }}</option>
            @endforeach
        </select>
        {{-- The controller always honoured ?status=…; the form never offered it. --}}
        <select name="status" class="od-select" aria-label="{{ __('public.staff_portal.col_status', [], app()->getLocale()) }}">
            <option value="">{{ __('staff.desk.filter_any_status') }}</option>
            @foreach($statuses ?? [] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>@enum($s)</option>
            @endforeach
        </select>
        <input
            type="date"
            name="date"
            class="od-input"
            aria-label="{{ __('public.staff_portal.col_datetime', [], app()->getLocale()) }}"
            value="{{ request('date') }}"
        >
        <button type="submit" class="od-btn od-btn--secondary">
            <i data-lucide="filter"></i>
            {{ __('public.staff_portal.filter_apply', [], app()->getLocale()) }}
        </button>
        <a href="{{ route('portals.staff.appointments') }}" class="od-btn od-btn--ghost">
            {{ __('public.staff_portal.filter_clear', [], app()->getLocale()) }}
        </a>
    </form>

@if(count($appointments) === 0)
    <div class="od-empty">
        <span class="od-empty__icon"><i data-lucide="calendar-x-2"></i></span>
        <div class="od-empty__title">{{ __('public.staff_portal.no_appointments_title', [], app()->getLocale()) }}</div>
        <p class="od-empty__text">{{ __('public.staff_portal.no_appointments_desc', [], app()->getLocale()) }}</p>
        <a href="{{ route('portals.staff.appointments.create') }}" class="od-btn od-btn--primary"><i data-lucide="calendar-plus"></i> {{ __('hospital_desk.empty_appointments_action') }}</a>
    </div>
@else

    <h3 class="odc-day">
        <i data-lucide="calendar"></i>
        {{ __('finder_supply.appt_today') }}
        <span class="od-pill od-pill--normal">{{ $todayAppointments->count() }}</span>
    </h3>

    @if($todayAppointments->isEmpty())
        <p class="od-muted odc-gap">{{ __('finder_supply.appt_today_empty') }}</p>
    @else
        @include('portals.staff._appointments_table', ['rows' => $todayAppointments, 'timeOnly' => true, 'facilityName' => $facilityName ?? null])
    @endif

    @if($otherAppointments->isNotEmpty())
        <h3 class="odc-day">
            <i data-lucide="clock"></i>
            {{ __('finder_supply.appt_other') }}
            <span class="od-pill od-pill--neutral">{{ $otherAppointments->count() }}</span>
        </h3>
        @include('portals.staff._appointments_table', ['rows' => $otherAppointments, 'timeOnly' => false, 'facilityName' => $facilityName ?? null])
    @endif

@endif
    </div>

    {{-- 50 per page: the book of a hospital is thousands of rows, and the
         previous limit(100) simply dropped the rest with no page 2. --}}
    @if(method_exists($appointments, 'hasPages') && $appointments->hasPages())
    <footer class="od-card__foot odc-pager">
        <span>{{ __('staff.desk.showing_page', ['page' => $appointments->currentPage(), 'last' => $appointments->lastPage(), 'total' => $appointments->total()]) }}</span>
        <div>{{ $appointments->links() }}</div>
    </footer>
    @endif
</section>

{{-- Cancel needs a reason (the controller validates it); everything else is one tap. --}}
<div id="appt-cancel-modal" class="odc-modal" hidden>
    <div class="odc-modal__box" role="dialog" aria-modal="true" aria-labelledby="appt-cancel-title">
        <h3 id="appt-cancel-title" class="odc-modal__title">
            <i data-lucide="x-circle"></i>
            {{ __('finder_supply.cancel_title') }}
        </h3>
        <form id="appt-cancel-form" method="POST" action="" class="odc-form">
            @csrf
            <p id="appt-cancel-label" class="od-muted"></p>
            <label class="odc-label" for="appt-cancel-reason">{{ __('finder_supply.cancel_reason_label') }}
                <textarea id="appt-cancel-reason" name="reason" class="odc-textarea" rows="3"
                          required minlength="5" maxlength="500"
                          placeholder="{{ __('finder_supply.cancel_reason_ph') }}"></textarea>
            </label>
            <div class="odc-actions">
                <button type="button" class="od-btn od-btn--secondary" data-call="closeApptCancelModal">{{ __('finder_supply.btn_keep') }}</button>
                <button type="submit" class="od-btn od-btn--danger">
                    <i data-lucide="x"></i>
                    {{ __('finder_supply.btn_cancel_confirm') }}
                </button>
            </div>
        </form>
    </div>
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
