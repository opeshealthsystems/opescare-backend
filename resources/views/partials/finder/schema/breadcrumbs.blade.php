{{--
    BreadcrumbList for a facility profile page — include alongside
    partials.finder.schema.facility with $facility in scope:

        @include('partials.finder.schema.breadcrumbs', ['facility' => $facility])

    Home → Directory → Facility. Search engines render this trail under the
    result instead of a raw URL, and it hands them the site hierarchy.
--}}
@php
    $fbItems = [
        ['name' => __('finder_seo.breadcrumbs.home'),     'item' => \App\Support\FinderSeo::base()],
        ['name' => __('finder_seo.breadcrumbs.care_map'), 'item' => route('public.care-map')],
        ['name' => $facility->facility_name,              'item' => route('public.care-map.profile', $facility->id)],
    ];

    // json_encode lives in this @php block on purpose — "@context" outside a
    // protected @php block is eaten by Blade's @context directive.
    $fbJsonLd = json_encode([
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => collect($fbItems)->map(fn ($fbItem, $fbI) => [
            '@type'    => 'ListItem',
            'position' => $fbI + 1,
            'name'     => $fbItem['name'],
            'item'     => $fbItem['item'],
        ])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $fbJsonLd !!}
</script>
