@extends('layouts.portal')

@section('title', __('triage.create_title'))
@section('breadcrumb_home', __('triage.title'))
@section('breadcrumb_home_url', route('portals.triage.index'))
@section('breadcrumb_section', __('triage.create_title'))

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ __('triage.create_title') }}</h1>
    </div>
    <a href="{{ route('portals.triage.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('triage.back_to_queue') }}
    </a>
</div>

@if(session('error'))<div class="alert alert-danger mb-6"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif

@if($errors->any())
<div class="alert alert-danger mb-6" role="alert">
    <i data-lucide="alert-circle"></i>
    <div>
        <ul class="alert-list">
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
    <div class="panel form-panel">
        <div class="panel-header">
            <h2 class="panel-title"><i data-lucide="search"></i> {{ __('triage.find_patient') }}</h2>
        </div>
        <div class="panel-body">

            @if($notFound)
            <div class="alert alert-danger mb-4" role="alert">
                <i data-lucide="user-x"></i>
                <div>{{ __('triage.not_found') }}</div>
            </div>
            @endif

            <form method="GET" action="{{ route('portals.triage.create') }}">
                <div class="form-group mb-4">
                    <label class="form-label form-label-required" for="triage-health-id">{{ __('triage.health_id') }}</label>
                    <input type="text" id="triage-health-id" name="health_id" class="form-control"
                           value="{{ request('health_id') }}" required autocomplete="off" autofocus>
                    <div class="form-hint">{{ __('triage.health_id_hint') }}</div>
                </div>
                <div class="row-actions-inline">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="search"></i> <span>{{ __('triage.lookup') }}</span>
                    </button>
                    <a href="{{ route('portals.triage.index') }}" class="btn btn-secondary">{{ __('triage.cancel') }}</a>
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
    <div class="panel mb-4">
        <div class="panel-header">
            <h2 class="panel-title"><i data-lucide="user-round"></i> {{ __('triage.patient') }}</h2>
            <a href="{{ route('portals.triage.create') }}" class="btn btn-ghost btn-sm">
                <i data-lucide="repeat"></i> <span>{{ __('triage.change_patient') }}</span>
            </a>
        </div>
        <div class="panel-body">
            <div class="td-strong">{{ $tName !== '' ? $tName : '—' }}</div>
            <div class="td-muted">
                {{ __('triage.health_id') }}: {{ $patient->health_id }}
                @if($tDob)
                    &middot; {{ $tDob->format('Y-m-d') }} &middot; {{ __('triage.age_years', ['years' => $bands->ageYears]) }}
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
    <div class="alert alert-warning mb-4" role="alert">
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

        <div class="panel mb-4">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="clipboard-list"></i> {{ __('triage.assessment') }}</h2>
            </div>
            <div class="panel-body">
                <div class="form-group mb-4">
                    <label class="form-label form-label-required" for="triage-complaint">{{ __('triage.presenting_complaint') }}</label>
                    <textarea id="triage-complaint" name="presenting_complaint" class="form-control" rows="3" required maxlength="2000">{{ old('presenting_complaint') }}</textarea>
                    <div class="form-hint">{{ __('triage.complaint_hint') }}</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="triage-pain">{{ __('triage.pain_score') }}</label>
                        <input type="number" id="triage-pain" name="pain_score" class="form-control" min="0" max="10" step="1" value="{{ old('pain_score') }}">
                        <div class="form-hint">{{ __('triage.pain_hint') }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="triage-pregnancy">{{ __('triage.pregnancy_status') }}</label>
                        <select id="triage-pregnancy" name="pregnancy_status" class="form-control">
                            <option value="">—</option>
                            @foreach($pregnancyStatus as $status)
                                <option value="{{ $status }}" @selected(old('pregnancy_status') === $status)>
                                    {{ __('triage.pregnancy.' . $status) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">{{ __('triage.pregnancy_hint') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel mb-4">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="activity"></i> {{ __('triage.vitals') }}</h2>
            </div>
            <div class="panel-body">
                <p class="form-hint mb-4">{{ __('triage.vitals_hint') }}</p>
                @include('portals.triage._vitals_fields', ['consciousness' => $consciousness])
            </div>
        </div>

        {{-- ── Acuity ─────────────────────────────────────────────────────
             No default and no pre-selected level. Nothing in this feature
             computes a priority; the nurse chooses one or the form does not
             submit. --}}
        <div class="panel mb-4">
            <div class="panel-header">
                <h2 class="panel-title"><i data-lucide="siren"></i> {{ __('triage.acuity_title') }}</h2>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="form-label form-label-required" for="triage-acuity">{{ __('triage.acuity') }}</label>
                    <select id="triage-acuity" name="acuity" class="form-control" required>
                        <option value="">{{ __('triage.acuity_choose') }}</option>
                        @foreach($acuityLevels as $level)
                            <option value="{{ $level }}" @selected(old('acuity') === $level)>
                                {{ __('triage.acuity_levels.' . $level) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint">{{ __('triage.acuity_hint') }}</div>
                </div>
            </div>
        </div>

        <div class="row-actions-inline">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="check"></i> <span>{{ __('triage.submit') }}</span>
            </button>
            <a href="{{ route('portals.triage.index') }}" class="btn btn-secondary">{{ __('triage.cancel') }}</a>
        </div>
    </form>

@endif

@endsection
