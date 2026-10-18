@extends('layouts.portal')

@section('title', __('queue_desk.create_title'))
@section('breadcrumb_home', __('queue_desk.title'))
@section('breadcrumb_home_url', route('portals.queue.index'))
@section('breadcrumb_section', __('queue_desk.create_title'))

@use('App\Services\Queue\PatientQueueService', 'Q')

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('queue_desk.create_title') }}</h1>
    </div>
    <a href="{{ route('portals.queue.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('queue_desk.back_to_queue') }}
    </a>
</div>

@if(session('error'))<div class="odc-note odc-note--urgent"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="odc-note odc-note--urgent" role="alert">
    <i data-lucide="alert-circle"></i>
    <div><ul class="odc-note__list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

@if($patient === null)

    {{-- ── Door 1: exact Health ID ─────────────────────────────────────────
         No name search and no partial match, for the same reason there is
         none on the triage or prescriber desk: a professional must not be
         able to enumerate this platform's patients. --}}
    <div class="od-card odc-form">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('queue_desk.find_patient') }}</h2>
        </div>
        <div class="od-card__body">
            @if($notFound)
            <div class="odc-note odc-note--urgent" role="alert">
                <i data-lucide="user-x"></i>
                <div>{{ __('queue_desk.not_found') }}</div>
            </div>
            @endif

            <form method="GET" action="{{ route('portals.queue.create') }}">
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="queue-health-id">{{ __('queue_desk.health_id') }}</label>
                    <input type="text" id="queue-health-id" name="health_id" class="od-input"
                           value="{{ request('health_id') }}" required autocomplete="off" autofocus>
                    <div class="odc-hint">{{ __('queue_desk.health_id_hint') }}</div>
                </div>
                <div class="odc-actions">
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> <span>{{ __('queue_desk.lookup') }}</span></button>
                    <a href="{{ route('portals.queue.index') }}" class="od-btn od-btn--secondary">{{ __('queue_desk.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Door 2: today's bookings at THIS facility ───────────────────────
         Read from the facility's own appointment book, never from the
         patient table, so it is a list of this facility's day and not a
         listing of patients. --}}
    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('queue_desk.todays_bookings') }}</h2>
        </div>
        @if($appointments->isEmpty())
            <div class="od-card__body"><p class="od-muted">{{ __('queue_desk.bookings_empty') }}</p></div>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <thead>
                        <tr>
                            <th>{{ __('queue_desk.col_time') }}</th>
                            <th>{{ __('queue_desk.col_patient') }}</th>
                            <th>{{ __('queue_desk.col_clinician') }}</th>
                            <th>{{ __('queue_desk.col_booking_status') }}</th>
                            <th class="od-table__actions">{{ __('queue_desk.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($appointments as $appointment)
                        @php
                            $aPatient = $appointment->patient;
                            $aName    = trim(($aPatient->first_name ?? '') . ' ' . ($aPatient->last_name ?? ''));
                        @endphp
                        <tr>
                            <td data-label="{{ __('queue_desk.col_time') }}" class="odc-strong">{{ $appointment->scheduled_at?->format('H:i') ?? '—' }}</td>
                            <td data-label="{{ __('queue_desk.col_patient') }}">
                                {{ $aName !== '' ? $aName : '—' }}
                                @if($aPatient?->health_id)<div class="od-muted">{{ $aPatient->health_id }}</div>@endif
                            </td>
                            <td data-label="{{ __('queue_desk.col_clinician') }}" class="od-muted">{{ $appointment->provider?->name ?? '—' }}</td>
                            <td data-label="{{ __('queue_desk.col_booking_status') }}"><span class="od-pill od-pill--neutral">@enum($appointment->status)</span></td>
                            <td class="od-table__actions" data-label="{{ __('queue_desk.col_actions') }}">
                                <form method="POST" action="{{ route('portals.queue.store') }}" class="odc-inline">
                                    @csrf
                                    <input type="hidden" name="appointment_id" value="{{ $appointment->id }}">
                                    <input type="hidden" name="priority_level" value="{{ Q::DEFAULT_ARRIVAL_PRIORITY }}">
                                    <button type="submit" class="od-btn od-btn--primary od-btn--sm"><i data-lucide="log-in"></i> {{ __('queue_desk.check_in_booking') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@else

    @php $pName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')); @endphp

    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('queue_desk.patient') }}</h2>
            <a href="{{ route('portals.queue.create') }}" class="od-btn od-btn--secondary od-btn--sm">{{ __('queue_desk.change_patient') }}</a>
        </div>
        <div class="od-card__body">
            <div class="odc-strong">{{ $pName !== '' ? $pName : '—' }}</div>
            <div class="od-muted">{{ $patient->health_id }}</div>
        </div>
    </div>

    @if($existing)
        <div class="odc-note odc-note--pending" role="alert">
            <i data-lucide="info"></i>
            <div>{{ __('queue_desk.already_in_queue', ['number' => Q::displayNumber($existing)]) }}</div>
        </div>
        <a href="{{ route('portals.queue.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> {{ __('queue_desk.back_to_queue') }}</a>
    @else
        <div class="od-card odc-form">
            <div class="od-card__head">
                <h2 class="od-card__title">{{ __('queue_desk.confirm_check_in') }}</h2>
            </div>
            <div class="od-card__body">
                <p class="od-muted">{{ __('queue_desk.walk_in_note') }}</p>
                <form method="POST" action="{{ route('portals.queue.store') }}">
                    @csrf
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">

                    <div class="odc-field">
                        <label class="odc-label" for="queue-priority">{{ __('queue_desk.arrival_priority') }}</label>
                        <select id="queue-priority" name="priority_level" class="od-select">
                            @foreach($priorities as $level)
                                <option value="{{ $level }}" @selected((int) old('priority_level', Q::DEFAULT_ARRIVAL_PRIORITY) === $level)>{{ __('queue_desk.arrival_levels.' . $level) }}</option>
                            @endforeach
                        </select>
                        <div class="odc-hint">{{ __('queue_desk.arrival_priority_hint') }}</div>
                    </div>

                    <div class="odc-field">
                        <label class="odc-label" for="queue-priority-reason">{{ __('queue_desk.priority_reason') }}</label>
                        <input type="text" id="queue-priority-reason" name="priority_reason" class="od-input" maxlength="500" value="{{ old('priority_reason') }}">
                        <div class="odc-hint">{{ __('queue_desk.priority_reason_hint') }}</div>
                    </div>

                    <div class="odc-actions">
                        <button type="submit" class="od-btn od-btn--primary"><i data-lucide="log-in"></i> <span>{{ __('queue_desk.confirm_check_in') }}</span></button>
                        <a href="{{ route('portals.queue.index') }}" class="od-btn od-btn--secondary">{{ __('queue_desk.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endif

</div>
@endsection
