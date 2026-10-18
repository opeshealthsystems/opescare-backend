@extends('layouts.finder')

@section('title', __('finder_story.how.page_title'))
@section('description', __('finder_story.how.meta_description'))

@push('head')
    @include('public.partials._od_pages_css')
@endpush

@section('content')

    {{--
        A patient walkthrough of the three finders plus booking — each one as
        three concrete steps, ending with the honest-limits box. The limits are
        not small print: stock is pharmacy-reported, listings are not vetted,
        and the page says "call ahead" wherever a trip could be wasted.

        Styling: layouts.finder contract only; the <style> block is layout glue.
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
        .fs-tool-head { display: flex; align-items: center; gap: 0.625rem; }
        .fs-tool-head > svg { color: var(--f-brand); flex-shrink: 0; }
        .fs-limit { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.375rem 0; }
        .fs-limit > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-warn); }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-note { display: flex; align-items: flex-start; gap: 0.625rem; }
        .fs-note > svg { flex-shrink: 0; margin-top: 0.2rem; }
    </style>

    {{-- 01 · Hero --}}
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow">{{ __('finder_story.how.eyebrow') }}</p>
                <h1 class="f-h1">{{ __('finder_story.how.title') }}</h1>
                <p class="f-lead">{{ __('finder_story.how.lead') }}</p>
            </div>
        </div>
    </section>

    {{-- 02 · The three finders + booking, each in three steps --}}
    <section class="f-section">
        <div class="f-container">
            <div class="f-grid">

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        @include('partials.finder.icon', ['name' => 'pill', 'size' => 22])
                        {{ __('finder_story.how.medicine_title') }}
                    </h2>
                    <ol class="fs-steps">
                        <li><span>{{ __('finder_story.how.medicine_step1') }}</span></li>
                        <li><span>{{ __('finder_story.how.medicine_step2') }}</span></li>
                        <li><span>{{ __('finder_story.how.medicine_step3') }}</span></li>
                    </ol>
                    <p class="f-small f-muted fs-note">
                        @include('partials.finder.icon', ['name' => 'info', 'size' => 16])
                        <span>{{ __('finder_story.how.medicine_note') }}</span>
                    </p>
                    <a class="f-btn f-btn--ghost" href="{{ route('public.network.medicine-finder') }}">
                        {{ __('finder_story.cta.medicine') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        @include('partials.finder.icon', ['name' => 'droplet', 'size' => 22])
                        {{ __('finder_story.how.blood_title') }}
                    </h2>
                    <ol class="fs-steps">
                        <li><span>{{ __('finder_story.how.blood_step1') }}</span></li>
                        <li><span>{{ __('finder_story.how.blood_step2') }}</span></li>
                        <li><span>{{ __('finder_story.how.blood_step3') }}</span></li>
                    </ol>
                    <a class="f-btn f-btn--ghost" href="{{ route('public.network.blood-finder') }}">
                        {{ __('finder_story.cta.blood') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 22])
                        {{ __('finder_story.how.facility_title') }}
                    </h2>
                    <ol class="fs-steps">
                        <li><span>{{ __('finder_story.how.facility_step1') }}</span></li>
                        <li><span>{{ __('finder_story.how.facility_step2') }}</span></li>
                        <li><span>{{ __('finder_story.how.facility_step3') }}</span></li>
                    </ol>
                    <a class="f-btn f-btn--ghost" href="{{ route('public.care-map') }}">
                        {{ __('finder_story.cta.directory') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>

                <div class="f-card">
                    <h2 class="fs-tool-head">
                        @include('partials.finder.icon', ['name' => 'calendar', 'size' => 22])
                        {{ __('finder_story.how.booking_title') }}
                    </h2>
                    <ol class="fs-steps">
                        <li><span>{{ __('finder_story.how.booking_step1') }}</span></li>
                        <li><span>{{ __('finder_story.how.booking_step2') }}</span></li>
                        <li><span>{{ __('finder_story.how.booking_step3') }}</span></li>
                    </ol>
                    <a class="f-btn f-btn--ghost" href="{{ route('public.care-map') }}">
                        {{ __('finder_shell.nav.book_appointment') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>

            </div>
        </div>
    </section>

    {{-- 03 · The honest-limits box --}}
    <section class="f-section f-section--wash f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2">{{ __('finder_story.how.limits_title') }}</h2>
                <p class="f-lead">{{ __('finder_story.how.limits_lead') }}</p>
                <div class="f-card">
                    <p class="fs-limit">
                        @include('partials.finder.icon', ['name' => 'phone', 'size' => 18])
                        <span>{{ __('finder_story.how.limit_1') }}</span>
                    </p>
                    <p class="fs-limit">
                        @include('partials.finder.icon', ['name' => 'clock', 'size' => 18])
                        <span>{{ __('finder_story.how.limit_2') }}</span>
                    </p>
                    <p class="fs-limit">
                        @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                        <span>{{ __('finder_story.how.limit_3') }}</span>
                    </p>
                    <p class="fs-limit">
                        @include('partials.finder.icon', ['name' => 'alert-circle', 'size' => 18])
                        <span>{{ __('finder_story.how.limit_4') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- 04 · Try it --}}
    <section class="f-section f-section--tight">
        <div class="f-container">
            <h2 class="f-h2">{{ __('finder_story.how.cta_title') }}</h2>
            @include('public.partials._finder_ctas', ['primary' => 'medicine'])
        </div>
    </section>

@endsection
