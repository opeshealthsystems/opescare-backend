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

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@php
    $odInitials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $odTone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

@section('content')
<div class="od-page odc-page">

<header class="od-page__head odc-head">
    <div>
        <h1 class="od-page__title">{{ __('blood_desk.title') }}</h1>
        <p class="od-page__sub">{{ __('blood_desk.subtitle') }}</p>
    </div>
    <div class="odc-head__actions">
        <a href="{{ route('portals.staff.inventory.blood') }}" class="od-btn od-btn--secondary">
            <i data-lucide="droplet"></i>
            {{ __('public.portal.nav_blood_inventory') }}
        </a>
        <a href="{{ route('portals.staff.blood_requests.create') }}" class="od-btn od-btn--primary">
            <i data-lucide="plus"></i>
            {{ __('blood_desk.create_btn') }}
        </a>
    </div>
</header>

@if(session('success'))
    <div class="odc-note odc-note--ok" role="status"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif

{{-- Why the queue may be empty, said out loud rather than left to guesswork. --}}
@if(! $hasFacility)
    <div class="odc-note odc-note--warn"><i data-lucide="triangle-alert"></i><div>{{ __('blood_desk.no_facility_notice') }}</div></div>
@elseif(! $hasListing)
    <div class="odc-note odc-note--warn"><i data-lucide="triangle-alert"></i><div>{{ __('blood_desk.no_listing_notice') }}</div></div>
@endif

@if($auditBlocked)
    <div class="odc-note odc-note--err"><i data-lucide="shield-alert"></i><div>{{ __('blood_desk.audit_blocked_notice') }}</div></div>
@endif

<div class="od-kpis">
    <div class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><x-od-icon name="droplet" /></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('blood_desk.stat_open') }}</span>
            <span class="od-kpi__value">{{ $openTotal }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><i data-lucide="hourglass"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('blood_desk.stat_pending') }}</span>
            <span class="od-kpi__value">{{ $counts['pending'] ?? 0 }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--teal">
        <span class="od-kpi__icon"><i data-lucide="check"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('blood_desk.stat_confirmed') }}</span>
            <span class="od-kpi__value">{{ $counts['confirmed'] ?? 0 }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="package-check"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('blood_desk.stat_ready') }}</span>
            <span class="od-kpi__value">{{ $counts['ready'] ?? 0 }}</span>
        </span>
    </div>
</div>

<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('blood_desk.title') }} <span class="od-card__count">({{ $rows->count() }})</span></h2>
    </header>
    <div class="od-card__body">
    <form method="GET" action="{{ route('portals.staff.blood_requests.index') }}" class="odc-toolbar">
        <select name="status" class="od-select" aria-label="{{ __('blood_desk.col_status') }}">
            <option value="">{{ __('blood_desk.filter_open_only') }}</option>
            @foreach(\App\Enums\BloodRequestStatus::cases() as $case)
                <option value="{{ $case->value }}" {{ $activeStatus === $case->value ? 'selected' : '' }}>
                    {{ __('blood_desk.status_' . $case->value) }} ({{ $counts[$case->value] ?? 0 }})
                </option>
            @endforeach
        </select>
        <select name="scope" class="od-select" aria-label="{{ __('blood_desk.filter_every_status') }}">
            <option value="open" {{ $activeScope === 'open' ? 'selected' : '' }}>{{ __('blood_desk.filter_open_only') }}</option>
            <option value="all" {{ $activeScope === 'all' ? 'selected' : '' }}>{{ __('blood_desk.filter_every_status') }}</option>
        </select>
        <button type="submit" class="od-btn od-btn--secondary">
            <i data-lucide="filter"></i> {{ __('blood_desk.filter_btn') }}
        </button>
        <a href="{{ route('portals.staff.blood_requests.index') }}" class="od-btn od-btn--ghost">{{ __('blood_desk.clear_btn') }}</a>
    </form>

        @if($rows->isEmpty())
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="inbox"></i></span>
                <div class="od-empty__title">{{ __('blood_desk.empty_title') }}</div>
                <p class="od-empty__text">{{ __('blood_desk.empty_desc') }}</p>
                <a href="{{ route('portals.staff.blood_requests.create') }}" class="od-btn od-btn--primary"><i data-lucide="plus"></i> {{ __('hospital_desk.empty_blood_requests_action') }}</a>
            </div>
        @else
            <div class="od-table-wrap">
                <table class="od-table odc-stack">
                    <thead>
                        <tr>
                            <th class="od-table__num">#</th>
                            <th>{{ __('blood_desk.col_patient') }}</th>
                            <th>{{ __('blood_desk.col_reference') }}</th>
                            <th>{{ __('blood_desk.col_request') }}</th>
                            <th>{{ __('blood_desk.col_urgency') }}</th>
                            <th>{{ __('blood_desk.col_status') }}</th>
                            <th>{{ __('blood_desk.col_filed') }}</th>
                            <th>{{ __('blood_desk.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $i => $r)
                        @php
                            $urgencyPill = match($r->urgency->value) {
                                'emergency' => 'urgent',
                                'urgent'    => 'pending',
                                default     => 'neutral',
                            };
                            $statusPill = match($r->status->value) {
                                'pending'                          => 'pending',
                                'confirmed', 'ready', 'fulfilled'  => 'ready',
                                default                            => 'neutral',
                            };
                            $pName = $r->patient ? trim($r->patient->first_name . ' ' . $r->patient->last_name) : '';
                        @endphp
                        <tr>
                            <td class="od-table__num">{{ $i + 1 }}</td>
                            <td data-label="{{ __('blood_desk.col_patient') }}">
                                @if($r->patient)
                                <div class="od-table__person">
                                    <span class="od-avatar od-tint--{{ $odTone($pName !== '' ? $pName : (string) $r->id) }}" aria-hidden="true">{{ $odInitials($r->patient->first_name, $r->patient->last_name) }}</span>
                                    <div>
                                        <a href="{{ route('portals.staff.blood_requests.show', $r->id) }}" class="od-table__name">{{ $pName !== '' ? $pName : '—' }}</a>
                                        <span class="od-table__sub odc-mono">{{ $r->patient->health_id }}</span>
                                    </div>
                                </div>
                                @else
                                    <span class="od-muted">{{ __('blood_desk.not_recorded') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('blood_desk.col_reference') }}">
                                <span class="odc-mono">{{ $r->reference }}</span>
                            </td>
                            <td data-label="{{ __('blood_desk.col_request') }}">
                                <span class="odc-btag">{{ $r->blood_group->value }}</span>
                                <span class="od-pill od-pill--neutral">@enum($r->component_type->value, 'blood_component')</span>
                                <span class="od-table__sub">{{ $r->quantity }} {{ __('blood_desk.units_label') }}</span>
                            </td>
                            <td data-label="{{ __('blood_desk.col_urgency') }}">
                                <span class="od-pill od-pill--dot od-pill--{{ $urgencyPill }}">{{ __('blood_desk.urgency_' . $r->urgency->value) }}</span>
                            </td>
                            <td data-label="{{ __('blood_desk.col_status') }}">
                                <span class="od-pill od-pill--{{ $statusPill }}">{{ __('blood_desk.status_' . $r->status->value) }}</span>
                            </td>
                            <td data-label="{{ __('blood_desk.col_filed') }}" class="od-muted">
                                {{ optional($r->created_at)->translatedFormat('M d, H:i') }}
                            </td>
                            <td class="od-table__actions" data-label="{{ __('blood_desk.col_actions') }}">
                                <a href="{{ route('portals.staff.blood_requests.show', $r->id) }}" class="od-btn od-btn--row">
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
</section>

</div>
@endsection
