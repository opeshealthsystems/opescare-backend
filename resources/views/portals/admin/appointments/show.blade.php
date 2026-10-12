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
@section('breadcrumb_home', __('public.adm_breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('public.adm_appt_breadcrumb'))
@section('content')

@php
    $patientName = trim(($appointment->patient?->first_name ?? '') . ' ' . ($appointment->patient?->last_name ?? ''));
    $status      = $appointment->status ?? '';
    $sBadge      = match ($status) {
        'scheduled' => 'badge-neutral',
        'confirmed' => 'badge-success',
        'cancelled' => 'badge-danger',
        'no_show'   => 'badge-warning',
        'completed' => 'badge-primary',
        default     => 'badge-neutral',
    };
@endphp

<div class="breadcrumb">
    <a href="{{ route('portals.admin.appointments.index') }}">{{ __('public.adm_appt_breadcrumb') }}</a>
    <i data-lucide="chevron-right"></i>
    <span>{{ $patientName ?: ($appointment->patient?->health_id ?? '—') }}</span>
</div>

<div class="entity-head">
    <div class="entity-head__icon"><i data-lucide="calendar"></i></div>
    <h2 class="entity-head__title">{{ $patientName ?: ($appointment->patient?->health_id ?? '—') }}</h2>
    <span class="badge {{ $sBadge }}">@enum($status)</span>
    <div class="entity-head__spacer"></div>
    <a href="{{ route('portals.admin.appointments.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('public.adm_appt_breadcrumb') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

<div class="field-grid mb-6">
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_appt_col_patient') }}</div>
        <div class="stat-card__value">{{ $patientName ?: '—' }}</div>
        @if($appointment->patient?->health_id)
            <div class="stat-card__hint" style="font-family:monospace;">{{ $appointment->patient->health_id }}</div>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_appt_col_facility') }}</div>
        <div class="stat-card__value">{{ $appointment->facility?->name ?? '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_appt_col_provider') }}</div>
        <div class="stat-card__value">{{ $appointment->provider?->name ?? '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_appt_col_type') }}</div>
        <div class="stat-card__value">@enum($appointment->appointment_type, 'appointment_type')</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_appt_col_scheduled_at') }}</div>
        <div class="stat-card__value">{{ $appointment->scheduled_at?->translatedFormat('d M Y H:i') ?? '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">{{ __('public.adm_appt_col_status') }}</div>
        <div class="stat-card__value">@enum($status)</div>
    </div>
</div>

<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="file-text"></i> {{ __('appointments.book.reason') }}</h3>
    </div>
    <div class="panel-body">
        <p style="margin:0;">{{ $appointment->reason ?: '—' }}</p>
    </div>
</div>

@if($appointment->cancelled_at || $appointment->cancellation_reason)
<div class="panel mb-6">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="ban"></i> {{ __('appointments.cancel_modal.reason') }}</h3>
    </div>
    <div class="panel-body">
        <p style="margin:0 0 .5rem;">{{ $appointment->cancellation_reason ?: '—' }}</p>
        @if($appointment->cancelled_at)
            <p class="td-muted" style="margin:0;font-size:.85rem;">{{ $appointment->cancelled_at->translatedFormat('d M Y H:i') }}</p>
        @endif
    </div>
</div>
@endif

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="clock"></i> {{ __('appointments.status.checked_in') }}</h3>
    </div>
    <div class="panel-body">
        <p style="margin:0;">{{ $appointment->checked_in_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
    </div>
</div>

@endsection
