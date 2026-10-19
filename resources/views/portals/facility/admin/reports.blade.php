@extends('layouts.portal')

@section('title', __('facility_admin.reports_title') . ' — OpesCare')


@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')

@php
    /* Seconds → a human duration, or the honest "not measured" when no
       completed interval exists to measure. Never "0 min". */
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
    $xaf = fn (int $amount): string => number_format($amount, 0, ',', ' ');

    $exportUrl = fn (string $table): string => route('portals.facility.admin.reports.export', [
        'table' => $table, 'from' => $from->toDateString(), 'to' => $to->toDateString(),
    ]);

    $roleLabel = fn (string $role): string => __('portal.' . $role . '_role') !== 'portal.' . $role . '_role' ? __('portal.' . $role . '_role') : $role;

    $a = $reports['appointments'];
@endphp

<div class="od-page">

<header class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('facility_admin.reports_title') }}</h1>
        <p class="od-page__sub">{{ __('facility_admin.reports_subtitle') }}</p>
    </div>
    <div class="odc-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="od-btn od-btn--secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
    </div>
</header>

{{-- ── Range ────────────────────────────────────────────────────────── --}}
<section class="od-card">
    <div class="od-card__body odc-range">
        <form method="GET" action="{{ route('portals.facility.admin.reports') }}" class="odc-form odc-form--range">
            <div>
                <label class="odc-label" for="from">{{ __('facility_admin.reports_range_from') }}</label>
                <input type="date" id="from" name="from" class="od-input" value="{{ $from->toDateString() }}" required>
            </div>
            <div>
                <label class="odc-label" for="to">{{ __('facility_admin.reports_range_to') }}</label>
                <input type="date" id="to" name="to" class="od-input" value="{{ $to->toDateString() }}" required>
            </div>
            <div class="odc-range__go">
                <button type="submit" class="od-btn od-btn--primary"><i data-lucide="calendar-range"></i> {{ __('facility_admin.reports_range_apply') }}</button>
            </div>
        </form>
        @error('to')
            <p class="odc-error">{{ $message }}</p>
        @enderror
        @error('from')
            <p class="odc-error">{{ $message }}</p>
        @enderror
        <p class="odc-hint" data-report-range>
            {{ __('facility_admin.reports_range_shown', ['from' => $from->isoFormat('LL'), 'to' => $to->isoFormat('LL'), 'days' => $from->diffInDays($to) + 1]) }}
        </p>
    </div>
</section>

{{-- ── Appointments ─────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.reports_table_appointments') }}</h2>
        <a href="{{ $exportUrl('appointments') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </header>
    <div class="od-card__body">
        <p class="odc-muted" data-report="no_show_rate">
            <span class="odc-strong">{{ __('facility_admin.reports_no_show_rate') }}:</span>
            @if($a['no_show_rate_pct'] === null)
                {{ __('facility_admin.kpi_no_measure') }}
            @else
                {{ __('facility_admin.reports_no_show_rate_value', ['pct' => $a['no_show_rate_pct'], 'no_show' => $a['no_show'], 'total' => $a['total']]) }}
            @endif
        </p>
        @if($a['days'] === [])
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="calendar-x"></i></span><p class="od-empty__text">{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="appointments">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        @foreach($a['statuses'] as $status)
                            <th class="odc-num">@enum($status)</th>
                        @endforeach
                        <th class="odc-num">{{ __('facility_admin.col_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($a['days'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td class="odc-nowrap">{{ $row['day'] }}</td>
                        @foreach($a['statuses'] as $status)
                            <td class="odc-num" data-status="{{ $status }}">{{ $row[$status] }}</td>
                        @endforeach
                        <td data-total>{{ $row['total'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>

<div class="od-grid od-grid--2">
{{-- ── Queue ────────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.reports_table_queue') }}</h2>
        <a href="{{ $exportUrl('queue') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __('facility_admin.reports_queue_note') }}</p>
        @if($reports['queue'] === [])
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="list-ordered"></i></span><p class="od-empty__text">{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="queue">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_checked_in') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_seen') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_left') }}</th>
                        <th>{{ __('facility_admin.col_median_wait') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['queue'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td class="odc-nowrap">{{ $row['day'] }}</td>
                        <td class="odc-num">{{ $row['checked_in'] }}</td>
                        <td class="odc-num">{{ $row['seen'] }}</td>
                        <td class="odc-num">{{ $row['left'] }}</td>
                        <td>{{ $duration($row['median_wait_seconds']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>

{{-- ── Lab ──────────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.reports_table_lab') }}</h2>
        <a href="{{ $exportUrl('lab') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __('facility_admin.reports_lab_note') }}</p>
        @if($reports['lab'] === [])
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="flask-conical"></i></span><p class="od-empty__text">{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="lab">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_released') }}</th>
                        <th>{{ __('facility_admin.col_median_turnaround') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['lab'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td class="odc-nowrap">{{ $row['day'] }}</td>
                        <td class="odc-num">{{ $row['released'] }}</td>
                        <td>{{ $duration($row['median_turnaround_seconds']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>
</div>

<div class="od-grid od-grid--main">
{{-- ── Billing ──────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.reports_table_billing') }}</h2>
        <a href="{{ $exportUrl('billing') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </header>
    <div class="od-card__body">
        <p class="odc-muted">{{ __('facility_admin.reports_billing_note') }}</p>
        @if($reports['billing'] === [])
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="receipt"></i></span><p class="od-empty__text">{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="billing">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_issued') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_issued_amount') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_collected') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_collected_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['billing'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td class="odc-nowrap">{{ $row['day'] }}</td>
                        <td class="odc-num">{{ $row['issued'] }}</td>
                        <td class="odc-num">{{ $xaf($row['issued_amount']) }}</td>
                        <td class="odc-num">{{ $row['collected'] }}</td>
                        <td data-collected-amount>{{ $xaf($row['collected_amount']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>

{{-- ── Staff ────────────────────────────────────────────────────────── --}}
<section class="od-card">
    <header class="od-card__head">
        <h2 class="od-card__title">{{ __('facility_admin.reports_table_staff') }}</h2>
        <a href="{{ $exportUrl('staff') }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </header>
    <div class="od-card__body">
        @if($reports['staff'] === [])
            <div class="od-empty"><span class="od-empty__icon"><i data-lucide="users"></i></span><p class="od-empty__text">{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
            <table class="od-table" data-table="staff">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_role') }}</th>
                        <th class="odc-num">{{ __('facility_admin.col_headcount') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['staff'] as $row)
                    <tr data-role="{{ $row['role'] }}">
                        <td><span class="od-table__name">{{ $roleLabel($row['role']) }}</span></td>
                        <td data-headcount>{{ $row['headcount'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</section>
</div>

</div>
@endsection
