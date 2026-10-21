<?php

/*
|-----------------------------------------------------------------------------
| Finder shell — layout chrome only (header, nav, footer, meta defaults).
|-----------------------------------------------------------------------------
| Page copy belongs in the page's own lang file, not here. Must stay 1:1
| with lang/fr/finder_shell.php (php scripts/i18n-audit.php).
*/

return [

    'brand' => [
        'name' => 'OpesCare',
        'tagline' => 'Find medicine, blood and care across Cameroon.',
    ],

    'meta' => [
        'default_title' => 'OpesCare',
        'default_description' => 'Find medicine in stock at a pharmacy, blood availability, and bookable appointments across 2,728 health facilities in all 10 regions of Cameroon.',
    ],

    'a11y' => [
        'skip_to_content' => 'Skip to content',
        'searching' => 'Searching…',
        'open_menu' => 'Open menu',
        'close_menu' => 'Close menu',
        'main_navigation' => 'Main navigation',
        'footer_navigation' => 'Footer',
        'language' => 'Language',
        'home' => 'OpesCare home',
    ],

    'nav' => [
        'find_medicine' => 'Find medicine',
        'find_blood' => 'Find blood',
        'book_appointment' => 'Book appointment',
        'directory' => 'Directory',
        'for_pharmacies' => 'For pharmacies',
        'login' => 'Log in',
        'menu' => 'Menu',
    ],

    'footer' => [
        'col_services' => 'Services',
        'col_facilities' => 'For facilities',
        'col_legal' => 'Legal & help',
        'emergency' => 'Emergency care',
        'for_hospitals' => 'For hospitals & clinics',
        'interop' => 'Interoperability',
        'mpi' => 'Master Patient Index',
        'health_id' => 'Health ID',
        'eprescription' => 'E-prescriptions',
        'app' => 'Get the app',
        'help' => 'Help center',
        'contact' => 'Contact',
        'terms' => 'Terms of service',
        'privacy' => 'Privacy policy',
        'attribution' => "Facility data from Cameroon's national health facility registry and © OpenStreetMap contributors.",
        'disclaimer' => 'Facility details, opening hours, stock and availability can change. Call ahead when it matters.',
        'copyright' => '© :year OpesCare. All rights reserved.',
    ],

];
