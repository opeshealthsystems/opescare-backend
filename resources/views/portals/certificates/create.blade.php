@extends('layouts.portal')
@section('title', __('doc_ex_cert.title.' . $type))
@section('breadcrumb_home', __('doc_ex_cert.desk_title'))
@section('breadcrumb_home_url', route('portals.certificates.index'))
@section('breadcrumb_section', __('doc_ex_cert.title.' . $type))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('doc_ex_cert.title.' . $type) }}</h1>
        <p class="od-page__sub">{{ __('doc_ex_cert.create_subtitle') }}</p>
    </div>
    <a href="{{ route('portals.certificates.index') }}" class="od-btn od-btn--secondary"><i data-lucide="arrow-left"></i> <span>{{ __('doc_ex_cert.back') }}</span></a>
</div>

@if(session('error'))
<div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
<div class="odc-note odc-note--err" role="alert"><i data-lucide="triangle-alert"></i>
    <div><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
</div>
@endif

@if($patient === null)
    <section class="od-card">
        <header class="od-card__head"><h2 class="od-card__title">{{ __('doc_ex_cert.find_patient') }}</h2></header>
        <div class="od-card__body">
            @if($notFound)
            <div class="odc-note odc-note--err odc-note--inset" role="alert"><i data-lucide="user-x"></i><div>{{ __('doc_ex_cert.not_found') }}</div></div>
            @endif
            <form method="GET" action="{{ route('portals.certificates.create') }}" class="odc-form">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="odc-grid odc-grid--1">
                    <div class="odc-field">
                        <label class="odc-label odc-label--req" for="cert-health-id">{{ __('doc_ex_cert.health_id') }}</label>
                        <input type="text" id="cert-health-id" name="health_id" class="od-input" value="{{ request('health_id') }}" required autocomplete="off" autofocus>
                    </div>
                </div>
                <div class="odc-actions">
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="search"></i> <span>{{ __('doc_ex_cert.lookup') }}</span></button>
                </div>
            </form>
        </div>
    </section>
@else
    <section class="od-card">
        <div class="od-card__head">
            <div>
                <div class="od-detail__name">{{ trim($patient->first_name . ' ' . $patient->last_name) ?: '—' }}</div>
                <div class="od-detail__facts"><span class="od-detail__fact"><i data-lucide="id-card"></i> {{ $patient->health_id }}</span></div>
            </div>
            <a href="{{ route('portals.certificates.create', ['type' => $type]) }}" class="od-btn od-btn--secondary od-btn--sm"><i data-lucide="repeat"></i> <span>{{ __('doc_ex_cert.change_patient') }}</span></a>
        </div>
    </section>

    <form method="POST" action="{{ route('portals.certificates.store') }}" class="odc-form">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <input type="hidden" name="type" value="{{ $type }}">

        <section class="od-card">
            <div class="od-card__body">
                <div class="odc-grid">
                    <div class="odc-field">
                        <label class="odc-label odc-label--req" for="cert-examined">{{ __('doc_ex_cert.field.examined_on') }}</label>
                        <input type="date" id="cert-examined" name="examined_on" class="od-input" value="{{ old('examined_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                    </div>
                    @if($type === 'sick_note')
                    <div class="odc-field">
                        <label class="odc-label odc-label--req" for="cert-from">{{ __('doc_ex_cert.field.rest_from') }}</label>
                        <input type="date" id="cert-from" name="rest_from" class="od-input" value="{{ old('rest_from', now()->toDateString()) }}" required>
                    </div>
                    <div class="odc-field">
                        <label class="odc-label odc-label--req" for="cert-to">{{ __('doc_ex_cert.field.rest_to') }}</label>
                        <input type="date" id="cert-to" name="rest_to" class="od-input" value="{{ old('rest_to') }}" required>
                    </div>
                    @else
                    <div class="odc-field">
                        <label class="odc-label odc-label--req" for="cert-purpose">{{ __('doc_ex_cert.field.purpose') }}</label>
                        <select id="cert-purpose" name="purpose" class="od-input" required>
                            @foreach($purposes as $purpose)
                                <option value="{{ $purpose }}" @selected(old('purpose') === $purpose)>{{ __('doc_ex_cert.purpose.' . $purpose) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="odc-field">
                        <label class="odc-label" for="cert-validity">{{ __('doc_ex_cert.validity_days') }}</label>
                        <input type="number" id="cert-validity" name="validity_days" class="od-input" min="1" max="365" value="{{ old('validity_days', 90) }}">
                    </div>
                    @endif
                    <div class="odc-field">
                        <label class="odc-label" for="cert-recipient">{{ __('doc_ex_cert.field.recipient') }}</label>
                        <input type="text" id="cert-recipient" name="recipient" class="od-input" maxlength="200" value="{{ old('recipient') }}">
                    </div>
                </div>
                <div class="odc-field">
                    <label class="odc-label odc-label--req" for="cert-statement">{{ __('doc_ex_cert.field.statement') }}</label>
                    <textarea id="cert-statement" name="statement" class="od-input" rows="5" maxlength="2000" required>{{ old('statement') }}</textarea>
                    <p class="odc-hint">{{ $type === 'sick_note' ? __('doc_ex_cert.statement_hint_sick') : __('doc_ex_cert.statement_hint_cert') }}</p>
                </div>
                <div class="odc-actions">
                    <a href="{{ route('portals.certificates.index') }}" class="od-btn od-btn--secondary">{{ __('doc_ex_cert.cancel') }}</a>
                    <button type="submit" class="od-btn od-btn--primary"><i data-lucide="file-check"></i> <span>{{ __('doc_ex_cert.issue') }}</span></button>
                </div>
            </div>
        </section>
    </form>
@endif

</div>
@endsection
