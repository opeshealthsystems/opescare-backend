@extends('layouts.finder')

@section('title', __('finder_home.page_title'))
@section('description', __('finder_home.page_desc', ['count' => number_format($stats['facilities'])]))

@section('content')

    {{--
        The homepage is the task, not a pitch. A visitor on a phone with a need
        (a medicine, blood, an appointment) must be able to act within one
        screen and one second of paint: no images above the fold, no JS
        required, three big tap targets that go straight to the directory.

        Every destination below is a verified route:
          - /care-map?facility_type=pharmacy  → server-side filter (557 pharmacies)
          - /care-map?facility_type=hospital  → hospitals hold blood services
            (there is no blood_bank facility_type in the registry data)
          - /care-map                         → browse everything, book or call
        The directory does NOT support a ?region= query, so the region chips
        link to the plain directory — no dead parameters.

        Styling: composed entirely from the layouts.finder CSS contract
        (.f-* classes and --f-* tokens). The <style> block below is layout
        glue only — flex/gap/alignment plus currentColor/opacity — it defines
        no colors, fonts or sizes of its own.
    --}}

    <style>
        .fh-actions { display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.25rem; }
        .fh-action { display: flex; align-items: center; gap: 0.875rem; text-decoration: none; }
        .fh-action-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.125rem; }
        .fh-action > svg:last-child, .fh-action > .f-icon:last-child { opacity: 0.6; flex-shrink: 0; }
        .fh-trust-list { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 0.625rem 1.5rem; }
        .fh-trust-list li { display: flex; align-items: flex-start; gap: 0.5rem; }
        .fh-trust-list li svg { flex-shrink: 0; margin-top: 0.125rem; }
        .fh-step-no { display: inline-flex; align-items: center; gap: 0.5rem; font-variant-numeric: tabular-nums; }
        .fh-limit { display: flex; align-items: flex-start; gap: 0.5rem; }
        .fh-limit svg { flex-shrink: 0; margin-top: 0.125rem; }
        .fh-card-cta { display: inline-flex; align-items: center; gap: 0.375rem; }
        .fh-chips { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .fh-claim { display: flex; flex-direction: column; gap: 0.75rem; align-items: flex-start; }
    </style>

    {{-- 01 ─────────────────────────── Hero: the task, three tap targets --}}
    <section class="f-section">
        <div class="f-container">
            <p class="f-eyebrow">{{ __('finder_home.hero.eyebrow') }}</p>
            <h1 class="f-h1">{{ __('finder_home.hero.title') }}</h1>
            <p class="f-lead">{{ __('finder_home.hero.lead') }}</p>

            <nav class="fh-actions" aria-label="{{ __('finder_home.hero.nav_label') }}">
                <a class="f-card fh-action" href="{{ route('public.network.medicine-finder') }}">
                    @include('partials.finder.icon', ['name' => 'pill', 'size' => 24])
                    <span class="fh-action-text">
                        <strong>{{ __('finder_home.hero.medicine_label') }}</strong>
                        <small class="f-muted">{{ __('finder_home.hero.medicine_sub') }}</small>
                    </span>
                    @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 20])
                </a>

                <a class="f-card fh-action" href="{{ route('public.network.blood-finder') }}">
                    @include('partials.finder.icon', ['name' => 'droplet', 'size' => 24])
                    <span class="fh-action-text">
                        <strong>{{ __('finder_home.hero.blood_label') }}</strong>
                        <small class="f-muted">{{ __('finder_home.hero.blood_sub') }}</small>
                    </span>
                    @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 20])
                </a>

                <a class="f-card fh-action" href="{{ route('public.care-map') }}">
                    @include('partials.finder.icon', ['name' => 'calendar', 'size' => 24])
                    <span class="fh-action-text">
                        <strong>{{ __('finder_home.hero.appointment_label') }}</strong>
                        <small class="f-muted">{{ __('finder_home.hero.appointment_sub') }}</small>
                    </span>
                    @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 20])
                </a>
            </nav>
        </div>
    </section>

    {{-- 02 ──────────────────────────────────── Trust strip: facts only --}}
    <section class="f-section">
        <div class="f-container">
            <ul class="fh-trust-list">
                <li>
                    @include('partials.finder.icon', ['name' => 'building-2', 'size' => 18])
                    <span>{{ __('finder_home.trust.facilities', ['count' => number_format($stats['facilities'])]) }}</span>
                </li>
                <li>
                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 18])
                    <span>{{ __('finder_home.trust.regions', ['count' => $stats['regions']]) }}</span>
                </li>
                <li>
                    @include('partials.finder.icon', ['name' => 'globe', 'size' => 18])
                    <span>{{ __('finder_home.trust.bilingual') }}</span>
                </li>
                <li>
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 18])
                    <span>{{ __('finder_home.trust.free') }}</span>
                </li>
            </ul>
        </div>
    </section>

    {{-- 03 ─────────────────────── How it works: search → call/book → go --}}
    <section class="f-section">
        <div class="f-container">
            <p class="f-eyebrow">{{ __('finder_home.how.eyebrow') }}</p>
            <h2 class="f-h2">{{ __('finder_home.how.title') }}</h2>

            <div class="f-grid">
                <div class="f-card">
                    <p class="fh-step-no">
                        @include('partials.finder.icon', ['name' => 'search', 'size' => 20])
                        <span>1</span>
                    </p>
                    <h3>{{ __('finder_home.how.step1_title') }}</h3>
                    <p>{{ __('finder_home.how.step1_desc') }}</p>
                </div>
                <div class="f-card">
                    <p class="fh-step-no">
                        @include('partials.finder.icon', ['name' => 'phone', 'size' => 20])
                        <span>2</span>
                    </p>
                    <h3>{{ __('finder_home.how.step2_title') }}</h3>
                    <p>{{ __('finder_home.how.step2_desc') }}</p>
                </div>
                <div class="f-card">
                    <p class="fh-step-no">
                        @include('partials.finder.icon', ['name' => 'navigation', 'size' => 20])
                        <span>3</span>
                    </p>
                    <h3>{{ __('finder_home.how.step3_title') }}</h3>
                    <p>{{ __('finder_home.how.step3_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 04 ─────────── The three finders: what each does, honest limits --}}
    <section class="f-section">
        <div class="f-container">
            <p class="f-eyebrow">{{ __('finder_home.finders.eyebrow') }}</p>
            <h2 class="f-h2">{{ __('finder_home.finders.title') }}</h2>

            <div class="f-grid">
                <div class="f-card">
                    @include('partials.finder.icon', ['name' => 'pill', 'size' => 22])
                    <h3>{{ __('finder_home.finders.medicine_title') }}</h3>
                    <p>{{ __('finder_home.finders.medicine_desc') }}</p>
                    <p class="fh-limit f-muted f-small">
                        @include('partials.finder.icon', ['name' => 'clock', 'size' => 16])
                        <span>{{ __('finder_home.finders.medicine_limit') }}</span>
                    </p>
                    <a class="f-btn f-btn--ghost fh-card-cta" href="{{ route('public.network.medicine-finder') }}">
                        {{ __('finder_home.finders.medicine_cta') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>

                <div class="f-card">
                    @include('partials.finder.icon', ['name' => 'droplet', 'size' => 22])
                    <h3>{{ __('finder_home.finders.blood_title') }}</h3>
                    <p>{{ __('finder_home.finders.blood_desc') }}</p>
                    <p class="fh-limit f-muted f-small">
                        @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
                        <span>{{ __('finder_home.finders.blood_limit') }}</span>
                    </p>
                    <a class="f-btn f-btn--ghost fh-card-cta" href="{{ route('public.network.blood-finder') }}">
                        {{ __('finder_home.finders.blood_cta') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>

                <div class="f-card">
                    @include('partials.finder.icon', ['name' => 'calendar', 'size' => 22])
                    <h3>{{ __('finder_home.finders.appointment_title') }}</h3>
                    <p>{{ __('finder_home.finders.appointment_desc') }}</p>
                    <p class="fh-limit f-muted f-small">
                        @include('partials.finder.icon', ['name' => 'phone', 'size' => 16])
                        <span>{{ __('finder_home.finders.appointment_limit') }}</span>
                    </p>
                    <a class="f-btn f-btn--ghost fh-card-cta" href="{{ route('public.care-map') }}">
                        {{ __('finder_home.finders.appointment_cta') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- 05 ───────────────────── Facility owners: claim your listing.
         The claim flow starts on the facility's own profile page
         (/care-map/facility/{id}/claim), so the CTA sends owners to the
         directory to find their facility first — there is no id-less
         claim entry point, by design. --}}
    <section class="f-section">
        <div class="f-container">
            <div class="f-card fh-claim">
                @include('partials.finder.icon', ['name' => 'building-2', 'size' => 22])
                <h2 class="f-h2">{{ __('finder_home.claim.title') }}</h2>
                <p>{{ __('finder_home.claim.desc') }}</p>
                <p class="fh-limit f-muted f-small">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 16])
                    <span>{{ __('finder_home.claim.hint') }}</span>
                </p>
                <a class="f-btn f-btn--primary fh-card-cta" href="{{ route('public.care-map') }}">
                    {{ __('finder_home.claim.cta') }}
                    @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                </a>
            </div>
        </div>
    </section>

    {{-- 06 ──────────────────────────────────── Coverage: the 10 regions.
         The directory has no ?region= filter (CareMapSearchService reads
         facility_type/query/city only), so these chips link to the plain
         directory rather than carrying a parameter nothing reads. --}}
    <section class="f-section">
        <div class="f-container">
            <h2 class="f-h2">{{ __('finder_home.coverage.title', ['count' => $stats['regions']]) }}</h2>
            <p class="f-lead">{{ __('finder_home.coverage.desc') }}</p>

            <div class="fh-chips">
                @foreach (__('finder_home.coverage.regions') as $region)
                    <a class="f-chip" href="{{ route('public.care-map') }}">{{ $region }}</a>
                @endforeach
            </div>
        </div>
    </section>

@endsection
