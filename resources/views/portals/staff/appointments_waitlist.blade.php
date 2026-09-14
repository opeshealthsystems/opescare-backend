@extends('layouts.portal')

@section('title', __('waitlist.staff_title'))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()) ?: 'Staff Portal')
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('waitlist.staff_title'))

@section('head')
@include('portals.pharmacy._supply_styles')
@endsection

@php
    $lApp   = app()->getLocale();
    $canAct = \App\Support\StaffCapabilities::can(auth()->user(), 'front_desk', 'schedule');
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('waitlist.staff_title') }}</h1>
        <p class="page-subtitle">{{ __('waitlist.staff_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.staff.appointments') }}" class="btn btn-ghost btn-sm">
        <i data-lucide="arrow-left"></i>
        {{ __('public.stf_back') }}
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success mb-4"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-4"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif

@if($entries->isEmpty())
    <div class="panel">
        <div class="panel-body panel-body--flush">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="list-ordered"></i>
                </div>
                <h3>{{ __('waitlist.staff_empty_title') }}</h3>
                <p>{{ __('waitlist.staff_empty_desc') }}</p>
            </div>
        </div>
    </div>
@else
    <div class="panel mb-6">
        <div class="panel-body panel-body--flush">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('waitlist.col_patient') }}</th>
                            <th>{{ __('public.staff_portal.col_type', [], $lApp) ?: 'Type' }}</th>
                            <th>{{ __('waitlist.col_window') }}</th>
                            <th>{{ __('waitlist.col_urgency') }}</th>
                            <th>{{ __('public.staff_portal.col_status', [], $lApp) ?: 'Status' }}</th>
                            <th>{{ __('waitlist.col_joined') }}</th>
                            <th>{{ __('public.staff_portal.col_actions', [], $lApp) ?: 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                        @php
                            $name = trim(($entry->patient?->first_name ?? '') . ' ' . ($entry->patient?->last_name ?? ''));
                            $healthId = $entry->patient?->health_id ?? '—';
                            $urgencyBadge = $entry->urgency === 'urgent' ? 'badge-danger' : 'badge-neutral';
                            $statusBadge  = $entry->status === 'notified' ? 'badge-teal' : 'badge-warning';
                            $from = $entry->preferred_earliest_date?->format('d M Y');
                            $to   = $entry->preferred_latest_date?->format('d M Y');
                        @endphp
                        <tr>
                            <td data-label="{{ __('waitlist.col_patient') }}">
                                <span class="td-strong">{{ $name !== '' ? $name : '—' }}</span>
                                <div><span class="mono">{{ $healthId }}</span></div>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_type', [], $lApp) ?: 'Type' }}">
                                <span class="badge badge-primary">@enum($entry->appointment_type)</span>
                            </td>
                            <td data-label="{{ __('waitlist.col_window') }}">
                                @if($from || $to)
                                    {{ $from ?? '…' }} → {{ $to ?? '…' }}
                                @else
                                    <span class="td-muted">{{ __('waitlist.any_date') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('waitlist.col_urgency') }}">
                                <span class="badge {{ $urgencyBadge }}">@enum($entry->urgency, 'urgency')</span>
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_status', [], $lApp) ?: 'Status' }}">
                                <span class="badge {{ $statusBadge }}">@enum($entry->status)</span>
                                @if($entry->notified_at)
                                    <div class="td-muted">{{ $entry->notified_at->format('d M, H:i') }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('waitlist.col_joined') }}">
                                {{ $entry->created_at?->format('d M Y') }}
                            </td>
                            <td data-label="{{ __('public.staff_portal.col_actions', [], $lApp) ?: 'Actions' }}">
                                @if($canAct)
                                <a class="fs-act-btn fs-act-btn--good"
                                   href="{{ route('portals.staff.appointments.create', ['waitlist' => $entry->id, 'patient_id' => $entry->patient_id]) }}">
                                    @include('partials.finder.icon', ['name' => 'calendar-plus', 'size' => 15])
                                    {{ __('waitlist.btn_book') }}
                                </a>
                                @else
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@endsection
