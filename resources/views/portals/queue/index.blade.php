@extends('layouts.portal')

@section('title', __('queue_desk.title'))
@section('breadcrumb_home', __('queue_desk.title'))
@section('breadcrumb_home_url', route('portals.queue.index'))
@section('breadcrumb_section', __('queue_desk.queue_title'))

@use('App\Services\Queue\PatientQueueService', 'Q')

@php
    $statusBadge = fn (string $status) => 'badge badge-' . match ($status) {
        Q::STATUS_WAITING   => 'neutral',
        Q::STATUS_CALLED    => 'warning',
        Q::STATUS_STARTED   => 'primary',
        Q::STATUS_COMPLETED => 'success',
        default             => 'danger',
    };
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('queue_desk.queue_title') }}</h1>
        <p class="page-subtitle">{{ __('queue_desk.queue_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.queue.create') }}" class="btn btn-primary">
        <i data-lucide="user-plus"></i> {{ __('queue_desk.check_in') }}
    </a>
</div>

@if(session('success'))<div class="alert alert-success mb-6"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="alert-circle"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

{{-- ── Today, measured ────────────────────────────────────────────────────
     Medians and counts from PatientQueueService::summaryFor(): every number
     here comes from a timestamp that was written once. This block is the
     platform's evidence, not its claim. --}}
<div class="stat-grid mb-6">
    <div class="stat-card stat-card--teal">
        <div class="stat-card__head"><i data-lucide="users"></i></div>
        <div class="stat-card__label">{{ __('queue_desk.summary_active') }}</div>
        <div class="stat-card__value">{{ $summary['active'] }}</div>
        <div class="stat-card__hint">{{ __('queue_desk.summary_waiting') }}: {{ $summary['waiting'] }}</div>
    </div>
    <div class="stat-card stat-card--primary">
        <div class="stat-card__head"><i data-lucide="timer"></i></div>
        <div class="stat-card__label">{{ __('queue_desk.summary_median_wait') }}</div>
        <div class="stat-card__value">{{ Q::formatDuration($summary['median_wait_seconds']) }}</div>
        <div class="stat-card__hint">
            @if($summary['longest_wait_seconds'] === null)
                {{ __('queue_desk.summary_none_yet') }}
            @else
                {{ __('queue_desk.summary_longest_wait') }}: {{ Q::formatDuration($summary['longest_wait_seconds']) }}
            @endif
        </div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-card__head"><i data-lucide="check-check"></i></div>
        <div class="stat-card__label">{{ __('queue_desk.summary_seen') }}</div>
        <div class="stat-card__value">{{ $summary['seen'] }}</div>
        <div class="stat-card__hint">{{ __('queue_desk.summary_median_service') }}: {{ Q::formatDuration($summary['median_service_seconds']) }}</div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-card__head"><i data-lucide="user-x"></i></div>
        <div class="stat-card__label">{{ __('queue_desk.summary_left') }}</div>
        <div class="stat-card__value">{{ $summary['left'] }}</div>
        <div class="stat-card__hint">{{ __('queue_desk.summary_checked_in') }}: {{ $summary['checked_in'] }}</div>
    </div>
</div>
<p class="td-muted mb-6">{{ __('queue_desk.summary_hint') }}</p>

{{-- ── The live queue ────────────────────────────────────────────────────── --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="list-ordered"></i> {{ __('queue_desk.queue_title') }}</h2>
    </div>
    @if($activeRows->isEmpty())
        <div class="panel-body">
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="armchair"></i></div>
                <h3>{{ __('queue_desk.empty_queue') }}</h3>
            </div>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('queue_desk.col_number') }}</th>
                        <th>{{ __('queue_desk.col_patient') }}</th>
                        <th>{{ __('queue_desk.col_priority') }}</th>
                        <th>{{ __('queue_desk.col_status') }}</th>
                        <th>{{ __('queue_desk.col_checked_in') }}</th>
                        <th>{{ __('queue_desk.col_waited') }}</th>
                        <th>{{ __('queue_desk.col_with') }}</th>
                        <th class="row-actions">{{ __('queue_desk.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($activeRows as $row)
                    @php
                        $ticket   = $row['ticket'];
                        $qPatient = $ticket->patient;
                        $qName    = trim(($qPatient->first_name ?? '') . ' ' . ($qPatient->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('queue_desk.col_number') }}"><span class="td-strong">{{ $row['number'] }}</span></td>
                        <td data-label="{{ __('queue_desk.col_patient') }}">
                            <span class="td-strong">{{ $qName !== '' ? $qName : '—' }}</span>
                            @if($qPatient?->health_id)<div class="td-muted">{{ $qPatient->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('queue_desk.col_priority') }}">@include('portals.queue._priority_badge', ['ticket' => $ticket])</td>
                        <td data-label="{{ __('queue_desk.col_status') }}">
                            <span class="{{ $statusBadge($ticket->status) }}">{{ __('queue_desk.status.' . $ticket->status) }}</span>
                        </td>
                        <td data-label="{{ __('queue_desk.col_checked_in') }}" class="td-muted">{{ $ticket->checked_in_at?->format('H:i') ?? '—' }}</td>
                        <td data-label="{{ __('queue_desk.col_waited') }}">
                            {{ Q::formatDuration($row['durations']['waited']) }}
                            @if($ticket->status === Q::STATUS_WAITING)<div class="td-muted">{{ __('queue_desk.still_waiting') }}</div>@endif
                            @if($ticket->status === Q::STATUS_STARTED)<div class="td-muted">{{ __('queue_desk.still_in_room') }}: {{ Q::formatDuration($row['durations']['served']) }}</div>@endif
                        </td>
                        <td data-label="{{ __('queue_desk.col_with') }}" class="td-muted">{{ $ticket->assignedTo?->name ?? '—' }}</td>
                        <td class="row-actions" data-label="{{ __('queue_desk.col_actions') }}">
                            <div class="row-actions-inline">
                                @if($ticket->status === Q::STATUS_WAITING)
                                    <form method="POST" action="{{ route('portals.queue.call', $ticket->id) }}" class="inline-form">@csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="megaphone"></i> {{ __('queue_desk.action_call') }}</button>
                                    </form>
                                @endif
                                @if($ticket->status === Q::STATUS_CALLED)
                                    <form method="POST" action="{{ route('portals.queue.call', $ticket->id) }}" class="inline-form">@csrf
                                        <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="megaphone"></i> {{ __('queue_desk.action_recall') }}</button>
                                    </form>
                                @endif
                                @if(in_array($ticket->status, [Q::STATUS_WAITING, Q::STATUS_CALLED], true))
                                    <form method="POST" action="{{ route('portals.queue.start', $ticket->id) }}" class="inline-form">@csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="play"></i> {{ __('queue_desk.action_start') }}</button>
                                    </form>
                                @endif
                                @if($ticket->status === Q::STATUS_STARTED)
                                    <form method="POST" action="{{ route('portals.queue.complete', $ticket->id) }}" class="inline-form">@csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> {{ __('queue_desk.action_complete') }}</button>
                                    </form>
                                @endif
                                @if($ticket->status === Q::STATUS_CALLED)
                                    <form method="POST" action="{{ route('portals.queue.no_show', $ticket->id) }}" class="inline-form">@csrf
                                        <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="user-x"></i> {{ __('queue_desk.action_no_show') }}</button>
                                    </form>
                                @endif
                                <a href="{{ route('portals.queue.show', $ticket->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> {{ __('queue_desk.action_open') }}</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Finished today ────────────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="history"></i> {{ __('queue_desk.closed_title') }}</h2>
    </div>
    @if($closedRows->isEmpty())
        <div class="panel-body"><p class="td-muted">{{ __('queue_desk.closed_empty') }}</p></div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('queue_desk.col_number') }}</th>
                        <th>{{ __('queue_desk.col_patient') }}</th>
                        <th>{{ __('queue_desk.col_status') }}</th>
                        <th>{{ __('queue_desk.col_checked_in') }}</th>
                        <th>{{ __('queue_desk.col_waited') }}</th>
                        <th>{{ __('queue_desk.col_served') }}</th>
                        <th class="row-actions">{{ __('queue_desk.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($closedRows as $row)
                    @php
                        $ticket   = $row['ticket'];
                        $qPatient = $ticket->patient;
                        $qName    = trim(($qPatient->first_name ?? '') . ' ' . ($qPatient->last_name ?? ''));
                    @endphp
                    <tr>
                        <td data-label="{{ __('queue_desk.col_number') }}"><span class="td-strong">{{ $row['number'] }}</span></td>
                        <td data-label="{{ __('queue_desk.col_patient') }}">
                            {{ $qName !== '' ? $qName : '—' }}
                            @if($qPatient?->health_id)<div class="td-muted">{{ $qPatient->health_id }}</div>@endif
                        </td>
                        <td data-label="{{ __('queue_desk.col_status') }}"><span class="{{ $statusBadge($ticket->status) }}">{{ __('queue_desk.status.' . $ticket->status) }}</span></td>
                        <td data-label="{{ __('queue_desk.col_checked_in') }}" class="td-muted">{{ $ticket->checked_in_at?->format('H:i') ?? '—' }}</td>
                        <td data-label="{{ __('queue_desk.col_waited') }}">{{ Q::formatDuration($row['durations']['waited']) }}</td>
                        <td data-label="{{ __('queue_desk.col_served') }}">{{ Q::formatDuration($row['durations']['served']) }}</td>
                        <td class="row-actions" data-label="{{ __('queue_desk.col_actions') }}">
                            <a href="{{ route('portals.queue.show', $ticket->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> {{ __('queue_desk.action_open') }}</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
