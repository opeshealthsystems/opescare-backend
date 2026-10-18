@extends('layouts.portal')

@section('title', __('facility_admin.overview_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.overview_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

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

    $canQueue = auth()->user() && \Illuminate\Support\Facades\Blade::check('capability', 'queue');
    $queueUrl = $canQueue ? route('portals.queue.index') : null;
    $isDiagnostic = \App\Services\DiagnosticCentre\DiagnosticCentreDashboardService::appliesToType($facility?->type);

    /* One tile = [label, value, data-kpi, tint, icon, url|null, text?, sub?]. */
    $tile = fn (string $label, $value, string $kpi, string $tint, string $icon, ?string $url, bool $text = false, ?string $sub = null): array
        => compact('label', 'value', 'kpi', 'tint', 'icon', 'url', 'text', 'sub');

    $sections = [
        ['title' => __('facility_admin.section_appointments'), 'cols' => 4, 'tiles' => [
            $tile(__('facility_admin.kpi_appointments_today'), $a['today'], 'appointments_today', 'blue', 'calendar-check', route('portals.facility.admin.reports')),
            $tile(__('facility_admin.kpi_checked_in'), $a['checked_in'], 'appointments_checked_in', 'green', 'user-check', route('portals.facility.admin.reports')),
            $tile(__('facility_admin.kpi_no_show'), $a['no_show'], 'appointments_no_show', 'amber', 'user-x', route('portals.facility.admin.reports')),
            $tile(__('facility_admin.kpi_cancelled'), $a['cancelled'], 'appointments_cancelled', 'rose', 'calendar-x', route('portals.facility.admin.reports')),
        ]],
        ['title' => __('facility_admin.section_queue'), 'cols' => 5, 'tiles' => [
            $tile(__('facility_admin.kpi_queue_checked_in'), $q['checked_in'], 'queue_checked_in', 'blue', 'log-in', $queueUrl),
            $tile(__('facility_admin.kpi_queue_waiting'), $q['waiting'], 'queue_waiting', 'amber', 'hourglass', $queueUrl),
            $tile(__('facility_admin.kpi_queue_seen'), $q['seen'], 'queue_seen', 'green', 'check', $queueUrl),
            $tile(__('facility_admin.kpi_median_wait'), $duration($q['median_wait_seconds']), 'queue_median_wait', 'sky', 'timer', $queueUrl, true),
            $tile(__('facility_admin.kpi_longest_wait'), $duration($q['longest_wait_seconds']), 'queue_longest_wait', 'teal', 'clock', $queueUrl, true),
        ]],
        ['title' => __('facility_admin.section_lab'), 'cols' => 3, 'tiles' => [
            $tile(__('facility_admin.kpi_lab_open'), $l['open'], 'lab_open', 'amber', 'flask-conical', route('portals.facility.admin.reports')),
            $tile(__('facility_admin.kpi_lab_resulted_today'), $l['resulted_today'], 'lab_resulted_today', 'green', 'check-circle-2', route('portals.facility.admin.reports')),
            $tile(__('facility_admin.kpi_lab_turnaround'), $duration($l['median_turnaround_seconds']), 'lab_turnaround', 'sky', 'timer', route('portals.facility.admin.reports'), true),
        ]],
    ];
@endphp

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('facility_admin.overview_title') }}</h1>
        <p class="od-page__sub">{{ __('facility_admin.overview_subtitle', ['facility' => $facility?->name ?? '—']) }}</p>
        <p class="od-page__sub">{{ __('facility_admin.overview_day', ['date' => $day->isoFormat('LL')]) }}</p>
    </div>
    <div class="odc-actions">
        <a href="{{ route('portals.facility.team') }}" class="od-btn od-btn--secondary">
            <i data-lucide="users"></i> {{ __('facility_admin.action_open_team') }}
        </a>
        @capability('queue')
        <a href="{{ route('portals.queue.index') }}" class="od-btn od-btn--primary">
            <i data-lucide="list-ordered"></i> {{ __('facility_admin.action_open_queue') }}
        </a>
        @endcapability
    </div>
</header>

@foreach($sections as $section)
<section class="od-card">
    <header class="od-card__head"><h2 class="od-card__title">{{ $section['title'] }}</h2></header>
    <div class="od-card__body">
        <div class="od-kpis odc-kpis {{ $section['cols'] === 4 ? '' : 'odc-kpis--' . $section['cols'] }}">
            @foreach($section['tiles'] as $k)
                @include('portals.facility.admin._kpi_tile', ['k' => $k])
            @endforeach
        </div>
    </div>
</section>
@endforeach

{{-- ── Beds: only when the facility has wards. No wards → no invented 0%.
     A diagnostic or imaging centre (I-7) admits nobody: no panel at all. ── --}}
@unless($isDiagnostic)
<section class="od-card" data-panel="beds">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.section_beds') }}</h2>
        <a href="{{ route('portals.facility.admin.wards') }}" class="od-card__link">{{ __('facility_admin.action_open_wards') }}</a>
    </header>
    <div class="od-card__body">
        @if($b === null)
            <div class="od-empty">
                <span class="od-empty__icon"><i data-lucide="bed"></i></span>
                <p class="od-empty__text">{{ __('facility_admin.beds_none') }}</p>
                <a href="{{ route('portals.facility.admin.wards') }}" class="od-btn od-btn--primary"><i data-lucide="bed"></i> {{ __('facility_admin.action_open_wards') }}</a>
            </div>
        @else
            <div class="od-kpis odc-kpis odc-kpis--5">
                @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_wards'), $b['wards'], 'wards', 'blue', 'layout-grid', route('portals.facility.admin.wards'))])
                @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_beds'), $b['beds'], 'beds', 'sky', 'bed', route('portals.facility.admin.wards'))])
                @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_beds_occupied'), $b['occupied'], 'beds_occupied', 'amber', 'bed-double', route('portals.facility.admin.wards'))])
                @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_beds_available'), $b['available'], 'beds_available', 'green', 'bed-single', route('portals.facility.admin.wards'))])
                @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_occupancy'), $b['occupancy_pct'] === null ? '—' : $b['occupancy_pct'] . ' %', 'occupancy', 'teal', 'percent', route('portals.facility.admin.wards'), false, __('facility_admin.kpi_admissions') . ': ' . $b['active_admissions'])])
            </div>
        @endif
    </div>
</section>
@endunless

{{-- ── Billing ──────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.section_billing') }}</h2>
        <a href="{{ route('portals.facility.admin.invoices') }}" class="od-card__link">{{ __('facility_admin.action_open_invoices') }}</a>
    </header>
    <div class="od-card__body">
        <div class="od-kpis odc-kpis odc-kpis--3">
            @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_collected_today'), $xaf($m['collected_today']), 'collected_today', 'green', 'banknote', route('portals.facility.admin.invoices'))])
            @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_outstanding'), $xaf($m['outstanding']), 'outstanding', 'amber', 'wallet', route('portals.facility.admin.invoices'))])
            @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_issued_today'), $m['issued_today'], 'issued_today', 'blue', 'receipt-text', route('portals.facility.admin.invoices'))])
        </div>
        <p class="odc-muted odc-muted--tight odc-links">{{ __('facility_admin.billing_note') }}</p>
        <div class="odc-links">
            <a href="{{ route('portals.facility.admin.invoices') }}" class="od-btn od-btn--secondary od-btn--sm">
                <i data-lucide="receipt-text"></i> {{ __('facility_admin.action_open_invoices') }}
            </a>
            <a href="{{ route('portals.facility.admin.reports') }}" class="od-btn od-btn--secondary od-btn--sm">
                <i data-lucide="bar-chart-3"></i> {{ __('facility_admin.action_open_reports') }}
            </a>
        </div>
    </div>
</section>

{{-- ── Team ─────────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.section_team') }}</h2>
        <a href="{{ route('portals.facility.team') }}" class="od-card__link">{{ __('facility_admin.action_open_team') }}</a>
    </header>
    <div class="od-card__body">
        <div class="od-kpis odc-kpis odc-kpis--3">
            @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_team_active'), $t['active'], 'team_active', 'blue', 'users', route('portals.facility.team'))])
            @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_open_invites'), $t['open_invites'], 'open_invites', 'sky', 'mail', route('portals.facility.team'))])
            @include('portals.facility.admin._kpi_tile', ['k' => $tile(__('facility_admin.kpi_pending_joins'), $t['pending_joins'], 'pending_joins', 'amber', 'user-plus', route('portals.facility.team'))])
        </div>
        {{-- A diagnostic or imaging centre has no departments to group (I-7). --}}
        @unless($isDiagnostic)
        <div class="odc-links">
            <a href="{{ route('portals.facility.admin.departments') }}" class="od-btn od-btn--secondary od-btn--sm">
                <i data-lucide="layout-grid"></i> {{ __('facility_admin.action_open_departments') }}
            </a>
        </div>
        @endunless
    </div>
</section>

@include('portals.facility.admin._go_live_checklist')
</div>
@endsection
