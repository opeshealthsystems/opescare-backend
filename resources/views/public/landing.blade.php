{{--
    OpesCare Finder — public landing page (v2, 2026-10-18).

    A standalone document on purpose: it carries its own header and footer so
    the first page partners see is designed end to end. Styling lives only in
    public/css/landing-v2.css (loaded by this view alone) and shares the desk
    design-system foundations: Inter stack, #0F4C81, canvas #EEF3F9, 16px
    cards, the desk status palette. Never purple.

    Rules honoured here:
      - every string via __('landing.v2.*'); EN/FR kept 1:1
      - no inline style=, no on*= handlers; no JS needed at all (the search
        tabs and mobile menu are CSS-only: radio inputs and <details>)
      - the only number shown ($stats['facilities']) is a cached scalar int
        read from the directory — no invented statistics
      - every href is a named route; the medicine form GETs the existing
        medicine finder (?q=), blood GETs the blood finder (?group=), the
        facility/book forms GET the directory (?query=, the param the site
        SearchAction already advertises)
      - interop claims: open REST API + FHIR R4 ingest (routes/fhir.php) only;
        never a full FHIR server, HL7 v2 or any certification
      - SEO head (canonical, hreflang, OG, JSON-LD) comes from the same
        partials.finder.schema.site include as every other Finder page
--}}
@section('title', __('landing.v2.meta_title'))
@section('description', __('landing.v2.meta_desc', ['count' => number_format($stats['facilities'])]))
@php
    $lxCount  = number_format($stats['facilities']);
    $lxLocale = app()->getLocale();
    $lxGroups = array_map(static fn ($g) => $g->value, \App\Enums\BloodGroup::cases());

    // Illustrative QR glyph for the Health ID card: three finder squares and
    // a fixed, deterministic module pattern. Decorative only (aria-hidden).
    $lxQr = [];
    for ($y = 0; $y < 21; $y++) {
        for ($x = 0; $x < 21; $x++) {
            $inFinder = ($x < 8 && $y < 8) || ($x > 12 && $y < 8) || ($x < 8 && $y > 12);
            if (! $inFinder && ((($x * 7 + $y * 13 + $x * $y) % 5) < 2)) {
                $lxQr[] = [$x, $y];
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $lxLocale) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ __('landing.v2.meta_title') }} — OpesCare Finder</title>
    <meta name="description" content="{{ __('landing.v2.meta_desc', ['count' => $lxCount]) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preload" href="{{ asset('fonts/inter/inter-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/inter/inter-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/landing-v2.css') }}?v={{ @filemtime(public_path('css/landing-v2.css')) ?: '1' }}">
    @include('partials.finder.schema.site')
</head>
<body class="lx">

<a class="lx-skip" href="#lx-main">{{ __('landing.v2.skip') }}</a>

{{-- 1 · Header ─────────────────────────────────────────────────────────── --}}
<header class="lx-header">
    <div class="lx-wrap lx-header__row">
        <a class="lx-brand" href="{{ route('public.landing') }}" aria-label="{{ __('landing.v2.nav.home') }}">
            <svg class="lx-brand__mark" width="34" height="34" viewBox="0 0 34 34" aria-hidden="true" focusable="false">
                <circle cx="17" cy="17" r="15.5" fill="none" stroke="currentColor" stroke-width="2.2"/>
                <path d="M17 9.5v15M9.5 17h15" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
                <circle cx="17" cy="17" r="3.4" class="lx-brand__dot"/>
            </svg>
            <span class="lx-brand__word">OPES</span>
            <span class="lx-brand__sub">Finder</span>
        </a>

        <nav class="lx-nav" aria-label="{{ __('landing.v2.nav.label') }}">
            <a href="{{ route('public.network.medicine-finder') }}">{{ __('landing.v2.nav.medicine') }}</a>
            <a href="{{ route('public.network.blood-finder') }}">{{ __('landing.v2.nav.blood') }}</a>
            <a href="{{ route('public.care-map') }}">{{ __('landing.v2.nav.facilities') }}</a>
            <a href="#lx-network">{{ __('landing.v2.nav.for_facilities') }}</a>
            <a href="{{ route('public.developers') }}">{{ __('landing.v2.nav.developers') }}</a>
        </nav>

        <div class="lx-header__actions">
            <div class="lx-lang" role="group" aria-label="{{ __('landing.v2.nav.language') }}">
                <a href="{{ route('lang.switch', 'en') }}" lang="en" hreflang="en" @if($lxLocale === 'en') aria-current="true" @endif>EN</a>
                <a href="{{ route('lang.switch', 'fr') }}" lang="fr" hreflang="fr" @if($lxLocale === 'fr') aria-current="true" @endif>FR</a>
            </div>
            <a class="lx-link-quiet lx-hide-sm" href="{{ route('login') }}">{{ __('landing.v2.nav.sign_in') }}</a>
            <a class="lx-btn lx-btn--primary lx-hide-sm" href="{{ route('register.patient') }}">{{ __('landing.v2.nav.cta') }}</a>

            <details class="lx-menu">
                <summary aria-label="{{ __('landing.v2.nav.menu') }}"><x-landing-icon name="menu" /></summary>
                <nav class="lx-menu__panel" aria-label="{{ __('landing.v2.nav.label') }}">
                    <a href="{{ route('public.network.medicine-finder') }}">{{ __('landing.v2.nav.medicine') }}</a>
                    <a href="{{ route('public.network.blood-finder') }}">{{ __('landing.v2.nav.blood') }}</a>
                    <a href="{{ route('public.care-map') }}">{{ __('landing.v2.nav.facilities') }}</a>
                    <a href="#lx-network">{{ __('landing.v2.nav.for_facilities') }}</a>
                    <a href="{{ route('public.developers') }}">{{ __('landing.v2.nav.developers') }}</a>
                    <a href="{{ route('login') }}">{{ __('landing.v2.nav.sign_in') }}</a>
                    <a class="lx-btn lx-btn--primary" href="{{ route('register.patient') }}">{{ __('landing.v2.nav.cta') }}</a>
                </nav>
            </details>
        </div>
    </div>
</header>

<main id="lx-main">

{{-- 2 · Hero: promise, the four services as search tabs, one trust line ── --}}
<section class="lx-hero" aria-labelledby="lx-hero-title">
    <div class="lx-wrap">
        <h1 id="lx-hero-title" class="lx-h1">{{ __('landing.v2.hero.title') }}</h1>
        <p class="lx-lead">{{ __('landing.v2.hero.lead') }}</p>

        <div class="lx-search">
            <input class="lx-tab-input" type="radio" name="lx-tab" id="lx-t-med" checked>
            <input class="lx-tab-input" type="radio" name="lx-tab" id="lx-t-blood">
            <input class="lx-tab-input" type="radio" name="lx-tab" id="lx-t-fac">
            <input class="lx-tab-input" type="radio" name="lx-tab" id="lx-t-book">

            <div class="lx-tabs" role="group" aria-label="{{ __('landing.v2.hero.tabs_label') }}">
                <label for="lx-t-med"><x-landing-icon name="medicine" :size="18" />{{ __('landing.v2.hero.tab_medicine') }}</label>
                <label for="lx-t-blood"><x-landing-icon name="blood" :size="18" />{{ __('landing.v2.hero.tab_blood') }}</label>
                <label for="lx-t-fac"><x-landing-icon name="facility" :size="18" />{{ __('landing.v2.hero.tab_facility') }}</label>
                <label for="lx-t-book"><x-landing-icon name="calendar" :size="18" />{{ __('landing.v2.hero.tab_book') }}</label>
            </div>

            <div class="lx-panels">
                <form class="lx-panel lx-panel--med" method="get" action="{{ route('public.network.medicine-finder') }}" role="search">
                    <label class="lx-sr" for="lx-q-med">{{ __('landing.v2.hero.medicine_label') }}</label>
                    <span class="lx-field">
                        <x-landing-icon name="search" :size="20" />
                        <input id="lx-q-med" name="q" type="search" autocomplete="off" placeholder="{{ __('landing.v2.hero.medicine_ph') }}" required>
                    </span>
                    <button class="lx-btn lx-btn--primary lx-btn--lg" type="submit">{{ __('landing.v2.hero.medicine_btn') }}</button>
                </form>

                <form class="lx-panel lx-panel--blood" method="get" action="{{ route('public.network.blood-finder') }}">
                    <label class="lx-sr" for="lx-q-blood">{{ __('landing.v2.hero.blood_label') }}</label>
                    <span class="lx-field">
                        <x-landing-icon name="blood" :size="20" />
                        <select id="lx-q-blood" name="group" required>
                            <option value="" disabled selected>{{ __('landing.v2.hero.blood_label') }}</option>
                            @foreach ($lxGroups as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </select>
                    </span>
                    <button class="lx-btn lx-btn--primary lx-btn--lg" type="submit">{{ __('landing.v2.hero.blood_btn') }}</button>
                </form>

                <form class="lx-panel lx-panel--fac" method="get" action="{{ route('public.care-map') }}" role="search">
                    <label class="lx-sr" for="lx-q-fac">{{ __('landing.v2.hero.facility_label') }}</label>
                    <span class="lx-field">
                        <x-landing-icon name="search" :size="20" />
                        <input id="lx-q-fac" name="query" type="search" autocomplete="off" placeholder="{{ __('landing.v2.hero.facility_ph') }}">
                    </span>
                    <button class="lx-btn lx-btn--primary lx-btn--lg" type="submit">{{ __('landing.v2.hero.facility_btn') }}</button>
                </form>

                <form class="lx-panel lx-panel--book" method="get" action="{{ route('public.care-map') }}" role="search">
                    <label class="lx-sr" for="lx-q-book">{{ __('landing.v2.hero.book_label') }}</label>
                    <span class="lx-field">
                        <x-landing-icon name="calendar" :size="20" />
                        <input id="lx-q-book" name="query" type="search" autocomplete="off" placeholder="{{ __('landing.v2.hero.book_ph') }}">
                    </span>
                    <button class="lx-btn lx-btn--primary lx-btn--lg" type="submit">{{ __('landing.v2.hero.book_btn') }}</button>
                </form>
            </div>
        </div>

        <p class="lx-trust">{{ __('landing.v2.hero.trust', ['count' => $lxCount]) }}</p>
    </div>
</section>

{{-- 3 · Health ID: the real layered credential + the three protections ──
     The card mirrors opescare-finder-mobile SecureCredentialCard.tsx (frame
     → texture/mesh/DNA rail → corners → brand row → white face → foot →
     seal → microtext → edge rail). Identity is a neutral placeholder: grey
     bars, "CM-•••• ••••", no person. --}}
<section class="lx-section lx-section--white" aria-labelledby="lx-hid-title">
    <div class="lx-wrap lx-split">
        <div class="lx-split__text">
            <h2 id="lx-hid-title" class="lx-h2">{{ __('landing.v2.hid.title') }}</h2>
            <p class="lx-lead">{{ __('landing.v2.hid.lead') }}</p>
            <ul class="lx-points" role="list">
                <li><x-landing-icon name="qr" :size="22" /><span>{{ __('landing.v2.hid.b1') }}</span></li>
                <li><x-landing-icon name="consent" :size="22" /><span>{{ __('landing.v2.hid.b2') }}</span></li>
                <li><x-landing-icon name="health-id" :size="22" /><span>{{ __('landing.v2.hid.b3') }}</span></li>
            </ul>
            <div class="lx-protect">
                <h3 class="lx-h4">{{ __('landing.v2.hid.sec_label') }}</h3>
                <p>{{ __('landing.v2.hid.sec_1') }} · {{ __('landing.v2.hid.sec_2') }} · {{ __('landing.v2.hid.sec_3') }}</p>
                <a class="lx-more" href="{{ route('public.security') }}">{{ __('landing.v2.hid.sec_cta') }} <x-landing-icon name="arrow-right" :size="16" /></a>
            </div>
            <div class="lx-actions">
                <a class="lx-btn lx-btn--primary" href="{{ route('register.patient') }}">{{ __('landing.v2.hid.cta') }}</a>
                <a class="lx-btn lx-btn--ghost" href="{{ route('verify.health-id') }}">{{ __('landing.v2.hid.verify') }}</a>
            </div>
        </div>

        <figure class="lx-split__visual lx-cardstage" role="img" aria-label="{{ __('landing.v2.hid.card_alt') }}">
            <div class="hc" aria-hidden="true">
                <div class="hc__sub">
                    <img class="hc__fill hc__o45" src="{{ asset('images/health-id/guilloche-texture.webp') }}" alt="" loading="lazy">
                    <img class="hc__fill hc__o55" src="{{ asset('images/health-id/guilloche-mesh.webp') }}" alt="" loading="lazy">
                    <img class="hc__helix" src="{{ asset('images/health-id/dna-helix.webp') }}" alt="" loading="lazy">
                    <img class="hc__micro" src="{{ asset('images/health-id/dna-microdata.webp') }}" alt="" loading="lazy">
                </div>
                <span class="hc__corner hc__corner--tl"></span><span class="hc__corner hc__corner--tr"></span>
                <span class="hc__corner hc__corner--bl"></span><span class="hc__corner hc__corner--br"></span>

                <div class="hc__top">
                    <div class="hc__brand">
                        <span class="hc__logo">
                            <svg width="20" height="20" viewBox="0 0 34 34"><circle cx="17" cy="17" r="15.5" fill="none" stroke="#0F4C81" stroke-width="2.6"/><path d="M17 9.5v15M9.5 17h15" stroke="#0F4C81" stroke-width="3.4" stroke-linecap="round"/></svg>
                        </span>
                        <span>
                            <span class="hc__word">Opes<b>Care</b></span>
                            <span class="hc__brandsub">{{ __('landing.v2.card.brand_sub') }}</span>
                        </span>
                    </div>
                    <div class="hc__tag">
                        <span>{{ __('landing.v2.card.tagline') }}</span>
                        <img src="{{ asset('images/health-id/africa.webp') }}" alt="" width="34" height="36" loading="lazy">
                    </div>
                </div>

                <div class="hc__face">
                    <div class="hc__holder">
                        <span class="hc__avatar"></span>
                        <span class="hc__holdertext">
                            <span class="hc__bar hc__bar--name"></span>
                            <span class="hc__label">{{ __('landing.v2.card.label') }}</span>
                        </span>
                        <span class="hc__badge"><x-landing-icon name="shield" :size="12" />{{ __('landing.v2.card.verified') }}</span>
                    </div>
                    <div>
                        <span class="hc__label">{{ __('landing.v2.card.health_id') }}</span>
                        <span class="hc__id">CM-•••• ••••</span>
                    </div>
                    <div class="hc__body">
                        <div class="hc__fields">
                            <div><span class="hc__label">{{ __('landing.v2.card.dob') }}</span><span class="hc__bar"></span></div>
                            <div class="hc__row">
                                <div><span class="hc__label">{{ __('landing.v2.card.sex') }}</span><span class="hc__bar hc__bar--s"></span></div>
                                <div><span class="hc__label">{{ __('landing.v2.card.blood') }}</span><span class="hc__bar hc__bar--s"></span></div>
                            </div>
                            <img class="hc__ecg" src="{{ asset('images/health-id/ecg-line.webp') }}" alt="" loading="lazy">
                        </div>
                        <div class="hc__qrcol">
                            <span class="hc__qr">
                                <img class="hc__qrframe" src="{{ asset('images/health-id/qr-frame.webp') }}" alt="" loading="lazy">
                                <svg class="hc__qrcode" viewBox="0 0 21 21" shape-rendering="crispEdges">
                                    @foreach ([[0,0],[14,0],[0,14]] as [$fx, $fy])
                                        <rect x="{{ $fx }}" y="{{ $fy }}" width="7" height="7" fill="#001665"/>
                                        <rect x="{{ $fx + 1 }}" y="{{ $fy + 1 }}" width="5" height="5" fill="#fff"/>
                                        <rect x="{{ $fx + 2 }}" y="{{ $fy + 2 }}" width="3" height="3" fill="#001665"/>
                                    @endforeach
                                    @foreach ($lxQr as [$qx, $qy])
                                        <rect x="{{ $qx }}" y="{{ $qy }}" width="1" height="1" fill="#001665"/>
                                    @endforeach
                                </svg>
                            </span>
                            <span class="hc__scan">{{ __('landing.v2.card.scan') }}</span>
                        </div>
                    </div>
                </div>

                <div class="hc__foot">
                    <img class="hc__chip" src="{{ asset('images/health-id/chip.webp') }}" alt="" loading="lazy">
                    <span class="hc__footcopy">
                        <span class="hc__foot1">{{ __('landing.v2.card.foot_1') }}</span>
                        <span class="hc__foot2">{{ __('landing.v2.card.foot_2') }}</span>
                    </span>
                </div>
                <span class="hc__seal">
                    <img class="hc__ring" src="{{ asset('images/health-id/holo-ring.webp') }}" alt="" loading="lazy">
                    <img class="hc__sealimg" src="{{ asset('images/health-id/seal.webp') }}" alt="" loading="lazy">
                    <img class="hc__shield" src="{{ asset('images/health-id/verification-shield.webp') }}" alt="" loading="lazy">
                </span>
                <span class="hc__microtext">{{ __('landing.v2.card.microtext') }}</span>
                <span class="hc__edge">{{ __('landing.v2.card.edge') }}</span>
            </div>
        </figure>
    </div>
</section>

{{-- 4 · One network: three facility columns + one thin flow line ───────── --}}
<section id="lx-network" class="lx-section" aria-labelledby="lx-net-title">
    <div class="lx-wrap">
        <div class="lx-head">
            <h2 id="lx-net-title" class="lx-h2">{{ __('landing.v2.network.title') }}</h2>
            <p class="lx-lead">{{ __('landing.v2.network.lead') }}</p>
        </div>

        <ul class="lx-cols" role="list">
            @foreach ([
                ['medicine', 'pharmacy', route('public.solutions.pharmacies')],
                ['flask', 'lab', route('public.solutions.laboratories')],
                ['facility', 'hospital', route('public.solutions.hospitals')],
            ] as [$icon, $key, $href])
                <li class="lx-col">
                    <x-landing-icon :name="$icon" :size="24" />
                    <h3 class="lx-h3">{{ __('landing.v2.network.' . $key . '_title') }}</h3>
                    <p>{{ __('landing.v2.network.' . $key . '_desc') }}</p>
                    <a class="lx-more" href="{{ $href }}">{{ __('landing.v2.network.learn') }} <x-landing-icon name="arrow-right" :size="16" /></a>
                </li>
            @endforeach
        </ul>

        <ol class="lx-flowline" role="list" aria-label="{{ __('landing.v2.network.flow_label') }}">
            <li class="lx-flowline__node">{{ __('landing.v2.network.flow_from') }}</li>
            <li class="lx-flowline__edge">{{ __('landing.v2.network.flow_out') }}</li>
            <li class="lx-flowline__node lx-flowline__node--hub">OPES</li>
            <li class="lx-flowline__edge">{{ __('landing.v2.network.flow_back') }}</li>
            <li class="lx-flowline__node">{{ __('landing.v2.network.flow_to') }}</li>
        </ol>

        <p class="lx-api">
            <x-landing-icon name="api" :size="22" />
            <span>{{ __('landing.v2.network.api') }}</span>
            <a class="lx-more" href="{{ route('docs.index') }}">{{ __('landing.v2.network.api_cta') }} <x-landing-icon name="arrow-right" :size="16" /></a>
        </p>
    </div>
</section>

{{-- 5 · Partners CTA ───────────────────────────────────────────────────── --}}
<section class="lx-section lx-section--tight" aria-labelledby="lx-partner-title">
    <div class="lx-wrap">
        <div class="lx-band">
            <div>
                <h2 id="lx-partner-title" class="lx-h2">{{ __('landing.v2.partners.title') }}</h2>
                <p>{{ __('landing.v2.partners.lead') }}</p>
            </div>
            <div class="lx-actions">
                <a class="lx-btn lx-btn--light lx-btn--lg" href="{{ route('register.organization') }}">{{ __('landing.v2.partners.cta') }}</a>
                <a class="lx-btn lx-btn--outline-light lx-btn--lg" href="{{ route('public.contact') }}">{{ __('landing.v2.partners.secondary') }}</a>
            </div>
        </div>
    </div>
</section>

</main>

{{-- 10 · Footer ────────────────────────────────────────────────────────── --}}
<footer class="lx-footer">
    <div class="lx-wrap">
        <div class="lx-footer__grid">
            <div>
                <a class="lx-brand lx-brand--footer" href="{{ route('public.landing') }}" aria-label="{{ __('landing.v2.nav.home') }}">
                    <svg class="lx-brand__mark" width="30" height="30" viewBox="0 0 34 34" aria-hidden="true" focusable="false">
                        <circle cx="17" cy="17" r="15.5" fill="none" stroke="currentColor" stroke-width="2.2"/>
                        <path d="M17 9.5v15M9.5 17h15" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
                        <circle cx="17" cy="17" r="3.4" class="lx-brand__dot"/>
                    </svg>
                    <span class="lx-brand__word">OPES</span>
                    <span class="lx-brand__sub">Finder</span>
                </a>
                <p class="lx-footer__tagline">{{ __('landing.v2.footer.tagline') }}</p>
            </div>
            <nav aria-labelledby="lx-f1">
                <h2 id="lx-f1" class="lx-footer__title">{{ __('landing.v2.footer.product') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('public.network.medicine-finder') }}">{{ __('landing.v2.nav.medicine') }}</a></li>
                    <li><a href="{{ route('public.network.blood-finder') }}">{{ __('landing.v2.nav.blood') }}</a></li>
                    <li><a href="{{ route('public.care-map') }}">{{ __('landing.v2.nav.facilities') }}</a></li>
                    <li><a href="{{ route('public.care-map.emergency') }}">{{ __('landing.v2.footer.emergency') }}</a></li>
                    <li><a href="{{ route('verify.health-id') }}">{{ __('landing.v2.hid.verify') }}</a></li>
                    <li class="lx-footer__note">{{ __('landing.v2.footer.pay') }}</li>
                </ul>
            </nav>
            <nav aria-labelledby="lx-f2">
                <h2 id="lx-f2" class="lx-footer__title">{{ __('landing.v2.footer.facilities') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('public.solutions.pharmacies') }}">{{ __('landing.v2.footer.pharmacies') }}</a></li>
                    <li><a href="{{ route('public.solutions.laboratories') }}">{{ __('landing.v2.footer.labs') }}</a></li>
                    <li><a href="{{ route('public.solutions.hospitals') }}">{{ __('landing.v2.footer.hospitals') }}</a></li>
                    <li><a href="{{ route('register.organization') }}">{{ __('landing.v2.footer.register') }}</a></li>
                    <li><a href="{{ route('docs.index') }}">{{ __('landing.v2.footer.docs') }}</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="lx-f3">
                <h2 id="lx-f3" class="lx-footer__title">{{ __('landing.v2.footer.legal') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('public.terms') }}">{{ __('landing.v2.footer.terms') }}</a></li>
                    <li><a href="{{ route('public.privacy') }}">{{ __('landing.v2.footer.privacy') }}</a></li>
                    <li><a href="{{ route('public.consent') }}">{{ __('landing.v2.footer.consent') }}</a></li>
                    <li><a href="{{ route('public.security') }}">{{ __('landing.v2.footer.security') }}</a></li>
                    <li><a href="{{ route('public.contact') }}">{{ __('landing.v2.footer.contact') }}</a></li>
                </ul>
            </nav>
        </div>
        <div class="lx-footer__meta">
            <p>{{ __('landing.v2.footer.copyright', ['year' => date('Y')]) }}</p>
            <p class="lx-footer__lang">
                <x-landing-icon name="globe" :size="16" />
                <a href="{{ route('lang.switch', 'en') }}" lang="en" hreflang="en">English</a>
                <span aria-hidden="true">·</span>
                <a href="{{ route('lang.switch', 'fr') }}" lang="fr" hreflang="fr">Français</a>
            </p>
        </div>
    </div>
</footer>

</body>
</html>
