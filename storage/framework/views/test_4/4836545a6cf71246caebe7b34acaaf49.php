

<?php
    $seoLocale    = app()->getLocale();
    $seoPath      = '/' . ltrim(request()->path() === '/' ? '' : request()->path(), '/');
    $seoPath      = rtrim($seoPath, '/') ?: '/';
    $seoBase      = rtrim(url('/'), '/');
    $seoCanonical = $seoBase . ($seoPath === '/' ? '' : $seoPath)
                    . ($seoLocale === 'en' ? '' : '?lang=' . $seoLocale);
    $seoEnUrl     = $seoBase . ($seoPath === '/' ? '' : $seoPath);
    $seoFrUrl     = $seoEnUrl . '?lang=fr';

    /*
     * A Service node for the audience pages.
     *
     * The WebPage node above says a page exists; it does not say what is being
     * offered or to whom. Answer engines lean on Service + audience to decide
     * whether OpesCare belongs in "how do Cameroonian pharmacies share stock
     * data" rather than only in a query that names OpesCare already.
     *
     * Scoped to /solutions/* deliberately — the /network/* pages already push
     * their own Service, and two nodes for one page would just compete.
     */
    $seoAudiences = [
        'hospitals'     => 'Hospitals and clinics',
        'laboratories'  => 'Diagnostic laboratories',
        'pharmacies'    => 'Pharmacies',
        'insurers'      => 'Health insurers',
        'patients'      => 'Patients',
        'public-health' => 'Public health authorities',
    ];
    $seoSegment = str_starts_with($seoPath, '/solutions/')
        ? substr($seoPath, strlen('/solutions/'))
        : null;
    $seoAudience = $seoAudiences[$seoSegment] ?? null;
?>


<meta property="og:type" content="website">
<meta property="og:site_name" content="OpesCare">
<meta property="og:title" content="<?php echo $__env->yieldContent('title', 'OpesCare | One Health ID. One Trusted Medical History.'); ?>">
<meta property="og:description" content="<?php echo $__env->yieldContent('meta_description', 'OpesCare is a digital Health ID and healthcare interoperability platform built to connect patients, hospitals, labs, pharmacies, and insurers.'); ?>">

<meta property="og:image" content="<?php echo e(asset('og-image.png')); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="OpesCare — one Health ID, connected across healthcare">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?php echo e(asset('og-image.png')); ?>">
<meta name="twitter:title" content="<?php echo $__env->yieldContent('title', 'OpesCare | One Health ID. One Trusted Medical History.'); ?>">
<meta name="twitter:description" content="<?php echo $__env->yieldContent('meta_description', 'OpesCare is a digital Health ID and healthcare interoperability platform.'); ?>">

<link rel="canonical" href="<?php echo e($seoCanonical); ?>">


<link rel="alternate" hreflang="en" href="<?php echo e($seoEnUrl); ?>">
<link rel="alternate" hreflang="fr" href="<?php echo e($seoFrUrl); ?>">
<link rel="alternate" hreflang="x-default" href="<?php echo e($seoEnUrl); ?>">

<meta property="og:url" content="<?php echo e($seoCanonical); ?>">
<meta property="og:locale" content="<?php echo e($seoLocale === 'fr' ? 'fr_CM' : 'en_CM'); ?>">
<meta property="og:locale:alternate" content="<?php echo e($seoLocale === 'fr' ? 'en_CM' : 'fr_CM'); ?>">


<script type="application/ld+json">
<?php echo json_encode([
    '<?php $__contextArgs = [];
if (context()->has($__contextArgs[0])) :
if (isset($value)) { $__contextPrevious[] = $value; }
$value = context()->get($__contextArgs[0]); ?>' => 'https://schema.org',
    '@graph'   => array_merge([
        [
            '@type'       => 'MedicalOrganization',
            '@id'         => $seoBase . '/#organization',
            'name'        => 'OpesCare',
            'legalName'   => 'Opes Health Systems Sarl',
            'url'         => $seoBase,
            'logo'        => $seoBase . '/brand/opescare-logo-512.png',
            'description' => __('seo.org_description'),
            'areaServed'  => [
                '@type' => 'Country',
                'name'  => 'Cameroon',
            ],
            'availableLanguage' => [
                ['@type' => 'Language', 'name' => 'English', 'alternateName' => 'en'],
                ['@type' => 'Language', 'name' => 'French',  'alternateName' => 'fr'],
            ],
            'knowsAbout' => [
                'Digital health identity',
                'Health information exchange',
                'HL7 FHIR',
                'Patient consent management',
                'Master patient index',
                'Medicine availability',
                'Blood availability',
            ],
        ],
        [
            '@type'    => 'WebSite',
            '@id'      => $seoBase . '/#website',
            'url'      => $seoBase,
            'name'     => 'OpesCare',
            'publisher' => ['@id' => $seoBase . '/#organization'],
            'inLanguage' => ['en', 'fr'],
        ],
        [
            '@type'         => 'WebPage',
            '@id'           => $seoCanonical . '#webpage',
            'url'           => $seoCanonical,
            'name'          => trim($__env->yieldContent('title', 'OpesCare')),
            'description'   => trim($__env->yieldContent('meta_description', __('seo.org_description'))),
            'isPartOf'      => ['@id' => $seoBase . '/#website'],
            'about'         => ['@id' => $seoBase . '/#organization'],
            'inLanguage'    => $seoLocale,
        ],
    ], $seoAudience === null ? [] : [[
        '@type'       => 'Service',
        '@id'         => $seoEnUrl . '#service',
        'name'        => trim($__env->yieldContent('title', 'OpesCare')),
        'description' => trim($__env->yieldContent('meta_description', __('seo.org_description'))),
        'serviceType' => 'Health information exchange',
        'provider'    => ['@id' => $seoBase . '/#organization'],
        'areaServed'  => ['@type' => 'Country', 'name' => 'Cameroon'],
        'audience'    => ['@type' => 'Audience', 'audienceType' => $seoAudience],
        'availableChannel' => [
            '@type'             => 'ServiceChannel',
            'serviceUrl'        => $seoEnUrl,
            'availableLanguage' => ['en', 'fr'],
        ],
        'inLanguage'  => $seoLocale,
    ]]),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

</script>

<?php echo $__env->yieldPushContent('schema'); ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/partials/seo_head.blade.php ENDPATH**/ ?>