@extends('layouts.finder')

@section('title', $document->title)
@section('description', $document->title)

@section('content')

    {{--
        Shell restyle only: this page was a standalone HTML document with its
        own purple-accented styles; it now lives inside layouts.finder. The
        rendered legal document ($currentVersion->content_html) and its
        metadata are untouched.
    --}}
    <style>
        .fs-narrow { max-width: 800px; }
        .fs-legal-back { display: inline-flex; align-items: center; gap: 0.375rem; text-decoration: none; font-size: 0.875rem; }
        .fs-legal-meta { display: flex; flex-wrap: wrap; gap: 1rem 1.5rem; font-size: 0.8125rem; margin-bottom: 1.5rem; }
        .fs-legal-meta__item { color: var(--f-muted); }
        .fs-legal-meta__item strong { color: var(--f-ink); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
        .fs-legal-body { line-height: 1.7; }
        .fs-legal-body h1, .fs-legal-body h2, .fs-legal-body h3 { color: var(--f-brand-ink); }
        .fs-legal-body table { border-collapse: collapse; width: 100%; }
        .fs-legal-body th, .fs-legal-body td { border: 1px solid var(--f-line); padding: 0.5rem 0.75rem; text-align: left; }
        .fs-no-content { text-align: center; padding: 3rem 1rem; color: var(--f-muted); }
    </style>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p>
                    <a class="fs-legal-back" href="{{ route('public.legal') }}">
                        @include('partials.finder.icon', ['name' => 'chevron-left', 'size' => 16])
                        Legal Centre
                    </a>
                </p>

                <h1 class="f-h2">{{ $document->title }}</h1>

                @if($currentVersion)
                    <div class="f-card fs-legal-meta">
                        <div class="fs-legal-meta__item"><strong>Version</strong>{{ $currentVersion->version }}</div>
                        @if($currentVersion->effective_at)
                            <div class="fs-legal-meta__item"><strong>Effective</strong>{{ $currentVersion->effective_at->format('d F Y') }}</div>
                        @endif
                        @if($currentVersion->published_at)
                            <div class="fs-legal-meta__item"><strong>Published</strong>{{ $currentVersion->published_at->format('d F Y') }}</div>
                        @endif
                        <div class="fs-legal-meta__item"><strong>Language</strong>{{ strtoupper($document->language) }}</div>
                    </div>

                    <div class="f-card fs-legal-body">
                        {!! $currentVersion->content_html !!}
                    </div>
                @else
                    <div class="f-card fs-no-content">
                        <p>This document has not been published yet.</p>
                        <a href="{{ route('public.legal') }}">Legal Centre</a>
                    </div>
                @endif
            </div>
        </div>
    </section>

@endsection
