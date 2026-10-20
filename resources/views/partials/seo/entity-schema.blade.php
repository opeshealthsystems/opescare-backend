{{--
    OpesCare entity JSON-LD for answer engines. Include once per page:

        includeIf partials.seo.entity-schema

    partials/finder/schema/site.blade.php (already in layouts.finder) emits the
    Organization (#organization) and WebSite (#website, SearchAction on
    /care-map?query=). This partial complements it and does NOT repeat them:
    it adds the SoftwareApplication node and references the same ids, so a
    page carrying both blocks still describes ONE entity.

    Pass ['standalone' => true] on a layout that lacks the site partial to
    also emit the Organization + WebSite nodes here.

    Facts only: free for patients (CareIsNeverGatedOnPaymentTest), web +
    Android, EN/FR, Cameroon. No sameAs (no real social profiles in the repo),
    no ratings. JSON is built in the php block, never a literal at-context key in Blade
    (the directive trap).
--}}
@php
    $esBase = \App\Support\FinderSeo::base();
    $esOrg = [
        '@type'      => 'Organization',
        '@id'        => $esBase . '/#organization',
        'name'       => 'OpesCare',
        'legalName'  => 'Opes Health Systems Sarl',
        'url'        => $esBase,
        'logo'       => asset('brand/opescare-logo-512.png'),
        'areaServed' => ['@type' => 'Country', 'name' => 'Cameroon'],
        'knowsLanguage' => ['en', 'fr'],
        'contactPoint' => [
            ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'email' => 'support@opescare.com', 'url' => route('public.contact'), 'availableLanguage' => ['English', 'French'], 'areaServed' => 'CM'],
            ['@type' => 'ContactPoint', 'contactType' => 'data protection', 'email' => 'privacy@opeshealthsystems.com', 'availableLanguage' => ['English', 'French']],
        ],
    ];
    $esGraph = [[
        '@type'               => 'SoftwareApplication',
        '@id'                 => $esBase . '/#app',
        'name'                => 'OpesCare',
        'url'                 => $esBase,
        'applicationCategory' => 'HealthApplication',
        'operatingSystem'     => 'Web, Android',
        'inLanguage'          => ['en', 'fr'],
        'description'         => __('facts.definition'),
        'publisher'           => ['@id' => $esBase . '/#organization'],
        'featureList'         => [
            __('facts.mod_drug'), __('facts.mod_blood'), __('facts.mod_booking'),
            __('facts.mod_directory'), __('facts.mod_health_id'),
        ],
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'XAF', 'description' => __('facts.row_price_v')],
    ]];
    if (! empty($standalone)) {
        $esGraph[] = $esOrg;
        $esGraph[] = ['@type' => 'WebSite', '@id' => $esBase . '/#website', 'url' => $esBase, 'name' => 'OpesCare',
            'publisher' => ['@id' => $esBase . '/#organization'], 'inLanguage' => ['en', 'fr'],
            'potentialAction' => ['@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $esBase . '/care-map?query={search_term_string}'],
                'query-input' => 'required name=search_term_string']];
    } else {
        // Contact points + languages enrich the site partial's Organization by @id.
        $esGraph[] = array_intersect_key($esOrg, array_flip(['@type', '@id', 'knowsLanguage', 'contactPoint']));
    }
    $esJsonLd = json_encode(['@' . 'context' => 'https://schema.org', '@graph' => $esGraph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
@endphp
<script type="application/ld+json">
{!! $esJsonLd !!}
</script>
