{{--
    Signpost page for legacy marketing routes (api pricing, pricing, security,
    SLA, request-demo, some solutions pages) that used to describe a separate
    platform. Since 2026-10-18 opescare.com IS the platform, so each route
    keeps rendering (old links and search results still resolve) and points
    the visitor at the opescare.com page that does the job today.

    Include with:
        'pageKey'     => key into finder_story.redirect.titles (required)
        'showPrivacy' => true to add the privacy pointer (security page)
--}}
@php
    // Primary destination per legacy page; every target is an opescare.com route.
    $primary = match ($pageKey) {
        'api_pricing', 'developers' => 'developers',
        'security', 'sla', 'pricing' => 'contact',
        default => 'request',
    };
    $targets = [
        'request'    => route('public.solutions.request'),
        'solutions'  => route('public.solutions.index'),
        'developers' => route('public.developers'),
        'docs'       => route('docs.index'),
        'contact'    => route('public.contact'),
        'status'     => route('public.status'),
    ];
    $secondary = array_values(array_diff(
        in_array($pageKey, ['api_pricing', 'developers'], true)
            ? ['docs', 'contact']
            : ($pageKey === 'sla' ? ['status', 'solutions'] : ['solutions', 'developers']),
        [$primary]
    ));
@endphp
<style>
    .fs-redirect { max-width: 640px; }
    .fs-redirect .f-card { margin-bottom: 1.25rem; }
    .fs-cta-row { display: flex; flex-wrap: wrap; gap: 0.625rem; }
    .fs-platform-cta { display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem; }
</style>

<section class="f-section">
    <div class="f-container">
        <div class="fs-redirect">
            <p class="f-eyebrow">{{ __('finder_story.redirect.titles.' . $pageKey) }}</p>
            <h1 class="f-h2">{{ __('finder_story.redirect.heading') }}</h1>
            <p class="f-lead">{{ __('finder_story.redirect.lead') }}</p>

            <div class="f-card">
                <h2>{{ __('finder_story.redirect.platform_title') }}</h2>
                <p class="f-muted">{{ __('finder_story.redirect.platform_p') }}</p>
                <div class="fs-cta-row">
                    <a class="f-btn f-btn--primary fs-platform-cta" href="{{ $targets[$primary] }}">
                        {{ __('finder_story.redirect.links.' . $primary) }}
                        @include('partials.finder.icon', ['name' => 'arrow-right', 'size' => 16])
                    </a>
                    @foreach ($secondary as $key)
                        <a class="f-btn fs-platform-cta" href="{{ $targets[$key] }}">{{ __('finder_story.redirect.links.' . $key) }}</a>
                    @endforeach
                </div>
            </div>

            <div class="f-card">
                <h2>{{ __('finder_story.redirect.consumer_title') }}</h2>
                <p class="f-muted">{{ __('finder_story.redirect.consumer_p') }}</p>
                @include('public.partials._finder_ctas')
            </div>

            @if (!empty($showPrivacy))
                <p class="f-small f-muted">
                    {{ __('finder_story.redirect.privacy_note') }}
                    <a href="{{ route('public.privacy') }}">{{ __('finder_story.redirect.privacy_cta') }}</a>
                </p>
            @endif
        </div>
    </div>
</section>
