@extends('layouts.portal')

@section('title', __('queue_desk.ticket_title', ['number' => $number]))
@section('breadcrumb_home', __('queue_desk.title'))
@section('breadcrumb_home_url', route('portals.queue.index'))
@section('breadcrumb_section', __('queue_desk.ticket_title', ['number' => $number]))

@use('App\Services\Queue\PatientQueueService', 'Q')

@php
    $qPatient = $ticket->patient;
    $qName    = trim(($qPatient->first_name ?? '') . ' ' . ($qPatient->last_name ?? ''));
    $stamps   = [
        'checked_in_at'      => $ticket->checked_in_at,
        'called_at'          => $ticket->called_at,
        'service_started_at' => $ticket->service_started_at,
        'completed_at'       => $ticket->completed_at,
        'cancelled_at'       => $ticket->cancelled_at,
    ];
    $isOpen = in_array($ticket->status, Q::ACTIVE_STATUSES, true);
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('queue_desk.ticket_title', ['number' => $number]) }}</h1>
        <p class="page-subtitle">
            <span class="td-strong">{{ $qName !== '' ? $qName : '—' }}</span>
            @if($qPatient?->health_id) · {{ $qPatient->health_id }} @endif
            · {{ $ticket->appointment_id ? __('queue_desk.booking') : __('queue_desk.walk_in') }}
        </p>
    </div>
    <a href="{{ route('portals.queue.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('queue_desk.back_to_queue') }}
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

{{-- ── Timeline: the five timestamps, each written once ─────────────────── --}}
<div class="panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="timer"></i> {{ __('queue_desk.timeline') }}</h2>
        <div>
            <span class="badge badge-neutral">{{ __('queue_desk.status.' . $ticket->status) }}</span>
            @include('portals.queue._priority_badge', ['ticket' => $ticket])
        </div>
    </div>
    <div class="table-wrapper">
        <table class="data-table">
            <tbody>
                @foreach($stamps as $key => $at)
                <tr>
                    <td class="td-strong">{{ __('queue_desk.' . $key) }}</td>
                    <td>{{ $at ? $at->translatedFormat('d M Y H:i:s') : __('queue_desk.not_yet') }}</td>
                </tr>
                @endforeach
                <tr>
                    <td class="td-strong">{{ __('queue_desk.waited_total') }}</td>
                    <td>{{ Q::formatDuration($durations['waited']) }}</td>
                </tr>
                <tr>
                    <td class="td-strong">{{ __('queue_desk.served_total') }}</td>
                    <td>{{ Q::formatDuration($durations['served']) }}</td>
                </tr>
                @if($ticket->priority_reason)
                <tr>
                    <td class="td-strong">{{ __('queue_desk.priority_reason') }}</td>
                    <td>{{ $ticket->priority_reason }}</td>
                </tr>
                @endif
                @if($ticket->status_reason)
                <tr>
                    <td class="td-strong">{{ __('queue_desk.col_reason') }}</td>
                    <td>{{ $ticket->status_reason }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- ── Next steps for this patient: the chart (consent-gated inside), triage
     for this visit, and the booking the ticket came from. Each link is gated
     by the same capability as its route so nothing here 403s. ─────────── --}}
@capability('clinical_chart', 'triage')
<div class="panel mb-6" data-panel="next-steps">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="route"></i> {{ __('hospital_desk.patient_next_steps') }}</h2>
    </div>
    <div class="panel-body">
        <p class="td-muted mb-4">{{ __('hospital_desk.patient_next_steps_hint') }}</p>
        <div class="row-actions-inline">
            @capability('clinical_chart')
            @if($qPatient?->health_id)
            <a href="{{ route('portals.chart.index', ['health_id' => $qPatient->health_id]) }}" class="btn btn-primary btn-sm">
                <i data-lucide="folder-heart"></i> {{ __('hospital_desk.open_chart') }}
            </a>
            @endif
            @endcapability
            @capability('triage')
            @if($isOpen && $qPatient?->health_id)
            <a href="{{ route('portals.triage.create', ['health_id' => $qPatient->health_id]) }}" class="btn btn-secondary btn-sm">
                <i data-lucide="siren"></i> {{ __('hospital_desk.open_triage') }}
            </a>
            @endif
            @endcapability
            @capability('front_desk', 'schedule')
            @if($ticket->appointment_id)
            <a href="{{ route('portals.staff.appointments', ['date' => $ticket->appointment?->scheduled_at?->toDateString()]) }}" class="btn btn-secondary btn-sm">
                <i data-lucide="calendar-check-2"></i> {{ __('hospital_desk.open_appointment') }}
            </a>
            @endif
            @endcapability
        </div>
    </div>
</div>
@endcapability

{{-- ── Cancel, with a mandatory reason. Only while the ticket is open and
     not mid-consultation: a consultation that began is completed, not
     cancelled. ──────────────────────────────────────────────────────────── --}}
@if(in_array($ticket->status, [Q::STATUS_WAITING, Q::STATUS_CALLED], true))
<div class="panel form-panel mb-6">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="x-circle"></i> {{ __('queue_desk.action_cancel') }}</h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('portals.queue.cancel', $ticket->id) }}" data-confirm="{{ __('hospital_desk.confirm_queue_cancel') }}">
            @csrf
            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="queue-cancel-reason">{{ __('queue_desk.cancel_reason') }}</label>
                <input type="text" id="queue-cancel-reason" name="reason" class="form-control" maxlength="500" required value="{{ old('reason') }}">
            </div>
            <button type="submit" class="btn btn-secondary"><i data-lucide="x-circle"></i> {{ __('queue_desk.action_cancel') }}</button>
        </form>
    </div>
</div>
@endif

{{-- ── History: append-only ──────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="history"></i> {{ __('queue_desk.history_title') }}</h2>
    </div>
    <div class="panel-body"><p class="td-muted">{{ __('queue_desk.history_hint') }}</p></div>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('queue_desk.col_at') }}</th>
                    <th>{{ __('queue_desk.col_from') }}</th>
                    <th>{{ __('queue_desk.col_to') }}</th>
                    <th>{{ __('queue_desk.col_by') }}</th>
                    <th>{{ __('queue_desk.col_reason') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($ticket->histories as $h)
                <tr>
                    <td data-label="{{ __('queue_desk.col_at') }}" class="td-muted">{{ $h->changed_at?->translatedFormat('d M Y H:i:s') ?? '—' }}</td>
                    <td data-label="{{ __('queue_desk.col_from') }}">{{ $h->from_status ? __('queue_desk.status.' . $h->from_status) : '—' }}</td>
                    <td data-label="{{ __('queue_desk.col_to') }}"><span class="badge badge-neutral">{{ __('queue_desk.status.' . $h->to_status) }}</span></td>
                    <td data-label="{{ __('queue_desk.col_by') }}" class="td-muted">{{ $h->changed_by ? ($actors[$h->changed_by] ?? '—') : '—' }}</td>
                    <td data-label="{{ __('queue_desk.col_reason') }}" class="td-muted">{{ $h->reason ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
