@extends('layouts.finder')

@section('title', __('finder_story.faq.page_title'))
@section('description', __('finder_story.faq.meta_description'))

{{--
    FAQPage schema, built from the same lang array the page renders so the
    markup can never drift from the visible answers. Answer engines lift Q&A
    pairs almost verbatim when they are marked up, and infer them unreliably
    when they are not. (tests/Feature/Seo/DiscoverabilityTest expects at least
    ten marked-up questions here.)
--}}
@push('schema')
{{-- FAQPage JSON-LD, built from the same lang array the page renders --}}
@include('partials.finder.schema.faq')
@endpush

@push('head')
    @include('public.partials._od_pages_css')
@endpush

@section('content')

    {{--
        Consumer questions, answered plainly and honestly — free, where the
        data comes from, listed-not-verified, how to report an error, how a
        pharmacy claims its listing, privacy.

        Each Q/A is one <details>/<summary> pair with an <h3> question — the
        clean semantic structure an SEO pass can wrap FAQPage markup around
        (and this view already emits that markup from the same array).
    --}}
    <style>
        .fs-narrow { max-width: 720px; }
        .fs-qa { border-bottom: 1px solid var(--f-line); }
        .fs-qa summary {
            display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
            padding: 1rem 0; cursor: pointer; list-style: none;
        }
        .fs-qa summary::-webkit-details-marker { display: none; }
        .fs-qa summary h3 { margin: 0; font-size: 1rem; }
        .fs-qa summary svg { flex-shrink: 0; transition: transform 0.15s ease; }
        .fs-qa[open] summary svg { transform: rotate(180deg); }
        .fs-qa > p { padding: 0 0 1rem; margin: 0; }
        .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
    </style>

    {{-- 01 · Hero --}}
    <section class="f-section f-section--surface f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-eyebrow">{{ __('finder_story.faq.eyebrow') }}</p>
                <h1 class="f-h1">{{ __('finder_story.faq.title') }}</h1>
                <p class="f-lead">{{ __('finder_story.faq.lead') }}</p>
            </div>
        </div>
    </section>

    {{-- 02 · The questions --}}
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                @foreach (__('finder_story.faq.items') as $fsItem)
                    <details class="fs-qa" @if ($loop->first) open @endif>
                        <summary>
                            <h3>{{ $fsItem['q'] }}</h3>
                            @include('partials.finder.icon', ['name' => 'chevron-down', 'size' => 18])
                        </summary>
                        <p class="f-muted">{{ $fsItem['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 03 · Still stuck --}}
    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h2 class="f-h2">{{ __('finder_story.faq.still_title') }}</h2>
                <p class="f-muted">{{ __('finder_story.faq.still_p') }}</p>
                <div class="fs-cta-row">
                    <a class="f-btn f-btn--primary" href="{{ route('public.contact') }}">
                        {{ __('finder_story.faq.still_cta') }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                    <a class="f-btn f-btn--ghost" href="{{ route('public.how-it-works') }}">{{ __('finder_story.cta.how') }}</a>
                </div>
            </div>
        </div>
    </section>

@endsection
