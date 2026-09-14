{{-- One ticket's priority, and WHERE it came from.

     `priority_rank` and `triage_priority` are selected by
     PatientQueueService::ranked() — the same SQL that orders the rows — so
     the badge can never disagree with the position. A triage acuity outranks
     the arrival priority and is labelled as triage; an arrival priority is
     labelled as such, so nobody mistakes a receptionist's lane for a nurse's
     acuity. --}}
@php
    $qTriage = $ticket->triage_priority ?? null;
    $qKey    = \App\Support\TriageAcuity::labelKey($qTriage);
    $qRank   = (int) ($ticket->priority_rank ?? $ticket->priority_level ?? 6);
    $qClass  = 'badge badge-' . match (true) {
        $qRank <= 1 => 'danger',
        $qRank === 2 => 'warning',
        $qRank === 3 => 'info',
        default => 'neutral',
    };
    $qArrival = __('queue_desk.arrival_levels');
@endphp
@if($qKey)
    <span class="{{ $qClass }}">{{ __($qKey) }}</span>
    <div class="td-muted">{{ __('queue_desk.priority_from_triage') }}</div>
@else
    <span class="{{ $qClass }}">{{ $qArrival[(int) $ticket->priority_level] ?? $ticket->priority_level }}</span>
    <div class="td-muted">{{ __('queue_desk.priority_from_arrival') }}</div>
@endif
