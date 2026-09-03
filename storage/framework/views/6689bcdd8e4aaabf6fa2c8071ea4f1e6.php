
<?php
    $fsLocale    = app()->getLocale();
    $fsPath      = '/' . ltrim(request()->path() === '/' ? '' : request()->path(), '/');
    $fsPath      = rtrim($fsPath, '/') ?: '/';
    $fsBase      = rtrim(url('/'), '/');
    $fsEnUrl     = $fsBase . ($fsPath === '/' ? '' : $fsPath);
    $fsCanonical = $fsEnUrl . ($fsLocale === 'en' ? '' : '?lang=' . $fsLocale);

    $fsSection   = trim($__env->yieldContent('title'));
    $fsTitle     = $fsSection !== '' ? $fsSection . ' — OpesCare Finder' : __('finder_seo.site.title');
    $fsDesc      = trim($__env->yieldContent('description'));
    $fsDesc      = $fsDesc !== '' ? $fsDesc : \App\Support\FinderSeo::description();
    $fsOgImage   = asset(config('finder-seo.og_image', 'assets/og.png'));

    // Built inside this @php block ON PURPOSE: Blade compiles directives
    // before echos, and "@context" outside a protected @php block is eaten
    // by the @context directive, silently corrupting the JSON-LD key.
    $fsSiteJsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'Organization',
                '@id'         => $fsBase . '/#organization',
                'name'        => 'OpesCare',
                'legalName'   => 'Opes Health Systems Sarl',
                'url'         => $fsBase,
                'logo'        => asset('brand/opescare-logo-512.png'),
                'description' => __('finder_seo.site.org_description'),
                'areaServed'  => ['@type' => 'Country', 'name' => 'Cameroon'],
            ],
            [
                '@type'      => 'WebSite',
                '@id'        => $fsBase . '/#website',
                'url'        => $fsBase,
                'name'       => 'OpesCare Finder',
                'publisher'  => ['@id' => $fsBase . '/#organization'],
                'inLanguage' => ['en', 'fr'],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => $fsBase . '/care-map?query={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<link rel="canonical" href="<?php echo e($fsCanonical); ?>">
<link rel="alternate" hreflang="en" href="<?php echo e($fsEnUrl); ?>">
<link rel="alternate" hreflang="fr" href="<?php echo e($fsEnUrl); ?>?lang=fr">
<link rel="alternate" hreflang="x-default" href="<?php echo e($fsEnUrl); ?>">
<meta name="geo.region" content="CM">
<meta property="og:type" content="website">
<meta property="og:site_name" content="OpesCare Finder">
<meta property="og:title" content="<?php echo e($fsTitle); ?>">
<meta property="og:description" content="<?php echo e($fsDesc); ?>">
<meta property="og:url" content="<?php echo e($fsCanonical); ?>">
<meta property="og:locale" content="<?php echo e($fsLocale === 'fr' ? 'fr_CM' : 'en_CM'); ?>">
<meta property="og:locale:alternate" content="<?php echo e($fsLocale === 'fr' ? 'en_CM' : 'fr_CM'); ?>">
<meta property="og:image" content="<?php echo e($fsOgImage); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="OpesCare Finder">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo e($fsTitle); ?>">
<meta name="twitter:description" content="<?php echo e($fsDesc); ?>">
<meta name="twitter:image" content="<?php echo e($fsOgImage); ?>">
<script type="application/ld+json">
<?php echo $fsSiteJsonLd; ?>

</script>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\finder\schema\site.blade.php ENDPATH**/ ?>