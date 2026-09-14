@extends('layouts.portal')

@section('title', __('lab_manager.dashboard_title'))
@section('breadcrumb_home', __('lab_desk.title'))
@section('breadcrumb_home_url', route('portals.lab.index'))
@section('breadcrumb_section', __('lab_manager.dashboard_title'))

@php
    // Whole minutes → the shortest honest unit. Nothing is rounded into existence:
    // a null (no data) is printed as the "not enough data" sentence, never as 0.
    $duration = function (?int $minutes): ?string {
        if ($minutes === null) {
            return null;
        }
        if ($minutes < 60) {
            return __('lab_manager.tat_minutes', ['minutes' => $minutes]);
        }
        if ($minutes < 60 * 24) {
            return __('lab_manager.tat_hours', ['hours' => intdiv($minutes, 60), 'minutes' => $minutes % 60]);
        }

        return __('lab_manager.tat_days', ['days' => intdiv($minutes, 60 * 24), 'hours' => intdiv($minutes % (60 * 24), 60)]);
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('lab_manager.dashboard_title') }}</h1>
        <p class="page-subtitle">{{ __('lab_manager.dashboard_subtitle') }}</p>
    </div>
    <div class="row-actions-inline">
        <a href="{{ route('portals.lab.index') }}" class="btn btn-primary"><i data-lucide="flask-conical"></i> {{ __('lab_manager.open_worklist') }}</a>
        <a href="{{ route('portals.lab.collection') }}" class="btn btn-secondary"><i data-lucide="test-tube-2"></i> {{ __('lab_manager.open_collection') }}</a>
        <a href="{{ route('portals.lab_manager.requests') }}" class="btn btn-secondary"><i data-lucide="inbox"></i> {{ __('lab_manager.open_requests') }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

{{-- ── The bench, one tile per column ───────────────────────────────────── --}}
<div class="stat-grid">
    <a href="{{ route('portals.lab.index', ['status' => 'ordered']) }}" class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="test-tube-2"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_awaiting_sample') }}</div>
        <div class="stat-card__value">{{ $counts['ordered'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_column') }}</div>
    </a>
    <a href="{{ route('portals.lab.index', ['status' => 'collected']) }}" class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="beaker"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_sample_in') }}</div>
        <div class="stat-card__value">{{ $counts['collected'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_column') }}</div>
    </a>
    <a href="{{ route('portals.lab.index', ['status' => 'in_progress']) }}" class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="flask-conical"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_in_progress') }}</div>
        <div class="stat-card__value">{{ $counts['in_progress'] ?? 0 }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_column') }}</div>
    </a>
    <a href="{{ route('portals.lab.index', ['status' => 'resulted']) }}" class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="shield-check"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_awaiting_validation') }}</div>
        <div class="stat-card__value">{{ $awaiting_validation }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_column') }}</div>
    </a>
</div>

{{-- ── Today ────────────────────────────────────────────────────────────── --}}
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__head"><i data-lucide="send"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_released_today') }}</div>
        <div class="stat-card__value">{{ $today['released'] }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_today') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__head"><i data-lucide="badge-check"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_validated_today') }}</div>
        <div class="stat-card__value">{{ $today['validated'] }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_validated') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__head"><i data-lucide="syringe"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_collected_today') }}</div>
        <div class="stat-card__value">{{ $today['collected'] }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_today') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card__head"><i data-lucide="ban"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_rejected_today') }}</div>
        <div class="stat-card__value">{{ $today['rejected'] }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_today') }}</div>
    </div>
</div>

{{-- ── Attention ────────────────────────────────────────────────────────── --}}
<div class="stat-grid">
    <div class="stat-card {{ $critical_open > 0 ? 'stat-card--danger' : '' }}">
        <div class="stat-card__head"><i data-lucide="siren"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_critical_open') }}</div>
        <div class="stat-card__value">{{ $critical_open }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_critical') }}</div>
    </div>
    <a href="{{ route('portals.lab_manager.requests') }}" class="stat-card {{ $requests_pending > 0 ? 'stat-card--primary' : '' }}">
        <div class="stat-card__head"><i data-lucide="inbox"></i></div>
        <div class="stat-card__label">{{ __('lab_manager.kpi_requests_pending') }}</div>
        <div class="stat-card__value">{{ $requests_pending }}</div>
        <div class="stat-card__hint">{{ __('lab_manager.kpi_hint_requests') }}</div>
    </a>
</div>

{{-- ── Turnaround ───────────────────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="timer"></i> {{ __('lab_manager.tat_title') }}</h2>
    </div>
    <div class="panel-body">
        <p class="page-subtitle">{{ __('lab_manager.tat_subtitle', ['days' => $turnaround['window_days']]) }}</p>
        @if($turnaround['released_orders'] === 0)
            <div class="alert alert-info"><i data-lucide="circle-help"></i><div>{{ __('lab_manager.tat_none', ['days' => $turnaround['window_days']]) }}</div></div>
        @else
            <div class="form-grid">
                <div class="form-group">
                    <div class="form-label">{{ __('lab_manager.tat_median') }}</div>
                    <div class="td-strong">{{ $duration($turnaround['median_minutes']) }}</div>
                </div>
                <div class="form-group">
                    <div class="form-label">{{ __('lab_manager.tat_mean') }}</div>
                    <div class="td-strong">{{ $duration($turnaround['mean_minutes']) }}</div>
                </div>
                <div class="form-group">
                    <div class="form-label">{{ __('lab_manager.tat_orders') }}</div>
                    <div class="td-strong">{{ $turnaround['released_orders'] }}</div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ── Critical values nobody has acknowledged ──────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="siren"></i> {{ __('lab_manager.critical_title') }}</h2>
    </div>
    @if($critical->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="check-circle"></i></div>
                <h3>{{ __('lab_manager.critical_empty') }}</h3>
            </div>
        </div>
    @else
        <div class="panel-body"><p class="form-hint">{{ __('lab_manager.critical_subtitle') }}</p></div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('lab_manager.col_raised') }}</th>
                        <th>{{ __('lab_manager.col_patient') }}</th>
                        <th>{{ __('lab_manager.col_test') }}</th>
                        <th>{{ __('lab_manager.col_value') }}</th>
                        <th>{{ __('lab_manager.col_threshold') }}</th>
                        <th class="row-actions">{{ __('lab_manager.col_open') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($critical as $alert)
                    @php
                        $order = $alert->labResult?->labOrder;
                        $p     = $order?->patient;
                        $pName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('lab_manager.col_raised') }}" class="td-muted">{{ $alert->created_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td data-label="{{ __('lab_manager.col_patient') }}">
                            <span class="td-strong">{{ $pName !== '' ? $pName : '—' }}</span>
                            @if($p?->health_id)<div class="td-muted">{{ $p->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('lab_manager.col_test') }}">{{ $alert->test_name }}</td>
                        <td data-label="{{ __('lab_manager.col_value') }}"><span class="badge badge-danger">{{ $alert->result_value }}</span></td>
                        <td data-label="{{ __('lab_manager.col_threshold') }}" class="td-muted">{{ $alert->critical_threshold }}</td>
                        <td class="row-actions" data-label="{{ __('lab_manager.col_open') }}">
                            @if($order)
                            <a href="{{ route('portals.lab.show', $order->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> {{ __('lab_manager.open_order') }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
