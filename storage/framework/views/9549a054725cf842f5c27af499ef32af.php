
<?php
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
?>
<?php if($fqJsonLd !== null): ?>
<script type="application/ld+json">
<?php echo $fqJsonLd; ?>

</script>
<?php endif; ?>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views\partials\finder\schema\faq.blade.php ENDPATH**/ ?>