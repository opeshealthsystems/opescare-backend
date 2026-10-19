@extends('layouts.portal')

@section('title', __('chart.title'))

@php
    $sexKey = $identity['sex'] ? 'chart.sexes.' . $identity['sex'] : null;
    $sexLabel = $sexKey && \Illuminate\Support\Facades\Lang::has($sexKey) ? __($sexKey) : ($identity['sex'] ?: __('chart.not_recorded'));
    $odName = trim((string) ($identity['name'] ?? ''));
    $odParts = preg_split('/\s+/', $odName, -1, PREG_SPLIT_NO_EMPTY);
    $odInitials = $odParts ? mb_strtoupper(mb_substr($odParts[0], 0, 1) . (count($odParts) > 1 ? mb_substr(end($odParts), 0, 1) : '')) : '—';
    $odTone = ['blue', 'amber', 'teal', 'rose', 'sky', 'green'][crc32($odName !== '' ? $odName : (string) $patient->id) % 6];
@endphp

@section('head')
<link rel="stylesheet" href="{{ asset('css/od-clinic.css') }}?v={{ @filemtime(public_path('css/od-clinic.css')) ?: '1' }}">
@endsection


@section('content')
<div class="od-page odc-page">

<div class="od-page__head">
    <div>
        <h1 class="od-page__title">{{ __('chart.title') }}</h1>
        <p class="od-page__sub">{{ __('chart.tabs.' . $tab) }}</p>
    </div>
    <a href="{{ route('portals.chart.index') }}" class="od-btn od-btn--secondary">
        <i data-lucide="arrow-left"></i> {{ __('chart.back_to_lookup') }}
    </a>
</div>

<div class="od-grid od-grid--main">
    {{-- ── Patient detail panel ────────────────────────────────────────────
         Age in years and the published age band — never the date of birth. The
         Health ID confirms identity; the date is encrypted at rest and this
         screen has no need of it. --}}
    <section class="od-card" aria-labelledby="odc-chart-name">
        <header class="od-card__head">
            <h2 class="od-card__title">{{ __('chart.patient') }}</h2>
            <span class="od-chip"><i data-lucide="lock"></i> {{ __('chart.read_only') }}</span>
        </header>
        <div class="od-card__body od-detail">
            <div class="od-detail__head">
                <span class="od-avatar od-avatar--lg od-tint--{{ $odTone }}" aria-hidden="true">{{ $odInitials }}</span>
                <div class="od-detail__main">
                    <h3 class="od-detail__name" id="odc-chart-name">{{ $odName !== '' ? $odName : '—' }}</h3>
                    <div class="od-detail__ref">
                        <span>{{ __('chart.health_id') }}: {{ $identity['health_id'] ?? '—' }}</span>
                        @if($basis)<span>{{ __('chart.opened_on_basis') }} {{ __('chart.basis.' . $basis) }}</span>@endif
                    </div>
                    <div class="od-detail__facts">
                        <span class="od-detail__fact"><i data-lucide="calendar"></i>
                            @if($identity['age_years'] !== null){{ __('chart.age_years', ['years' => $identity['age_years']]) }}@else{{ __('chart.age_unknown') }}@endif
                        </span>
                        <span class="od-detail__fact"><i data-lucide="users"></i> {{ __('chart.age_band') }}: {{ $identity['age_band'] ? __('chart.age_bands.' . $identity['age_band']) : '—' }}</span>
                        <span class="od-detail__fact"><i data-lucide="user"></i> {{ __('chart.sex') }}: {{ $sexLabel }}</span>
                        <span class="od-detail__fact"><i data-lucide="droplet"></i> {{ __('chart.blood_group') }}: {{ $identity['blood_group'] ?: __('chart.not_recorded') }}</span>
                    </div>
                </div>
            </div>

            @if($scope === \App\Support\ClinicalRelationshipGate::SCOPE_PREVIEW)
            <div class="odc-note odc-note--pending" role="alert">
                <i data-lucide="eye-off"></i>
                <div>
                    <strong>{{ __('chart.scope_preview_title') }}</strong>
                    <p>{{ __('chart.scope_preview_note') }}</p>
                </div>
            </div>
            @endif

            {{-- ── Tabs ─────────────────────────────────────────────────
                 One request per tab, one access-log row per tab. --}}
            <nav class="od-tabs odc-chart-tabs" aria-label="{{ __('chart.title') }}">
                @foreach($tabs as $name)
                    <a href="{{ route('portals.chart.show', ['id' => $patient->id, 'tab' => $name]) }}"
                       class="od-tab {{ $tab === $name ? 'is-active' : '' }}" @if($tab === $name) aria-current="page" @endif>{{ __('chart.tabs.' . $name) }}</a>
                @endforeach
            </nav>

            <div class="odc-tab-body">
            @if($consentRequired)
            <div class="odc-note odc-note--pending" role="alert">
                <i data-lucide="lock"></i>
                <div>
                    <strong>{{ __('chart.consent_required_title') }}</strong>
                    <p>{{ __('chart.consent_required_note') }}</p>
                </div>
            </div>
            @else
                @include('portals.chart.tabs.' . $tab)
            @endif
            </div>
        </div>
    </section>

    <aside class="od-stack">
        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('chart.privacy_title') }}</h2></header>
            <div class="od-card__body"><ul class="od-list">
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--blue"><i data-lucide="shield"></i></span>
                    <span class="od-list__text"><span class="od-list__title">{{ $identity['privacy']['requires_consent_for_full_record'] ? __('chart.privacy_consent_on') : __('chart.privacy_consent_off') }}</span></span>
                </li>
                <li class="od-list__row">
                    <span class="od-list__icon od-tint--rose"><i data-lucide="siren"></i></span>
                    <span class="od-list__text"><span class="od-list__title">{{ $identity['privacy']['emergency_access_allowed'] ? __('chart.privacy_emergency_on') : __('chart.privacy_emergency_off') }}</span></span>
                </li>
            </ul></div>
        </section>

        {{-- ── Desks: only those whose capability the reader holds; each desk
             gates itself again on its own routes. --}}
        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">{{ __('chart.links_title') }}</h2></header>
            <div class="od-card__body"><ul class="od-list">
                @capability('triage')
                <li class="od-list__row"><span class="od-list__icon od-tint--rose"><i data-lucide="siren"></i></span><span class="od-list__text"><a href="{{ route('portals.triage.index') }}" class="od-list__title">{{ __('chart.link_triage') }}</a></span></li>
                @endcapability
                @capability('queue')
                <li class="od-list__row"><span class="od-list__icon od-tint--blue"><i data-lucide="list-ordered"></i></span><span class="od-list__text"><a href="{{ route('portals.queue.index') }}" class="od-list__title">{{ __('chart.link_queue') }}</a></span></li>
                @endcapability
                @capability('clinical_chart')
                <li class="od-list__row"><span class="od-list__icon od-tint--teal"><i data-lucide="paperclip"></i></span><span class="od-list__text"><a href="{{ route('portals.files.index', ['patientId' => $patient->id]) }}" class="od-list__title">{{ __('files.chart_link') }}</a></span></li>
                @endcapability
                @capability('blood_inventory')
                <li class="od-list__row"><span class="od-list__icon od-tint--rose"><i data-lucide="droplet"></i></span><span class="od-list__text"><a href="{{ route('portals.staff.blood_requests.index') }}" class="od-list__title">{{ __('chart.link_blood') }}</a></span></li>
                @endcapability
            </ul></div>
        </section>

        {{-- Partner-pushed data (fhir_partner_*) is deliberately absent; see
             App\Services\Chart\PatientChartService. --}}
        <p class="odc-hint"><i data-lucide="info"></i> {{ __('chart.partner_notice') }}</p>
    </aside>
</div>

</div>
@endsection
