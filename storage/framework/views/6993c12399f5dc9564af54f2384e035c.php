
<?php
    $fbItems = [
        ['name' => __('finder_seo.breadcrumbs.home'),     'item' => rtrim(url('/'), '/')],
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
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<script type="application/ld+json">
<?php echo $fbJsonLd; ?>

</script>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\finder\schema\breadcrumbs.blade.php ENDPATH**/ ?>