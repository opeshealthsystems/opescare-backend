{{--
    Honest hand-off page for routes that survive from the platform's marketing
    site but describe the WRONG product here. Finder is consumer infrastructure;
    interoperability, APIs, institutional pricing, demos and SLAs belong to the
    OpesCare platform at opescare.cloud. Each of these routes keeps rendering
    (old links and search results still resolve) but says so plainly and offers
    the visitor both ways forward.

    Include with:
        'pageKey'     => key into finder_story.redirect.titles (required)
        'showPrivacy' => true to add the Finder privacy pointer (security page)
--}}
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
                <a class="f-btn f-btn--primary fs-platform-cta" href="https://opescare.cloud" rel="noopener">
                    {{ __('finder_story.redirect.platform_cta') }}
                    @include('partials.finder.icon', ['name' => 'external-link', 'size' => 16])
                </a>
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
