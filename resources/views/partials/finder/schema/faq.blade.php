{{--
    FAQPage JSON-LD — the single highest-value structured-data block on the
    site for answer engines: ChatGPT, Perplexity and AI Overviews lift Q&A
    pairs almost verbatim when marked up, and infer them unreliably when not.

    Include from the FAQ page (inside @push('schema')):

        @include('partials.finder.schema.faq')

    Built from the same lang array the page renders — markup can never drift
    from the visible answers. If the FAQ copy moves to a different lang file,
    pass its key: @include('partials.finder.schema.faq', ['faqSource' => 'finder_story.faq.categories'])
    (expects categories => [ ['items' => [ ['q' => …, 'a' => …] ]] ]).
--}}
@php
    $fqSource     = $faqSource ?? 'public.faq.categories';
    $fqCategories = __($fqSource, [], app()->getLocale());
    $fqEntities   = [];

    if (is_array($fqCategories)) {
        foreach ($fqCategories as $fqCat) {
            foreach ($fqCat['items'] ?? [] as $fqItem) {
                if (! isset($fqItem['q'], $fqItem['a'])) {
                    continue;
                }
                $fqEntities[] = [
                    '@type'          => 'Question',
                    'name'           => $fqItem['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $fqItem['a']],
                ];
            }
        }
    }

    // json_encode lives in this @php block on purpose — "@context" outside a
    // protected @php block is eaten by Blade's @context directive.
    $fqJsonLd = $fqEntities === [] ? null : json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'inLanguage' => app()->getLocale(),
        'mainEntity' => $fqEntities,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp
@if ($fqJsonLd !== null)
<script type="application/ld+json">
{!! $fqJsonLd !!}
</script>
@endif
