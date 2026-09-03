@extends('layouts.finder')

@section('title', __('finder_story.hospitals.page_title'))
@section('description', __('finder_story.hospitals.meta_description'))

@section('content')

    {{--
        The facility-side pitch for hospitals and clinics: claim the listing,
        report blood availability, take bookings — all free, all on Finder.
        Institutions that came looking for the full OpesCare platform (records,
        interoperability, APIs) get an honest pointer to opescare.cloud instead
        of hospital-sales copy on a consumer product.
    --}}
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-steps { list-style: none; padding: 0; margin: 0 0 0.75rem; counter-reset: fs-step; }
        .fs-steps li { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.375rem 0; }
        .fs-steps li::before {
            counter-increment: fs-step;
            content: counter(fs-step);
            flex-shrink: 0;
            width: 1.75rem; height: 1.75rem;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: var(--f-wash); color: var(--f-brand-ink);
            font-weight: 600; font-size: 0.875rem;
        }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-benefit > svg { color: var(--f-brand); }
        .fs-platform-cta { display: inline-flex; align-items: center; gap: 0.5rem; }
    </style>

    {{-- 01 · Hero --}}
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow">{{ __('finder_story.hospitals.eyebrow') }}</p>
                <h1 class="f-h1">{{ __('finder_story.hospitals.title') }}</h1>
                <p class="f-lead">{{ __('finder_story.hospitals.lead') }}</p>
                <div class="fs-cta-row">
                    <a class="f-btn f-btn--primary" href="{{ route('public.care-map') }}">
                        {{ __('finder_story.hospitals.cta_primary') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                    <a class="f-btn" href="{{ route('register') }}">{{ __('finder_story.hospitals.cta_secondary') }}</a>
                </div>
            </div>
        </div>
    </section>

    {{-- 02 · What a claimed listing does --}}
    <section class="f-section">
        <div class="f-container">
            <div class="f-grid">
                <div class="f-card fs-benefit">
                    @include('partials.finder.icon', ['name' => 'building-2', 'size' => 22])
                    <h2>{{ __('finder_story.hospitals.listing_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.hospitals.listing_p') }}</p>
                </div>
                <div class="f-card fs-benefit">
                    @include('partials.finder.icon', ['name' => 'droplet', 'size' => 22])
                    <h2>{{ __('finder_story.hospitals.blood_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.hospitals.blood_p') }}</p>
                </div>
                <div class="f-card fs-benefit">
                    @include('partials.finder.icon', ['name' => 'calendar', 'size' => 22])
                    <h2>{{ __('finder_story.hospitals.booking_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.hospitals.booking_p') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 03 · How to claim --}}
    <section class="f-section f-section--wash f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2">{{ __('finder_story.hospitals.steps_title') }}</h2>
                <ol class="fs-steps">
                    <li><span>{{ __('finder_story.hospitals.step1') }}</span></li>
                    <li><span>{{ __('finder_story.hospitals.step2') }}</span></li>
                    <li><span>{{ __('finder_story.hospitals.step3') }}</span></li>
                    <li><span>{{ __('finder_story.hospitals.step4') }}</span></li>
                </ol>
            </div>
        </div>
    </section>

    {{-- 04 · The platform, honestly signposted --}}
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <div class="f-card">
                    <h2>{{ __('finder_story.hospitals.platform_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.hospitals.platform_p') }}</p>
                    <a class="f-btn fs-platform-cta" href="https://opescare.cloud" rel="noopener">
                        {{ __('finder_story.hospitals.platform_cta') }}
                        @include('partials.finder.icon', ['name' => 'external-link', 'size' => 16])
                    </a>
                </div>
            </div>
        </div>
    </section>

@endsection
