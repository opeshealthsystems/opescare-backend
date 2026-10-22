{{--
    /developers — what the OpesCare API offers today and how to get access.
    Home-page design (layouts.hm), same family as /interoperability.

    Every claim maps to code; keep it that way:
      - Connect tokens + introspect, widget sessions, stock sync: routes/api.php (v1/connect)
      - care map search API: routes/api.php (v1/care-map)
      - FHIR R4 ingest (Observation, DiagnosticReport, Immunization, Bundle), scopes:
        routes/fhir.php, FhirIngestController::SCOPE_*, consent + Health ID: FhirIngestGuard
      - access path: /signup/developer (Lead), /developer portal (onboard, apps,
        rotate secret, webhook deliveries, production requests approved by admin)
      - plans: api_plans (ApiPlan::public())
    Ingest only: no FHIR reads, no outbound FHIR server, no HL7 v2.
--}}
@extends('layouts.hm')

@section('title', __('public_developers.meta_title'))
@section('description', __('public_developers.meta_desc'))

@php
    $exIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
    $plans = $plans ?? collect();
@endphp

@section('content')
<style>.dev-rows code{flex:1;min-width:0;font-size:12.5px;overflow-wrap:anywhere;color:var(--muted)}.dev-rows .hm-pill{white-space:normal}</style>
{{-- Hero ──────────────────────────────────────────────────────────────── --}}
<section class="hm-hero" aria-labelledby="ex-title">
    <div class="hm-wrap hm-hero__grid">
        <div class="hm-hero__text">
            <nav class="ex-crumbs" aria-label="{{ __('finder_seo.breadcrumbs.home') }}">
                <a href="{{ route('public.landing') }}">{{ __('finder_seo.breadcrumbs.home') }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ __('public_developers.breadcrumb') }}</span>
            </nav>
            <span class="hm-kicker">{!! $exIcon('braces', 15) !!}{{ __('public_developers.hero.kicker') }}</span>
            <h1 id="ex-title">{{ __('public_developers.hero.title_1') }}<br><span>{{ __('public_developers.hero.title_2') }}</span></h1>
            <p class="hm-lead">{{ __('public_developers.hero.lead') }}</p>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--white" href="{{ route('register.developer') }}">{!! $exIcon('user-plus', 18) !!}{{ __('public_developers.hero.cta_access') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('docs.index') }}">{!! $exIcon('book-open', 18) !!}{{ __('public_developers.hero.cta_docs') }}</a>
            </div>
            <p class="hm-lead"><a class="hm-btn hm-btn--light hm-btn--sm" href="{{ route('portals.developer.dashboard') }}">{!! $exIcon('log-in', 16) !!}{{ __('public_developers.hero.cta_portal') }}</a></p>
        </div>
        <figure class="hm-panel" aria-label="{{ __('public_developers.hero.panel_label') }}">
            <div class="hm-panel__ph" aria-hidden="true"><i></i><i></i><i></i>{{ __('public_developers.hero.panel_title') }}</div>
            <div class="hm-rows dev-rows">
                @foreach ([
                    ['POST', '/v1/connect/auth/token', 'row_token', 'hm-pill--b'],
                    ['POST', '/fhir/R4/Observation', 'row_fhir', 'hm-pill--g'],
                    ['POST', '/v1/connect/inventory/pharmacy-stock/sync', 'row_stock', 'hm-pill--b'],
                    ['GET', '/v1/care-map/nearby', 'row_map', 'hm-pill--g'],
                ] as [$verb, $path, $row, $pill])
                    <div><code>{{ $verb }} {{ $path }}</code><span class="hm-pill {{ $pill }}">{{ __('public_developers.hero.' . $row) }}</span></div>
                @endforeach
            </div>
        </figure>
    </div>
</section>

{{-- What the API offers ───────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="dev-offer-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_developers.offer.eyebrow') }}</p>
            <h2 id="dev-offer-title">{{ __('public_developers.offer.title') }}</h2>
            <p class="hm-sub">{{ __('public_developers.offer.sub') }}</p>
        </div>
        <ul class="hm-grid4 ex-feats" role="list">
            @foreach ([
                'auth' => 'key-round', 'fhir' => 'network', 'stock' => 'pill', 'map' => 'map-pin',
                'widget' => 'plug', 'webhooks' => 'webhook', 'sandbox' => 'flask-conical', 'docs' => 'book-open',
            ] as $k => $icon)
                <li class="ex-feat">
                    <span class="hm-ic">{!! $exIcon($icon, 20) !!}</span>
                    <h3>{{ __('public_developers.offer.cards.' . $k . '.title') }}</h3>
                    <p>{{ __('public_developers.offer.cards.' . $k . '.desc') }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- FHIR R4 ingest ────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="dev-fhir-title">
    <div class="hm-wrap hm-split">
        <div>
            <p class="hm-eyebrow">{{ __('public_developers.fhir.eyebrow') }}</p>
            <h2 id="dev-fhir-title">{{ __('public_developers.fhir.title') }}</h2>
            <p class="hm-sub">{{ __('public_developers.fhir.sub') }}</p>
            <ul class="hm-list" role="list">
                @foreach ([['key-round', 'b1'], ['id-card', 'b2'], ['shield-check', 'b3'], ['inbox', 'b4']] as [$icon, $k])
                    <li><span class="hm-ic hm-ic--good">{!! $exIcon($icon, 18) !!}</span><div><b>{{ __('public_developers.fhir.' . $k . '_title') }}</b><span>{{ __('public_developers.fhir.' . $k . '_desc') }}</span></div></li>
                @endforeach
            </ul>
        </div>
        <figure class="hm-panel" aria-label="{{ __('public_developers.fhir.panel_label') }}">
            <div class="hm-panel__ph" aria-hidden="true"><i></i><i></i><i></i>{{ __('public_developers.fhir.panel_title') }}</div>
            <div class="hm-rows dev-rows">
                @foreach ([
                    ['GET', '/metadata', 'row_metadata'],
                    ['POST', '/Observation', 'row_observation'],
                    ['POST', '/DiagnosticReport', 'row_report'],
                    ['POST', '/Immunization', 'row_immunization'],
                    ['POST', '/', 'row_bundle'],
                ] as [$verb, $path, $row])
                    <div><code>{{ $verb }} {{ $path }}</code><span class="hm-pill hm-pill--b">{{ __('public_developers.fhir.' . $row) }}</span></div>
                @endforeach
            </div>
        </figure>
    </div>
</section>

{{-- Access path ───────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="dev-steps-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_developers.steps.eyebrow') }}</p>
            <h2 id="dev-steps-title">{{ __('public_developers.steps.title') }}</h2>
        </div>
        <ol class="hm-steps" role="list">
            @foreach (['request', 'review', 'portal', 'keys', 'sandbox', 'prod'] as $i => $key)
                <li class="hm-step"><span class="hm-step__n" aria-hidden="true">0{{ $i + 1 }}</span><b>{{ __('public_developers.steps.' . $key . '_title') }}</b><span>{{ __('public_developers.steps.' . $key . '_desc') }}</span></li>
            @endforeach
        </ol>
    </div>
</section>

{{-- Plans (real rows from api_plans; section hidden when none are public) ── --}}
@if ($plans->isNotEmpty())
<section class="hm-s" id="plans" aria-labelledby="dev-plans-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('public_developers.plans.eyebrow') }}</p>
            <h2 id="dev-plans-title">{{ __('public_developers.plans.title') }}</h2>
            <p class="hm-sub">{{ __('public_developers.plans.sub') }}</p>
        </div>
        <ul class="hm-grid4 ex-feats" role="list">
            @foreach ($plans as $plan)
                <li class="ex-feat">
                    <h3>{{ $plan->name }}</h3>
                    <p><b>{{ $plan->price_xaf > 0 ? number_format($plan->price_xaf, 0, ',', ' ') . ' FCFA / ' . __('public.api_pricing.per_month') : __('public.api_pricing.free') }}</b></p>
                    <p>{{ $plan->isUnlimited() ? __('public.api_pricing.unlimited') : number_format($plan->monthly_request_quota, 0, ',', ' ') }} {{ __('public.api_pricing.requests_month') }}<br>{{ $plan->rate_limit_per_min }} {{ __('public.api_pricing.rate_per_min') }}</p>
                    <ul class="hm-list" role="list">
                        @foreach ((array) $plan->features as $feat)
                            @if (\Illuminate\Support\Facades\Lang::has('public.api_pricing.' . $feat))
                                <li><span class="hm-ic hm-ic--good">{!! $exIcon('check', 14) !!}</span><div><span>{{ __('public.api_pricing.' . $feat) }}</span></div></li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif

{{-- CTA ───────────────────────────────────────────────────────────────── --}}
<section class="hm-s">
    <div class="hm-wrap">
        <div class="hm-cta">
            <div>
                <h2>{{ __('public_developers.cta.title') }}</h2>
                <p>{{ __('public_developers.cta.sub') }}</p>
            </div>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--white" href="{{ route('register.developer') }}">{{ __('public_developers.hero.cta_access') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('docs.index') }}">{{ __('public_developers.hero.cta_docs') }}</a>
                <a class="hm-btn hm-btn--light" href="{{ route('public.interoperability') }}">{{ __('public_developers.cta.interop') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
