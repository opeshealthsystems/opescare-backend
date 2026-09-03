@extends('layouts.finder')

@section('title', __('public.legal_page.page_title'))
@section('description', __('public.legal_page.hero_subtitle'))

@section('content')

    {{--
        Shell restyle only: this page was a standalone HTML document with its
        own purple-accented styles; it now lives inside layouts.finder. The
        wording (public.legal_page.* keys and the document list) is untouched.
    --}}
    <style>
        .fs-narrow { max-width: 860px; }
        .fs-legal-card { display: flex; align-items: center; justify-content: space-between; gap: 1rem; text-decoration: none; margin-bottom: 0.75rem; }
        .fs-legal-card__title { font-weight: 700; }
        .fs-legal-card__meta { font-size: 0.8125rem; color: var(--f-muted); margin-top: 3px; }
        .fs-legal-card > svg { flex-shrink: 0; color: var(--f-brand); }
    </style>

    <section class="f-section f-section--surface f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <h1 class="f-h1">{{ __('public.legal_page.hero_title') }}</h1>
                <p class="f-lead">{{ __('public.legal_page.hero_subtitle') }}</p>
            </div>
        </div>
    </section>

    <section class="f-section f-section--tight">
        <div class="f-container">
            <div class="fs-narrow">
                <p class="f-small f-muted">{{ __('public.legal_page.intro') }}</p>

                @forelse($documents as $doc)
                    @php $ver = $doc->versions->first(); @endphp
                    <a href="{{ route('public.legal.show', $doc->slug) }}" class="f-card fs-legal-card">
                        <div>
                            <div class="fs-legal-card__title">{{ $doc->title }}</div>
                            <div class="fs-legal-card__meta">
                                @if($ver)
                                    {{ __('public.legal_page.version_label') }} {{ $ver->version }}
                                    @if($ver->effective_at)
                                        · {{ __('public.legal_page.effective_label') }} {{ $ver->effective_at->format('d F Y') }}
                                    @endif
                                @else
                                    {{ __('public.legal_page.no_version') }}
                                @endif
                            </div>
                        </div>
                        @include('partials.finder.icon', ['name' => 'chevron-right', 'size' => 18])
                    </a>
                @empty
                    <div class="f-card f-muted" style="text-align:center;">
                        {{ __('public.legal_page.no_docs') }}
                    </div>
                @endforelse

                <div class="f-card f-small" style="margin-top:1.5rem;">
                    <strong>{{ __('public.legal_page.questions') }}</strong> {{ __('public.legal_page.contact_privacy') }}
                    <a href="mailto:privacy@opescare.com">privacy@opescare.com</a>
                    {{ __('public.legal_page.or_visit') }} <a href="{{ route('public.help') }}">{{ __('public.legal_page.help_centre') }}</a>.
                </div>
            </div>
        </div>
    </section>

@endsection
