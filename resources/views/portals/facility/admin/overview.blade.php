@extends('layouts.portal')

@section('title', __('facility_admin.overview_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.overview_title'))

@section('content')

@php
    /* Seconds → a human duration, or the honest "not measured yet" when the
       source has no completed interval to measure. Never "0 min". */
    $duration = function (?int $seconds): string {
        if ($seconds === null) {
            return __('facility_admin.kpi_no_measure');
        }
        $minutes = intdiv($seconds, 60);
        return $minutes >= 60
            ? __('facility_admin.duration_hours', ['h' => intdiv($minutes, 60), 'm' => $minutes % 60])
            : __('facility_admin.duration_minutes', ['n' => $minutes]);
    };

    /* XAF has exponent 0: whole francs, no decimals, ever. */
    $xaf = fn (float $amount): string => __('facility_admin.amount_xaf', ['amount' => number_format($amount, 0, ',', ' ')]);

    $a = $kpis['appointments'];
    $q = $kpis['queue'];
    $l = $kpis['lab'];
    $b = $kpis['beds'];
    $m = $kpis['billing'];
    $t = $kpis['team'];
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('facility_admin.overview_title') }}</h1>
        <p class="page-subtitle">{{ __('facility_admin.overview_subtitle', ['facility' => $facility?->name ?? '—']) }}</p>
        <p class="page-subtitle" style="margin:.25rem 0 0;">{{ __('facility_admin.overview_day', ['date' => $day->isoFormat('LL')]) }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.facility.team') }}" class="btn btn-secondary">
            <i data-lucide="users"></i> {{ __('facility_admin.action_open_team') }}
        </a>
        @capability('queue')
        <a href="{{ route('portals.queue.index') }}" class="btn btn-secondary">
            <i data-lucide="list-ordered"></i> {{ __('facility_admin.action_open_queue') }}
        </a>
        @endcapability
    </div>
</div>

{{-- ── Appointments ─────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="calendar-check"></i> {{ __('facility_admin.section_appointments') }}</h3>
    </div>
    <div class="panel-body">
        <div class="stat-grid" style="--cols:4">
            <a href="{{ route('portals.facility.admin.reports') }}" class="stat-card stat-card--primary">
                <div class="stat-card__label">{{ __('facility_admin.kpi_appointments_today') }}</div>
                <div class="stat-card__value" data-kpi="appointments_today">{{ $a['today'] }}</div>
            </a>
            <a href="{{ route('portals.facility.admin.reports') }}" class="stat-card stat-card--success">
                <div class="stat-card__label">{{ __('facility_admin.kpi_checked_in') }}</div>
                <div class="stat-card__value" data-kpi="appointments_checked_in">{{ $a['checked_in'] }}</div>
            </a>
            <a href="{{ route('portals.facility.admin.reports') }}" class="stat-card stat-card--warning">
                <div class="stat-card__label">{{ __('facility_admin.kpi_no_show') }}</div>
                <div class="stat-card__value" data-kpi="appointments_no_show">{{ $a['no_show'] }}</div>
            </a>
            <a href="{{ route('portals.facility.admin.reports') }}" class="stat-card">
                <div class="stat-card__label">{{ __('facility_admin.kpi_cancelled') }}</div>
                <div class="stat-card__value" data-kpi="appointments_cancelled">{{ $a['cancelled'] }}</div>
            </a>
        </div>
    </div>
</div>

{{-- ── Waiting room ─────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="list-ordered"></i> {{ __('facility_admin.section_queue') }}</h3>
    </div>
    <div class="panel-body">
        <div class="stat-grid" style="--cols:5">
            @capability('queue')
            <a href="{{ route('portals.queue.index') }}" class="stat-card stat-card--primary">
            @else
            <div class="stat-card stat-card--primary">
            @endcapability
                <div class="stat-card__label">{{ __('facility_admin.kpi_queue_checked_in') }}</div>
                <div class="stat-card__value" data-kpi="queue_checked_in">{{ $q['checked_in'] }}</div>
            @capability('queue')
            </a>
            @else
            </div>
            @endcapability
            @capability('queue')
            <a href="{{ route('portals.queue.index') }}" class="stat-card stat-card--warning">
            @else
            <div class="stat-card stat-card--warning">
            @endcapability
                <div class="stat-card__label">{{ __('facility_admin.kpi_queue_waiting') }}</div>
                <div class="stat-card__value" data-kpi="queue_waiting">{{ $q['waiting'] }}</div>
            @capability('queue')
            </a>
            @else
            </div>
            @endcapability
            @capability('queue')
            <a href="{{ route('portals.queue.index') }}" class="stat-card stat-card--success">
            @else
            <div class="stat-card stat-card--success">
            @endcapability
                <div class="stat-card__label">{{ __('facility_admin.kpi_queue_seen') }}</div>
                <div class="stat-card__value" data-kpi="queue_seen">{{ $q['seen'] }}</div>
            @capability('queue')
            </a>
            @else
            </div>
            @endcapability
            @capability('queue')
            <a href="{{ route('portals.queue.index') }}" class="stat-card">
            @else
            <div class="stat-card">
            @endcapability
                <div class="stat-card__label">{{ __('facility_admin.kpi_median_wait') }}</div>
                <div class="stat-card__value" style="font-size:1.15rem;" data-kpi="queue_median_wait">{{ $duration($q['median_wait_seconds']) }}</div>
            @capability('queue')
            </a>
            @else
            </div>
            @endcapability
            @capability('queue')
            <a href="{{ route('portals.queue.index') }}" class="stat-card">
            @else
            <div class="stat-card">
            @endcapability
                <div class="stat-card__label">{{ __('facility_admin.kpi_longest_wait') }}</div>
                <div class="stat-card__value" style="font-size:1.15rem;" data-kpi="queue_longest_wait">{{ $duration($q['longest_wait_seconds']) }}</div>
            @capability('queue')
            </a>
            @else
            </div>
            @endcapability
        </div>
    </div>
</div>

{{-- ── Laboratory ───────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="flask-conical"></i> {{ __('facility_admin.section_lab') }}</h3>
    </div>
    <div class="panel-body">
        <div class="stat-grid" style="--cols:3">
            <a href="{{ route('portals.facility.admin.reports') }}" class="stat-card stat-card--warning">
                <div class="stat-card__label">{{ __('facility_admin.kpi_lab_open') }}</div>
                <div class="stat-card__value" data-kpi="lab_open">{{ $l['open'] }}</div>
            </a>
            <a href="{{ route('portals.facility.admin.reports') }}" class="stat-card stat-card--success">
                <div class="stat-card__label">{{ __('facility_admin.kpi_lab_resulted_today') }}</div>
                <div class="stat-card__value" data-kpi="lab_resulted_today">{{ $l['resulted_today'] }}</div>
            </a>
            <a href="{{ route('portals.facility.admin.reports') }}" class="stat-card">
                <div class="stat-card__label">{{ __('facility_admin.kpi_lab_turnaround') }}</div>
                <div class="stat-card__value" style="font-size:1.15rem;" data-kpi="lab_turnaround">{{ $duration($l['median_turnaround_seconds']) }}</div>
            </a>
        </div>
    </div>
</div>

{{-- ── Beds: only when the facility has wards. No wards → no invented 0%.
     A diagnostic or imaging centre (I-7) admits nobody: no panel at all. ── --}}
@unless(\App\Services\DiagnosticCentre\DiagnosticCentreDashboardService::appliesToType($facility?->type))
<div class="panel mb-4" data-panel="beds">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="bed"></i> {{ __('facility_admin.section_beds') }}</h3>
    </div>
    <div class="panel-body">
        @if($b === null)
            <div class="empty-state">
                <i data-lucide="bed"></i>
                <p>{{ __('facility_admin.beds_none') }}</p>
            </div>
        @else
            <div class="stat-grid" style="--cols:5">
                <a href="{{ route('portals.facility.admin.wards') }}" class="stat-card">
                    <div class="stat-card__label">{{ __('facility_admin.kpi_wards') }}</div>
                    <div class="stat-card__value" data-kpi="wards">{{ $b['wards'] }}</div>
                </a>
                <a href="{{ route('portals.facility.admin.wards') }}" class="stat-card stat-card--primary">
                    <div class="stat-card__label">{{ __('facility_admin.kpi_beds') }}</div>
                    <div class="stat-card__value" data-kpi="beds">{{ $b['beds'] }}</div>
                </a>
                <a href="{{ route('portals.facility.admin.wards') }}" class="stat-card stat-card--warning">
                    <div class="stat-card__label">{{ __('facility_admin.kpi_beds_occupied') }}</div>
                    <div class="stat-card__value" data-kpi="beds_occupied">{{ $b['occupied'] }}</div>
                </a>
                <a href="{{ route('portals.facility.admin.wards') }}" class="stat-card stat-card--success">
                    <div class="stat-card__label">{{ __('facility_admin.kpi_beds_available') }}</div>
                    <div class="stat-card__value" data-kpi="beds_available">{{ $b['available'] }}</div>
                </a>
                <a href="{{ route('portals.facility.admin.wards') }}" class="stat-card">
                    <div class="stat-card__label">{{ __('facility_admin.kpi_occupancy') }}</div>
                    <div class="stat-card__value" data-kpi="occupancy">{{ $b['occupancy_pct'] === null ? '—' : $b['occupancy_pct'] . ' %' }}</div>
                    <div class="stat-card__hint">{{ __('facility_admin.kpi_admissions') }}: {{ $b['active_admissions'] }}</div>
                </a>
            </div>
        @endif
        <p style="margin:.75rem 0 0;">
            <a href="{{ route('portals.facility.admin.wards') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="bed"></i> {{ __('facility_admin.action_open_wards') }}
            </a>
        </p>
    </div>
</div>
@endunless

{{-- ── Billing ──────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="receipt"></i> {{ __('facility_admin.section_billing') }}</h3>
    </div>
    <div class="panel-body">
        <div class="stat-grid" style="--cols:3">
            <a href="{{ route('portals.facility.admin.invoices') }}" class="stat-card stat-card--success">
                <div class="stat-card__label">{{ __('facility_admin.kpi_collected_today') }}</div>
                <div class="stat-card__value" style="font-size:1.25rem;" data-kpi="collected_today">{{ $xaf($m['collected_today']) }}</div>
            </a>
            <a href="{{ route('portals.facility.admin.invoices') }}" class="stat-card stat-card--warning">
                <div class="stat-card__label">{{ __('facility_admin.kpi_outstanding') }}</div>
                <div class="stat-card__value" style="font-size:1.25rem;" data-kpi="outstanding">{{ $xaf($m['outstanding']) }}</div>
            </a>
            <a href="{{ route('portals.facility.admin.invoices') }}" class="stat-card">
                <div class="stat-card__label">{{ __('facility_admin.kpi_issued_today') }}</div>
                <div class="stat-card__value" data-kpi="issued_today">{{ $m['issued_today'] }}</div>
            </a>
        </div>
        <p class="page-subtitle" style="margin:.75rem 0 0;">{{ __('facility_admin.billing_note') }}</p>
        <p style="margin:.5rem 0 0;">
            <a href="{{ route('portals.facility.admin.invoices') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="receipt-text"></i> {{ __('facility_admin.action_open_invoices') }}
            </a>
            <a href="{{ route('portals.facility.admin.reports') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="bar-chart-3"></i> {{ __('facility_admin.action_open_reports') }}
            </a>
        </p>
    </div>
</div>

{{-- ── Team ─────────────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> {{ __('facility_admin.section_team') }}</h3>
    </div>
    <div class="panel-body">
        <div class="stat-grid" style="--cols:3">
            <a href="{{ route('portals.facility.team') }}" class="stat-card stat-card--primary">
                <div class="stat-card__label">{{ __('facility_admin.kpi_team_active') }}</div>
                <div class="stat-card__value" data-kpi="team_active">{{ $t['active'] }}</div>
            </a>
            <a href="{{ route('portals.facility.team') }}" class="stat-card">
                <div class="stat-card__label">{{ __('facility_admin.kpi_open_invites') }}</div>
                <div class="stat-card__value" data-kpi="open_invites">{{ $t['open_invites'] }}</div>
            </a>
            <a href="{{ route('portals.facility.team') }}" class="stat-card stat-card--warning">
                <div class="stat-card__label">{{ __('facility_admin.kpi_pending_joins') }}</div>
                <div class="stat-card__value" data-kpi="pending_joins">{{ $t['pending_joins'] }}</div>
            </a>
        </div>
        {{-- A diagnostic or imaging centre has no departments to group (I-7). --}}
        @unless(\App\Services\DiagnosticCentre\DiagnosticCentreDashboardService::appliesToType($facility?->type))
        <p style="margin:.75rem 0 0;">
            <a href="{{ route('portals.facility.admin.departments') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="layout-grid"></i> {{ __('facility_admin.action_open_departments') }}
            </a>
        </p>
        @endunless
    </div>
</div>

@include('portals.facility.admin._go_live_checklist')
@endsection
