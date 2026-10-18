{{--
    OpesCare — public landing page (v3, 2026-10-18, hospital pilot).

    A standalone document: its own header and footer. Styling lives only in
    public/css/landing-v2.css. The hero matches the 2026-10-18 login look:
    public/brand/login/background.webp under a white veil (AA contrast), the
    OpesCare logo (a 176px webp derived from public/brand/opescare-logo.png),
    brand #0F4C81, small-caps taglines and a four-icon row. Never purple.

    Rules honoured here:
      - every string via __('landing.v2.*'); EN/FR kept 1:1
      - no inline style=, no on*= handlers, no scripts (mobile menu is <details>)
      - claims are only what the code ships: Health ID + QR verification,
        consent-gated record access with an access log, open REST API and
        FHIR R4 *ingest* for partners (routes/fhir.php). No FHIR server,
        HL7 v2, video consultations, insurance or certifications.
      - "How the pilot works" mirrors the real path: register.organization
        records a Lead with a reference -> an OpesCare admin converts it
        (LeadConversionService: facility + directory link + invite) -> the
        contact activates via invite.accept -> facility admin invites staff
        (portals.facility.team.invite) -> staff verify Health IDs and request
        consent before opening a record.
      - SEO head (canonical, hreflang, OG, JSON-LD) comes from the shared
        partials.finder.schema.site include, untouched.
--}}
@section('title', __('landing.v2.meta_title'))
@section('description', __('landing.v2.meta_desc', ['count' => number_format($stats['facilities'])]))
@php
    $lxCount  = number_format($stats['facilities']);
    $lxLocale = app()->getLocale();

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
    $lxLogo = asset('brand/landing/opescare-logo-176.webp');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $lxLocale) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ __('landing.v2.meta_title') }} — OpesCare</title>
    <meta name="description" content="{{ __('landing.v2.meta_desc', ['count' => $lxCount]) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preload" href="{{ asset('fonts/inter/inter-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/inter/inter-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('brand/login/background.webp') }}" as="image" type="image/webp">
    <link rel="stylesheet" href="{{ asset('css/landing-v2.css') }}?v={{ @filemtime(public_path('css/landing-v2.css')) ?: '1' }}">
    @include('partials.finder.schema.site')
</head>
<body class="lx">

<a class="lx-skip" href="#lx-main">{{ __('landing.v2.skip') }}</a>

{{-- 1 · Header ─────────────────────────────────────────────────────────── --}}
<header class="lx-header">
    <div class="lx-wrap lx-header__row">
        <a class="lx-brand" href="{{ route('public.landing') }}" aria-label="{{ __('landing.v2.nav.home') }}">
            <img class="lx-brand__mark" src="{{ $lxLogo }}" alt="" width="38" height="35">
            <span class="lx-brand__word">OPES<b>CARE</b></span>
        </a>

        <nav class="lx-nav" aria-label="{{ __('landing.v2.nav.label') }}">
            <a href="#lx-hid">{{ __('landing.v2.nav.health_id') }}</a>
            <a href="#lx-flow">{{ __('landing.v2.nav.interop') }}</a>
            <a href="#lx-pilot">{{ __('landing.v2.nav.pilot') }}</a>
            <a href="#lx-who">{{ __('landing.v2.nav.who') }}</a>
        </nav>

        <div class="lx-header__actions">
            <div class="lx-lang" role="group" aria-label="{{ __('landing.v2.nav.language') }}">
                <a href="{{ route('lang.switch', 'en') }}" lang="en" hreflang="en" @if($lxLocale === 'en') aria-current="true" @endif>EN</a>
                <a href="{{ route('lang.switch', 'fr') }}" lang="fr" hreflang="fr" @if($lxLocale === 'fr') aria-current="true" @endif>FR</a>
            </div>
            <a class="lx-link-quiet lx-hide-sm" href="{{ route('login') }}">{{ __('landing.v2.nav.sign_in') }}</a>
            <a class="lx-btn lx-btn--primary lx-hide-sm" href="{{ route('register.organization') }}">{{ __('landing.v2.cta_hospital') }}</a>

            <details class="lx-menu">
                <summary aria-label="{{ __('landing.v2.nav.menu') }}"><x-landing-icon name="menu" /></summary>
                <nav class="lx-menu__panel" aria-label="{{ __('landing.v2.nav.label') }}">
                    <a href="#lx-hid">{{ __('landing.v2.nav.health_id') }}</a>
                    <a href="#lx-flow">{{ __('landing.v2.nav.interop') }}</a>
                    <a href="#lx-pilot">{{ __('landing.v2.nav.pilot') }}</a>
                    <a href="#lx-who">{{ __('landing.v2.nav.who') }}</a>
                    <a href="{{ route('login') }}">{{ __('landing.v2.nav.sign_in') }}</a>
                    <a class="lx-btn lx-btn--primary" href="{{ route('register.organization') }}">{{ __('landing.v2.cta_hospital') }}</a>
                </nav>
            </details>
        </div>
    </div>
</header>

<main id="lx-main">

{{-- 2 · Hero: login look — background, logo, taglines, two CTAs, icon row --}}
<section class="lx-hero" aria-labelledby="lx-hero-title">
    <div class="lx-wrap lx-hero__inner">
        <p class="lx-caps lx-caps--line">{{ __('landing.v2.hero.caps_1') }}</p>

        <div class="lx-lockup">
            <img class="lx-lockup__logo" src="{{ $lxLogo }}" alt="" width="88" height="82">
            <div>
                <p class="lx-lockup__word" aria-hidden="true">OPES<b>CARE</b></p>
                <p class="lx-lockup__tag">{{ __('landing.v2.hero.tagline') }}</p>
            </div>
        </div>

        <h1 id="lx-hero-title" class="lx-h1">{{ __('landing.v2.hero.title') }}</h1>
        <p class="lx-lead">{{ __('landing.v2.hero.lead') }}</p>

        <div class="lx-actions">
            <a class="lx-btn lx-btn--primary lx-btn--lg" href="{{ route('register.organization') }}">
                <x-landing-icon name="hospital" :size="20" />{{ __('landing.v2.cta_hospital') }}
            </a>
            <a class="lx-btn lx-btn--ghost lx-btn--lg" href="{{ route('register.patient') }}">
                <x-landing-icon name="fingerprint" :size="20" />{{ __('landing.v2.cta_patient') }}
            </a>
        </div>

        <p class="lx-caps lx-caps--sub">{{ __('landing.v2.hero.caps_2') }}</p>

        <ul class="lx-pillars" role="list">
            @foreach ([['fingerprint', 'identity'], ['network', 'connected'], ['shield-check', 'trusted'], ['chart', 'communities']] as [$icon, $key])
                <li><x-landing-icon :name="$icon" :size="30" /><span>{{ __('landing.v2.hero.pillar_' . $key) }}</span></li>
            @endforeach
        </ul>
    </div>
</section>

{{-- 3 · Health ID ────────────────────────────────────────────────────────
     The card mirrors opescare-finder-mobile SecureCredentialCard.tsx. Identity
     is a neutral placeholder: grey bars, "CM-•••• ••••", no person. --}}
<section id="lx-hid" class="lx-section lx-section--white" aria-labelledby="lx-hid-title">
    <div class="lx-wrap lx-split">
        <div class="lx-split__text">
            <p class="lx-caps">{{ __('landing.v2.hid.kicker') }}</p>
            <h2 id="lx-hid-title" class="lx-h2">{{ __('landing.v2.hid.title') }}</h2>
            <p class="lx-lead">{{ __('landing.v2.hid.lead') }}</p>
            <ul class="lx-points" role="list">
                <li><x-landing-icon name="qr" :size="22" /><span>{{ __('landing.v2.hid.b1') }}</span></li>
                <li><x-landing-icon name="badge-check" :size="22" /><span>{{ __('landing.v2.hid.b3') }}</span></li>
            </ul>
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

{{-- 4 · How information moves (interoperability + consent) ─────────────── --}}
<section id="lx-flow" class="lx-section" aria-labelledby="lx-flow-title">
    <div class="lx-wrap">
        <div class="lx-head">
            <p class="lx-caps">{{ __('landing.v2.flow.kicker') }}</p>
            <h2 id="lx-flow-title" class="lx-h2">{{ __('landing.v2.flow.title') }}</h2>
            <p class="lx-lead">{{ __('landing.v2.flow.lead') }}</p>
        </div>

        <ol class="lx-flowline" role="list" aria-label="{{ __('landing.v2.flow.label') }}">
            <li class="lx-flowline__node">{{ __('landing.v2.flow.from') }}</li>
            <li class="lx-flowline__edge">{{ __('landing.v2.flow.out') }}</li>
            <li class="lx-flowline__node lx-flowline__node--hub">OPESCARE</li>
            <li class="lx-flowline__edge">{{ __('landing.v2.flow.back') }}</li>
            <li class="lx-flowline__node">{{ __('landing.v2.flow.to') }}</li>
        </ol>

        <ul class="lx-trio" role="list">
            <li>
                <x-landing-icon name="lock" :size="22" />
                <div><h3 class="lx-h4">{{ __('landing.v2.flow.consent_title') }}</h3><p>{{ __('landing.v2.flow.consent_desc') }}</p></div>
            </li>
            <li>
                <x-landing-icon name="history" :size="22" />
                <div><h3 class="lx-h4">{{ __('landing.v2.flow.log_title') }}</h3><p>{{ __('landing.v2.flow.log_desc') }}</p></div>
            </li>
            <li>
                <x-landing-icon name="code" :size="22" />
                <div>
                    <h3 class="lx-h4">{{ __('landing.v2.flow.api_title') }}</h3><p>{{ __('landing.v2.flow.api_desc') }}</p>
                    <a class="lx-more" href="{{ route('docs.index') }}">{{ __('landing.v2.flow.api_cta') }} <x-landing-icon name="arrow-right" :size="16" /></a>
                </div>
            </li>
        </ul>
    </div>
</section>

{{-- 5 · How the pilot works — only steps that exist in the code ────────── --}}
<section id="lx-pilot" class="lx-section lx-section--white" aria-labelledby="lx-pilot-title">
    <div class="lx-wrap">
        <div class="lx-head">
            <p class="lx-caps">{{ __('landing.v2.pilot.kicker') }}</p>
            <h2 id="lx-pilot-title" class="lx-h2">{{ __('landing.v2.pilot.title') }}</h2>
        </div>
        <ol class="lx-steps" role="list">
            @foreach ([['file-text', 'apply'], ['badge-check', 'verify'], ['mail', 'activate'], ['user-plus', 'team'], ['qr', 'start']] as $i => [$icon, $key])
                <li class="lx-step">
                    <span class="lx-step__top"><span class="lx-step__num" aria-hidden="true">{{ $i + 1 }}</span><x-landing-icon :name="$icon" :size="22" /></span>
                    <h3 class="lx-h4">{{ __('landing.v2.pilot.' . $key . '_title') }}</h3>
                    <p>{{ __('landing.v2.pilot.' . $key . '_desc') }}</p>
                </li>
            @endforeach
        </ol>
        <div class="lx-actions">
            <a class="lx-btn lx-btn--primary lx-btn--lg" href="{{ route('register.organization') }}">{{ __('landing.v2.cta_hospital') }}</a>
            <a class="lx-btn lx-btn--ghost lx-btn--lg" href="{{ route('public.contact') }}">{{ __('landing.v2.pilot.contact') }}</a>
        </div>
    </div>
</section>

{{-- 6 · Who it is for ──────────────────────────────────────────────────── --}}
<section id="lx-who" class="lx-section" aria-labelledby="lx-who-title">
    <div class="lx-wrap">
        <div class="lx-head">
            <h2 id="lx-who-title" class="lx-h2">{{ __('landing.v2.who.title') }}</h2>
        </div>
        <ul class="lx-cols" role="list">
            @foreach ([
                ['hospital', 'hospital', route('public.solutions.hospitals')],
                ['pill', 'pharmacy', route('public.solutions.pharmacies')],
                ['flask', 'lab', route('public.solutions.laboratories')],
                ['user', 'patient', route('register.patient')],
            ] as [$icon, $key, $href])
                <li class="lx-col">
                    <x-landing-icon :name="$icon" :size="26" />
                    <h3 class="lx-h3">{{ __('landing.v2.who.' . $key . '_title') }}</h3>
                    <p>{{ __('landing.v2.who.' . $key . '_desc') }}</p>
                    <a class="lx-more" href="{{ $href }}">{{ __('landing.v2.who.' . $key . '_cta') }} <x-landing-icon name="arrow-right" :size="16" /></a>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- 7 · Closing band ───────────────────────────────────────────────────── --}}
<section class="lx-section lx-section--tight" aria-labelledby="lx-partner-title">
    <div class="lx-wrap">
        <div class="lx-band">
            <div>
                <p class="lx-caps lx-caps--light">{{ __('landing.v2.hero.tagline') }}</p>
                <h2 id="lx-partner-title" class="lx-h2">{{ __('landing.v2.band.title') }}</h2>
            </div>
            <div class="lx-actions">
                <a class="lx-btn lx-btn--light lx-btn--lg" href="{{ route('register.organization') }}">{{ __('landing.v2.cta_hospital') }}</a>
                <a class="lx-btn lx-btn--outline-light lx-btn--lg" href="{{ route('register.patient') }}">{{ __('landing.v2.cta_patient') }}</a>
            </div>
        </div>
    </div>
</section>

</main>

{{-- 8 · Footer ─────────────────────────────────────────────────────────── --}}
<footer class="lx-footer">
    <div class="lx-wrap">
        <div class="lx-footer__grid">
            <div>
                <a class="lx-brand lx-brand--footer" href="{{ route('public.landing') }}" aria-label="{{ __('landing.v2.nav.home') }}">
                    <img class="lx-brand__mark" src="{{ $lxLogo }}" alt="" width="34" height="32" loading="lazy">
                    <span class="lx-brand__word">OPES<b>CARE</b></span>
                </a>
                <p class="lx-footer__tagline">{{ __('landing.v2.footer.tagline') }}</p>
            </div>
            <nav aria-labelledby="lx-f1">
                <h2 id="lx-f1" class="lx-footer__title">{{ __('landing.v2.footer.product') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('register.patient') }}">{{ __('landing.v2.cta_patient') }}</a></li>
                    <li><a href="{{ route('public.network.medicine-finder') }}">{{ __('landing.v2.footer.medicine') }}</a></li>
                    <li><a href="{{ route('public.network.blood-finder') }}">{{ __('landing.v2.footer.blood') }}</a></li>
                    <li><a href="{{ route('public.care-map') }}">{{ __('landing.v2.footer.directory') }}</a></li>
                    <li><a href="{{ route('public.care-map.emergency') }}">{{ __('landing.v2.footer.emergency') }}</a></li>
                </ul>
            </nav>
            <nav aria-labelledby="lx-f2">
                <h2 id="lx-f2" class="lx-footer__title">{{ __('landing.v2.footer.facilities') }}</h2>
                <ul role="list">
                    <li><a href="{{ route('register.organization') }}">{{ __('landing.v2.cta_hospital') }}</a></li>
                    <li><a href="{{ route('public.solutions.hospitals') }}">{{ __('landing.v2.footer.hospitals') }}</a></li>
                    <li><a href="{{ route('public.solutions.pharmacies') }}">{{ __('landing.v2.footer.pharmacies') }}</a></li>
                    <li><a href="{{ route('public.solutions.laboratories') }}">{{ __('landing.v2.footer.labs') }}</a></li>
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
