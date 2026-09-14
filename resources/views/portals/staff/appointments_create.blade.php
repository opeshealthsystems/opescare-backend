@extends('layouts.portal')

@section('title', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment')

@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()) ?: 'Clinical Staff')

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment')

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment' }}</h1>
        <p class="page-subtitle">{{ __('public.staff_portal.appointments_subtitle', [], app()->getLocale()) ?: 'Schedule a new patient appointment.' }}</p>
    </div>
    <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i>
        {{ __('public.stf_back') }}
    </a>
</div>

@if(session('error'))
    <div class="auth-alert auth-alert-danger mb-4">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.staff.appointments.store') }}">
            @csrf

            @if(!empty($waitlistEntry))
                {{-- Booking off the waitlist: the entry is closed when this saves. --}}
                <input type="hidden" name="waitlist_id" value="{{ $waitlistEntry->id }}">
                <div class="alert alert-info mb-4">
                    <i data-lucide="list-ordered"></i>
                    <div>
                        {{ __('waitlist.booking_from', ['health_id' => $waitlistEntry->patient?->health_id ?? '—']) }}
                        @if($waitlistEntry->notes)
                            <div class="td-muted">{{ $waitlistEntry->notes }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_appt_patient_id') }}</label>
                @if(count($patients) > 0)
                    <select name="patient_id" class="form-control" required>
                        <option value="">{{ __('public.stf_select_patient') }}</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->health_id ?? $p->id }} ({{ $p->first_name ?? '' }} {{ $p->last_name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="patient_id" class="form-control" required
                        placeholder="{{ __('public.stf_appt_enter_patient_id') }}" value="{{ old('patient_id', !empty($waitlistEntry) ? ($waitlistEntry->patient?->health_id ?? $waitlistEntry->patient_id) : request('patient_id')) }}">
                @endif
                @error('patient_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            {{-- The facility is the one this session is working in, shown rather
                 than chosen. It used to be a free choice posted in the body, so a
                 clerk could book into any facility in the country by picking a
                 different option; the controller now takes it from the session and
                 ignores the field entirely, so offering a picker would only mislead. --}}
            <div class="form-group mb-4">
                <label class="form-label">{{ __('public.stf_appt_facility') }}</label>
                <input type="text" class="form-control" disabled
                       value="{{ $activeFacilityName ?? __('public.stf_appt_facility_session') }}">
            </div>

            <div class="form-row mb-4">
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_appt_type') }}</label>
                    <select name="appointment_type" class="form-control" required>
                        <option value="general" {{ old('appointment_type') === 'general' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_general', [], app()->getLocale()) ?: 'General' }}
                        </option>
                        <option value="follow_up" {{ old('appointment_type') === 'follow_up' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_followup', [], app()->getLocale()) ?: 'Follow-up' }}
                        </option>
                        <option value="specialist" {{ old('appointment_type') === 'specialist' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_specialist', [], app()->getLocale()) ?: 'Specialist' }}
                        </option>
                        <option value="lab" {{ old('appointment_type') === 'lab' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_lab', [], app()->getLocale()) ?: 'Lab / Diagnostics' }}
                        </option>
                        <option value="pharmacy" {{ old('appointment_type') === 'pharmacy' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_pharmacy', [], app()->getLocale()) ?: 'Pharmacy' }}
                        </option>
                        <option value="emergency" {{ old('appointment_type') === 'emergency' ? 'selected' : '' }}>
                            {{ __('public.staff_portal.type_emergency', [], app()->getLocale()) ?: 'Emergency' }}
                        </option>
                    </select>
                    @error('appointment_type')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('public.stf_appt_datetime') }}</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" required
                        value="{{ old('scheduled_at', now()->addDay()->format('Y-m-d\TH:i')) }}">
                    @error('scheduled_at')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-group mb-6">
                <label class="form-label">{{ __('public.stf_appt_reason') }}</label>
                <textarea name="reason" class="form-control" rows="3" maxlength="500"
                    placeholder="{{ __('public.stf_appt_reason_placeholder') }}">{{ old('reason') }}</textarea>
            </div>

            <div class="row-actions-inline">
                <button type="submit" class="btn btn-primary fs-tap">
                    <i data-lucide="calendar-plus"></i>
                    {{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) ?: 'Book Appointment' }}
                </button>
                <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost">{{ __('public.stf_cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@endsection
