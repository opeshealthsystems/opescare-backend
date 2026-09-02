@extends('layouts.portal')

@section('title', __('public.appt_reschedule_title') . ' — OpesCare')

@section('breadcrumb_home', __('public.portal.my_portal', [], app()->getLocale()) ?: 'My Portal')
@section('breadcrumb_home_url', route('portals.patient'))
@section('breadcrumb_section', __('public.appt_reschedule_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.appt_reschedule_title') }}</h1>
        <p class="page-subtitle">{{ __('public.appt_reschedule_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.patient.appointments') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('public.portal.nav_appointments', [], app()->getLocale()) ?: 'Appointments' }}
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-4"><i data-lucide="alert-triangle"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="panel" style="max-width:640px;">
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 1rem;">
            {{ __('public.appt_reschedule_current') }}:
            <strong>{{ $appt->scheduled_at?->isoFormat('LLLL') ?? '—' }}</strong>
            · {{ __('public.appt_type_' . $appt->appointment_type, [], app()->getLocale()) }}
        </p>
        <form method="POST" action="{{ route('portals.patient.appointments.reschedule.store', $appt->id) }}">
            @csrf
            <div style="margin-bottom:1rem;">
                <label class="form-label" for="scheduled_at">{{ __('public.appt_reschedule_new') }}</label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="calendar-clock"></i> {{ __('public.appt_reschedule_submit') }}
            </button>
        </form>
    </div>
</div>

@endsection
