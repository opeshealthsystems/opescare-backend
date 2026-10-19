@extends('layouts.portal')

@section('title', __('queue_desk.ticket_title', ['number' => $number]))

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

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('queue_desk.ticket_title', ['number' => $number]) }}</h1>
        <p class="od-page__sub">
            <span class="odc-strong">{{ $qName !== '' ? $qName : '—' }}</span>
            @if($qPatient?->health_id) · {{ $qPatient->health_id }} @endif
            · {{ $ticket->appointment_id ? __('queue_desk.booking') : __('queue_desk.walk_in') }}
        </p>
    </div>
    <a href="{{ route('portals.queue.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('queue_desk.back_to_queue') }}
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

{{-- ── Timeline: the five timestamps, each written once ─────────────────── --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('queue_desk.timeline') }}</h2>
        <div>
            <span class="od-pill od-pill--neutral">{{ __('queue_desk.status.' . $ticket->status) }}</span>
            @include('portals.queue._priority_badge', ['ticket' => $ticket])
        </div>
    </div>
    <div class="od-table-wrap">
        <table class="od-table">
            <tbody>
                @foreach($stamps as $key => $at)
                <tr>
                    <td class="odc-strong">{{ __('queue_desk.' . $key) }}</td>
                    <td>{{ $at ? $at->translatedFormat('d M Y H:i:s') : __('queue_desk.not_yet') }}</td>
                </tr>
                @endforeach
                <tr>
                    <td class="odc-strong">{{ __('queue_desk.waited_total') }}</td>
                    <td>{{ Q::formatDuration($durations['waited']) }}</td>
                </tr>
                <tr>
                    <td class="odc-strong">{{ __('queue_desk.served_total') }}</td>
                    <td>{{ Q::formatDuration($durations['served']) }}</td>
                </tr>
                @if($ticket->priority_reason)
                <tr>
                    <td class="odc-strong">{{ __('queue_desk.priority_reason') }}</td>
                    <td>{{ $ticket->priority_reason }}</td>
                </tr>
                @endif
                @if($ticket->status_reason)
                <tr>
                    <td class="odc-strong">{{ __('queue_desk.col_reason') }}</td>
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
<div class="od-card" data-panel="next-steps">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('hospital_desk.patient_next_steps') }}</h2>
    </div>
    <div class="od-card__body">
        <p class="od-muted">{{ __('hospital_desk.patient_next_steps_hint') }}</p>
        <div class="odc-actions">
            @capability('clinical_chart')
            @if($qPatient?->health_id)
            <a href="{{ route('portals.chart.index', ['health_id' => $qPatient->health_id]) }}" class="od-btn od-btn--primary od-btn--sm">
                <i data-lucide="folder-heart"></i> {{ __('hospital_desk.open_chart') }}
            </a>
            @endif
            @endcapability
            @capability('triage')
            @if($isOpen && $qPatient?->health_id)
            <a href="{{ route('portals.triage.create', ['health_id' => $qPatient->health_id]) }}" class="od-btn od-btn--secondary od-btn--sm">
                <i data-lucide="siren"></i> {{ __('hospital_desk.open_triage') }}
            </a>
            @endif
            @endcapability
            @capability('front_desk', 'schedule')
            @if($ticket->appointment_id)
            <a href="{{ route('portals.staff.appointments', ['date' => $ticket->appointment?->scheduled_at?->toDateString()]) }}" class="od-btn od-btn--secondary od-btn--sm">
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
<div class="od-card odc-form">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('queue_desk.action_cancel') }}</h2>
    </div>
    <div class="od-card__body">
        <form method="POST" action="{{ route('portals.queue.cancel', $ticket->id) }}" data-confirm="{{ __('hospital_desk.confirm_queue_cancel') }}">
            @csrf
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="queue-cancel-reason">{{ __('queue_desk.cancel_reason') }}</label>
                <input type="text" id="queue-cancel-reason" name="reason" class="od-input" maxlength="500" required value="{{ old('reason') }}">
            </div>
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="x-circle"></i> {{ __('queue_desk.action_cancel') }}</button>
        </form>
    </div>
</div>
@endif

{{-- ── History: append-only ──────────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('queue_desk.history_title') }}</h2>
    </div>
    <div class="od-card__body"><p class="od-muted">{{ __('queue_desk.history_hint') }}</p></div>
    <div class="od-table-wrap">
        <table class="od-table">
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
                    <td data-label="{{ __('queue_desk.col_at') }}" class="od-muted">{{ $h->changed_at?->translatedFormat('d M Y H:i:s') ?? '—' }}</td>
                    <td data-label="{{ __('queue_desk.col_from') }}">{{ $h->from_status ? __('queue_desk.status.' . $h->from_status) : '—' }}</td>
                    <td data-label="{{ __('queue_desk.col_to') }}"><span class="od-pill od-pill--neutral">{{ __('queue_desk.status.' . $h->to_status) }}</span></td>
                    <td data-label="{{ __('queue_desk.col_by') }}" class="od-muted">{{ $h->changed_by ? ($actors[$h->changed_by] ?? '—') : '—' }}</td>
                    <td data-label="{{ __('queue_desk.col_reason') }}" class="od-muted">{{ $h->reason ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

</div>
@endsection
