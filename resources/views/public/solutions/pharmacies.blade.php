@extends('layouts.finder')

@section('title', __('finder_story.pharmacies.page_title'))
@section('description', __('finder_story.pharmacies.meta_description'))

@section('content')

    {{--
        The "for pharmacies" claim-your-listing pitch. The brief asked for a
        new public/for-pharmacies page; this existing route IS that page —
        public.solutions.pharmacies is already wired into the finder shell's
        header nav and footer as "For pharmacies", so the content lives here
        rather than at a new, unrouted view.

        CTA targets are the real flows: the directory filtered to pharmacies
        (claims start from a facility's own page) and the free signup.
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
        .fs-note { display: flex; align-items: flex-start; gap: 0.625rem; }
        .fs-note > svg { flex-shrink: 0; margin-top: 0.2rem; }
        .fs-benefit > svg { color: var(--f-brand); }
    </style>

    {{-- 01 · Hero --}}
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow">{{ __('finder_story.pharmacies.eyebrow') }}</p>
                <h1 class="f-h1">{{ __('finder_story.pharmacies.title') }}</h1>
                <p class="f-lead">{{ __('finder_story.pharmacies.lead') }}</p>
                <div class="fs-cta-row">
                    <a class="f-btn f-btn--primary" href="{{ route('public.care-map', ['facility_type' => 'pharmacy']) }}">
                        {{ __('finder_story.pharmacies.cta_primary') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                    <a class="f-btn" href="{{ route('register') }}">{{ __('finder_story.pharmacies.cta_secondary') }}</a>
                </div>
            </div>
        </div>
    </section>

    {{-- 02 · What claiming gets you --}}
    <section class="f-section">
        <div class="f-container">
            <div class="f-grid">
                <div class="f-card fs-benefit">
                    @include('partials.finder.icon', ['name' => 'search', 'size' => 22])
                    <h2>{{ __('finder_story.pharmacies.found_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.pharmacies.found_p') }}</p>
                </div>
                <div class="f-card fs-benefit">
                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 22])
                    <h2>{{ __('finder_story.pharmacies.edit_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.pharmacies.edit_p') }}</p>
                </div>
                <div class="f-card fs-benefit">
                    @include('partials.finder.icon', ['name' => 'pill', 'size' => 22])
                    <h2>{{ __('finder_story.pharmacies.stock_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.pharmacies.stock_p') }}</p>
                </div>
                <div class="f-card fs-benefit">
                    @include('partials.finder.icon', ['name' => 'check', 'size' => 22])
                    <h2>{{ __('finder_story.pharmacies.free_title') }}</h2>
                    <p class="f-muted">{{ __('finder_story.pharmacies.free_p') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- 03 · How to claim --}}
    <section class="f-section f-section--wash f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2">{{ __('finder_story.pharmacies.steps_title') }}</h2>
                <ol class="fs-steps">
                    <li><span>{{ __('finder_story.pharmacies.step1') }}</span></li>
                    <li><span>{{ __('finder_story.pharmacies.step2') }}</span></li>
                    <li><span>{{ __('finder_story.pharmacies.step3') }}</span></li>
                    <li><span>{{ __('finder_story.pharmacies.step4') }}</span></li>
                </ol>
                <p class="f-small f-muted fs-note">
                    @include('partials.finder.icon', ['name' => 'info', 'size' => 16])
                    <span>{{ __('finder_story.pharmacies.note') }}</span>
                </p>
                <div class="fs-cta-row">
                    <a class="f-btn f-btn--primary" href="{{ route('public.care-map', ['facility_type' => 'pharmacy']) }}">
                        {{ __('finder_story.pharmacies.cta_primary') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                    <a class="f-btn f-btn--ghost" href="{{ route('public.contact') }}">{{ __('finder_shell.footer.contact') }}</a>
                </div>
            </div>
        </div>
    </section>

@endsection
