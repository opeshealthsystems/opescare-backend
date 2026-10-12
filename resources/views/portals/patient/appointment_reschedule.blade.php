@extends('layouts.portal')

@section('title', __('public.appt_reschedule_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.appt_reschedule_title'))

@section('head')
    @include('portals.patient._finder_head')
@endsection

@section('patient_banner')
    @include('portals.patient._viewing_banner')
@endsection

@section('content')

<div class="pf pf--form">

    <div class="pf-head">
        <div>
            <h1>{{ __('public.appt_reschedule_title') }}</h1>
            <p class="pf-sub">{{ __('public.appt_reschedule_subtitle') }}</p>
        </div>
        <a href="{{ route('portals.patient.appointments') }}" class="f-btn">
            @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 18])
            {{ __('public.portal.nav_appointments', [], app()->getLocale()) }}
        </a>
    </div>

    @if($errors->any())
    <div class="pf-alert pf-alert--danger" role="alert">
        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
        <p>{{ $errors->first() }}</p>
    </div>
    @endif

    {{-- What is being moved --}}
    <div class="f-card" style="margin-bottom:12px;">
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'calendar', 'size' => 18])
            {{ __('finder_patient.current_appointment') }}
        </h2>
        <dl class="pf-summary">
            <div>
                <dt>{{ __('finder_patient.confirm_type') }}</dt>
                <dd>@enum($appt->appointment_type ?? 'consultation')</dd>
            </div>
            <div>
                <dt>{{ __('public.appt_reschedule_current') }}</dt>
                <dd>{{ $appt->scheduled_at?->isoFormat('LLLL') ?? '—' }}</dd>
            </div>
            @if($appt->facility?->name)
            <div>
                <dt>{{ __('finder_patient.confirm_facility') }}</dt>
                <dd>{{ $appt->facility->name }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Pick the new time --}}
    <form method="POST" action="{{ route('portals.patient.appointments.reschedule.store', $appt->id) }}" class="f-card">
        @csrf
        <h2 class="pf-card-head">
            @include('partials.finder.icon', ['name' => 'clock', 'size' => 18])
            {{ __('public.appt_reschedule_new') }}
        </h2>
        <div class="f-field">
            <label class="f-label" for="scheduled_at">{{ __('finder_patient.step_time') }}</label>
            <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="f-input"
                   min="{{ now()->format('Y-m-d\TH:i') }}" required>
            <p class="f-hint">{{ __('finder_patient.time_help') }}</p>
        </div>

        <div class="pf-alert pf-alert--warn">
            @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
            <p>{{ __('finder_patient.reschedule_note') }}</p>
        </div>

        <button type="submit" class="f-btn f-btn--primary f-btn--block" style="min-height:48px;">
            {{ __('public.appt_reschedule_submit') }}
        </button>
    </form>
</div>

@endsection
