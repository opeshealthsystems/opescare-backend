@extends('layouts.public')

@section('title', __('landing.page_title_home'))
@section('meta_description', __('landing.page_desc_home'))

@section('content')

    {{--
        The homepage answers five questions and nothing else: what OpesCare is,
        why it matters, how it works, what you can connect, and what to do next.
        Everything that teaches a workflow, documents a module or sells a
        stakeholder now lives on its own page and is linked from here.
    --}}

    {{-- 01 ─────────────────────────────────────────── Hero: the thesis --}}
    <section class="lp-hero">
        <div class="container lp-hero-inner">
            <div class="lp-hero-copy">
                <div class="lp-badge"><i data-lucide="id-card"></i> {{ __('landing.hero.badge') }}</div>
                <h1>{{ __('landing.hero.title') }}</h1>
                <p class="lp-hero-sub">{{ __('landing.hero.subtitle') }}</p>

                {{-- The single most misread thing about OpesCare, said early. --}}
                <p class="lp-positioning">
                    <i data-lucide="quote"></i>
                    <span>{{ __('landing.hero.positioning') }}</span>
                </p>

                <div class="lp-hero-actions">
                    <a href="{{ route('public.request-demo') }}" class="btn btn-primary btn-lg">
                        <i data-lucide="cable"></i> {{ __('landing.hero.cta_primary') }}
                    </a>
                    <a href="{{ route('register.patient') }}" class="btn btn-secondary btn-lg">
                        <i data-lucide="id-card"></i> {{ __('landing.hero.cta_secondary') }}
                    </a>
                </div>

                <div class="lp-hero-facts">
                    <span><i data-lucide="languages"></i> {{ __('landing.hero.fact_bilingual') }}</span>
                    <span><i data-lucide="git-merge"></i> {{ __('landing.hero.fact_standards') }}</span>
                    <span><i data-lucide="shield-check"></i> {{ __('landing.hero.fact_consent') }}</span>
                </div>
            </div>

            {{--
                Deliberately not a plastic ID card. The Health ID is an identity
                object at the centre of a network, because that is what it is.
            --}}
            <div class="lp-hero-visual" aria-hidden="true">
                <div class="lp-identity">
                    <div class="lp-identity-core">
                        <div class="lp-identity-pulse"></div>
                        <i data-lucide="id-card"></i>
                        <strong>{{ __('landing.hero_card.label_health_id') }}</strong>
                        <span class="lp-identity-id">{{ __('landing.hero_card.demo_id') }}</span>
                        <span class="lp-identity-verified"><i data-lucide="badge-check"></i> {{ __('landing.hero_card.label_verified') }}</span>
                    </div>
                    <div class="lp-identity-ring">
                        <span class="lp-node n1"><i data-lucide="hospital"></i><em>{{ __('landing.ecosystem.chip_hospitals') }}</em></span>
                        <span class="lp-node n2"><i data-lucide="flask-conical"></i><em>{{ __('landing.ecosystem.chip_labs') }}</em></span>
                        <span class="lp-node n3"><i data-lucide="pill"></i><em>{{ __('landing.ecosystem.chip_pharmacies') }}</em></span>
                        <span class="lp-node n4"><i data-lucide="heart-handshake"></i><em>{{ __('landing.ecosystem.chip_insurers') }}</em></span>
                        <span class="lp-node n5"><i data-lucide="landmark"></i><em>{{ __('landing.ecosystem.chip_public_health') }}</em></span>
                        <span class="lp-node n6"><i data-lucide="stethoscope"></i><em>{{ __('landing.ecosystem.chip_providers') }}</em></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 02 ─────────────────────────────────── The problem: fragmentation --}}
    <section class="section section-muted">
        <div class="container text-center mb-12">
            <h2>{{ __('landing.problem.title') }}</h2>
            <p class="section-subtitle">{{ __('landing.problem.subtitle') }}</p>
        </div>
        <div class="container lp-grid-4">
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="users"></i><h3>{{ __('landing.problem.identity_title') }}</h3></div>
                <p>{{ __('landing.problem.identity_desc') }}</p>
            </div>
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="unlink"></i><h3>{{ __('landing.problem.records_title') }}</h3></div>
                <p>{{ __('landing.problem.records_desc') }}</p>
            </div>
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="clock-alert"></i><h3>{{ __('landing.problem.delay_title') }}</h3></div>
                <p>{{ __('landing.problem.delay_desc') }}</p>
            </div>
            <div class="card card-problem">
                <div class="problem-header"><i data-lucide="eye-off"></i><h3>{{ __('landing.problem.visibility_title') }}</h3></div>
                <p>{{ __('landing.problem.visibility_desc') }}</p>
            </div>
        </div>
    </section>

    {{-- 03 ──────────────────────────── The answer: the signature diagram --}}
    <section class="section">
        <div class="container text-center mb-12">
            <h2>{{ __('landing.answer.title') }}</h2>
            <p class="section-subtitle">{{ __('landing.answer.subtitle') }}</p>
        </div>
        <div class="container">
            <ol class="lp-chain">
                @foreach (['identity' => 'id-card', 'index' => 'git-merge', 'trust' => 'shield-check', 'interop' => 'cable', 'care' => 'heart-pulse'] as $key => $icon)
                    <li class="lp-chain-step">
                        <div class="lp-chain-icon"><i data-lucide="{{ $icon }}"></i></div>
                        <h3>{{ __("landing.answer.{$key}_title") }}</h3>
                        <p>{{ __("landing.answer.{$key}_desc") }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 04 ───────────────────────────────────── How information moves --}}
    <section class="section section-muted" id="how-it-works">
        <div class="container text-center mb-12">
            <h2>{{ __('landing.exchange.title') }}</h2>
            <p class="section-subtitle">{{ __('landing.exchange.subtitle') }}</p>
        </div>

        <div class="container">
            {{-- Existing system → OpesCare → another authorised system. --}}
            <div class="lp-exchange" aria-hidden="true">
                <div class="lp-exchange-end">
                    <i data-lucide="hospital"></i>
                    <strong>{{ __('landing.exchange.source_title') }}</strong>
                    <span>{{ __('landing.exchange.source_desc') }}</span>
                </div>
                <div class="lp-exchange-flow"><i data-lucide="arrow-left-right"></i></div>
                <div class="lp-exchange-core">
                    <i data-lucide="network"></i>
                    <strong>{{ __('landing.exchange.core_title') }}</strong>
                    <span>{{ __('landing.exchange.core_desc') }}</span>
                </div>
                <div class="lp-exchange-flow"><i data-lucide="arrow-left-right"></i></div>
                <div class="lp-exchange-end">
                    <i data-lucide="building-2"></i>
                    <strong>{{ __('landing.exchange.target_title') }}</strong>
                    <span>{{ __('landing.exchange.target_desc') }}</span>
                </div>
            </div>

            {{-- The five steps are a real sequence, so they are numbered. --}}
            <ol class="lp-steps">
                @foreach (['identify' => 'user-search', 'match' => 'git-merge', 'authorize' => 'shield-check', 'exchange' => 'refresh-cw', 'record' => 'history'] as $key => $icon)
                    <li class="lp-step">
                        <span class="lp-step-num">{{ $loop->iteration }}</span>
                        <i data-lucide="{{ $icon }}"></i>
                        <h4>{{ __("landing.exchange.step_{$key}_title") }}</h4>
                        <p>{{ __("landing.exchange.step_{$key}_desc") }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="lp-transports">
                <span class="lp-transports-label">{{ __('landing.exchange.transports_label') }}</span>
                <div class="lp-transport-chips">
                    <span>FHIR R4</span><span>Connect API</span><span>SDK</span>
                    <span>Widget</span><span>Bridge Agent</span><span>OpesCare Lite</span><span>Webhooks</span>
                </div>
                <a href="{{ route('public.interoperability') }}" class="lp-arrow-link">
                    {{ __('landing.exchange.cta') }} <i data-lucide="arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- 05 ───────────────────────── What OpesCare gives the ecosystem --}}
    <section class="section">
        <div class="container text-center mb-12">
            <h2>{{ __('landing.pillars.title') }}</h2>
            <p class="section-subtitle">{{ __('landing.pillars.subtitle') }}</p>
        </div>
        <div class="container lp-pillars">
            {{--
                Each pillar links to the page that actually explains it. The
                Patient Index has no page of its own yet, so it points at the
                Match step above rather than at a page that never mentions it.
            --}}
            @foreach ([
                'identity' => ['icon' => 'id-card',       'href' => route('public.solutions.patients')],
                'index'    => ['icon' => 'git-merge',     'href' => '#how-it-works'],
                'record'   => ['icon' => 'history',       'href' => route('public.solutions.patients') . '#timeline'],
                'trust'    => ['icon' => 'shield-check',  'href' => route('public.consent')],
                'interop'  => ['icon' => 'cable',         'href' => route('public.interoperability')],
            ] as $key => $meta)
                <a href="{{ $meta['href'] }}" class="card lp-pillar">
                    <div class="lp-pillar-icon"><i data-lucide="{{ $meta['icon'] }}"></i></div>
                    <h3>{{ __("landing.pillars.{$key}_title") }}</h3>
                    <p>{{ __("landing.pillars.{$key}_desc") }}</p>
                    <span class="lp-pillar-more"><i data-lucide="arrow-right"></i></span>
                </a>
            @endforeach
        </div>

        {{--
            Laboratory, pharmacy, referral and insurance connectivity are real
            and they matter — but they are consequences of the layer above, not
            products of their own. One line each, then the detail page.
        --}}
        <div class="container lp-also">
            <span class="lp-also-label">{{ __('landing.pillars.also_label') }}</span>
            <ul>
                <li><i data-lucide="send"></i> {{ __('landing.pillars.also_referrals') }} <a href="{{ route('public.interoperability') }}">{{ __('landing.pillars.also_more') }}</a></li>
                <li><i data-lucide="flask-conical"></i> {{ __('landing.pillars.also_labs') }} <a href="{{ route('public.solutions.laboratories') }}">{{ __('landing.pillars.also_more') }}</a></li>
                <li><i data-lucide="pill"></i> {{ __('landing.pillars.also_pharmacy') }} <a href="{{ route('public.solutions.pharmacies') }}">{{ __('landing.pillars.also_more') }}</a></li>
                <li><i data-lucide="heart-handshake"></i> {{ __('landing.pillars.also_insurance') }} <a href="{{ route('public.solutions.insurers') }}">{{ __('landing.pillars.also_more') }}</a></li>
            </ul>
        </div>
    </section>

    {{-- 06 ──────────────────────────────────────────── Network services --}}
    <section class="section section-muted" id="network">
        <div class="container text-center mb-12">
            <h2>{{ __('landing.network.title') }}</h2>
            <p class="section-subtitle">{{ __('landing.network.subtitle') }}</p>
        </div>
        <div class="container grid-2">
            <a href="{{ route('public.network.medicine-finder') }}" class="card lp-service lp-service-med">
                <div class="lp-service-icon"><i data-lucide="map-pin"></i></div>
                <h3>{{ __('landing.network.medicine_title') }}</h3>
                <p>{{ __('landing.network.medicine_desc') }}</p>
                <span class="lp-arrow-link">{{ __('landing.network.medicine_cta') }} <i data-lucide="arrow-right"></i></span>
            </a>
            <a href="{{ route('public.network.blood-finder') }}" class="card lp-service lp-service-blood">
                <div class="lp-service-icon"><i data-lucide="droplet"></i></div>
                <h3>{{ __('landing.network.blood_title') }}</h3>
                <p>{{ __('landing.network.blood_desc') }}</p>
                <span class="lp-arrow-link">{{ __('landing.network.blood_cta') }} <i data-lucide="arrow-right"></i></span>
            </a>
        </div>
        <div class="container text-center" style="margin-top:1.75rem;">
            <p class="lp-note"><i data-lucide="info"></i> {{ __('landing.network.note') }}</p>
        </div>
    </section>

    {{-- 07 ────────────────────────────────── Who connects to OpesCare --}}
    <section class="section" id="ecosystem">
        <div class="container text-center mb-12">
            <h2>{{ __('landing.ecosystem.title') }}</h2>
            <p class="section-subtitle">{{ __('landing.ecosystem.subtitle') }}</p>
        </div>

        <div class="container">
            <div class="lp-chips">
                <a href="{{ route('public.solutions.patients') }}"><i data-lucide="user"></i> {{ __('landing.ecosystem.chip_patients') }}</a>
                <a href="{{ route('public.solutions.hospitals') }}"><i data-lucide="stethoscope"></i> {{ __('landing.ecosystem.chip_providers') }}</a>
                <a href="{{ route('public.solutions.hospitals') }}"><i data-lucide="hospital"></i> {{ __('landing.ecosystem.chip_hospitals') }}</a>
                <a href="{{ route('public.solutions.laboratories') }}"><i data-lucide="flask-conical"></i> {{ __('landing.ecosystem.chip_labs') }}</a>
                <a href="{{ route('public.solutions.pharmacies') }}"><i data-lucide="pill"></i> {{ __('landing.ecosystem.chip_pharmacies') }}</a>
                <a href="{{ route('public.solutions.insurers') }}"><i data-lucide="heart-handshake"></i> {{ __('landing.ecosystem.chip_insurers') }}</a>
                <a href="{{ route('public.solutions.public-health') }}"><i data-lucide="landmark"></i> {{ __('landing.ecosystem.chip_public_health') }}</a>
                <a href="{{ route('public.developers') }}"><i data-lucide="code-2"></i> {{ __('landing.ecosystem.chip_developers') }}</a>
            </div>

            <div class="lp-destinations">
                <a href="{{ route('public.solutions.patients') }}" class="card lp-destination">
                    <i data-lucide="user"></i>
                    <h3>{{ __('landing.ecosystem.card_patients_title') }}</h3>
                    <p>{{ __('landing.ecosystem.card_patients_desc') }}</p>
                </a>
                <a href="{{ route('public.solutions.hospitals') }}" class="card lp-destination">
                    <i data-lucide="hospital"></i>
                    <h3>{{ __('landing.ecosystem.card_facilities_title') }}</h3>
                    <p>{{ __('landing.ecosystem.card_facilities_desc') }}</p>
                </a>
                <a href="{{ route('public.solutions.insurers') }}" class="card lp-destination">
                    <i data-lucide="landmark"></i>
                    <h3>{{ __('landing.ecosystem.card_orgs_title') }}</h3>
                    <p>{{ __('landing.ecosystem.card_orgs_desc') }}</p>
                </a>
                <a href="{{ route('public.developers') }}" class="card lp-destination">
                    <i data-lucide="code-2"></i>
                    <h3>{{ __('landing.ecosystem.card_devs_title') }}</h3>
                    <p>{{ __('landing.ecosystem.card_devs_desc') }}</p>
                </a>
            </div>
        </div>
    </section>

    {{-- 08 ──────────────────────────────────────────────────────── Trust --}}
    <section class="section section-dark" id="trust">
        <div class="container grid-2 items-center">
            <div>
                <h2 class="text-white">{{ __('landing.trust.title') }}</h2>
                <p class="text-lg text-muted-light mb-8">{{ __('landing.trust.desc') }}</p>

                {{-- The consent question, reduced to the five things it decides. --}}
                <ul class="lp-consent-questions">
                    <li><i data-lucide="user-search"></i> {{ __('landing.trust.q_who') }}</li>
                    <li><i data-lucide="help-circle"></i> {{ __('landing.trust.q_why') }}</li>
                    <li><i data-lucide="layers"></i> {{ __('landing.trust.q_what') }}</li>
                    <li><i data-lucide="clock"></i> {{ __('landing.trust.q_how_long') }}</li>
                    <li><i data-lucide="toggle-right"></i> {{ __('landing.trust.q_control') }}</li>
                </ul>

                <a href="{{ route('public.consent') }}" class="lp-arrow-link lp-arrow-light">
                    {{ __('landing.trust.consent_cta') }} <i data-lucide="arrow-right"></i>
                </a>
            </div>

            <div class="lp-trust-side">
                {{-- Break-glass gets a teaser, not a product page on the homepage. --}}
                <div class="lp-breakglass">
                    <div class="lp-breakglass-head">
                        <i data-lucide="siren"></i>
                        <strong>{{ __('landing.trust.emergency_title') }}</strong>
                    </div>
                    <p>{{ __('landing.trust.emergency_desc') }}</p>
                    <a href="{{ route('public.care-map.emergency') }}" class="lp-arrow-link lp-arrow-light">
                        {{ __('landing.trust.emergency_cta') }} <i data-lucide="arrow-right"></i>
                    </a>
                </div>

                <div class="lp-trust-pillars">
                    <div><i data-lucide="lock-keyhole"></i><strong>{{ __('landing.trust.pillar_private_title') }}</strong><span>{{ __('landing.trust.pillar_private_desc') }}</span></div>
                    <div><i data-lucide="clipboard-check"></i><strong>{{ __('landing.trust.pillar_audit_title') }}</strong><span>{{ __('landing.trust.pillar_audit_desc') }}</span></div>
                    <div><i data-lucide="git-merge"></i><strong>{{ __('landing.trust.pillar_standards_title') }}</strong><span>{{ __('landing.trust.pillar_standards_desc') }}</span></div>
                    <div><i data-lucide="languages"></i><strong>{{ __('landing.trust.pillar_local_title') }}</strong><span>{{ __('landing.trust.pillar_local_desc') }}</span></div>
                </div>

                <a href="{{ route('public.security') }}" class="btn btn-secondary lp-trust-cta">
                    {{ __('landing.trust.security_cta') }} <i data-lucide="arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- 09 ────────────────────────────────────────────────── Final CTA --}}
    <section class="section lp-final">
        <div class="container text-center">
            <h2>{{ __('landing.footer_cta.title') }}</h2>
            <p class="section-subtitle" style="margin-bottom:2.5rem;">{{ __('landing.footer_cta.subtitle') }}</p>
            <div class="lp-final-actions">
                <a href="{{ route('public.request-demo') }}" class="btn btn-primary btn-lg">
                    <i data-lucide="cable"></i> {{ __('landing.footer_cta.cta_primary') }}
                </a>
                <a href="{{ route('register.patient') }}" class="btn btn-secondary btn-lg">
                    <i data-lucide="id-card"></i> {{ __('landing.footer_cta.cta_secondary') }}
                </a>
            </div>
            <p class="lp-faq-prompt">
                {{ __('landing.footer_cta.faq_prompt') }}
                <a href="{{ route('public.faq') }}">{{ __('landing.footer_cta.faq_cta') }} <i data-lucide="arrow-right"></i></a>
            </p>
        </div>
    </section>
@endsection
