@extends('layouts.portal')

@section('title', __('facility_admin.reports_title') . ' — OpesCare')

@section('breadcrumb_home', __('facility_admin.breadcrumb_home'))
@section('breadcrumb_home_url', route('portals.admin'))
@section('breadcrumb_section', __('facility_admin.reports_title'))

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

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('facility_admin.reports_title') }}</h1>
        <p class="page-subtitle">{{ __('facility_admin.reports_subtitle') }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('portals.facility.admin.overview') }}" class="btn btn-secondary">
            <i data-lucide="gauge"></i> {{ __('facility_admin.nav_overview') }}
        </a>
    </div>
</div>

{{-- ── Range ────────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="{{ route('portals.facility.admin.reports') }}" class="form-inline" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group">
                <label for="from">{{ __('facility_admin.reports_range_from') }}</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ $from->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label for="to">{{ __('facility_admin.reports_range_to') }}</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ $to->toDateString() }}" required>
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="calendar-range"></i> {{ __('facility_admin.reports_range_apply') }}</button>
        </form>
        @error('to')
            <p class="text-danger" style="margin:.5rem 0 0;">{{ $message }}</p>
        @enderror
        @error('from')
            <p class="text-danger" style="margin:.5rem 0 0;">{{ $message }}</p>
        @enderror
        <p class="page-subtitle" style="margin:.5rem 0 0;" data-report-range>
            {{ __('facility_admin.reports_range_shown', ['from' => $from->isoFormat('LL'), 'to' => $to->isoFormat('LL'), 'days' => $from->diffInDays($to) + 1]) }}
        </p>
    </div>
</div>

{{-- ── Appointments ─────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="calendar-check"></i> {{ __('facility_admin.reports_table_appointments') }}</h3>
        <a href="{{ $exportUrl('appointments') }}" class="btn btn-secondary btn-sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </div>
    <div class="panel-body">
        <p style="margin:0 0 .75rem;" data-report="no_show_rate">
            <strong>{{ __('facility_admin.reports_no_show_rate') }}:</strong>
            @if($a['no_show_rate_pct'] === null)
                {{ __('facility_admin.kpi_no_measure') }}
            @else
                {{ __('facility_admin.reports_no_show_rate_value', ['pct' => $a['no_show_rate_pct'], 'no_show' => $a['no_show'], 'total' => $a['total']]) }}
            @endif
        </p>
        @if($a['days'] === [])
            <div class="empty-state"><i data-lucide="calendar-x"></i><p>{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="appointments">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        @foreach($a['statuses'] as $status)
                            <th>@enum($status)</th>
                        @endforeach
                        <th>{{ __('facility_admin.col_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($a['days'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td>{{ $row['day'] }}</td>
                        @foreach($a['statuses'] as $status)
                            <td data-status="{{ $status }}">{{ $row[$status] }}</td>
                        @endforeach
                        <td data-total>{{ $row['total'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Queue ────────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="list-ordered"></i> {{ __('facility_admin.reports_table_queue') }}</h3>
        <a href="{{ $exportUrl('queue') }}" class="btn btn-secondary btn-sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </div>
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 .75rem;">{{ __('facility_admin.reports_queue_note') }}</p>
        @if($reports['queue'] === [])
            <div class="empty-state"><i data-lucide="list-ordered"></i><p>{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="queue">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        <th>{{ __('facility_admin.col_checked_in') }}</th>
                        <th>{{ __('facility_admin.col_seen') }}</th>
                        <th>{{ __('facility_admin.col_left') }}</th>
                        <th>{{ __('facility_admin.col_median_wait') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['queue'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td>{{ $row['day'] }}</td>
                        <td>{{ $row['checked_in'] }}</td>
                        <td>{{ $row['seen'] }}</td>
                        <td>{{ $row['left'] }}</td>
                        <td>{{ $duration($row['median_wait_seconds']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Lab ──────────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="flask-conical"></i> {{ __('facility_admin.reports_table_lab') }}</h3>
        <a href="{{ $exportUrl('lab') }}" class="btn btn-secondary btn-sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </div>
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 .75rem;">{{ __('facility_admin.reports_lab_note') }}</p>
        @if($reports['lab'] === [])
            <div class="empty-state"><i data-lucide="flask-conical"></i><p>{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="lab">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        <th>{{ __('facility_admin.col_released') }}</th>
                        <th>{{ __('facility_admin.col_median_turnaround') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['lab'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td>{{ $row['day'] }}</td>
                        <td>{{ $row['released'] }}</td>
                        <td>{{ $duration($row['median_turnaround_seconds']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Billing ──────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="receipt"></i> {{ __('facility_admin.reports_table_billing') }}</h3>
        <a href="{{ $exportUrl('billing') }}" class="btn btn-secondary btn-sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </div>
    <div class="panel-body">
        <p class="page-subtitle" style="margin:0 0 .75rem;">{{ __('facility_admin.reports_billing_note') }}</p>
        @if($reports['billing'] === [])
            <div class="empty-state"><i data-lucide="receipt"></i><p>{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="billing">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_day') }}</th>
                        <th>{{ __('facility_admin.col_issued') }}</th>
                        <th>{{ __('facility_admin.col_issued_amount') }}</th>
                        <th>{{ __('facility_admin.col_collected') }}</th>
                        <th>{{ __('facility_admin.col_collected_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['billing'] as $row)
                    <tr data-day="{{ $row['day'] }}">
                        <td>{{ $row['day'] }}</td>
                        <td>{{ $row['issued'] }}</td>
                        <td>{{ $xaf($row['issued_amount']) }}</td>
                        <td>{{ $row['collected'] }}</td>
                        <td data-collected-amount>{{ $xaf($row['collected_amount']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Staff ────────────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><i data-lucide="users"></i> {{ __('facility_admin.reports_table_staff') }}</h3>
        <a href="{{ $exportUrl('staff') }}" class="btn btn-secondary btn-sm"><i data-lucide="download"></i> {{ __('facility_admin.reports_export_csv') }}</a>
    </div>
    <div class="panel-body">
        @if($reports['staff'] === [])
            <div class="empty-state"><i data-lucide="users"></i><p>{{ __('facility_admin.reports_empty') }}</p></div>
        @else
            <div class="table-wrapper">
            <table class="data-table" data-table="staff">
                <thead>
                    <tr>
                        <th>{{ __('facility_admin.col_role') }}</th>
                        <th>{{ __('facility_admin.col_headcount') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reports['staff'] as $row)
                    <tr data-role="{{ $row['role'] }}">
                        <td>{{ $roleLabel($row['role']) }}</td>
                        <td data-headcount>{{ $row['headcount'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
</div>

@endsection
