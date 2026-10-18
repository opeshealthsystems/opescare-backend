@extends('layouts.portal')

@section('title', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()))

@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('public.staff_portal.btn_book_appointment', [], app()->getLocale()))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) }}</h1>
        <p class="od-page__sub">{{ __('public.staff_portal.appointments_subtitle', [], app()->getLocale()) }}</p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.staff.appointments') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i>
            {{ __('public.stf_back') }}
        </a>
    </div>
</header>

@if(session('error'))
    <div class="odc-note odc-note--err" role="alert">
        <i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div>
    </div>
@endif

<form method="POST" action="{{ route('portals.staff.appointments.store') }}" class="odc-form">
    @csrf

    @if(!empty($waitlistEntry))
        {{-- Booking off the waitlist: the entry is closed when this saves. --}}
        <input type="hidden" name="waitlist_id" value="{{ $waitlistEntry->id }}">
        <div class="odc-note odc-note--info">
            <i data-lucide="list-ordered"></i>
            <div>
                {{ __('waitlist.booking_from', ['health_id' => $waitlistEntry->patient?->health_id ?? '—']) }}
                @if($waitlistEntry->notes)
                    <div class="od-muted">{{ $waitlistEntry->notes }}</div>
                @endif
            </div>
        </div>
    @endif

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ rtrim(__('public.stf_appt_patient_id'), ' *') }}</h2></header>
        <div class="od-card__body odc-grid">
            <label class="odc-label">{{ __('public.stf_appt_patient_id') }}
                @if(count($patients) > 0)
                    <select name="patient_id" class="od-select" required>
                        <option value="">{{ __('public.stf_select_patient') }}</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->health_id ?? $p->id }} ({{ $p->first_name ?? '' }} {{ $p->last_name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="patient_id" class="od-input" required
                        placeholder="{{ __('public.stf_appt_enter_patient_id') }}" value="{{ old('patient_id', !empty($waitlistEntry) ? ($waitlistEntry->patient?->health_id ?? $waitlistEntry->patient_id) : request('patient_id')) }}">
                @endif
                @error('patient_id')<span class="odc-error">{{ $message }}</span>@enderror
            </label>

            {{-- The facility is the one this session is working in, shown rather
                 than chosen. It used to be a free choice posted in the body, so a
                 clerk could book into any facility in the country by picking a
                 different option; the controller now takes it from the session and
                 ignores the field entirely, so offering a picker would only mislead. --}}
            <label class="odc-label">{{ __('public.stf_appt_facility') }}
                <input type="text" class="od-input" disabled
                       value="{{ $activeFacilityName ?? __('public.stf_appt_facility_session') }}">
            </label>
        </div>
    </section>

    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ rtrim(__('public.stf_appt_datetime'), ' *') }}</h2></header>
        <div class="od-card__body odc-grid">
            <label class="odc-label">{{ __('public.stf_appt_type') }}
                <select name="appointment_type" class="od-select" required>
                    <option value="general" {{ old('appointment_type') === 'general' ? 'selected' : '' }}>
                        {{ __('public.staff_portal.type_general', [], app()->getLocale()) }}
                    </option>
                    <option value="follow_up" {{ old('appointment_type') === 'follow_up' ? 'selected' : '' }}>
                        {{ __('public.staff_portal.type_followup', [], app()->getLocale()) }}
                    </option>
                    <option value="specialist" {{ old('appointment_type') === 'specialist' ? 'selected' : '' }}>
                        {{ __('public.staff_portal.type_specialist', [], app()->getLocale()) }}
                    </option>
                    <option value="lab" {{ old('appointment_type') === 'lab' ? 'selected' : '' }}>
                        {{ __('public.staff_portal.type_lab', [], app()->getLocale()) }}
                    </option>
                    <option value="pharmacy" {{ old('appointment_type') === 'pharmacy' ? 'selected' : '' }}>
                        {{ __('public.staff_portal.type_pharmacy', [], app()->getLocale()) }}
                    </option>
                    <option value="dental" {{ old('appointment_type') === 'dental' ? 'selected' : '' }}>
                        {{ __('public.appt_type_dental') }}
                    </option>
                    <option value="emergency" {{ old('appointment_type') === 'emergency' ? 'selected' : '' }}>
                        {{ __('public.staff_portal.type_emergency', [], app()->getLocale()) }}
                    </option>
                    {{-- Maternity presets imported from the platform MaternityService. --}}
                    <optgroup label="{{ __('public.appt_preset_group_maternity') }}">
                        @foreach(\App\Modules\Maternity\Services\MaternityService::appointmentPresets() as $preset)
                        <option value="{{ $preset }}" @selected(old('appointment_type') === $preset)>{{ __('public.appt_type_' . $preset) }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <span class="odc-hint">{{ __('public.appt_preset_anc_weeks_hint', ['weeks' => implode(', ', \App\Modules\Maternity\Services\MaternityService::recommendedVisitWeeks())]) }}</span>
                @error('appointment_type')<span class="odc-error">{{ $message }}</span>@enderror
            </label>
            <label class="odc-label">{{ __('public.stf_appt_datetime') }}
                <input type="datetime-local" name="scheduled_at" class="od-input" required
                    value="{{ old('scheduled_at', now()->addDay()->format('Y-m-d\TH:i')) }}">
                @error('scheduled_at')<span class="odc-error">{{ $message }}</span>@enderror
            </label>
            <label class="odc-label odc-span">{{ __('public.stf_appt_reason') }}
                <textarea name="reason" class="odc-textarea" rows="3" maxlength="500"
                    placeholder="{{ __('public.stf_appt_reason_placeholder') }}">{{ old('reason') }}</textarea>
            </label>
        </div>
    </section>

    <div class="odc-actions">
        <a href="{{ route('portals.staff.appointments') }}" class="od-btn od-btn--secondary">{{ __('public.stf_cancel') }}</a>
        <button type="submit" class="od-btn od-btn--primary">
            <i data-lucide="calendar-plus"></i>
            {{ __('public.staff_portal.btn_book_appointment', [], app()->getLocale()) }}
        </button>
    </div>
</form>

</div>
@endsection
