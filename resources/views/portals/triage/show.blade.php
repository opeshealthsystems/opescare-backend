@extends('layouts.portal')

@section('title', __('triage.show_title'))
@section('breadcrumb_home', __('triage.title'))
@section('breadcrumb_home_url', route('portals.triage.index'))
@section('breadcrumb_section', __('triage.show_title'))

@php
    $badge = fn (?string $level) => 'badge badge-' . match (\App\Support\TriageAcuity::severity($level)) {
        'critical' => 'danger',
        'warning'  => 'warning',
        'info'     => 'info',
        default    => 'neutral',
    };

    $flagBadge = fn (string $severity) => 'badge badge-' . match ($severity) {
        'critical' => 'danger',
        'warning'  => 'warning',
        'success'  => 'success',
        default    => 'neutral',
    };

    $tName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
    $tKey  = \App\Support\TriageAcuity::labelKey($priority);

    // The measures shown against the newest observation row, in the order a
    // clinician reads them. Interpretation is looked up by the SAME key the
    // controller used, so a measure with no verdict simply has none.
    $measures = [
        'temperature'       => $vitals->first()?->temperature,
        'pulse_rate'        => $vitals->first()?->pulse_rate,
        'respiratory_rate'  => $vitals->first()?->respiratory_rate,
        'blood_pressure'    => null,
        'oxygen_saturation' => $vitals->first()?->oxygen_saturation,
    ];
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('triage.show_title') }}</h1>
        <p class="page-subtitle">
            {{ __('triage.current_priority') }}:
            @if($tKey)
                <span class="{{ $badge($priority) }}">{{ __($tKey) }}</span>
            @elseif($priority)
                <span class="badge badge-neutral">{{ __('triage.acuity_legacy', ['value' => $priority]) }}</span>
            @else
                <span class="td-muted">{{ __('triage.acuity_none') }}</span>
            @endif
            <span class="td-muted">&middot; {{ __('triage.assigned_by_nurse') }}</span>
        </p>
    </div>
    <a href="{{ route('portals.triage.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('triage.back_to_queue') }}
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

{{-- ── Patient + assessment ───────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="user-round"></i> {{ __('triage.patient') }}</h2>
    </div>
    <div class="panel-body">
        <div class="td-strong">{{ $tName !== '' ? $tName : '—' }}</div>
        <div class="td-muted">
            {{ __('triage.health_id') }}: {{ $patient?->health_id ?? '—' }}
            @if($patient?->date_of_birth)
                &middot; {{ $patient->date_of_birth->format('Y-m-d') }}
                @if($bands->ageYears !== null) &middot; {{ __('triage.age_years', ['years' => $bands->ageYears]) }} @endif
            @else
                &middot; {{ __('triage.age_unknown') }}
            @endif
        </div>
    </div>
</div>

<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('triage.assessment') }}</h2>
    </div>
    <div class="panel-body">
        <p>{{ $record->presenting_complaint ?: '—' }}</p>
        <div class="td-muted">
            {{ __('triage.pain_score') }}: {{ $record->pain_score ?? '—' }}
            @if($record->pregnancy_status)
                &middot; {{ __('triage.pregnancy_status') }}: {{ __('triage.pregnancy.' . $record->pregnancy_status) }}
            @endif
            &middot; {{ __('triage.col_assessed_by') }}: {{ $record->nurse?->name ?? '—' }}
            &middot; {{ __('triage.col_assessed_at') }}: {{ $record->created_at?->format('d M Y H:i') ?? '—' }}
        </div>
    </div>
</div>

{{-- ── Observations, and whether this platform may interpret them ─────────
     $interpretation is EMPTY whenever TriageBandContext refused — a patient
     under 16, an unknown date of birth, or a recorded pregnancy. In that case
     the refusal below is printed and no flag of any kind is rendered: not a
     greyed-out one, not a softened one. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="activity"></i> {{ __('triage.vitals') }}</h2>
        @if($bands->eligible)
            <span class="badge badge-neutral">{{ __('triage.advisory_source') }}</span>
        @endif
    </div>
    <div class="panel-body">

        @if(! $bands->eligible)
        <div class="alert alert-warning mb-4" role="alert">
            <i data-lucide="shield-alert"></i>
            <div>
                <strong>{{ __('triage.no_bands_title') }}</strong>
                <p>{{ __($bands->reasonKey()) }}</p>
            </div>
        </div>
        @endif

        @if($vitals->isEmpty())
            <p class="td-muted">{{ __('triage.no_vitals') }}</p>
        @else
            @php $latest = $vitals->first(); @endphp
            <div class="table-wrapper">
                <table class="data-table">
                    <tbody>
                    @foreach($measures as $measure => $ignored)
                        @php
                            $value = match ($measure) {
                                'temperature'       => $latest->temperature,
                                'pulse_rate'        => $latest->pulse_rate,
                                'respiratory_rate'  => $latest->respiratory_rate,
                                'oxygen_saturation' => $latest->oxygen_saturation,
                                'blood_pressure'    => ($latest->systolic_bp !== null && $latest->diastolic_bp !== null)
                                    ? $latest->systolic_bp . '/' . $latest->diastolic_bp
                                    : null,
                                default => null,
                            };
                            $label = $measure === 'blood_pressure'
                                ? __('triage.systolic_bp') . ' / ' . __('triage.diastolic_bp')
                                : __('triage.' . $measure);
                            $flag = $interpretation[$measure] ?? null;
                        @endphp
                        <tr>
                            <td class="td-strong">{{ $label }}</td>
                            <td>{{ $value ?? '—' }}</td>
                            <td>
                                @if($flag && $flag['label_key'])
                                    <span class="{{ $flagBadge($flag['severity']) }}">{{ __($flag['label_key']) }}</span>
                                @elseif($value === null)
                                    <span class="td-muted">{{ __('triage.not_measured') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="td-strong">{{ __('triage.gcs_score') }}</td>
                        <td>{{ $latest->gcs_score ?? '—' }}</td>
                        <td>
                            @if($latest->consciousness_level)
                                <span class="td-muted">{{ __('triage.consciousness.' . $latest->consciousness_level) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="td-strong">{{ __('triage.weight_kg') }} / {{ __('triage.height_cm') }}</td>
                        <td>{{ $latest->weight_kg ?? '—' }} / {{ $latest->height_cm ?? '—' }}</td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="td-muted mt-3">
                {{ __('triage.recorded_at') }}: {{ $latest->recorded_at?->format('d M Y H:i') ?? '—' }}
                @if($vitals->count() > 1)
                    &middot; {{ __('triage.vitals_retaken') }} ({{ $vitals->count() }})
                @endif
            </div>

            @if($bands->eligible)
                <p class="form-hint mt-3">{{ __('triage.advisory_note') }}</p>
                @if(empty($interpretation))
                    <p class="td-muted">{{ __('triage.advisory_none') }}</p>
                @endif
            @endif
        @endif

    </div>
</div>

{{-- The patient's own readings: context only. --}}
@include('portals.triage._self_reported', ['selfReported' => $selfReported])

{{-- ── Reassessment history ───────────────────────────────────────────── --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="history"></i> {{ __('triage.history_title') }}</h2>
    </div>
    <div class="panel-body">
        @if($reassessments->isEmpty())
            <p class="td-muted">{{ __('triage.no_reassessments') }}</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <tbody>
                    @foreach($reassessments as $entry)
                        @php
                            $fromKey = \App\Support\TriageAcuity::labelKey($entry->previous_priority);
                            $toKey   = \App\Support\TriageAcuity::labelKey($entry->new_priority);
                            $up      = \App\Support\TriageAcuity::isEscalation($entry->previous_priority, $entry->new_priority);
                        @endphp
                        <tr>
                            <td>
                                <span class="td-strong">{{ __('triage.from_to', [
                                    'from' => $fromKey ? __($fromKey) : ($entry->previous_priority ?: '—'),
                                    'to'   => $toKey ? __($toKey) : $entry->new_priority,
                                ]) }}</span>
                                <div>
                                    @if($up === true)
                                        <span class="badge badge-danger">{{ __('triage.escalated') }}</span>
                                    @elseif($up === false)
                                        <span class="badge badge-neutral">{{ __('triage.de_escalated') }}</span>
                                    @endif
                                    @if($entry->new_vital_signs)
                                        <span class="badge badge-info">{{ __('triage.vitals_retaken') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $entry->reason_for_change }}</td>
                            <td class="td-muted">
                                {{ __('triage.reassessed_at') }}: {{ $entry->reassessed_at?->format('d M Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Reassess ───────────────────────────────────────────────────────────
     Re-triage, not edit. The form writes a new reassessment, a new score row
     and — if observations were retaken — a new observation row. Nothing above
     is modified. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="refresh-cw"></i> {{ __('triage.reassess_title') }}</h2>
    </div>
    <div class="panel-body">
        <p class="form-hint mb-4">{{ __('triage.reassess_hint') }}</p>

        <form method="POST" action="{{ route('portals.triage.reassess', $record->id) }}">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label form-label-required" for="triage-new-priority">{{ __('triage.new_priority') }}</label>
                    <select id="triage-new-priority" name="new_priority" class="form-control" required>
                        <option value="">{{ __('triage.acuity_choose') }}</option>
                        @foreach($acuityLevels as $level)
                            <option value="{{ $level }}" @selected(old('new_priority') === $level)>
                                {{ __('triage.acuity_levels.' . $level) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="triage-reassess-pain">{{ __('triage.pain_score') }}</label>
                    <input type="number" id="triage-reassess-pain" name="pain_score" class="form-control" min="0" max="10" step="1" value="{{ old('pain_score') }}">
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label form-label-required" for="triage-reason">{{ __('triage.reason_for_change') }}</label>
                <textarea id="triage-reason" name="reason_for_change" class="form-control" rows="3" required maxlength="2000">{{ old('reason_for_change') }}</textarea>
                <div class="form-hint">{{ __('triage.reason_hint') }}</div>
            </div>

            <div class="form-group mb-4">
                <div class="form-label">{{ __('triage.retake_vitals') }}</div>
                @include('portals.triage._vitals_fields', ['consciousness' => $consciousness])
            </div>

            <button type="submit" class="btn btn-primary">
                <i data-lucide="check"></i> <span>{{ __('triage.reassess_submit') }}</span>
            </button>
        </form>
    </div>
</div>

{{-- ── Priority history ───────────────────────────────────────────────── --}}
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="list-ordered"></i> {{ __('triage.score_history') }}</h2>
    </div>
    <div class="panel-body">
        <div class="table-wrapper">
            <table class="data-table">
                <tbody>
                @foreach($scores as $score)
                    @php $sKey = \App\Support\TriageAcuity::labelKey($score->priority_level); @endphp
                    <tr>
                        <td><span class="{{ $badge($score->priority_level) }}">{{ $sKey ? __($sKey) : $score->priority_level }}</span></td>
                        <td class="td-muted">{{ __('triage.scoring_manual') }}</td>
                        <td class="td-muted">{{ $score->scored_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
