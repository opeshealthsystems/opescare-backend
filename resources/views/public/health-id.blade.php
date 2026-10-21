{{--
    /health-id — everything about the OpesCare Health ID. Home-page design
    (layouts.hm), same pattern as /interoperability and /master-patient-index.

    Every claim maps to code; keep it that way:
      - CM-HID-XXXX-XXXX-XXXX, safe alphabet, check block, unique: App\Services\Identity\HealthIdGeneratorService
      - duplicate refusal (name + DOB normalised, hashed phone), never a merge: Patient::findExistingIdentity
        (MobileAuthController register, PatientProfileCompletionController, DeskPatientController, MobileFamilyController)
      - ways in: register.patient (+ profile completion), api/mobile/desk/patients (front desk),
        POST api/mobile/family + register.guardian (child, provisional, family link)
      - card fields + day-long card QR: App\Support\HealthIdCardPayload; live 15-minute code:
        MobileQrController::generateTemporary (app PresentationMode)
      - /verify/qr states verified|expired|revoked|invalid + reference: VerifyController::qr, QrTokenService
      - /verify/health-id masked vs signed-in staff view, every lookup audited: VerifyController::healthIdLookup
      - privacy switches: Patient::requiresConsentForFullRecord(), allowsEmergencyAccess()
      - gate + preview/full: App\Support\ClinicalRelationshipGate; break-glass: EmergencyAccessService
      - FHIR R4 ingest only: routes/fhir.php, FhirIngestGuard (no reads out, no Health ID creation)
      - sign in by Health ID (identifier, never a credential): App\Services\Auth\HealthIdLogin
      - MPI scans, records-officer review, reversible merge, alias resolves: /admin/mpi, Patient::resolveHealthId
--}}
@extends('layouts.hm')

@section('title', __('public_hid.meta_title'))
@section('description', __('public_hid.meta_desc'))

@push('schema')
    @include('public.explainer._schema', ['ns' => 'public_hid', 'route' => 'public.health-id'])
@endpush
@push('scripts')
    <script src="{{ asset('js/explainer-flow.js') }}?v={{ @filemtime(public_path('js/explainer-flow.js')) ?: '1' }}" @cspNonce defer></script>
@endpush

@php
    $exIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
    $exCards = function (string $group, array $items, string $class = 'hm-grid4 ex-feats') {
        return view('public.explainer._cards', ['ns' => 'public_hid', 'group' => $group, 'items' => $items, 'class' => $class])->render();
    };
@endphp

@section('content')
{{-- Hero ──────────────────────────────────────────────────────────────── --}}
<section class="hm-hero" aria-labelledby="ex-title">
    <div class="hm-wrap hm-hero__grid">
        <div class="hm-hero__text">
            <nav class="ex-crumbs" aria-label="{{ __('finder_seo.breadcrumbs.home') }}">
                <a href="{{ route('public.landing') }}">{{ __('finder_seo.breadcrumbs.home') }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ __('public_hid.breadcrumb') }}</span>
            </nav>
            <span class="hm-kicker">{!! $exIcon('id-card', 15) !!}{{ __('public_hid.hero.kicker') }}</span>
            <h1 id="ex-title">{{ __('public_hid.hero.title_1') }}<br><span>{{ __('public_hid.hero.title_2') }}</span></h1>
            <p class="hm-lead">{{ __('public_hid.hero.lead') }}</p>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--white" href="{{ route('register.patient') }}">{!! $exIcon('id-card', 18) !!}{{ __('public_hid.hero.cta_primary') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('public.download') }}">{{ __('public_hid.hero.cta_secondary') }}</a>
            </div>
        </div>
        <div class="ex-hvis">
            <div class="hx-net" role="img" aria-label="{{ __('public_hid.net.label') }}">
                <svg class="hx-lines" viewBox="0 0 440 390" aria-hidden="true"><path d="M95 62 L220 195"/><path d="M345 62 L220 195"/><path d="M95 328 L220 195"/><path d="M345 328 L220 195"/></svg>
                <div class="hx-core" aria-hidden="true">{!! $exIcon('id-card', 22) !!}<b>{{ __('public_hid.net.core') }}</b><small>{{ __('public_hid.net.core_sub') }}</small></div>
                <div class="hx-node hx-n1" aria-hidden="true">{!! $exIcon('qr-code', 20) !!}<div><b>{{ __('public_hid.net.card') }}</b><small>{{ __('public_hid.net.card_sub') }}</small></div></div>
                <div class="hx-node hx-n2" aria-hidden="true">{!! $exIcon('building-2', 20) !!}<div><b>{{ __('public_hid.net.desk') }}</b><small>{{ __('public_hid.net.desk_sub') }}</small></div></div>
                <div class="hx-node hx-n3" aria-hidden="true">{!! $exIcon('shield-check', 20) !!}<div><b>{{ __('public_hid.net.verify') }}</b><small>{{ __('public_hid.net.verify_sub') }}</small></div></div>
                <div class="hx-node hx-n4" aria-hidden="true">{!! $exIcon('eye', 20) !!}<div><b>{{ __('public_hid.net.log') }}</b><small>{{ __('public_hid.net.log_sub') }}</small></div></div>
            </div>
        </div>
    </div>
</section>

{{-- What it is ────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-what-title">
    <div class="hm-wrap hm-split">
        <div>
            <p class="hm-eyebrow">{{ __('public_hid.what.eyebrow') }}</p>
            <h2 id="ex-what-title">{{ __('public_hid.what.title') }}</h2>
            <p class="hm-sub">{{ __('public_hid.what.sub') }}</p>
            <ul class="hm-list" role="list">
                @foreach ([['fingerprint', 'b1'], ['user-check', 'b2'], ['lock', 'b3']] as [$icon, $k])
                    <li><span class="hm-ic hm-ic--good">{!! $exIcon($icon, 18) !!}</span><div><b>{{ __('public_hid.what.' . $k . '_title') }}</b><span>{{ __('public_hid.what.' . $k . '_desc') }}</span></div></li>
                @endforeach
            </ul>
        </div>
        <figure class="hm-panel" aria-label="{{ __('public_hid.what.panel_label') }}">
            <div class="hm-panel__ph" aria-hidden="true"><i></i><i></i><i></i>{{ __('public_hid.what.panel_title') }}</div>
            <div class="hm-rows ex-rows">
                @foreach (['row1' => 'hm-pill--b', 'row2' => 'hm-pill--g', 'row3' => 'hm-pill--b'] as $row => $pill)
                    <div><span class="ex-before ex-mono">{{ __('public_hid.what.' . $row) }}</span>{!! $exIcon('arrow-right', 16) !!}<span class="hm-pill {{ $pill }}">{{ __('public_hid.what.' . $row . '_pill') }}</span></div>
                @endforeach
            </div>
        </figure>
    </div>
</section>

{{-- How to get one ────────────────────────────────────────────────────── --}}
<section class="hm-s hm-s--alt" aria-labelledby="ex-get-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_hid.get.eyebrow') }}</p>
            <h2 id="ex-get-title">{{ __('public_hid.get.title') }}</h2>
            <p class="hm-sub">{{ __('public_hid.get.sub') }}</p>
        </div>
        {!! $exCards('get.items', ['self' => 'user-plus', 'desk' => 'building-2', 'family' => 'baby'], 'ex-feats ex-feats--3') !!}
    </div>
</section>

{{-- How it works (animated) ───────────────────────────────────────────── --}}
@include('public.explainer._flow', [
    'ns' => 'public_hid',
    'icons' => ['user-plus', 'search', 'id-card', 'scan-line', 'key-round', 'scroll-text'],
])

{{-- The card ──────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-card-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_hid.card.eyebrow') }}</p>
            <h2 id="ex-card-title">{{ __('public_hid.card.title') }}</h2>
            <p class="hm-sub">{{ __('public_hid.card.sub') }}</p>
        </div>
        {!! $exCards('card.items', ['qr' => 'qr-code', 'live' => 'timer', 'qrpage' => 'shield-check', 'lookup' => 'search']) !!}
    </div>
</section>

{{-- What it holds ─────────────────────────────────────────────────────── --}}
<section class="hm-s hm-s--alt" aria-labelledby="ex-holds-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_hid.holds.eyebrow') }}</p>
            <h2 id="ex-holds-title">{{ __('public_hid.holds.title') }}</h2>
            <p class="hm-sub">{{ __('public_hid.holds.sub') }}</p>
        </div>
        <ul class="hm-grid4 ex-feats" role="list">
            @foreach (['identity' => 'id-card', 'blood' => 'droplet', 'allergies' => 'shield-alert', 'care' => 'heart-pulse'] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_hid.holds.' . $k) }}</h3>
                    <p>{{ __('public_hid.holds.' . $k . '_desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- Privacy and consent ───────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-privacy-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_hid.privacy.eyebrow') }}</p>
            <h2 id="ex-privacy-title">{{ __('public_hid.privacy.title') }}</h2>
            <p class="hm-sub">{{ __('public_hid.privacy.sub') }}</p>
        </div>
        {!! $exCards('privacy.items', ['consent' => 'key-round', 'glass' => 'shield-alert', 'log' => 'eye', 'grants' => 'file-check']) !!}
    </div>
</section>

{{-- The interoperability layer ────────────────────────────────────────── --}}
<section class="hm-s hm-s--alt" aria-labelledby="ex-layer-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_hid.layer.eyebrow') }}</p>
            <h2 id="ex-layer-title">{{ __('public_hid.layer.title') }}</h2>
            <p class="hm-sub">{{ __('public_hid.layer.sub') }}</p>
        </div>
        {!! $exCards('layer.items', ['gate' => 'shield-check', 'scope' => 'eye', 'fhir' => 'network', 'log' => 'scroll-text']) !!}
        <p class="ex-more"><a class="hm-btn" href="{{ route('public.interoperability') }}">{{ __('public_hid.layer.more') }}{!! $exIcon('arrow-right', 16) !!}</a></p>
    </div>
</section>

{{-- Duplicates + MPI, and family ──────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-mpi-title">
    <div class="hm-wrap ex-two">
        <div class="ex-card">
            <p class="hm-eyebrow">{{ __('public_hid.mpi.eyebrow') }}</p>
            <h2 id="ex-mpi-title" class="ex-card__t">{{ __('public_hid.mpi.title') }}</h2>
            <p class="ex-card__p">{{ __('public_hid.mpi.sub') }}</p>
            <a class="hm-btn" href="{{ route('public.master-patient-index') }}">{!! $exIcon('git-merge', 16) !!}{{ __('public_hid.mpi.more') }}</a>
        </div>
        <div class="ex-card">
            <p class="hm-eyebrow">{{ __('public_hid.family.eyebrow') }}</p>
            <h2 class="ex-card__t">{{ __('public_hid.family.title') }}</h2>
            <p class="ex-card__p">{{ __('public_hid.family.sub') }}</p>
            <a class="hm-btn" href="{{ route('register.guardian') }}">{!! $exIcon('users', 16) !!}{{ __('public_hid.family.cta') }}</a>
        </div>
    </div>
</section>

{{-- Lost card / recovery ──────────────────────────────────────────────── --}}
<section class="hm-s hm-s--alt" aria-labelledby="ex-lost-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_hid.lost.eyebrow') }}</p>
            <h2 id="ex-lost-title">{{ __('public_hid.lost.title') }}</h2>
        </div>
        {!! $exCards('lost.items', ['card' => 'download', 'signin' => 'log-in', 'password' => 'key-round'], 'ex-feats ex-feats--3') !!}
    </div>
</section>

@include('public.explainer._faq_cta', [
    'ns' => 'public_hid',
    'ctaLinks' => [
        [route('public.download'), __('public_hid.cta.secondary'), 'hm-btn--light'],
        [route('register.patient'), __('public_hid.cta.primary'), 'hm-btn--white'],
    ],
])
@endsection
