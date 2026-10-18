@extends('layouts.finder')

@section('title', __('finder_story.about.page_title'))
@section('description', __('finder_story.about.meta_description'))

@push('head')
    @include('public.partials._od_pages_css')
@endpush

@section('content')

    {{--
        The about page answers three questions a careful visitor asks:
        what is this, where does the data really come from, and who is it for.
        The data story is told honestly — registry, OpenStreetMap, and what
        facilities report themselves — because "listed" and "verified" are
        different claims, and this page is where the difference is explained.

        Styling: layouts.finder contract only (.f-* + --f-* tokens). The
        <style> block is layout glue — flex/gap/alignment, no colors of its own.
    --}}
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-source { display: flex; align-items: flex-start; gap: 0.875rem; }
        .fs-source > svg { flex-shrink: 0; margin-top: 0.25rem; color: var(--f-brand); }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-note { display: flex; align-items: flex-start; gap: 0.625rem; }
        .fs-note > svg { flex-shrink: 0; margin-top: 0.2rem; }
    </style>

    {{-- 01 · What Finder is --}}
    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow">{{ __('finder_story.about.eyebrow') }}</p>
                <h1 class="f-h1">{{ __('finder_story.about.title') }}</h1>
                <p class="f-lead">{{ __('finder_story.about.lead') }}</p>
            </div>
        </div>
    </section>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2">{{ __('finder_story.about.what_title') }}</h2>
                <p>{{ __('finder_story.about.what_p1') }}</p>
                <p>{{ __('finder_story.about.what_p2') }}</p>

                <h2 class="f-h2">{{ __('finder_story.about.free_title') }}</h2>
                <p>{{ __('finder_story.about.free_p') }}</p>
            </div>
        </div>
    </section>

    {{-- 02 · The data story, told honestly --}}
    <section class="f-section f-section--wash">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2">{{ __('finder_story.about.data_title') }}</h2>
                <p class="f-lead">{{ __('finder_story.about.data_lead') }}</p>
            </div>

            <div class="f-grid">
                <div class="f-card fs-source">
                    @include('partials.finder.icon', ['name' => 'building-2', 'size' => 22])
                    <div>
                        <h3>{{ __('finder_story.about.data_registry_title') }}</h3>
                        <p class="f-muted">{{ __('finder_story.about.data_registry_p') }}</p>
                    </div>
                </div>
                <div class="f-card fs-source">
                    @include('partials.finder.icon', ['name' => 'map-pin', 'size' => 22])
                    <div>
                        <h3>{{ __('finder_story.about.data_osm_title') }}</h3>
                        <p class="f-muted">{{ __('finder_story.about.data_osm_p') }}</p>
                    </div>
                </div>
                <div class="f-card fs-source">
                    @include('partials.finder.icon', ['name' => 'refresh-cw', 'size' => 22])
                    <div>
                        <h3>{{ __('finder_story.about.data_self_title') }}</h3>
                        <p class="f-muted">{{ __('finder_story.about.data_self_p') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 03 · What a listing is — and is not --}}
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2">{{ __('finder_story.about.honest_title') }}</h2>
                <p class="fs-note">
                    @include('partials.finder.icon', ['name' => 'info', 'size' => 18])
                    <span>{{ __('finder_story.about.honest_p1') }}</span>
                </p>
                <p class="fs-note">
                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 18])
                    <span>{{ __('finder_story.about.honest_p2') }}</span>
                </p>
            </div>
        </div>
    </section>

    {{-- 04 · Who it is for --}}
    <section class="f-section f-section--surface">
        <div class="f-container">
            <h2 class="f-h2">{{ __('finder_story.about.who_title') }}</h2>
            <div class="f-grid">
                <div class="f-card">
                    @include('partials.finder.icon', ['name' => 'heart-pulse', 'size' => 22])
                    <h3>{{ __('finder_story.about.who_patients_title') }}</h3>
                    <p class="f-muted">{{ __('finder_story.about.who_patients_p') }}</p>
                </div>
                <div class="f-card">
                    @include('partials.finder.icon', ['name' => 'users', 'size' => 22])
                    <h3>{{ __('finder_story.about.who_caregivers_title') }}</h3>
                    <p class="f-muted">{{ __('finder_story.about.who_caregivers_p') }}</p>
                </div>
                <div class="f-card">
                    @include('partials.finder.icon', ['name' => 'building-2', 'size' => 22])
                    <h3>{{ __('finder_story.about.who_facilities_title') }}</h3>
                    <p class="f-muted">{{ __('finder_story.about.who_facilities_p') }}</p>
                    <a class="f-btn f-btn--ghost" href="{{ route('public.solutions.pharmacies') }}">
                        {{ __('finder_story.about.who_facilities_cta') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- 05 · Start --}}
    <section class="f-section f-section--tight">
        <div class="f-container">
            <h2 class="f-h2">{{ __('finder_story.about.cta_title') }}</h2>
            @include('public.partials._finder_ctas', ['primary' => 'medicine'])
        </div>
    </section>

@endsection
