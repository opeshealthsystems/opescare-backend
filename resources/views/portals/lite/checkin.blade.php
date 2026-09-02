@extends('layouts.lite')
@section('title', __('public.lite_portal.action_checkin', [], app()->getLocale()) ?: 'Check-In')
@php $l = app()->getLocale(); @endphp

@section('content')

<h1 class="lite-page-title">{{ __('public.lite_portal.checkin_title', [], $l) ?: 'Queue check-in' }}</h1>
<p class="lite-page-sub">{{ __('public.lite_portal.checkin_subtitle', [], $l) ?: "Add a patient to today's queue" }}</p>

@if($errors->any())
    <div class="lite-alert lite-alert--danger lite-alert--column">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
@endif

{{-- Patient search / selection --}}
@if(!$patient)
<div class="lite-mb">
    <div class="lite-alert lite-alert--info">
        <i data-lucide="info"></i>
        <span>{{ __('public.lite_portal.checkin_no_patient', [], $l) ?: 'Search for the patient first to pre-fill this form.' }}
        <a href="{{ route('portals.lite.lookup') }}" class="lite-alert__link">{{ __('public.lite_portal.checkin_lnk_lookup', [], $l) ?: 'Lookup →' }}</a></span>
    </div>
</div>
@else
<div class="lite-card lite-mb">
    <div class="lite-card__body lite-patient-chip">
        <div class="lite-patient-chip__avatar lite-patient-chip__avatar--primary"><i data-lucide="user"></i></div>
        <div>
            <div class="lite-td-strong">{{ $patient->first_name }} {{ $patient->last_name }}</div>
            <div class="lite-mono--primary">{{ $patient->health_id }}</div>
        </div>
        <a href="{{ route('portals.lite.lookup') }}" class="lite-muted-link lite-ml-auto">{{ __('public.lite_portal.lnk_change', [], $l) ?: 'Change' }}</a>
    </div>
</div>
@endif

<form method="POST" action="{{ route('portals.lite.checkin.store') }}">
    @csrf

    @if($patient)
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
    @else
        <div class="lite-form-group">
            <label class="lite-label" for="patient_id">{{ __('public.lite_portal.checkin_lbl_patient_id', [], $l) ?: 'Patient ID' }}</label>
            <input id="patient_id" name="patient_id" type="text" value="{{ old('patient_id') }}"
                   class="lite-input" placeholder="{{ __('public.lite_portal.checkin_ph_patient_id', [], $l) ?: 'Paste patient UUID' }}" required>
        </div>
    @endif

    <div class="lite-card">
        <div class="lite-card__head">{{ __('public.lite_portal.checkin_card_visit', [], $l) ?: 'Visit details' }}</div>
        <div class="lite-card__body">
            <div class="lite-form-group">
                <label class="lite-label" for="reason">{{ __('public.lite_portal.checkin_lbl_reason', [], $l) ?: 'Reason for visit' }}</label>
                <textarea id="reason" name="reason" class="lite-input" rows="3"
                          placeholder="{{ __('public.lite_portal.checkin_ph_reason', [], $l) ?: 'Brief reason (optional)' }}">{{ old('reason') }}</textarea>
            </div>
            <div class="lite-form-group">
                <label class="lite-label">{{ __('public.lite_portal.checkin_lbl_priority', [], $l) ?: 'Priority' }}</label>
                <div class="lite-radio-row">
                    @foreach([
                        1 => __('public.lite_portal.checkin_priority_urgent', [], $l) ?: 'Urgent (P1)',
                        2 => __('public.lite_portal.checkin_priority_high', [], $l) ?: 'High (P2)',
                        3 => __('public.lite_portal.checkin_priority_normal', [], $l) ?: 'Normal (P3)',
                        4 => __('public.lite_portal.checkin_priority_low', [], $l) ?: 'Low (P4)',
                        5 => __('public.lite_portal.checkin_priority_routine', [], $l) ?: 'Routine (P5)',
                    ] as $val => $label)
                        <label class="lite-radio-chip">
                            <input type="radio" name="priority" value="{{ $val }}" {{ old('priority', 3) == $val ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="lite-btn lite-btn--primary lite-btn--full lite-mt">
        <i data-lucide="log-in"></i> {{ __('public.lite_portal.checkin_btn_submit', [], $l) ?: 'Check in patient' }}
    </button>
    <div class="lite-empty lite-mt">
        <a href="{{ route('portals.lite.dashboard') }}" class="lite-muted-link">{{ __('public.lite_portal.checkin_btn_cancel', [], $l) ?: '← Cancel' }}</a>
    </div>
</form>

@endsection
