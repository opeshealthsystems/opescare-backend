@extends('layouts.portal')

@section('title', __('queue_desk.title'))
@section('breadcrumb_home', __('queue_desk.title'))
@section('breadcrumb_home_url', route('portals.queue.index'))
@section('breadcrumb_section', __('queue_desk.queue_title'))

@use('App\Services\Queue\PatientQueueService', 'Q')

@php
    $statusBadge = fn (string $status) => 'od-pill od-pill--' . match ($status) {
        Q::STATUS_WAITING   => 'neutral',
        Q::STATUS_CALLED    => 'pending',
        Q::STATUS_STARTED   => 'normal',
        Q::STATUS_COMPLETED => 'ready',
        default             => 'urgent',
    };
    $odInitials = function ($first, $last): string {
        $s = mb_strtoupper(mb_substr(trim((string) $first), 0, 1) . mb_substr(trim((string) $last), 0, 1));
        return $s !== '' ? $s : '—';
    };
    $odTone = fn (string $key): string => ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($key) % 6];
@endphp

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('queue_desk.queue_title') }}</h1>
        <p class="od-page__sub">{{ __('queue_desk.queue_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.queue.create') }}" class="od-btn od-btn--primary">
        <i data-lucide="user-plus"></i> {{ __('queue_desk.check_in') }}
    </a>
</div>

@if(session('success'))<div class="odc-note odc-note--ready"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="odc-note odc-note--urgent"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--urgent" role="alert">
    <i data-lucide="alert-circle"></i>
    <div><ul class="odc-note__list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

{{-- ── Today, measured ────────────────────────────────────────────────────
     Medians and counts from PatientQueueService::summaryFor(): every number
     here comes from a timestamp that was written once. This block is the
     platform's evidence, not its claim. --}}
<div class="od-kpis">
    <div class="od-kpi od-tint--teal">
        <span class="od-kpi__icon"><i data-lucide="users"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('queue_desk.summary_active') }}</span>
            <span class="od-kpi__value">{{ $summary['active'] }}</span>
            <span class="od-kpi__sub">{{ __('queue_desk.summary_waiting') }}: {{ $summary['waiting'] }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--blue">
        <span class="od-kpi__icon"><i data-lucide="timer"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('queue_desk.summary_median_wait') }}</span>
            <span class="od-kpi__value">{{ Q::formatDuration($summary['median_wait_seconds']) }}</span>
            <span class="od-kpi__sub">
            @if($summary['longest_wait_seconds'] === null)
                {{ __('queue_desk.summary_none_yet') }}
            @else
                {{ __('queue_desk.summary_longest_wait') }}: {{ Q::formatDuration($summary['longest_wait_seconds']) }}
            @endif
        </span>
        </span>
    </div>
    <div class="od-kpi od-tint--green">
        <span class="od-kpi__icon"><i data-lucide="check-check"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('queue_desk.summary_seen') }}</span>
            <span class="od-kpi__value">{{ $summary['seen'] }}</span>
            <span class="od-kpi__sub">{{ __('queue_desk.summary_median_service') }}: {{ Q::formatDuration($summary['median_service_seconds']) }}</span>
        </span>
    </div>
    <div class="od-kpi od-tint--amber">
        <span class="od-kpi__icon"><i data-lucide="user-x"></i></span>
        <span class="od-kpi__body">
            <span class="od-kpi__label">{{ __('queue_desk.summary_left') }}</span>
            <span class="od-kpi__value">{{ $summary['left'] }}</span>
            <span class="od-kpi__sub">{{ __('queue_desk.summary_checked_in') }}: {{ $summary['checked_in'] }}</span>
        </span>
    </div>
</div>
<p class="od-muted">{{ __('queue_desk.summary_hint') }}</p>

{{-- ── The live queue ────────────────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('queue_desk.queue_title') }} <span class="od-card__count">{{ __('hospital_desk.c_count_paren', ['count' => $activeRows->count()]) }}</span></h2>
    </div>
    @if($activeRows->isEmpty())
        <div class="od-card__body">
            <div class="od-empty">
                <div class="od-empty__icon"><i data-lucide="armchair"></i></div>
                <div class="od-empty__title">{{ __('queue_desk.empty_queue') }}</div>
            </div>
        </div>
    @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('queue_desk.col_number') }}</th>
                        <th>{{ __('queue_desk.col_patient') }}</th>
                        <th>{{ __('queue_desk.col_priority') }}</th>
                        <th>{{ __('queue_desk.col_status') }}</th>
                        <th>{{ __('queue_desk.col_checked_in') }}</th>
                        <th>{{ __('queue_desk.col_waited') }}</th>
                        <th>{{ __('queue_desk.col_with') }}</th>
                        <th class="od-table__actions">{{ __('queue_desk.col_actions') }}</th>
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
                        <td data-label="{{ __('queue_desk.col_number') }}"><span class="odc-ticket">{{ $row['number'] }}</span></td>
                        <td data-label="{{ __('queue_desk.col_patient') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-tint--{{ $odTone($qName !== '' ? $qName : (string) $ticket->id) }}" aria-hidden="true">{{ $odInitials($qPatient?->first_name, $qPatient?->last_name) }}</span>
                                <div><span class="od-table__name">{{ $qName !== '' ? $qName : '—' }}</span>@if($qPatient?->health_id)<span class="od-table__sub">{{ $qPatient->health_id }}@capability('clinical_chart') · <a href="{{ route('portals.chart.index', ['health_id' => $qPatient->health_id]) }}" class="odc-link">{{ __('hospital_desk.open_chart') }}</a>@endcapability</span>@endif</div>
                            </div>
                        </td>
                        <td data-label="{{ __('queue_desk.col_priority') }}">@include('portals.queue._priority_badge', ['ticket' => $ticket])</td>
                        <td data-label="{{ __('queue_desk.col_status') }}">
                            <span class="{{ $statusBadge($ticket->status) }}">{{ __('queue_desk.status.' . $ticket->status) }}</span>
                        </td>
                        <td data-label="{{ __('queue_desk.col_checked_in') }}" class="od-muted">{{ $ticket->checked_in_at?->format('H:i') ?? '—' }}</td>
                        <td data-label="{{ __('queue_desk.col_waited') }}" class="odc-waited">
                            {{ Q::formatDuration($row['durations']['waited']) }}
                            @if($ticket->status === Q::STATUS_WAITING)<div class="od-muted">{{ __('queue_desk.still_waiting') }}</div>@endif
                            @if($ticket->status === Q::STATUS_STARTED)<div class="od-muted">{{ __('queue_desk.still_in_room') }}: {{ Q::formatDuration($row['durations']['served']) }}</div>@endif
                        </td>
                        <td data-label="{{ __('queue_desk.col_with') }}" class="od-muted">{{ $ticket->assignedTo?->name ?? '—' }}</td>
                        <td class="od-table__actions" data-label="{{ __('queue_desk.col_actions') }}">
                            <div class="odc-actions">
                                @if($ticket->status === Q::STATUS_WAITING)
                                    <form method="POST" action="{{ route('portals.queue.call', $ticket->id) }}" class="odc-inline">@csrf
                                        <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="megaphone"></i> {{ __('queue_desk.action_call') }}</button>
                                    </form>
                                @endif
                                @if($ticket->status === Q::STATUS_CALLED)
                                    <form method="POST" action="{{ route('portals.queue.call', $ticket->id) }}" class="odc-inline">@csrf
                                        <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="megaphone"></i> {{ __('queue_desk.action_recall') }}</button>
                                    </form>
                                @endif
                                @if(in_array($ticket->status, [Q::STATUS_WAITING, Q::STATUS_CALLED], true))
                                    <form method="POST" action="{{ route('portals.queue.start', $ticket->id) }}" class="odc-inline">@csrf
                                        <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="play"></i> {{ __('queue_desk.action_start') }}</button>
                                    </form>
                                @endif
                                @if($ticket->status === Q::STATUS_STARTED)
                                    <form method="POST" action="{{ route('portals.queue.complete', $ticket->id) }}" class="odc-inline" data-confirm="{{ __('hospital_desk.confirm_queue_complete') }}">@csrf
                                        <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="check"></i> {{ __('queue_desk.action_complete') }}</button>
                                    </form>
                                @endif
                                @if($ticket->status === Q::STATUS_CALLED)
                                    <form method="POST" action="{{ route('portals.queue.no_show', $ticket->id) }}" class="odc-inline" data-confirm="{{ __('hospital_desk.confirm_queue_no_show') }}">@csrf
                                        <button type="submit" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="user-x"></i> {{ __('queue_desk.action_no_show') }}</button>
                                    </form>
                                @endif
                                <a href="{{ route('portals.queue.show', $ticket->id) }}" class="od-btn od-btn--row">{{ __('queue_desk.action_open') }}</a>
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
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('queue_desk.closed_title') }} <span class="od-card__count">{{ __('hospital_desk.c_count_paren', ['count' => $closedRows->count()]) }}</span></h2>
    </div>
    @if($closedRows->isEmpty())
        <div class="od-card__body"><p class="od-muted">{{ __('queue_desk.closed_empty') }}</p></div>
    @else
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>{{ __('queue_desk.col_number') }}</th>
                        <th>{{ __('queue_desk.col_patient') }}</th>
                        <th>{{ __('queue_desk.col_status') }}</th>
                        <th>{{ __('queue_desk.col_checked_in') }}</th>
                        <th>{{ __('queue_desk.col_waited') }}</th>
                        <th>{{ __('queue_desk.col_served') }}</th>
                        <th class="od-table__actions">{{ __('queue_desk.col_actions') }}</th>
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
                        <td data-label="{{ __('queue_desk.col_number') }}"><span class="odc-ticket">{{ $row['number'] }}</span></td>
                        <td data-label="{{ __('queue_desk.col_patient') }}">
                            <div class="od-table__person">
                                <span class="od-avatar od-avatar--sm od-tint--{{ $odTone($qName !== '' ? $qName : (string) $ticket->id) }}" aria-hidden="true">{{ $odInitials($qPatient?->first_name, $qPatient?->last_name) }}</span>
                                <div><span class="od-table__name">{{ $qName !== '' ? $qName : '—' }}</span>@if($qPatient?->health_id)<span class="od-table__sub">{{ $qPatient->health_id }}</span>@endif</div>
                            </div>
                        </td>
                        <td data-label="{{ __('queue_desk.col_status') }}"><span class="{{ $statusBadge($ticket->status) }}">{{ __('queue_desk.status.' . $ticket->status) }}</span></td>
                        <td data-label="{{ __('queue_desk.col_checked_in') }}" class="od-muted">{{ $ticket->checked_in_at?->format('H:i') ?? '—' }}</td>
                        <td data-label="{{ __('queue_desk.col_waited') }}" class="odc-waited">{{ Q::formatDuration($row['durations']['waited']) }}</td>
                        <td data-label="{{ __('queue_desk.col_served') }}">{{ Q::formatDuration($row['durations']['served']) }}</td>
                        <td class="od-table__actions" data-label="{{ __('queue_desk.col_actions') }}">
                            <a href="{{ route('portals.queue.show', $ticket->id) }}" class="od-btn od-btn--row">{{ __('queue_desk.action_open') }}</a>
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
