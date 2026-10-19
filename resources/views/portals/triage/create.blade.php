@extends('layouts.portal')

@section('title', __('triage.create_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('triage.create_title') }}</h1>
    </div>
    <a href="{{ route('portals.triage.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('triage.back_to_queue') }}
    </a>
</div>

@if(session('error'))<div class="odc-note odc-note--urgent"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($errors->any())
<div class="odc-note odc-note--urgent" role="alert">
    <i data-lucide="alert-circle"></i>
    <div>
        <ul class="odc-note__list">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@if($patient === null)

    {{-- ── Step 1: find the patient ────────────────────────────────────────
         Exact Health ID only. There is no name search and no partial match on
         this desk, for the same reason there is none on the prescriber desk:
         a professional must not be able to enumerate this platform's patients. --}}
    <div class="od-card odc-form">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('triage.find_patient') }}</h2>
        </div>
        <div class="od-card__body">

            @if($notFound)
            <div class="odc-note odc-note--urgent" role="alert">
                <i data-lucide="user-x"></i>
                <div>{{ __('triage.not_found') }}</div>
            </div>
            @endif

            <form method="GET" action="{{ route('portals.triage.create') }}">
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="triage-health-id">{{ __('triage.health_id') }}</label>
                    <input type="text" id="triage-health-id" name="health_id" class="od-input"
                           value="{{ request('health_id') }}" required autocomplete="off" autofocus>
                    <div class="odc-hint">{{ __('triage.health_id_hint') }}</div>
                </div>
                <div class="odc-actions">
                    <button type="submit" class="od-btn od-btn--primary">
                        <i data-lucide="search"></i> <span>{{ __('triage.lookup') }}</span>
                    </button>
                    <a href="{{ route('portals.triage.index') }}" class="od-btn od-btn--secondary">{{ __('triage.cancel') }}</a>
                </div>
            </form>

        </div>
    </div>

@else

    @php
        $tName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''));
        $tDob  = $patient->date_of_birth;
    @endphp

    {{-- ── The patient ───────────────────────────────────────────────────── --}}
    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('triage.patient') }}</h2>
            <a href="{{ route('portals.triage.create') }}" class="od-btn od-btn--ghost od-btn--sm">
                <i data-lucide="repeat"></i> <span>{{ __('triage.change_patient') }}</span>
            </a>
        </div>
        <div class="od-card__body">
            <div class="odc-strong">{{ $tName !== '' ? $tName : '—' }}</div>
            <div class="od-muted">
                {{ __('triage.health_id') }}: {{ $patient->health_id }}
                @if($tDob)
                    &middot; {{ __('triage.age_years', ['years' => $bands->ageYears]) }}
                @else
                    &middot; {{ __('triage.age_unknown') }}
                @endif
                @if($patient->sex)
                    &middot; @enum($patient->sex)
                @endif
            </div>
        </div>
    </div>

    {{-- ── The paediatric / unknown-age refusal, shown BEFORE the form ─────
         A nurse who is about to be given no interpretation should know that
         before she starts typing, not after. Pregnancy is not tested here —
         it is answered on the form below and appears on the record page. --}}
    @if(! $bands->eligible)
    <div class="odc-note odc-note--pending" role="alert">
        <i data-lucide="shield-alert"></i>
        <div>
            <strong>{{ __('triage.no_bands_title') }}</strong>
            <p>{{ __($bands->reasonKey()) }}</p>
        </div>
    </div>
    @endif

    {{-- The patient's own readings: context only. Never copied into the form. --}}
    @include('portals.triage._self_reported', ['selfReported' => $selfReported])

    <form method="POST" action="{{ route('portals.triage.store') }}">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <div class="od-card">
            <div class="od-card__head">
                <h2 class="od-card__title">{{ __('triage.assessment') }}</h2>
            </div>
            <div class="od-card__body">
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="triage-complaint">{{ __('triage.presenting_complaint') }}</label>
                    <textarea id="triage-complaint" name="presenting_complaint" class="od-input" rows="3" required maxlength="2000">{{ old('presenting_complaint') }}</textarea>
                    <div class="odc-hint">{{ __('triage.complaint_hint') }}</div>
                </div>

                <div class="odc-form-grid">
                    <div class="odc-field">
                        <label class="odc-label" for="triage-pain">{{ __('triage.pain_score') }}</label>
                        <input type="number" id="triage-pain" name="pain_score" class="od-input" min="0" max="10" step="1" value="{{ old('pain_score') }}">
                        <div class="odc-hint">{{ __('triage.pain_hint') }}</div>
                    </div>

                    <div class="odc-field">
                        <label class="odc-label" for="triage-pregnancy">{{ __('triage.pregnancy_status') }}</label>
                        <select id="triage-pregnancy" name="pregnancy_status" class="od-select">
                            <option value="">—</option>
                            @foreach($pregnancyStatus as $status)
                                <option value="{{ $status }}" @selected(old('pregnancy_status') === $status)>
                                    {{ __('triage.pregnancy.' . $status) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="odc-hint">{{ __('triage.pregnancy_hint') }}</div>
                    </div>
                </div>

                @include('portals.triage._pain_site_fields', ['name' => 'pain_sites', 'rows' => 3])
            </div>
        </div>

        <div class="od-card">
            <div class="od-card__head">
                <h2 class="od-card__title">{{ __('triage.vitals') }}</h2>
            </div>
            <div class="od-card__body">
                <p class="odc-hint">{{ __('triage.vitals_hint') }}</p>
                @include('portals.triage._vitals_fields', ['consciousness' => $consciousness])
            </div>
        </div>

        {{-- ── Acuity ─────────────────────────────────────────────────────
             No default and no pre-selected level. Nothing in this feature
             computes a priority; the nurse chooses one or the form does not
             submit. --}}
        <div class="od-card">
            <div class="od-card__head">
                <h2 class="od-card__title">{{ __('triage.acuity_title') }}</h2>
            </div>
            <div class="od-card__body">
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="triage-acuity">{{ __('triage.acuity') }}</label>
                    <select id="triage-acuity" name="acuity" class="od-select" required>
                        <option value="">{{ __('triage.acuity_choose') }}</option>
                        @foreach($acuityLevels as $level)
                            <option value="{{ $level }}" @selected(old('acuity') === $level)>
                                {{ __('triage.acuity_levels.' . $level) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="odc-hint">{{ __('triage.acuity_hint') }}</div>
                </div>
            </div>
        </div>

        <div class="odc-actions">
            <button type="submit" class="od-btn od-btn--primary">
                <i data-lucide="check"></i> <span>{{ __('triage.submit') }}</span>
            </button>
            <a href="{{ route('portals.triage.index') }}" class="od-btn od-btn--secondary">{{ __('triage.cancel') }}</a>
        </div>
    </form>

@endif

</div>
@endsection
