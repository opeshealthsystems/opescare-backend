@extends('layouts.portal')

@section('title', __('triage.show_title'))

@php
    $badge = fn (?string $level) => 'od-pill od-pill--' . match (\App\Support\TriageAcuity::severity($level)) {
        'critical' => 'urgent',
        'warning'  => 'pending',
        'info'     => 'normal',
        default    => 'neutral',
    };

    $flagBadge = fn (string $severity) => 'od-pill od-pill--' . match ($severity) {
        'critical' => 'urgent',
        'warning'  => 'pending',
        'success'  => 'ready',
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

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('triage.show_title') }}</h1>
        <p class="od-page__sub">
            {{ __('triage.current_priority') }}:
            @if($tKey)
                <span class="{{ $badge($priority) }}">{{ __($tKey) }}</span>
            @elseif($priority)
                <span class="od-pill od-pill--neutral">{{ __('triage.acuity_legacy', ['value' => $priority]) }}</span>
            @else
                <span class="od-muted">{{ __('triage.acuity_none') }}</span>
            @endif
            <span class="od-muted">&middot; {{ __('triage.assigned_by_nurse') }}</span>
        </p>
    </div>
    <a href="{{ route('portals.triage.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('triage.back_to_queue') }}
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

{{-- ── Patient + assessment ───────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('triage.patient') }}</h2>
    </div>
    <div class="od-card__body">
        <div class="odc-strong">{{ $tName !== '' ? $tName : '—' }}</div>
        <div class="od-muted">
            {{ __('triage.health_id') }}: {{ $patient?->health_id ?? '—' }}
            @if($patient?->date_of_birth)
                @if($bands->ageYears !== null) &middot; {{ __('triage.age_years', ['years' => $bands->ageYears]) }} @endif
            @else
                &middot; {{ __('triage.age_unknown') }}
            @endif
        </div>
    </div>
</div>

<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('triage.assessment') }}</h2>
    </div>
    <div class="od-card__body">
        <p>{{ $record->presenting_complaint ?: '—' }}</p>
        <div class="od-muted">
            {{ __('triage.pain_score') }}: {{ $record->pain_score ?? '—' }}
            @if($record->pregnancy_status)
                &middot; {{ __('triage.pregnancy_status') }}: {{ __('triage.pregnancy.' . $record->pregnancy_status) }}
            @endif
            &middot; {{ __('triage.col_assessed_by') }}: {{ $record->nurse?->name ?? '—' }}
            &middot; {{ __('triage.col_assessed_at') }}: {{ $record->created_at?->translatedFormat('d M Y H:i') ?? '—' }}
        </div>
    </div>
</div>

{{-- ── Observations, and whether this platform may interpret them ─────────
     $interpretation is EMPTY whenever TriageBandContext refused — a patient
     under 16, an unknown date of birth, or a recorded pregnancy. In that case
     the refusal below is printed and no flag of any kind is rendered: not a
     greyed-out one, not a softened one. --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('triage.vitals') }}</h2>
        @if($bands->eligible)
            <span class="od-pill od-pill--neutral">{{ __('triage.advisory_source') }}</span>
        @endif
    </div>
    <div class="od-card__body">

        @if(! $bands->eligible)
        <div class="odc-note odc-note--pending" role="alert">
            <i data-lucide="shield-alert"></i>
            <div>
                <strong>{{ __('triage.no_bands_title') }}</strong>
                <p>{{ __($bands->reasonKey()) }}</p>
            </div>
        </div>
        @endif

        @if($vitals->isEmpty())
            <p class="od-muted">{{ __('triage.no_vitals') }}</p>
        @else
            @php $latest = $vitals->first(); @endphp
            <div class="od-table-wrap">
                <table class="od-table">
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
                            <td class="odc-strong">{{ $label }}</td>
                            <td>{{ $value ?? '—' }}</td>
                            <td>
                                @if($flag && $flag['label_key'])
                                    <span class="{{ $flagBadge($flag['severity']) }}">{{ __($flag['label_key']) }}</span>
                                @elseif($value === null)
                                    <span class="od-muted">{{ __('triage.not_measured') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="odc-strong">{{ __('triage.gcs_score') }}</td>
                        <td>{{ $latest->gcs_score ?? '—' }}</td>
                        <td>
                            @if($latest->consciousness_level)
                                <span class="od-muted">{{ __('triage.consciousness.' . $latest->consciousness_level) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="odc-strong">{{ __('triage.weight_kg') }} / {{ __('triage.height_cm') }}</td>
                        <td>{{ $latest->weight_kg ?? '—' }} / {{ $latest->height_cm ?? '—' }}</td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="od-muted odc-mt">
                {{ __('triage.recorded_at') }}: {{ $latest->recorded_at?->translatedFormat('d M Y H:i') ?? '—' }}
                @if($vitals->count() > 1)
                    &middot; {{ __('triage.vitals_retaken') }} ({{ $vitals->count() }})
                @endif
            </div>

            @if($bands->eligible)
                <p class="odc-hint odc-mt">{{ __('triage.advisory_note') }}</p>
                @if(empty($interpretation))
                    <p class="od-muted">{{ __('triage.advisory_none') }}</p>
                @endif
            @endif
        @endif

    </div>
</div>

{{-- The patient's own readings: context only. --}}
@include('portals.triage._self_reported', ['selfReported' => $selfReported])

{{-- ── Reassessment history ───────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('triage.history_title') }}</h2>
    </div>
    <div class="od-card__body">
        @if($reassessments->isEmpty())
            <p class="od-muted">{{ __('triage.no_reassessments') }}</p>
        @else
            <div class="od-table-wrap">
                <table class="od-table">
                    <tbody>
                    @foreach($reassessments as $entry)
                        @php
                            $fromKey = \App\Support\TriageAcuity::labelKey($entry->previous_priority);
                            $toKey   = \App\Support\TriageAcuity::labelKey($entry->new_priority);
                            $up      = \App\Support\TriageAcuity::isEscalation($entry->previous_priority, $entry->new_priority);
                        @endphp
                        <tr>
                            <td>
                                <span class="odc-strong">{{ __('triage.from_to', [
                                    'from' => $fromKey ? __($fromKey) : ($entry->previous_priority ?: '—'),
                                    'to'   => $toKey ? __($toKey) : $entry->new_priority,
                                ]) }}</span>
                                <div>
                                    @if($up === true)
                                        <span class="od-pill od-pill--urgent">{{ __('triage.escalated') }}</span>
                                    @elseif($up === false)
                                        <span class="od-pill od-pill--neutral">{{ __('triage.de_escalated') }}</span>
                                    @endif
                                    @if($entry->new_vital_signs)
                                        <span class="od-pill od-pill--normal">{{ __('triage.vitals_retaken') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $entry->reason_for_change }}</td>
                            <td class="od-muted">
                                {{ __('triage.reassessed_at') }}: {{ $entry->reassessed_at?->translatedFormat('d M Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ── Pain sites ─────────────────────────────────────────────────────────
     Where it hurts, at THIS facility. Added here by the nurse or the
     clinician working from this triage record (the chart itself is read-only);
     never deleted, only resolved. --}}
<div class="od-card" id="pain-sites">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('pain.list_title') }}</h2>
    </div>
    <div class="od-card__body">
        @if($painSites->isEmpty())
            <p class="od-muted">{{ __('pain.none_active') }}</p>
        @else
            <ul class="od-list">
                @foreach($painSites as $site)
                    <li class="od-list__row">
                        <span class="od-list__text">
                            <span class="od-list__title">{{ $site->region->label() }} · {{ $site->severity }}/10</span>
                            <span class="od-list__sub">{{ $site->source->label() }}@if($site->trend) · {{ $site->trend->label() }}@endif @if($site->note) · {{ $site->note }}@endif</span>
                        </span>
                        <form method="POST" action="{{ route('portals.triage.pain_sites.resolve', ['id' => $record->id, 'site' => $site->id]) }}">
                            @csrf
                            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="check"></i> {{ __('pain.resolve') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('portals.triage.pain_sites.store', $record->id) }}">
            @csrf
            @include('portals.triage._pain_site_fields', ['name' => 'pain_sites', 'rows' => 1])
            <button type="submit" class="od-btn od-btn--secondary"><i data-lucide="plus"></i> {{ __('pain.add') }}</button>
        </form>
    </div>
</div>

{{-- ── Reassess ───────────────────────────────────────────────────────────
     Re-triage, not edit. The form writes a new reassessment, a new score row
     and — if observations were retaken — a new observation row. Nothing above
     is modified. --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('triage.reassess_title') }}</h2>
    </div>
    <div class="od-card__body">
        <p class="odc-hint">{{ __('triage.reassess_hint') }}</p>

        <form method="POST" action="{{ route('portals.triage.reassess', $record->id) }}">
            @csrf

            <div class="odc-form-grid">
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="triage-new-priority">{{ __('triage.new_priority') }}</label>
                    <select id="triage-new-priority" name="new_priority" class="od-select" required>
                        <option value="">{{ __('triage.acuity_choose') }}</option>
                        @foreach($acuityLevels as $level)
                            <option value="{{ $level }}" @selected(old('new_priority') === $level)>
                                {{ __('triage.acuity_levels.' . $level) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="odc-field">
                    <label class="odc-label" for="triage-reassess-pain">{{ __('triage.pain_score') }}</label>
                    <input type="number" id="triage-reassess-pain" name="pain_score" class="od-input" min="0" max="10" step="1" value="{{ old('pain_score') }}">
                </div>
            </div>

            <div class="odc-field">
                <label class="odc-label odc-label--req" for="triage-reason">{{ __('triage.reason_for_change') }}</label>
                <textarea id="triage-reason" name="reason_for_change" class="od-input" rows="3" required maxlength="2000">{{ old('reason_for_change') }}</textarea>
                <div class="odc-hint">{{ __('triage.reason_hint') }}</div>
            </div>

            <div class="odc-field">
                <div class="odc-label">{{ __('triage.retake_vitals') }}</div>
                @include('portals.triage._vitals_fields', ['consciousness' => $consciousness])
            </div>

            <button type="submit" class="od-btn od-btn--primary">
                <i data-lucide="check"></i> <span>{{ __('triage.reassess_submit') }}</span>
            </button>
        </form>
    </div>
</div>

{{-- ── Priority history ───────────────────────────────────────────────── --}}
<div class="od-card">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('triage.score_history') }}</h2>
    </div>
    <div class="od-card__body">
        <div class="od-table-wrap">
            <table class="od-table">
                <tbody>
                @foreach($scores as $score)
                    @php $sKey = \App\Support\TriageAcuity::labelKey($score->priority_level); @endphp
                    <tr>
                        <td><span class="{{ $badge($score->priority_level) }}">{{ $sKey ? __($sKey) : $score->priority_level }}</span></td>
                        <td class="od-muted">{{ __('triage.scoring_manual') }}</td>
                        <td class="od-muted">{{ $score->scored_at?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>
@endsection
