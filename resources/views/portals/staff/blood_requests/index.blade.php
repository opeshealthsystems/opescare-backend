{{--
    Staff blood-request queue — /facility/blood-requests

    The sibling of portals/staff/inventory/blood.blade.php: same portal layout,
    same capability (blood_inventory), same breadcrumbs. That page is the
    SHELF; this page is the QUEUE of patients asking for what is on it. The
    notification BloodRequestService raises when a request lands now points
    here instead of at the shelf.

    Nothing on this screen moves stock.
--}}
@extends('layouts.portal')

@section('title', __('blood_desk.title'))

@section('sidebar_user_role', __('public.staff_portal.role_clinical_staff', [], app()->getLocale()))

@section('breadcrumb_home', __('public.staff_portal.title', [], app()->getLocale()))
@section('breadcrumb_home_url', route('portals.staff'))
@section('breadcrumb_section', __('blood_desk.breadcrumb'))

@section('content')

<div class="page-head">
    <h2>{{ __('blood_desk.title') }}</h2>
    <div class="page-head__spacer"></div>
    <a href="{{ route('portals.staff.blood_requests.create') }}" class="btn btn-primary btn-sm fs-tap">
        <i data-lucide="plus"></i>
        {{ __('blood_desk.create_btn') }}
    </a>
    <a href="{{ route('portals.staff.inventory.blood') }}" class="btn btn-ghost btn-sm fs-tap">
        <i data-lucide="droplet"></i>
        {{ __('public.portal.nav_blood_inventory') }}
    </a>
</div>
<p class="page-subtitle mb-6">{{ __('blood_desk.subtitle') }}</p>

@if(session('success'))
    <div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-6"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Why the queue may be empty, said out loud rather than left to guesswork. --}}
@if(! $hasFacility)
    <div class="alert alert-warning mb-6">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('blood_desk.no_facility_notice') }}</div>
    </div>
@elseif(! $hasListing)
    <div class="alert alert-warning mb-6">
        <i data-lucide="triangle-alert"></i>
        <div>{{ __('blood_desk.no_listing_notice') }}</div>
    </div>
@endif

@if($auditBlocked)
    <div class="alert alert-danger mb-6">
        <i data-lucide="shield-alert"></i>
        <div>{{ __('blood_desk.audit_blocked_notice') }}</div>
    </div>
@endif

<div class="stat-grid mb-6">
    <div class="stat-card stat-card--primary">
        <div class="stat-card__value">{{ $openTotal }}</div>
        <div class="stat-card__label">{{ __('blood_desk.stat_open') }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__value">{{ $counts['pending'] ?? 0 }}</div>
        <div class="stat-card__label">{{ __('blood_desk.stat_pending') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $counts['confirmed'] ?? 0 }}</div>
        <div class="stat-card__label">{{ __('blood_desk.stat_confirmed') }}</div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__value">{{ $counts['ready'] ?? 0 }}</div>
        <div class="stat-card__label">{{ __('blood_desk.stat_ready') }}</div>
    </div>
</div>

<form method="GET" action="{{ route('portals.staff.blood_requests.index') }}" class="filter-bar">
    <select name="status" class="filter-select">
        <option value="">{{ __('blood_desk.filter_open_only') }}</option>
        @foreach(\App\Enums\BloodRequestStatus::cases() as $case)
            <option value="{{ $case->value }}" {{ $activeStatus === $case->value ? 'selected' : '' }}>
                {{ __('blood_desk.status_' . $case->value) }} ({{ $counts[$case->value] ?? 0 }})
            </option>
        @endforeach
    </select>
    <select name="scope" class="filter-select">
        <option value="open" {{ $activeScope === 'open' ? 'selected' : '' }}>{{ __('blood_desk.filter_open_only') }}</option>
        <option value="all" {{ $activeScope === 'all' ? 'selected' : '' }}>{{ __('blood_desk.filter_every_status') }}</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">
        <i data-lucide="filter"></i> {{ __('blood_desk.filter_btn') }}
    </button>
    <a href="{{ route('portals.staff.blood_requests.index') }}" class="btn btn-ghost btn-sm">{{ __('blood_desk.clear_btn') }}</a>
</form>

<div class="panel">
    <div class="panel-body panel-body--flush">
        @if($rows->isEmpty())
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="inbox"></i></div>
                <h3>{{ __('blood_desk.empty_title') }}</h3>
                <p>{{ __('blood_desk.empty_desc') }}</p>
                <a href="{{ route('portals.staff.blood_requests.create') }}" class="btn btn-primary btn-sm mt-6"><i data-lucide="plus"></i> {{ __('hospital_desk.empty_blood_requests_action') }}</a>
            </div>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('blood_desk.col_reference') }}</th>
                            <th>{{ __('blood_desk.col_patient') }}</th>
                            <th>{{ __('blood_desk.col_request') }}</th>
                            <th>{{ __('blood_desk.col_urgency') }}</th>
                            <th>{{ __('blood_desk.col_status') }}</th>
                            <th>{{ __('blood_desk.col_filed') }}</th>
                            <th>{{ __('blood_desk.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                        @php
                            $urgencyBadge = match($r->urgency->value) {
                                'emergency' => 'badge-danger',
                                'urgent'    => 'badge-warning',
                                default     => 'badge-neutral',
                            };
                            $statusBadge = match($r->status->value) {
                                'pending'                          => 'badge-warning',
                                'confirmed', 'ready', 'fulfilled'  => 'badge-success',
                                default                            => 'badge-neutral',
                            };
                        @endphp
                        <tr>
                            <td data-label="{{ __('blood_desk.col_reference') }}">
                                <span class="mono">{{ $r->reference }}</span>
                            </td>
                            <td data-label="{{ __('blood_desk.col_patient') }}">
                                @if($r->patient)
                                    {{ trim($r->patient->first_name . ' ' . $r->patient->last_name) }}
                                    <div class="td-muted mono">{{ $r->patient->health_id }}</div>
                                @else
                                    <span class="td-muted">{{ __('blood_desk.not_recorded') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('blood_desk.col_request') }}">
                                <span class="fs-bgroup-tag">{{ $r->blood_group->value }}</span>
                                <span class="badge badge-neutral">@enum($r->component_type->value, 'blood_component')</span>
                                <div class="td-muted">{{ $r->quantity }} {{ __('blood_desk.units_label') }}</div>
                            </td>
                            <td data-label="{{ __('blood_desk.col_urgency') }}">
                                <span class="badge {{ $urgencyBadge }}">{{ __('blood_desk.urgency_' . $r->urgency->value) }}</span>
                            </td>
                            <td data-label="{{ __('blood_desk.col_status') }}">
                                <span class="badge {{ $statusBadge }}">{{ __('blood_desk.status_' . $r->status->value) }}</span>
                            </td>
                            <td data-label="{{ __('blood_desk.col_filed') }}" class="td-muted">
                                {{ optional($r->created_at)->translatedFormat('M d, H:i') }}
                            </td>
                            <td data-label="{{ __('blood_desk.col_actions') }}">
                                <a href="{{ route('portals.staff.blood_requests.show', $r->id) }}" class="btn btn-primary btn-xs fs-tap">
                                    <i data-lucide="arrow-right"></i>
                                    {{ __('blood_desk.view_btn') }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
