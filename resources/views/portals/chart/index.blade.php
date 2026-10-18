@extends('layouts.portal')

@section('title', __('chart.lookup_title'))
@section('breadcrumb_home', __('chart.title'))
@section('breadcrumb_home_url', route('portals.chart.index'))
@section('breadcrumb_section', __('chart.lookup_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection

@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('chart.lookup_title') }}</h1>
        <p class="od-page__sub">{{ __('chart.lookup_subtitle') }}</p>
    </div>
</div>

{{-- Exact Health ID only. There is no name search, no partial match and no
     list on this desk: a professional must not be able to enumerate this
     platform's patients. A Health ID nobody holds and one this clinician has
     no basis to open produce the same message, so the form never confirms
     that a record exists. --}}
<div class="od-card odc-form">
    <div class="od-card__head">
        <h2 class="od-card__title">{{ __('chart.health_id') }}</h2>
    </div>
    <div class="od-card__body">

        @if(session('success'))
        <div class="odc-note" role="status">
            <i data-lucide="send"></i>
            <div>{{ session('success') }}</div>
        </div>
        @endif

        @if($notFound)
        <div class="odc-note odc-note--urgent" role="alert">
            <i data-lucide="user-x"></i>
            <div>{{ __('chart.not_found') }}</div>
        </div>
        @endif

        <form method="GET" action="{{ route('portals.chart.index') }}">
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="chart-health-id">{{ __('chart.health_id') }}</label>
                <input type="text" id="chart-health-id" name="health_id" class="od-input"
                       value="{{ $healthId }}" required autocomplete="off" autofocus maxlength="64">
                <div class="odc-hint">{{ __('chart.health_id_hint') }}</div>
            </div>
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--primary">
                    <i data-lucide="folder-open"></i> {{ __('chart.lookup') }}
                </button>
            </div>
        </form>

        {{-- No basis? Ask the patient. The form is offered for every lookup
             that did not open a chart, and the answer is the same whether or
             not the Health ID exists, so it confirms nothing. --}}
        @if($notFound)
        @capability('request_access')
        <form method="POST" action="{{ route('portals.chart.consent_request') }}" class="odc-mt">
            @csrf
            <input type="hidden" name="health_id" value="{{ $healthId }}">
            <h3 class="od-card__title">{{ __('chart.consent_request_title') }}</h3>
            <div class="odc-hint">{{ __('chart.consent_request_hint') }}</div>
            <div class="odc-field">
                <label class="odc-label odc-label--req" for="chart-consent-purpose">{{ __('chart.consent_purpose') }}</label>
                <select id="chart-consent-purpose" name="purpose" class="od-input" required>
                    @foreach($purposes as $purpose)
                    <option value="{{ $purpose }}">{{ __('consent_requests.purpose.' . $purpose) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="odc-field">
                <label class="odc-label" for="chart-consent-justification">{{ __('chart.consent_justification') }}</label>
                <textarea id="chart-consent-justification" name="justification" class="od-input" maxlength="1000" rows="2"></textarea>
            </div>
            <div class="odc-actions">
                <button type="submit" class="od-btn od-btn--primary">
                    <i data-lucide="key-round"></i> {{ __('chart.consent_request_submit') }}
                </button>
            </div>
        </form>
        @endcapability
        @endif

        <p class="odc-hint odc-mt">
            <a href="{{ route('portals.staff') }}">{{ __('chart.landing_hint') }}</a>
        </p>
    </div>
</div>

</div>
@endsection
