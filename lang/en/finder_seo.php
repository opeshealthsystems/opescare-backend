<?php

/*
| SEO meta layer for OpesCare (see config/finder-seo.php and
| App\Support\FinderSeo). Kept 1:1 with lang/fr/finder_seo.php.
|
| Page titles are written WITHOUT the site name — the layout appends
| " — OpesCare". Profile titles are full strings (the profile page
| renders its own <head>). Titles aim ≤60 rendered chars, descriptions ≤155.
*/

return [

    'site' => [
        'title' => 'OpesCare',
        'description' => 'Find medicine in stock, blood availability and healthcare facilities across Cameroon — free, bilingual English and French.',
        // Quotable by answer engines: what it is AND what the data is not.
        'org_description' => 'OpesCare is a free bilingual health finder for Cameroon: live pharmacy stock, blood availability and the national health facility directory. Availability is reported by facilities and timestamped, never a verified guarantee of stock.',
    ],

    'pages' => [
        'home' => [
            'title' => 'Find Medicine, Blood & Care in Cameroon',
            'description' => 'Search live pharmacy stock, blood availability and 2,700+ hospitals, clinics and pharmacies across Cameroon. Free, in English and French.',
        ],
        'medicine_finder' => [
            'title' => 'Find a medicine in stock near you in Cameroon',
            'description' => 'Find a medicine in stock at a pharmacy near you. Availability is reported by pharmacies on the network and timestamped — check before you travel.',
        ],
        'blood_finder' => [
            'title' => 'Find blood at hospitals and blood banks in Cameroon',
            'description' => 'Check blood availability by blood group and component at connected hospitals and blood banks in Cameroon, with the report time on every listing.',
        ],
        'care_map' => [
            'title' => 'Care Map — Health Facility Directory',
            'description' => 'Browse 2,700+ hospitals, clinics, health centres, pharmacies and laboratories from Cameroon\'s national health facility registry, on a searchable map.',
        ],
        'emergency' => [
            'title' => 'Emergency Care Access',
            'description' => 'Hospitals across Cameroon with a phone line you can call right now, on one map. In an emergency, call ahead or go to the nearest hospital.',
        ],
        'profile' => [
            'title' => 'Facility Profile',
            'description' => 'Address, phone number, opening hours and services for healthcare facilities across Cameroon, on OpesCare.',
        ],
        'about' => [
            'title' => 'About Us',
            'description' => 'What OpesCare is, where the directory data comes from, and what reported availability does and does not mean.',
        ],
        'how_it_works' => [
            'title' => 'How It Works',
            'description' => 'How OpesCare works: pharmacies report stock, hospitals report blood availability, and the directory follows the national facility registry.',
        ],
        'faq' => [
            'title' => 'Frequently Asked Questions',
            'description' => 'Answers about finding medicine and blood, how availability reports work, facility listings, claiming your facility, and using the site in French.',
        ],
        'contact' => [
            'title' => 'Contact Us',
            'description' => 'Contact the OpesCare team — questions, corrections to a facility listing, or partnership requests from pharmacies and hospitals.',
        ],
        'help' => [
            'title' => 'Help Centre',
            'description' => 'Guides for searching medicine and blood availability, reading freshness labels, and getting the best from the facility directory.',
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'description' => 'How OpesCare handles personal data: what we collect, what we never collect, and the choices you have.',
        ],
        'terms' => [
            'title' => 'Terms of Use',
            'description' => 'The terms that govern using OpesCare, including what reported availability means and does not mean.',
        ],
        'consent' => [
            'title' => 'Consent & Data Choices',
            'description' => 'Your consent and data choices on OpesCare, explained in plain language.',
        ],
        'status' => [
            'title' => 'Service Status',
            'description' => 'Live operational status of OpesCare services.',
        ],
        'sla' => [
            'title' => 'Service Levels',
            'description' => 'Service level commitments for partners integrating with OpesCare.',
        ],
    ],

    // Facility profile pages — locality words in the title are the GEO signal.
    'profile' => [
        'title'            => ':name — :city, :region | OpesCare',
        'title_city'       => ':name — :city | OpesCare',
        'title_bare'       => ':name | OpesCare',
        'description'      => ':name — :type in :city, Cameroon. See the address, phone number, opening hours and services on OpesCare.',
        'description_bare' => ':name — :type in Cameroon. See the address, phone number, opening hours and services on OpesCare.',
    ],

    'breadcrumbs' => [
        'home'     => 'Home',
        'care_map' => 'Directory',
    ],

    // Facility type labels used in meta descriptions (registry vocabulary).
    'types' => [
        'hospital'          => 'hospital',
        'clinic'            => 'clinic',
        'pharmacy'          => 'pharmacy',
        'laboratory'        => 'medical laboratory',
        'health_center'     => 'health centre',
        'dental'            => 'dental clinic',
        'imaging_center'    => 'medical imaging centre',
        'maternity'         => 'maternity',
        'dispensary'        => 'dispensary',
        'eye_clinic'        => 'eye clinic',
        'diagnostic_center' => 'diagnostic centre',
        'specialist'        => 'specialist practice',
        'facility'          => 'health facility',
    ],
];
