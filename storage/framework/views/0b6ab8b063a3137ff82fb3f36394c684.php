
<?php
    $fmTitle     = \App\Support\FinderSeo::facilityTitle($facility);
    $fmDesc      = \App\Support\FinderSeo::facilityDescription($facility);
    $fmUrl       = route('public.care-map.profile', $facility->id);
    $fmLocale    = app()->getLocale();
    $fmCanonical = $fmUrl . ($fmLocale === 'en' ? '' : '?lang=' . $fmLocale);
    $fmCity      = \App\Models\CareFacility::realValue($facility->city);
    $fmRegion    = \App\Models\CareFacility::realValue($facility->region);
    $fmOgImage   = asset(config('finder-seo.og_image', 'assets/og.png'));
?>
<title><?php echo e($fmTitle); ?></title>
<meta name="description" content="<?php echo e($fmDesc); ?>">
<link rel="canonical" href="<?php echo e($fmCanonical); ?>">
<link rel="alternate" hreflang="en" href="<?php echo e($fmUrl); ?>">
<link rel="alternate" hreflang="fr" href="<?php echo e($fmUrl); ?>?lang=fr">
<link rel="alternate" hreflang="x-default" href="<?php echo e($fmUrl); ?>">
<meta name="geo.region" content="CM">
<?php if($fmCity !== null): ?>
<meta name="geo.placename" content="<?php echo e($fmCity); ?><?php echo e($fmRegion !== null && strcasecmp($fmCity, $fmRegion) !== 0 ? ', ' . $fmRegion : ''); ?>">
<?php endif; ?>
<?php if($facility->latitude && $facility->longitude): ?>
<meta name="geo.position" content="<?php echo e($facility->latitude); ?>;<?php echo e($facility->longitude); ?>">
<meta name="ICBM" content="<?php echo e($facility->latitude); ?>, <?php echo e($facility->longitude); ?>">
<?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="OpesCare Finder">
<meta property="og:title" content="<?php echo e($fmTitle); ?>">
<meta property="og:description" content="<?php echo e($fmDesc); ?>">
<meta property="og:url" content="<?php echo e($fmCanonical); ?>">
<meta property="og:locale" content="<?php echo e($fmLocale === 'fr' ? 'fr_CM' : 'en_CM'); ?>">
<meta property="og:locale:alternate" content="<?php echo e($fmLocale === 'fr' ? 'en_CM' : 'fr_CM'); ?>">
<meta property="og:image" content="<?php echo e($fmOgImage); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="OpesCare Finder">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo e($fmTitle); ?>">
<meta name="twitter:description" content="<?php echo e($fmDesc); ?>">
<meta name="twitter:image" content="<?php echo e($fmOgImage); ?>">
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\finder\schema\facility-meta.blade.php ENDPATH**/ ?>