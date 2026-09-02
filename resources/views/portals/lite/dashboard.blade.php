@extends('layouts.lite')
@section('title', __('public.lite_portal.page_title', [], app()->getLocale()) ?: 'Dashboard')

@section('content')

<div class="lite-page-head">
    <div>
        <h1 class="lite-page-title">{{ __('public.lite_portal.page_title', [], app()->getLocale()) ?: 'Dashboard' }}</h1>
        <p class="lite-page-sub">{{ now()->format('l, d F Y') }}</p>
    </div>
    <a href="{{ route('portals.staff') }}" class="lite-btn lite-btn--outline lite-btn--sm">
        <i data-lucide="monitor"></i> {{ __('public.lite_portal.btn_full_portal', [], app()->getLocale()) ?: 'Full portal' }}
    </a>
</div>

{{-- Today's queue summary --}}
<div class="lite-stat-row">
    <div class="lite-stat-chip">
        <div class="lite-stat-chip__val">{{ array_sum($todayQueue) }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_total_today', [], app()->getLocale()) ?: 'Total today' }}</div>
    </div>
    <div class="lite-stat-chip lite-stat-chip--warning">
        <div class="lite-stat-chip__val">{{ $todayQueue['waiting'] ?? 0 }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_waiting', [], app()->getLocale()) ?: 'Waiting' }}</div>
    </div>
    <div class="lite-stat-chip lite-stat-chip--success">
        <div class="lite-stat-chip__val">{{ $todayQueue['completed'] ?? 0 }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_completed', [], app()->getLocale()) ?: 'Completed' }}</div>
    </div>
    @if(($stats['open_conflicts'] ?? 0) > 0)
    <div class="lite-stat-chip lite-stat-chip--danger">
        <div class="lite-stat-chip__val">{{ $stats['open_conflicts'] }}</div>
        <div class="lite-stat-chip__label">{{ __('public.lite_portal.stat_conflicts', [], app()->getLocale()) ?: 'Conflicts' }}</div>
    </div>
    @endif
</div>

{{-- Quick actions --}}
<div class="lite-section-title">{{ __('public.lite_portal.section_quick_actions', [], app()->getLocale()) ?: 'Quick actions' }}</div>
<div class="lite-grid">
    <a href="{{ route('portals.lite.lookup') }}" class="lite-btn-card">
        <div class="lite-btn-card__icon lite-btn-card__icon--info"><i data-lucide="search"></i></div>
        {{ __('public.lite_portal.action_lookup', [], app()->getLocale()) ?: 'Health ID lookup' }}
    </a>
    <a href="{{ route('portals.lite.register_patient') }}" class="lite-btn-card">
        <div class="lite-btn-card__icon lite-btn-card__icon--success"><i data-lucide="user-plus"></i></div>
        {{ __('public.lite_portal.action_register', [], app()->getLocale()) ?: 'Register patient' }}
    </a>
    <a href="{{ route('portals.lite.checkin') }}" class="lite-btn-card">
        <div class="lite-btn-card__icon lite-btn-card__icon--info"><i data-lucide="log-in"></i></div>
        {{ __('public.lite_portal.action_checkin', [], app()->getLocale()) ?: 'Check-in' }}
    </a>
    <a href="{{ route('portals.lite.consultation') }}" class="lite-btn-card">
        <div class="lite-btn-card__icon lite-btn-card__icon--warning"><i data-lucide="stethoscope"></i></div>
        {{ __('public.lite_portal.action_consultation', [], app()->getLocale()) ?: 'Consultation' }}
    </a>
    @feature('billing')
    <a href="{{ route('portals.lite.billing') }}" class="lite-btn-card">
        <div class="lite-btn-card__icon lite-btn-card__icon--success"><i data-lucide="receipt"></i></div>
        {{ __('public.lite_portal.action_billing', [], app()->getLocale()) ?: 'Billing' }}
    </a>
    @endfeature
    <a href="{{ route('portals.lite.devices') }}" class="lite-btn-card">
        <div class="lite-btn-card__icon"><i data-lucide="monitor-smartphone"></i></div>
        {{ __('public.lite_portal.action_devices', [], app()->getLocale()) ?: 'Devices' }}
    </a>
</div>

{{-- Recent patients --}}
@if($recentPatients->isNotEmpty())
<div class="lite-section-title">{{ __('public.lite_portal.section_recent_patients', [], app()->getLocale()) ?: 'Recent patients' }}</div>
<div class="lite-card">
    <div class="lite-card__body lite-card__body--flush">
        <table class="lite-table">
            <thead><tr>
                <th>{{ __('public.portal.col_patient', [], app()->getLocale()) ?: 'Patient' }}</th>
                <th>{{ __('public.medical_id.health_id', [], app()->getLocale()) ?: 'Health ID' }}</th>
                <th></th>
            </tr></thead>
            <tbody>
                @foreach($recentPatients as $p)
                <tr>
                    <td class="lite-td-strong">{{ $p->first_name }} {{ $p->last_name }}</td>
                    <td class="lite-mono">{{ $p->health_id }}</td>
                    <td class="lite-td-right">
                        <a href="{{ route('portals.lite.checkin', ['patient_id' => $p->id]) }}" class="lite-btn lite-btn--outline lite-btn--sm">
                            {{ __('public.lite_portal.action_checkin', [], app()->getLocale()) ?: 'Check-in' }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Device health alert --}}
@if(($stats['open_conflicts'] ?? 0) > 0 || ($stats['pending_events'] ?? 0) > 0)
<div class="lite-alert lite-alert--warning">
    <i data-lucide="alert-triangle"></i>
    <span>
        @if($stats['open_conflicts'] > 0)
            {{ $stats['open_conflicts'] }} {{ __('public.lite_portal.lbl_open_conflicts', [], app()->getLocale()) ?: 'open sync conflict(s).' }}
            <a href="{{ route('portals.lite.conflicts') }}" class="lite-alert__link">{{ __('public.lite_portal.lnk_review', [], app()->getLocale()) ?: 'Review →' }}</a>
        @endif
        @if($stats['pending_events'] > 0)
            {{ $stats['pending_events'] }} {{ __('public.lite_portal.lbl_pending_events', [], app()->getLocale()) ?: 'event(s) pending sync.' }}
        @endif
    </span>
</div>
@endif

@endsection
