<?php

/*
| SEO meta layer for OpesCare Finder (see config/finder-seo.php and
| App\Support\FinderSeo). Kept 1:1 with lang/fr/finder_seo.php.
|
| Page titles are written WITHOUT the site name — the layout appends
| " — OpesCare Finder". Profile titles are full strings (the profile page
| renders its own <head>). Titles aim ≤60 rendered chars, descriptions ≤155.
*/

return [

    'site' => [
        'title' => 'OpesCare Finder',
        'description' => 'Find medicine in stock, blood availability and healthcare facilities across Cameroon — free, bilingual English and French.',
        // Quotable by answer engines: what it is AND what the data is not.
        'org_description' => 'OpesCare Finder is a free bilingual health finder for Cameroon: live pharmacy stock, blood availability and the national health facility directory. Availability is reported by facilities and timestamped, never a verified guarantee of stock.',
    ],

    'pages' => [
        'home' => [
            'title' => 'Find Medicine, Blood & Care in Cameroon',
            'description' => 'Search live pharmacy stock, blood availability and 2,700+ hospitals, clinics and pharmacies across Cameroon. Free, in English and French.',
        ],
        'medicine_finder' => [
            'title' => 'Medicine Finder — Pharmacy Stock Near You',
            'description' => 'Find a medicine in stock at a pharmacy near you. Availability is reported by pharmacies on the network and timestamped — check before you travel.',
        ],
        'blood_finder' => [
            'title' => 'Blood Finder — Blood Availability',
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
            'description' => 'Address, phone number, opening hours and services for healthcare facilities across Cameroon, on OpesCare Finder.',
        ],
        'about' => [
            'title' => 'About Us',
            'description' => 'What OpesCare Finder is, where the directory data comes from, and what reported availability does and does not mean.',
        ],
        'how_it_works' => [
            'title' => 'How It Works',
            'description' => 'How OpesCare Finder works: pharmacies report stock, hospitals report blood availability, and the directory follows the national facility registry.',
        ],
        'faq' => [
            'title' => 'Frequently Asked Questions',
            'description' => 'Answers about finding medicine and blood, how availability reports work, facility listings, claiming your facility, and using the site in French.',
        ],
        'contact' => [
            'title' => 'Contact Us',
            'description' => 'Contact the OpesCare Finder team — questions, corrections to a facility listing, or partnership requests from pharmacies and hospitals.',
        ],
        'help' => [
            'title' => 'Help Centre',
            'description' => 'Guides for searching medicine and blood availability, reading freshness labels, and getting the best from the facility directory.',
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'description' => 'How OpesCare Finder handles personal data: what we collect, what we never collect, and the choices you have.',
        ],
        'terms' => [
            'title' => 'Terms of Use',
            'description' => 'The terms that govern using OpesCare Finder, including what reported availability means and does not mean.',
        ],
        'consent' => [
            'title' => 'Consent & Data Choices',
            'description' => 'Your consent and data choices on OpesCare Finder, explained in plain language.',
        ],
        'status' => [
            'title' => 'Service Status',
            'description' => 'Live operational status of OpesCare Finder services.',
        ],
        'sla' => [
            'title' => 'Service Levels',
            'description' => 'Service level commitments for partners integrating with OpesCare Finder.',
        ],
    ],

    // Facility profile pages — locality words in the title are the GEO signal.
    'profile' => [
        'title'            => ':name — :city, :region | OpesCare Finder',
        'title_city'       => ':name — :city | OpesCare Finder',
        'title_bare'       => ':name | OpesCare Finder',
        'description'      => ':name — :type in :city, Cameroon. See the address, phone number, opening hours and services on OpesCare Finder.',
        'description_bare' => ':name — :type in Cameroon. See the address, phone number, opening hours and services on OpesCare Finder.',
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
