@extends('layouts.portal')

@section('title', __('imaging_desk.create_title'))

@section('head')
    @include('portals.lab.partials._head')
@endsection

@section('content')
<div class="od-page odl">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('imaging_desk.create_title') }}</h1>
    </div>
    <a href="{{ route('portals.imaging.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('imaging_desk.back_to_worklist') }}
    </a>
</div>

@if(session('error'))<div class="od-flash od-flash--err"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
@if($errors->any())
<div class="od-flash od-flash--err" role="alert">
    <i data-lucide="alert-circle"></i>
    <div><ul class="alert-list">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

@if($patient === null)

    {{-- ── Step 1: find the patient. Exact Health ID only — no name search,
         no partial match: a professional must not be able to enumerate
         this platform's patients. Same rule as the triage desk. --}}
    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('imaging_desk.find_patient') }}</h2>
        </div>
        <div class="od-card__body">
            @if($notFound)
                <div class="od-flash od-flash--warn" role="alert">
                    <i data-lucide="user-x"></i><div>{{ __('imaging_desk.patient_not_found') }}</div>
                </div>
            @endif
            <form method="GET" action="{{ route('portals.imaging.create') }}">
                <div class="odl-field">
                    <label class="odl-label odl-label--req" for="img-health-id">{{ __('imaging_desk.health_id') }}</label>
                    <input type="text" id="img-health-id" name="health_id" class="od-input"
                           value="{{ request('health_id') }}" required autocomplete="off" autofocus maxlength="64">
                    <div class="odl-hint">{{ __('imaging_desk.health_id_hint') }}</div>
                </div>
                <div class="od-row">
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> <span>{{ __('imaging_desk.lookup') }}</span></button>
                    <a href="{{ route('portals.imaging.index') }}" class="od-btn od-btn--secondary">{{ __('imaging_desk.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

@else

    @php $pName = trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')); @endphp

    {{-- ── The patient: age band, never the date of birth. ─────────────── --}}
    <div class="od-card">
        <div class="od-card__head">
            <h2 class="od-card__title">{{ __('imaging_desk.patient') }}</h2>
            <a href="{{ route('portals.imaging.create') }}" class="od-btn od-btn--ghost od-btn--sm">
                <i data-lucide="repeat"></i> <span>{{ __('imaging_desk.change_patient') }}</span>
            </a>
        </div>
        <div class="od-card__body">
            <div class="od-strong">{{ $pName !== '' ? $pName : '—' }}</div>
            <div class="od-muted">
                {{ __('imaging_desk.health_id') }}: {{ $patient->health_id }}
                &middot; {{ $ageBand ? __('imaging_desk.band.' . $ageBand) : __('imaging_desk.age_band_unknown') }}
                @if($patient->sex) &middot; @enum($patient->sex) @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('portals.imaging.store') }}">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">

        <div class="od-card">
            <div class="od-card__head">
                <h2 class="od-card__title">{{ __('imaging_desk.request_details') }}</h2>
            </div>
            <div class="od-card__body">
                <div class="odl-fgrid">
                    <div class="odl-field">
                        <label class="odl-label odl-label--req" for="img-modality">{{ __('imaging_desk.modality_label') }}</label>
                        <select id="img-modality" name="modality" class="od-input" required>
                            @foreach($modalities as $m)
                                <option value="{{ $m }}" @selected(old('modality') === $m)>{{ __('imaging_desk.modality.' . $m) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="odl-field">
                        <label class="odl-label" for="img-body-part">{{ __('imaging_desk.body_part') }}</label>
                        <input type="text" id="img-body-part" name="body_part" class="od-input" maxlength="100" value="{{ old('body_part') }}">
                        <div class="odl-hint">{{ __('imaging_desk.body_part_hint') }}</div>
                    </div>
                    <div class="odl-field">
                        <label class="odl-label odl-label--req" for="img-urgency">{{ __('imaging_desk.urgency_label') }}</label>
                        <select id="img-urgency" name="urgency" class="od-input" required>
                            @foreach($urgencies as $u)
                                <option value="{{ $u }}" @selected(old('urgency', 'routine') === $u)>{{ __('imaging_desk.urgency.' . $u) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="odl-field">
                        <label class="odl-label" for="img-referrer">{{ __('imaging_desk.referring_physician') }}</label>
                        <input type="text" id="img-referrer" name="referring_physician" class="od-input" maxlength="255" value="{{ old('referring_physician') }}">
                        <div class="odl-hint">{{ __('imaging_desk.referring_hint') }}</div>
                    </div>
                    <div class="odl-field">
                        <label class="odl-label" for="img-slot">{{ __('imaging_desk.scheduled_slot') }}</label>
                        <input type="datetime-local" id="img-slot" name="scheduled_at" class="od-input" value="{{ old('scheduled_at') }}">
                        <div class="odl-hint">{{ __('imaging_desk.scheduled_hint') }}</div>
                    </div>
                </div>
                <div class="odl-field">
                    <label class="odl-label odl-label--req" for="img-indication">{{ __('imaging_desk.clinical_indication') }}</label>
                    <textarea id="img-indication" name="clinical_indication" class="od-input" rows="3" required maxlength="2000">{{ old('clinical_indication') }}</textarea>
                    <div class="odl-hint">{{ __('imaging_desk.indication_hint') }}</div>
                </div>
                <div class="odl-field">
                    <label class="odl-label" for="img-notes">{{ __('imaging_desk.notes') }}</label>
                    <textarea id="img-notes" name="notes" class="od-input" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="od-row">
            <button type="submit" class="od-btn od-btn--primary"><i data-lucide="check"></i> <span>{{ __('imaging_desk.submit') }}</span></button>
            <a href="{{ route('portals.imaging.index') }}" class="od-btn od-btn--secondary">{{ __('imaging_desk.cancel') }}</a>
        </div>
    </form>

@endif

</div>
@endsection
