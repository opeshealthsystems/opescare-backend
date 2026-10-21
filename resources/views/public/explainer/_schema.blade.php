{{--
    JSON-LD for an explainer page: BreadcrumbList (Home → page) + FAQPage built
    from the same lang array the page renders ($ns.faq.categories), via the
    shared partials.finder.schema.faq. Expects $ns (lang file) and $route.
--}}
@php
    // json_encode inside @php on purpose: a bare "@context" is a Blade directive.
    $exCrumbs = json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => __('finder_seo.breadcrumbs.home'), 'item' => \App\Support\FinderSeo::base()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => __($ns . '.breadcrumb'), 'item' => \App\Support\FinderSeo::absolute(route($route))],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $exCrumbs !!}
</script>
@include('partials.finder.schema.faq', ['faqSource' => $ns . '.faq.categories'])
