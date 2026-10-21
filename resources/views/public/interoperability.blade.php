{{--
    /interoperability — what interoperability is, how a referral moves, and
    what OpesCare has in place for it. Home-page design (layouts.hm).

    Every claim maps to code; keep it that way:
      - care-relationship bases + preview/full scope: App\Support\ClinicalRelationshipGate
      - consent request → approve/deny: routes/mobile_consent_requests.php, routes/api.php
      - access log the patient reads: GET /api/mobile/access-logs, access_logs rows per chart section
      - break-glass (reason, 4h, patient alert): MobileEmergencyAccessController, EmergencyAccessService
      - privacy switches: Patient::requiresConsentForFullRecord(), allowsEmergencyAccess()
      - FHIR R4 ingest only (no reads): routes/fhir.php, App\Services\Fhir\FhirIngestGuard
      - patient FHIR export: MedicalRecordExportController::exportFhir
      - referrals: routes/mobile_doctor_referrals.php; document QR: /verify/doc/{token}
--}}
@extends('layouts.hm')

@section('title', __('public_interop.meta_title'))
@section('description', __('public_interop.meta_desc'))

@push('schema')
    @include('public.explainer._schema', ['ns' => 'public_interop', 'route' => 'public.interoperability'])
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
                <a href="{{ route('public.landing') }}">{{ __('finder_seo.breadcrumbs.home') }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ __('public_interop.breadcrumb') }}</span>
            </nav>
            <span class="hm-kicker">{!! $exIcon('share-2', 15) !!}{{ __('public_interop.hero.kicker') }}</span>
            <h1 id="ex-title">{{ __('public_interop.hero.title_1') }}<br><span>{{ __('public_interop.hero.title_2') }}</span></h1>
            <p class="hm-lead">{{ __('public_interop.hero.lead') }}</p>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--white" href="{{ route('register.organization') }}">{!! $exIcon('building-2', 18) !!}{{ __('public_interop.hero.cta_register') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('public.solutions.index') }}">{{ __('public_interop.hero.cta_solutions') }}</a>
            </div>
        </div>
        <div class="ex-hvis">
            <div class="hx-net" role="img" aria-label="{{ __('public_interop.net.label') }}">
                <svg class="hx-lines" viewBox="0 0 440 390" aria-hidden="true"><path d="M95 62 L220 195"/><path d="M345 62 L220 195"/><path d="M95 328 L220 195"/><path d="M345 328 L220 195"/></svg>
                <div class="hx-core" aria-hidden="true">{!! $exIcon('share-2', 22) !!}<b>{{ __('public_interop.net.core') }}</b><small>{{ __('public_interop.net.core_sub') }}</small></div>
                <div class="hx-node hx-n1" aria-hidden="true">{!! $exIcon('building-2', 20) !!}<div><b>{{ __('public_interop.net.hospital') }}</b><small>{{ __('public_interop.net.hospital_sub') }}</small></div></div>
                <div class="hx-node hx-n2" aria-hidden="true">{!! $exIcon('stethoscope', 20) !!}<div><b>{{ __('public_interop.net.clinic') }}</b><small>{{ __('public_interop.net.clinic_sub') }}</small></div></div>
                <div class="hx-node hx-n3" aria-hidden="true">{!! $exIcon('flask-conical', 20) !!}<div><b>{{ __('public_interop.net.lab') }}</b><small>{{ __('public_interop.net.lab_sub') }}</small></div></div>
                <div class="hx-node hx-n4" aria-hidden="true">{!! $exIcon('pill', 20) !!}<div><b>{{ __('public_interop.net.pharmacy') }}</b><small>{{ __('public_interop.net.pharmacy_sub') }}</small></div></div>
            </div>
        </div>
    </div>
</section>

{{-- What it is ────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-what-title">
    <div class="hm-wrap hm-split">
        <div>
            <p class="hm-eyebrow">{{ __('public_interop.what.eyebrow') }}</p>
            <h2 id="ex-what-title">{{ __('public_interop.what.title') }}</h2>
            <p class="hm-sub">{{ __('public_interop.what.sub') }}</p>
            <ul class="hm-list" role="list">
                @foreach ([['id-card', 'b1'], ['key-round', 'b2'], ['eye', 'b3']] as [$icon, $k])
                    <li><span class="hm-ic hm-ic--good">{!! $exIcon($icon, 18) !!}</span><div><b>{{ __('public_interop.what.' . $k . '_title') }}</b><span>{{ __('public_interop.what.' . $k . '_desc') }}</span></div></li>
                @endforeach
            </ul>
        </div>
        <figure class="hm-panel" aria-label="{{ __('public_interop.what.panel_label') }}">
            <div class="hm-panel__ph" aria-hidden="true"><i></i><i></i><i></i>{{ __('public_interop.what.panel_title') }}</div>
            <div class="hm-rows ex-rows">
                @foreach (['row1' => 'hm-pill--b', 'row2' => 'hm-pill--g', 'row3' => 'hm-pill--b'] as $row => $pill)
                    <div><span class="ex-before">{{ __('public_interop.what.' . $row) }}</span>{!! $exIcon('arrow-right', 16) !!}<span class="hm-pill {{ $pill }}">{{ __('public_interop.what.' . $row . '_pill') }}</span></div>
                @endforeach
            </div>
        </figure>
    </div>
</section>

{{-- How it works (animated) ───────────────────────────────────────────── --}}
@include('public.explainer._flow', [
    'ns' => 'public_interop',
    'icons' => ['stethoscope', 'smartphone', 'shield-check', 'building-2', 'flask-conical', 'scroll-text'],
])

{{-- How OpesCare makes it possible ────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="ex-how-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_interop.how.eyebrow') }}</p>
            <h2 id="ex-how-title">{{ __('public_interop.how.title') }}</h2>
            <p class="hm-sub">{{ __('public_interop.how.sub') }}</p>
        </div>
        <ul class="hm-grid4 ex-feats" role="list">
            @foreach ([
                'id' => 'id-card', 'gate' => 'shield-check', 'consent' => 'key-round', 'log' => 'eye',
                'glass' => 'shield-alert', 'fhir' => 'network', 'docs' => 'qr-code', 'export' => 'smartphone',
            ] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_interop.how.cards.' . $k . '.title') }}</h3>
                    <p>{{ __('public_interop.how.cards.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

@include('public.explainer._means', [
    'ns' => 'public_interop',
    'pIcons' => ['key-round', 'eye', 'file-check'],
    'fIcons' => ['inbox', 'user-check', 'scroll-text'],
])

@include('public.explainer._faq_cta', ['ns' => 'public_interop'])
@endsection
