@extends('layouts.portal')

@section('title', __('chart.title'))
@section('breadcrumb_home', __('chart.title'))
@section('breadcrumb_home_url', route('portals.chart.index'))
@section('breadcrumb_section', __('chart.tabs.' . $tab))

@php
    $sexKey = $identity['sex'] ? 'chart.sexes.' . $identity['sex'] : null;
    $sexLabel = $sexKey && \Illuminate\Support\Facades\Lang::has($sexKey) ? __($sexKey) : ($identity['sex'] ?: __('chart.not_recorded'));
@endphp

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $identity['name'] !== '' ? $identity['name'] : '—' }}</h1>
        <p class="page-subtitle">
            {{ __('chart.health_id') }}: <span class="td-strong">{{ $identity['health_id'] ?? '—' }}</span>
            @if($basis)
                &middot; {{ __('chart.opened_on_basis') }} {{ __('chart.basis.' . $basis) }}
            @endif
        </p>
    </div>
    <a href="{{ route('portals.chart.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> {{ __('chart.back_to_lookup') }}
    </a>
</div>

{{-- ── Identity card ─────────────────────────────────────────────────────
     Age in years and the published age band — never the date of birth. The
     Health ID confirms identity; the date is encrypted at rest and this
     screen has no need of it. --}}
<div class="panel mb-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="user-round"></i> {{ __('chart.patient') }}</h2>
        <span class="badge badge-neutral"><i data-lucide="lock"></i> {{ __('chart.read_only') }}</span>
    </div>
    <div class="panel-body">
        <div class="field-grid">
            <div>
                <div class="td-muted">{{ __('chart.age') }}</div>
                <div class="td-strong">
                    @if($identity['age_years'] !== null)
                        {{ __('chart.age_years', ['years' => $identity['age_years']]) }}
                    @else
                        {{ __('chart.age_unknown') }}
                    @endif
                </div>
            </div>
            <div>
                <div class="td-muted">{{ __('chart.age_band') }}</div>
                <div class="td-strong">{{ $identity['age_band'] ? __('chart.age_bands.' . $identity['age_band']) : '—' }}</div>
            </div>
            <div>
                <div class="td-muted">{{ __('chart.sex') }}</div>
                <div class="td-strong">{{ $sexLabel }}</div>
            </div>
            <div>
                <div class="td-muted">{{ __('chart.blood_group') }}</div>
                <div class="td-strong">{{ $identity['blood_group'] ?: __('chart.not_recorded') }}</div>
            </div>
        </div>

        <div class="td-muted mt-3">
            <i data-lucide="shield"></i> {{ __('chart.privacy_title') }}:
            {{ $identity['privacy']['requires_consent_for_full_record'] ? __('chart.privacy_consent_on') : __('chart.privacy_consent_off') }}
            &middot;
            {{ $identity['privacy']['emergency_access_allowed'] ? __('chart.privacy_emergency_on') : __('chart.privacy_emergency_off') }}
        </div>
    </div>
</div>

@if($scope === \App\Support\ClinicalRelationshipGate::SCOPE_PREVIEW)
<div class="alert alert-warning mb-4" role="alert">
    <i data-lucide="eye-off"></i>
    <div>
        <strong>{{ __('chart.scope_preview_title') }}</strong>
        <p>{{ __('chart.scope_preview_note') }}</p>
    </div>
</div>
@endif

{{-- ── Tabs ─────────────────────────────────────────────────────────────
     One request per tab, one access-log row per tab. --}}
<nav class="tabs" aria-label="{{ __('chart.title') }}">
    @foreach($tabs as $name)
        <a href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => $name]) }}"
           class="tab {{ $tab === $name ? 'active' : '' }}">{{ __('chart.tabs.' . $name) }}</a>
    @endforeach
</nav>

@if($consentRequired)
<div class="alert alert-warning mb-4" role="alert">
    <i data-lucide="lock"></i>
    <div>
        <strong>{{ __('chart.consent_required_title') }}</strong>
        <p>{{ __('chart.consent_required_note') }}</p>
    </div>
</div>
@else
    @include('portals.chart.tabs.' . $tab)
@endif

{{-- Partner-pushed data (`fhir_partner_*`) is deliberately absent — see
     App\Services\Chart\PatientChartService. --}}
<p class="form-hint mt-4"><i data-lucide="info"></i> {{ __('chart.partner_notice') }}</p>

{{-- ── Desks ────────────────────────────────────────────────────────────
     Links only to desks whose capability the reader holds; each desk gates
     itself again on its own routes. --}}
<div class="panel mt-4">
    <div class="panel-header">
        <h2 class="panel-title"><i data-lucide="layout-grid"></i> {{ __('chart.links_title') }}</h2>
    </div>
    <div class="panel-body row-actions-inline">
        @capability('triage')
        <a href="{{ route('portals.triage.index') }}" class="btn btn-secondary"><i data-lucide="siren"></i> {{ __('chart.link_triage') }}</a>
        @endcapability
        @capability('queue')
        <a href="{{ route('portals.queue.index') }}" class="btn btn-secondary"><i data-lucide="list-ordered"></i> {{ __('chart.link_queue') }}</a>
        @endcapability
        @capability('clinical_chart')
        <a href="{{ route('portals.files.index', ['patientId' => $patient->id]) }}" class="btn btn-secondary"><i data-lucide="paperclip"></i> {{ __('files.chart_link') }}</a>
        @endcapability
        @capability('blood_inventory')
        <a href="{{ route('portals.staff.blood_requests.index') }}" class="btn btn-secondary"><i data-lucide="droplet"></i> {{ __('chart.link_blood') }}</a>
        @endcapability
    </div>
</div>

@endsection
