@extends('layouts.finder')

@section('title', __('finder_story.patients.page_title'))
@section('description', __('finder_story.patients.meta_description'))

@section('content')

    {{--
        A short signpost: patients ARE the product's audience, so this page
        does not pitch — it routes. The three finders, booking, and the one
        honest habit (call ahead).
    --}}
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .fs-note { display: flex; align-items: flex-start; gap: 0.625rem; }
        .fs-note > svg { flex-shrink: 0; margin-top: 0.2rem; color: var(--f-warn); }
    </style>

    <section class="f-section f-section--surface">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow">{{ __('finder_story.patients.eyebrow') }}</p>
                <h1 class="f-h1">{{ __('finder_story.patients.title') }}</h1>
                <p class="f-lead">{{ __('finder_story.patients.lead') }}</p>
                @include('public.partials._finder_ctas', ['primary' => 'medicine'])
            </div>
        </div>
    </section>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="fs-note">
                    @include('partials.finder.icon', ['name' => 'phone', 'size' => 18])
                    <span>{{ __('finder_story.patients.honest_p') }}</span>
                </p>
            </div>
        </div>
    </section>

@endsection
