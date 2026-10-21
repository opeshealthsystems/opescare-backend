{{--
    OpesCare — public home page (v4, 2026-10-21, owner-approved design:
    docs/design/onboarding-2026-10-21/landing.html + landing-desktop.png).

    A standalone document with its own header and footer. Styling lives only
    in public/css/landing-home.css; the one script (public/js/landing-search.js)
    is external and nonce-tagged. Icons come from partials.finder.icon only.

    Rules honoured here:
      - every string via __('home.*'); EN/FR kept 1:1
      - no inline style=, no on*= handlers; mobile menu is <details>
      - the hero search works WITHOUT JavaScript: it is a GET form to the
        facility directory (care-map, ?query=); the Medicines / Blood tabs are
        plain links to their finder pages. With JS the tabs re-point the same
        form at medicine-finder (?q=) or blood-finder (?group=).
      - stats are real: active care_facilities (cached scalar, controller),
        10 regions (geography), systems = count(config('solutions.systems')),
        2 languages
      - the Health ID card and both panels are SAMPLE data, labelled as such
      - SEO head (canonical, hreflang, OG, JSON-LD) comes from the shared
        partials.finder.schema.site include, untouched.
--}}
@section('title', __('landing.v2.meta_title'))
@section('description', __('landing.v2.meta_desc', ['count' => \App\Support\LocaleNumber::format($stats['facilities'])]))
@php
    $hCount   = \App\Support\LocaleNumber::format($stats['facilities']);
    $hLocale  = app()->getLocale();
    $hSystems = (int) ($stats['systems'] ?? 0);
    $hLogo    = asset('brand/landing/opescare-logo-176.webp');
    $hApk     = false; // the APK lives behind /download (public.download), which explains and verifies it
    $hAppUrl  = route('public.download');
    $hHealthId = route('public.solutions.patients');
    $hIcon = fn (string $name, int $size = 20) => view('partials.finder.icon', ['name' => $name, 'size' => $size])->render();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $hLocale) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ __('landing.v2.meta_title') }} — OpesCare</title>
    <meta name="description" content="{{ __('landing.v2.meta_desc', ['count' => $hCount]) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preload" href="{{ asset('fonts/inter/inter-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/inter/inter-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/landing-home.css') }}?v={{ @filemtime(public_path('css/landing-home.css')) ?: '1' }}">
    @include('partials.finder.schema.site')
    @includeIf('partials.seo.entity-schema')
    <script src="{{ asset('js/landing-search.js') }}?v={{ @filemtime(public_path('js/landing-search.js')) ?: '1' }}" @cspNonce defer></script>
    <script src="{{ asset('js/landing-hero.js') }}?v={{ @filemtime(public_path('js/landing-hero.js')) ?: '1' }}" @cspNonce defer></script>
</head>
<body class="hm">

<a class="hm-skip" href="#hm-main">{{ __('home.skip') }}</a>

{{-- Header ─────────────────────────────────────────────────────────────── --}}
@include('public.partials._hm_header')

<main id="hm-main">

{{-- Hero ───────────────────────────────────────────────────────────────── --}}
<section class="hm-hero" aria-labelledby="hm-hero-title">
    <div class="hm-wrap hm-hero__grid">
        <div class="hm-hero__text">
            <span class="hm-kicker">{!! $hIcon('circle-check', 15) !!}<span data-hx-kicker>{{ __('home.hero.kicker') }}</span></span>
            <h1 id="hm-hero-title" data-hx-title>{{ __('home.hero.title_1') }}<br><span>{{ __('home.hero.title_2') }}</span></h1>
            <p class="hm-lead" data-hx-lead>{{ __('home.hero.lead') }}</p>

            <form class="hm-search" method="get" action="{{ route('public.care-map') }}" role="search"
                  aria-label="{{ __('home.hero.search_label') }}" data-hm-search>
                <div class="hm-search__tabs">
                    <a class="is-on" href="{{ route('public.care-map') }}" aria-current="true"
                       data-hm-tab="facilities" data-action="{{ route('public.care-map') }}" data-name="query"
                       data-placeholder="{{ __('home.hero.ph_facilities') }}">{{ __('home.hero.tab_facilities') }}</a>
                    <a href="{{ route('public.network.medicine-finder') }}"
                       data-hm-tab="medicines" data-action="{{ route('public.network.medicine-finder') }}" data-name="q"
                       data-placeholder="{{ __('home.hero.ph_medicines') }}">{{ __('home.hero.tab_medicines') }}</a>
                    <a href="{{ route('public.network.blood-finder') }}"
                       data-hm-tab="blood" data-action="{{ route('public.network.blood-finder') }}" data-name="group">{{ __('home.hero.tab_blood') }}</a>
                </div>
                <label class="hm-sr" for="hm-q">{{ __('home.hero.search_label') }}</label>
                <input id="hm-q" class="hm-search__input" type="search" name="query" placeholder="{{ __('home.hero.ph_facilities') }}" data-hm-input>
                <label class="hm-sr" for="hm-group">{{ __('home.hero.blood_group') }}</label>
                <select id="hm-group" class="hm-search__input" name="group" hidden disabled data-hm-group>
                    <option value="">{{ __('home.hero.any_group') }}</option>
                    @foreach (\App\Enums\BloodGroup::values() as $g)
                        <option value="{{ $g }}">{{ $g }}</option>
                    @endforeach
                </select>
                <button class="hm-btn hm-btn--sm hm-btn--primary" type="submit">{!! $hIcon('search', 17) !!}{{ __('home.hero.search') }}</button>
            </form>

            <ul class="hm-trust" role="list">
                <li>{!! $hIcon('shield-check', 17) !!}{{ __('home.hero.trust_consent') }}</li>
                <li>{!! $hIcon('languages', 17) !!}{{ __('home.hero.trust_languages') }}</li>
                <li>{!! $hIcon('cloud-off', 17) !!}{{ __('home.hero.trust_network') }}</li>
            </ul>
        </div>

        @php
            // Hero slides: the Health ID card, interoperability, master patient index.
            $hxSlides = collect(['id', 'interop', 'mpi'])->map(fn ($k) => [
                'k' => __("home.slides.$k.kicker"), 'a' => __("home.slides.$k.title_1"),
                'b' => __("home.slides.$k.title_2"), 'l' => __("home.slides.$k.lead"),
            ])->all();
            // The app's own living anatomy, turning through all four figures.
            $hxMeta = json_decode((string) @file_get_contents(public_path('images/health-id/living/meta.json')), true) ?: [];
            $hxFigures = collect(['adult-male', 'adult-female', 'child-boy', 'child-girl'])
                ->filter(fn ($f) => isset($hxMeta[$f]['heart']))
                ->map(fn ($f) => [
                    'base' => asset("images/health-id/living/$f-base.png"),
                    'current' => asset("images/health-id/living/$f-current.webp"),
                    'heart' => asset("images/health-id/living/$f-heart.png"),
                    'box' => $hxMeta[$f]['heart'],
                    'label' => __("home.card.figure_$f"),
                ])->values()->all();
            $hxFirst = $hxFigures[0] ?? null;
        @endphp
        <div class="hx-stage" data-hx-stage data-hx-slides="{{ json_encode($hxSlides, JSON_UNESCAPED_UNICODE) }}" data-hx-figures="{{ json_encode($hxFigures, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
            {{-- Slide 1: the Health ID card --}}
            <div class="hx-vis is-on" data-hx-vis>
            {{-- Merge: records from three facilities fold into the one Health ID (illustrative). --}}
            <div class="mg" data-hx-merge aria-hidden="true">
                <div class="mg-rec mg-r1"><small>{{ __('home.merge.r1_src') }}</small><b>Amara Dongmo</b><span>{{ __('home.merge.r1_fact') }}</span></div>
                <div class="mg-rec mg-r2"><small>{{ __('home.merge.r2_src') }}</small><b>Amara D. Dongmo</b><span>{{ __('home.merge.r2_fact') }}</span></div>
                <div class="mg-rec mg-r3"><small>{{ __('home.merge.r3_src') }}</small><b>DONGMO Amara</b><span>{{ __('home.merge.r3_fact') }}</span></div>
                <div class="mg-badge"><span class="mg-dot"></span>{{ __('home.merge.badge') }}</div>
            </div>
            <figure class="hm-card hx-card3" role="img" aria-label="{{ __('home.card.alt') }}">
                <div class="hx3-left" aria-hidden="true">
                    <div class="hm-card__t"><b>OpesCare</b><small>{{ __('home.card.sub') }}</small></div>
                    <div class="hx3-chiprow"><img class="hm-card__chip" src="{{ asset('images/health-id/chip.webp') }}" alt="" width="46" height="36"><span class="hm-card__sample">{{ __('home.card.sample') }}</span></div>
                    <div class="hm-card__num hx3-num">CM-HID 0000 0000 0000</div>
                    <div class="hm-card__row">
                        <div>{{ __('home.card.holder') }}<b>{{ __('home.card.holder_sample') }}</b></div>
                        <div>{{ __('home.card.issued') }}<b>00/0000</b></div>
                    </div>
                    <div class="hx3-tiles">
                        <div class="hx-t"><small>{!! $hIcon('droplet', 12) !!}{{ __('home.card.blood') }}</small><b>O+</b></div>
                        <div class="hx-t hx-t--alert"><small>{!! $hIcon('shield-alert', 12) !!}{{ __('home.card.allergy') }}</small><b>{{ __('home.card.allergy_sample') }}</b></div>
                        <div class="hx-t"><small>{!! $hIcon('activity', 12) !!}{{ __('home.card.condition') }}</small><b>{{ __('home.card.condition_sample') }}</b></div>
                        <div class="hx-t"><small>{!! $hIcon('phone', 12) !!}{{ __('home.card.emergency') }}</small><b>{{ __('home.card.emergency_sample') }}</b></div>
                    </div>
                    <div class="hx3-foot">
                        <span class="hx3-qr">{!! \App\Support\QrSvg::brandedSvg(url('/verify/health-id')) !!}<img src="{{ asset('images/health-id/seal.webp') }}" alt="" width="22" height="22"></span>
                        <span class="hx3-qrnote">{{ __('home.card.qr_note') }}</span>
                    </div>
                </div>
                @if($hxFirst)
                <div class="hx3-fig" aria-hidden="true">
                    <div class="hx3-spin" data-hx-spin>
                        @foreach(['front', 'back'] as $hxFace)
                        <div class="hx3-face hx3-{{ $hxFace }}">
                            <img class="hx3-base" src="{{ $hxFirst['base'] }}" alt="">
                            <span class="hx3-current" style="background-image:url('{{ $hxFirst['current'] }}')"></span>
                            <img class="hx3-heart" src="{{ $hxFirst['heart'] }}" alt=""
                                 style="left:{{ $hxFirst['box']['x'] * 100 }}%;top:{{ $hxFirst['box']['y'] * 100 }}%;width:{{ $hxFirst['box']['w'] * 100 }}%;height:{{ $hxFirst['box']['h'] * 100 }}%">
                        </div>
                        @endforeach
                    </div>
                    <span class="hx3-view" data-hx-view>{{ $hxFirst['label'] }}</span>
                </div>
                @endif
            </figure>
            </div>

            {{-- Slide 2: interoperability --}}
            <div class="hx-vis" data-hx-vis aria-hidden="true"><div class="hx-net">
                <svg class="hx-lines" viewBox="0 0 440 390"><path d="M95 62 L220 195"/><path d="M345 62 L220 195"/><path d="M95 328 L220 195"/><path d="M345 328 L220 195"/></svg>
                <div class="hx-core">{!! $hIcon('share-2', 22) !!}<b>{{ __('home.hx_interop.core') }}</b><small>{{ __('home.hx_interop.core_sub') }}</small></div>
                <div class="hx-node hx-n1">{!! $hIcon('building-2', 20) !!}<div><b>{{ __('home.hx_interop.hospital') }}</b><small>{{ __('home.hx_interop.hospital_sub') }}</small></div></div>
                <div class="hx-node hx-n2">{!! $hIcon('stethoscope', 20) !!}<div><b>{{ __('home.hx_interop.clinic') }}</b><small>{{ __('home.hx_interop.clinic_sub') }}</small></div></div>
                <div class="hx-node hx-n3">{!! $hIcon('flask-conical', 20) !!}<div><b>{{ __('home.hx_interop.lab') }}</b><small>{{ __('home.hx_interop.lab_sub') }}</small></div></div>
                <div class="hx-node hx-n4">{!! $hIcon('pill', 20) !!}<div><b>{{ __('home.hx_interop.pharmacy') }}</b><small>{{ __('home.hx_interop.pharmacy_sub') }}</small></div></div>
            </div></div>

            {{-- Slide 3: master patient index (illustrative sample records) --}}
            <div class="hx-vis" data-hx-vis aria-hidden="true"><div class="hx-mpi">
                <div class="hx-recs">
                    <div class="hx-rec"><header>{{ __('home.hx_mpi.rec1_src') }}</header><b>Amara Dongmo</b><span>F · 12/03/1991</span></div>
                    <div class="hx-rec"><header>{{ __('home.hx_mpi.rec2_src') }}</header><b>Amara D. Dongmo</b><span>F · 12/03/1991</span></div>
                    <div class="hx-rec"><header>{{ __('home.hx_mpi.rec3_src') }}</header><b>DONGMO Amara</b><span>F · 1991</span></div>
                </div>
                <div class="hx-arrow">{!! $hIcon('git-merge', 22) !!}</div>
                <div class="hx-gold"><small>{!! $hIcon('user-check', 14) !!}{{ __('home.hx_mpi.one') }}</small><b>Amara Dongmo</b><span class="hx-id">CM-HID 0000 0000 0000</span>
                    <dl>
                        <div><dt>{{ __('home.hx_mpi.linked') }}</dt><dd>3</dd></div>
                        <div><dt>{{ __('home.hx_mpi.facilities') }}</dt><dd>3</dd></div>
                        <div><dt>{{ __('home.hx_mpi.reviewed') }}</dt><dd>{{ __('home.hx_mpi.reviewed_v') }}</dd></div>
                        <div><dt>{{ __('home.hx_mpi.merge') }}</dt><dd>{{ __('home.hx_mpi.merge_v') }}</dd></div>
                    </dl>
                </div>
            </div></div>
        </div>
    </div>
</section>

{{-- Stats ──────────────────────────────────────────────────────────────── --}}
<section class="hm-stats" aria-label="{{ __('home.stats.label') }}">
    <dl class="hm-wrap hm-stats__grid">
        <div class="hm-stat"><dt>{{ __('home.stats.facilities') }}</dt><dd>{{ $hCount }}</dd></div>
        <div class="hm-stat"><dt>{{ __('home.stats.regions') }}</dt><dd>{{ \App\Support\LocaleNumber::format($stats['regions']) }}</dd></div>
        <div class="hm-stat"><dt>{{ __('home.stats.systems') }}</dt><dd>{{ \App\Support\LocaleNumber::format($hSystems) }}</dd></div>
        <div class="hm-stat"><dt>{{ __('home.stats.languages') }}</dt><dd>{{ $stats['languages'] ?? 2 }}</dd></div>
    </dl>
</section>

{{-- Services ───────────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="hm-svc-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('home.services.eyebrow') }}</p>
            <h2 id="hm-svc-title">{{ __('home.services.title') }}</h2>
            <p class="hm-sub">{{ __('home.services.sub') }}</p>
        </div>
        <div class="hm-grid4">
            @foreach ([
                ['pill', 'medicine', route('public.network.medicine-finder'), ''],
                ['droplet', 'blood', route('public.network.blood-finder'), 'hm-ic--red'],
                ['calendar-plus', 'appointments', route('public.care-map'), ''],
                ['map-pin', 'directory', route('public.care-map.places'), ''],
            ] as [$icon, $key, $href, $mod])
                <a class="hm-svc" href="{{ $href }}">
                    <span class="hm-ic {{ $mod }}">{!! $hIcon($icon, 20) !!}</span>
                    <h3>{{ __('home.services.' . $key . '_title') }}</h3>
                    <p>{{ __('home.services.' . $key . '_desc') }}</p>
                    <span class="hm-go">{{ __('home.services.' . $key . '_cta') }}{!! $hIcon('arrow-right', 16) !!}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- For patients ───────────────────────────────────────────────────────── --}}
<section class="hm-s hm-s--alt" aria-labelledby="hm-pat-title">
    <div class="hm-wrap hm-split">
        <div>
            <p class="hm-eyebrow">{{ __('home.patients.eyebrow') }}</p>
            <h2 id="hm-pat-title">{{ __('home.patients.title') }}</h2>
            <p class="hm-sub">{{ __('home.patients.sub') }}</p>
            <ul class="hm-list" role="list">
                @foreach ([['key-round', 'b1'], ['file-check', 'b2'], ['users', 'b3']] as [$icon, $k])
                    <li><span class="hm-ic hm-ic--good">{!! $hIcon($icon, 18) !!}</span><div><b>{{ __('home.patients.' . $k . '_title') }}</b><span>{{ __('home.patients.' . $k . '_desc') }}</span></div></li>
                @endforeach
            </ul>
            <a class="hm-btn hm-btn--primary" href="{{ $hAppUrl }}" @if($hApk) download="opescare.apk" @endif>{!! $hIcon('smartphone', 18) !!}{{ __('home.patients.cta') }}</a>
        </div>
        <figure class="hm-panel" role="img" aria-label="{{ __('home.patients.panel_label') }}">
            <div class="hm-panel__ph" aria-hidden="true"><i></i><i></i><i></i>{{ __('home.patients.panel_title') }}</div>
            <div class="hm-kpis" aria-hidden="true">
                <div class="hm-kpi"><small>{{ __('home.patients.kpi_visit') }}</small><b>{{ __('home.patients.kpi_visit_val') }}</b></div>
                <div class="hm-kpi"><small>{{ __('home.patients.kpi_results') }}</small><b>{{ __('home.patients.kpi_results_val') }}</b></div>
                <div class="hm-kpi"><small>{{ __('home.patients.kpi_access') }}</small><b>1</b></div>
            </div>
            <div class="hm-rows" aria-hidden="true">
                <div><span>{{ __('home.patients.row1') }}</span><span class="hm-pill hm-pill--b">{{ __('home.patients.row1_pill') }}</span></div>
                <div><span>{{ __('home.patients.row2') }}</span><span class="hm-pill hm-pill--g">{{ __('home.patients.row2_pill') }}</span></div>
                <div><span>{{ __('home.patients.row3') }}</span><span class="hm-muted">{{ __('home.patients.row3_state') }}</span></div>
            </div>
        </figure>
    </div>
</section>

{{-- For facilities ─────────────────────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="hm-fac-title">
    <div class="hm-wrap hm-split hm-split--rev">
        <figure class="hm-panel" role="img" aria-label="{{ __('home.facilities.panel_label') }}">
            <div class="hm-panel__ph" aria-hidden="true"><i></i><i></i><i></i>{{ __('home.facilities.panel_title') }}</div>
            <div class="hm-kpis" aria-hidden="true">
                <div class="hm-kpi"><small>{{ __('home.facilities.kpi_waiting') }}</small><b>14</b></div>
                <div class="hm-kpi"><small>{{ __('home.facilities.kpi_seen') }}</small><b>62</b></div>
                <div class="hm-kpi"><small>{{ __('home.facilities.kpi_results') }}</small><b>5</b></div>
            </div>
            <div class="hm-rows" aria-hidden="true">
                <div><span>{{ __('home.facilities.row1') }}</span><span class="hm-pill hm-pill--r">{{ __('home.facilities.row1_pill') }}</span></div>
                <div><span>{{ __('home.facilities.row2') }}</span><span class="hm-pill hm-pill--b">{{ __('home.facilities.row2_pill') }}</span></div>
                <div><span>{{ __('home.facilities.row3') }}</span><span class="hm-pill hm-pill--g">{{ __('home.facilities.row3_pill') }}</span></div>
            </div>
        </figure>
        <div>
            <p class="hm-eyebrow">{{ __('home.facilities.eyebrow') }}</p>
            <h2 id="hm-fac-title">{{ __('home.facilities.title') }}</h2>
            <p class="hm-sub">{{ __('home.facilities.sub') }}</p>
            <ul class="hm-list" role="list">
                @foreach ([['map-pin', 'b1'], ['network', 'b2'], ['gift', 'b3']] as [$icon, $k])
                    <li><span class="hm-ic hm-ic--good">{!! $hIcon($icon, 18) !!}</span><div><b>{{ __('home.facilities.' . $k . '_title') }}</b><span>{{ __('home.facilities.' . $k . '_desc') }}</span></div></li>
                @endforeach
            </ul>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--primary" href="{{ route('register.organization') }}">{!! $hIcon('building-2', 18) !!}{{ __('home.facilities.cta_register') }}</a>
                <a class="hm-btn" href="{{ route('public.solutions.index') }}">{{ __('home.facilities.cta_systems', ['count' => $hSystems]) }}</a>
            </div>
        </div>
    </div>
</section>

{{-- Interoperability ───────────────────────────────────────────────────── --}}
<section class="hm-s hm-s--alt" aria-labelledby="hm-int-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('home.interop.eyebrow') }}</p>
            <h2 id="hm-int-title">{{ __('home.interop.title') }}</h2>
            <p class="hm-sub">{{ __('home.interop.sub') }}</p>
        </div>
        <ol class="hm-flow" role="list" aria-label="{{ __('home.interop.label') }}">
            <li class="hm-node"><span class="hm-ic">{!! $hIcon('stethoscope', 20) !!}</span><b>{{ __('home.interop.clinic') }}</b><span>{{ __('home.interop.clinic_desc') }}</span></li>
            <li class="hm-arrow" aria-hidden="true">{!! $hIcon('arrow-right', 20) !!}</li>
            <li class="hm-node hm-node--hub"><span class="hm-ic">{!! $hIcon('shield-check', 20) !!}</span><b>{{ __('home.interop.layer') }}</b><span>{{ __('home.interop.layer_desc') }}</span></li>
            <li class="hm-arrow" aria-hidden="true">{!! $hIcon('arrow-right', 20) !!}</li>
            <li class="hm-node"><span class="hm-ic">{!! $hIcon('building-2', 20) !!}</span><b>{{ __('home.interop.hospital') }}</b><span>{{ __('home.interop.hospital_desc') }}</span></li>
        </ol>
        <div class="hm-actions hm-more">
            <a class="hm-btn" href="{{ route('public.interoperability') }}">{{ __('home.interop.learn_more') }}{!! $hIcon('arrow-right', 16) !!}</a>
            <a class="hm-btn" href="{{ route('public.master-patient-index') }}">{{ __('home.interop.mpi_more') }}{!! $hIcon('arrow-right', 16) !!}</a>
        </div>
    </div>
</section>

{{-- Live in four steps + CTA band ──────────────────────────────────────── --}}
<section class="hm-s" aria-labelledby="hm-steps-title">
    <div class="hm-wrap">
        <div class="hm-head">
            <p class="hm-eyebrow">{{ __('home.steps.eyebrow') }}</p>
            <h2 id="hm-steps-title">{{ __('home.steps.title') }}</h2>
        </div>
        <ol class="hm-steps" role="list">
            @foreach (['register', 'verify', 'activate', 'live'] as $i => $key)
                <li class="hm-step"><span class="hm-step__n" aria-hidden="true">0{{ $i + 1 }}</span><b>{{ __('home.steps.' . $key . '_title') }}</b><span>{{ __('home.steps.' . $key . '_desc') }}</span></li>
            @endforeach
        </ol>

        <div class="hm-cta">
            <div>
                <h2>{{ __('home.cta.title') }}</h2>
                <p>{{ __('home.cta.sub') }}</p>
            </div>
            <div class="hm-actions">
                <a class="hm-btn hm-btn--light" href="{{ route('public.contact') }}">{{ __('home.cta.talk') }}</a>
                <a class="hm-btn hm-btn--white" href="{{ route('register.organization') }}">{{ __('home.facilities.cta_register') }}</a>
            </div>
        </div>
    </div>
</section>

</main>

{{-- Footer ─────────────────────────────────────────────────────────────── --}}
@include('public.partials._hm_footer')

</body>
</html>
