{{--
    /e-prescription — what an OpesCare e-prescription is and how it travels from
    the doctor to the patient's app to the pharmacy counter. Home-page design
    (layouts.hm), same pattern as /interoperability and /master-patient-index.

    Every claim maps to code; keep it that way:
      - prescribe (doctors + medical supervisors): capability:prescribe, routes/web.php /facility/prescriptions
      - care-relationship check before issue: PrescriberController::store → ClinicalRelationshipGate
      - interaction warning, never a block, audited: PrescriberController::interactionAlerts
      - single write path, 30-day default validity, dispensed once: App\Services\Clinical\PrescriptionService
      - patient code on request + withdraw all: MobilePrescriptionController::share / withdrawShare
      - rx_ token + /verify/rx (one message for every failure, masked Health ID):
        PrescriptionAccessService, PrescriptionVerifyController
      - counter lookup by Health ID or reference (follows merges): PharmacyPortalController::prescriptionLookup
      - claim for one counter, ready → notify, full/partial dispense → notify:
        App\Services\Pharmacy\PharmacyDeskService (routeToFacility, markReady, dispense)
      - controlled register + monthly export: /pharmacy/controlled, /pharmacy/controlled/register
      - medicine finder shows only allow-listed reported stock; reservations: routes/api.php pharmacy/*
--}}
@extends('layouts.hm')

@section('title', __('public_erx.meta_title'))
@section('description', __('public_erx.meta_desc'))

@push('schema')
    @include('public.explainer._schema', ['ns' => 'public_erx', 'route' => 'public.e-prescription'])
@endpush
@push('scripts')
    <script src="{{ asset('js/explainer-flow.js') }}?v={{ @filemtime(public_path('js/explainer-flow.js')) ?: '1' }}" @cspNonce defer></script>
@endpush

@php
    $exIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp

@section('content')
{{-- Hero ──────────────────────────────────────────────────────────────── --}}
<section class="hm-hero" aria-labelledby="ex-title">
    <div class="hm-wrap hm-hero__grid">
        <div class="hm-hero__text">
            <nav class="ex-crumbs" aria-label="{{ __('finder_seo.breadcrumbs.home') }}">
                <a href="{{ route('public.landing') }}">{{ __('finder_seo.breadcrumbs.home') }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ __('public_erx.breadcrumb') }}</span>
            </nav>
            <span class="hm-kicker">{!! $exIcon('pill', 15) !!}{{ __('public_erx.hero.kicker') }}</span>
            <h1 id="ex-title">{{ __('public_erx.hero.title_1') }}<br><span>{{ __('public_erx.hero.title_2') }}</span></h1>
            <p class="hm-lead">{{ __('public_erx.hero.lead') }}</p>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--white" href="{{ route('register.patient') }}">{!! $exIcon('id-card', 18) !!}{{ __('public_erx.hero.cta_primary') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('public.network.medicine-finder') }}">{{ __('public_erx.hero.cta_secondary') }}</a>
            </div>
        </div>
        <div class="ex-hvis">
            <div class="hx-net" role="img" aria-label="{{ __('public_erx.net.label') }}">
                <svg class="hx-lines" viewBox="0 0 440 390" aria-hidden="true"><path d="M95 62 L220 195"/><path d="M345 62 L220 195"/><path d="M95 328 L220 195"/><path d="M345 328 L220 195"/></svg>
                <div class="hx-core" aria-hidden="true">{!! $exIcon('pill', 22) !!}<b>{{ __('public_erx.net.core') }}</b><small>{{ __('public_erx.net.core_sub') }}</small></div>
                <div class="hx-node hx-n1" aria-hidden="true">{!! $exIcon('stethoscope', 20) !!}<div><b>{{ __('public_erx.net.doctor') }}</b><small>{{ __('public_erx.net.doctor_sub') }}</small></div></div>
                <div class="hx-node hx-n2" aria-hidden="true">{!! $exIcon('smartphone', 20) !!}<div><b>{{ __('public_erx.net.patient') }}</b><small>{{ __('public_erx.net.patient_sub') }}</small></div></div>
                <div class="hx-node hx-n3" aria-hidden="true">{!! $exIcon('scroll-text', 20) !!}<div><b>{{ __('public_erx.net.record') }}</b><small>{{ __('public_erx.net.record_sub') }}</small></div></div>
                <div class="hx-node hx-n4" aria-hidden="true">{!! $exIcon('building-2', 20) !!}<div><b>{{ __('public_erx.net.pharmacy') }}</b><small>{{ __('public_erx.net.pharmacy_sub') }}</small></div></div>
            </div>
        </div>
    </div>
</section>

{{-- What it is ────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-what-title">
    <div class="hm-wrap hm-split">
        <div>
            <p class="hm-eyebrow">{{ __('public_erx.what.eyebrow') }}</p>
            <h2 id="ex-what-title">{{ __('public_erx.what.title') }}</h2>
            <p class="hm-sub">{{ __('public_erx.what.sub') }}</p>
            <ul class="hm-list" role="list">
                @foreach ([['stethoscope', 'b1'], ['smartphone', 'b2'], ['file-check', 'b3']] as [$icon, $k])
                    <li><span class="hm-ic hm-ic--good">{!! $exIcon($icon, 18) !!}</span><div><b>{{ __('public_erx.what.' . $k . '_title') }}</b><span>{{ __('public_erx.what.' . $k . '_desc') }}</span></div></li>
                @endforeach
            </ul>
        </div>
        <figure class="hm-panel" aria-label="{{ __('public_erx.what.panel_label') }}">
            <div class="hm-panel__ph" aria-hidden="true"><i></i><i></i><i></i>{{ __('public_erx.what.panel_title') }}</div>
            <div class="hm-rows ex-rows">
                @foreach (['row1' => 'hm-pill--b', 'row2' => 'hm-pill--g', 'row3' => 'hm-pill--b'] as $row => $pill)
                    <div><span class="ex-before">{{ __('public_erx.what.' . $row) }}</span>{!! $exIcon('arrow-right', 16) !!}<span class="hm-pill {{ $pill }}">{{ __('public_erx.what.' . $row . '_pill') }}</span></div>
                @endforeach
            </div>
        </figure>
    </div>
</section>

{{-- How it works (animated) ───────────────────────────────────────────── --}}
@include('public.explainer._flow', [
    'ns' => 'public_erx',
    'icons' => ['users', 'stethoscope', 'smartphone', 'map-pin', 'scan-line', 'circle-check'],
])

{{-- How OpesCare makes it possible ────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-how-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_erx.how.eyebrow') }}</p>
            <h2 id="ex-how-title">{{ __('public_erx.how.title') }}</h2>
            <p class="hm-sub">{{ __('public_erx.how.sub') }}</p>
        </div>
        <ul class="hm-grid4 ex-feats" role="list">
            @foreach ([
                'desk' => 'stethoscope', 'gate' => 'shield-check', 'interact' => 'shield-alert', 'code' => 'qr-code',
                'finder' => 'search', 'lookup' => 'id-card', 'partial' => 'pill', 'print' => 'file-check',
            ] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_erx.how.cards.' . $k . '.title') }}</h3>
                    <p>{{ __('public_erx.how.cards.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- The pharmacy counter (second animated strip) ──────────────────────── --}}
@include('public.explainer._flow', [
    'ns' => 'public_erx',
    'key' => 'counter',
    'fid' => 'ex-counter-title',
    'icons' => ['search', 'lock', 'bell', 'pill', 'scroll-text'],
])

@include('public.explainer._means', [
    'ns' => 'public_erx',
    'pIcons' => ['smartphone', 'map-pin', 'key-round'],
    'fIcons' => ['stethoscope', 'scan-line', 'lock'],
])

{{-- Safety ────────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-safety-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_erx.safety.eyebrow') }}</p>
            <h2 id="ex-safety-title">{{ __('public_erx.safety.title') }}</h2>
            <p class="hm-sub">{{ __('public_erx.safety.sub') }}</p>
        </div>
        <ul class="hm-grid4 ex-feats" role="list">
            @foreach (['verify' => 'qr-code', 'oracle' => 'eye-off', 'controlled' => 'shield-check', 'expiry' => 'timer'] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_erx.safety.items.' . $k . '.title') }}</h3>
                    <p>{{ __('public_erx.safety.items.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

@include('public.explainer._faq_cta', [
    'ns' => 'public_erx',
    'ctaLinks' => [
        [route('public.download'), __('public_erx.cta.secondary'), 'hm-btn--light'],
        [route('register.patient'), __('public_erx.cta.primary'), 'hm-btn--white'],
    ],
])
@endsection
