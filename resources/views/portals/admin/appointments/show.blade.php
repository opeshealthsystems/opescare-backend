{{--
    Admin appointment detail — the target of the eye button on
    portals.admin.appointments.index. Read-only: cancelling and deleting stay on
    the index, which already owns those forms.

    Strings reuse the adm_appt_* / appointments.* keys the index and the booking
    flow already ship in EN and FR; no new lang keys are introduced here.
--}}
@extends('layouts.portal')
@section('title', __('public.adm_appt_page_title'))
@include('portals.admin.control_center._sidebar')
@section('head')<link rel="stylesheet" href="{{ asset('css/od-admin.css') }}?v={{ @filemtime(public_path('css/od-admin.css')) ?: '1' }}">@endsection
@section('content')

@php
    $patientName = trim(($appointment->patient?->first_name ?? '') . ' ' . ($appointment->patient?->last_name ?? ''));
    $status      = $appointment->status ?? '';
    $sBadge      = match ($status) {
        'scheduled' => 'od-pill--neutral',
        'confirmed' => 'od-pill--ready',
        'cancelled' => 'od-pill--urgent',
        'no_show'   => 'od-pill--pending',
        'completed' => 'od-pill--normal',
        default     => 'od-pill--neutral',
    };
@endphp
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ $patientName ?: ($appointment->patient?->health_id ?? '—') }}</h1>
        <p class="od-page__sub"><a href="{{ route('portals.admin.appointments.index') }}">{{ __('public.adm_appt_breadcrumb') }}</a> <span class="od-pill {{ $sBadge }}">@enum($status)</span></p>
    </div>
    <div class="od-row">
        <a href="{{ route('portals.admin.appointments.index') }}" class="od-btn od-btn--secondary">
            <i data-lucide="arrow-left"></i> {{ __('public.adm_appt_breadcrumb') }}
        </a>
    </div>
</div>

@if(session('success'))<div class="oda-gd-flash oda-gd-flash--ok"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="oda-gd-flash oda-gd-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="od-grid od-grid--main">
    <div class="od-stack">
        <div class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="file-text"></i> {{ __('appointments.book.reason') }}</h2></div>
            <div class="od-card__body"><p class="oda-gd-text">{{ $appointment->reason ?: '—' }}</p></div>
        </div>

        @if($appointment->cancelled_at || $appointment->cancellation_reason)
        <div class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="ban"></i> {{ __('appointments.cancel_modal.reason') }}</h2></div>
            <div class="od-card__body">
                <p class="oda-gd-text">{{ $appointment->cancellation_reason ?: '—' }}</p>
                @if($appointment->cancelled_at)
                    <p class="oda-help">{{ $appointment->cancelled_at->translatedFormat('d M Y H:i') }}</p>
                @endif
            </div>
        </div>
        @endif

        <div class="od-card">
            <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="clock"></i> {{ __('appointments.status.checked_in') }}</h2></div>
            <div class="od-card__body"><p class="oda-gd-text">{{ $appointment->checked_in_at?->translatedFormat('d M Y H:i') ?? '—' }}</p></div>
        </div>
    </div>

    <div class="od-card">
        <div class="od-card__head"><h2 class="od-card__title"><i data-lucide="calendar"></i> {{ __('public.adm_appt_page_title') }}</h2></div>
        <div class="od-card__body">
            <dl class="oda-dl">
                <dt>{{ __('public.adm_appt_col_patient') }}</dt>
                <dd>{{ $patientName ?: '—' }}@if($appointment->patient?->health_id)<br><span class="oda-mono">{{ $appointment->patient->health_id }}</span>@endif</dd>
                <dt>{{ __('public.adm_appt_col_facility') }}</dt><dd>{{ $appointment->facility?->name ?? '—' }}</dd>
                <dt>{{ __('public.adm_appt_col_provider') }}</dt><dd>{{ $appointment->provider?->name ?? '—' }}</dd>
                <dt>{{ __('public.adm_appt_col_type') }}</dt><dd>@enum($appointment->appointment_type, 'appointment_type')</dd>
                <dt>{{ __('public.adm_appt_col_scheduled_at') }}</dt><dd>{{ $appointment->scheduled_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
                <dt>{{ __('public.adm_appt_col_status') }}</dt><dd>@enum($status)</dd>
            </dl>
        </div>
    </div>
</div>

</div>
@endsection
