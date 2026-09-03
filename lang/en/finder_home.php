<?php

/*
|--------------------------------------------------------------------------
| Finder homepage (public.landing → resources/views/public/landing.blade.php)
|--------------------------------------------------------------------------
| Consumer copy. Tone: a public utility — calm, direct, no pitch.
| Two hard rules, enforced by review not by tooling:
|   1. Never the word "verified". The true claim is "listed in Cameroon's
|      national facility registry" — listing is a fact, verification is not.
|   2. Honest limits stay in: stock and blood availability are reported by
|      facilities, so the page says "call ahead" wherever that matters.
| Must stay 1:1 with lang/fr/finder_home.php (php scripts/i18n-audit.php).
*/

return [

    // The layout suffixes every title with "— OpesCare Finder"; no brand here.
    'page_title' => 'Find medicine, blood and appointments near you',
    'page_desc'  => 'Search :count health facilities across all 10 regions of Cameroon — pharmacies, hospitals, clinics and laboratories. Free to use, in English and French.',

    // ── 01 · Hero — the task, not a slogan ─────────────────────────────────
    'hero' => [
        'eyebrow'           => "Cameroon's health facility finder",
        'title'             => 'Find care near you',
        'lead'              => 'Medicine, blood and appointments — search health facilities across Cameroon and call before you travel.',
        'nav_label'         => 'Find care',
        'medicine_label'    => 'Find medicine',
        'medicine_sub'      => 'Search pharmacies near you',
        'blood_label'       => 'Find blood',
        'blood_sub'         => 'Hospitals with transfusion services — call ahead',
        'appointment_label' => 'Book an appointment',
        'appointment_sub'   => 'Hospitals and clinics to book or call',
    ],

    // ── 02 · Trust strip — facts only ──────────────────────────────────────
    'trust' => [
        'facilities' => ":count facilities listed in Cameroon's national facility registry",
        'regions'    => 'All :count regions of Cameroon',
        'bilingual'  => 'In English and in French',
        'free'       => 'Free to use',
    ],

    // ── 03 · How it works — the patient's three steps ──────────────────────
    'how' => [
        'eyebrow'     => 'How it works',
        'title'       => 'Three steps, then you are on your way',
        'step1_title' => 'Search',
        'step1_desc'  => 'Tell us what you need — a medicine, blood or an appointment — and see facilities near you.',
        'step2_title' => 'Call or book',
        'step2_desc'  => 'Every listing has a phone number. Call ahead to confirm, or book where online booking is available.',
        'step3_title' => 'Go',
        'step3_desc'  => 'Get the address and directions, and travel once the facility has confirmed.',
    ],

    // ── 04 · The three finders — what each does, and its honest limits ─────
    'finders' => [
        'eyebrow'          => 'What you can find',
        'title'            => 'Three ways to find care',
        'medicine_title'   => 'Medicine',
        'medicine_desc'    => 'Search pharmacies across Cameroon by name or by city, with phone numbers and opening hours.',
        'medicine_limit'   => 'Stock is reported by pharmacies and changes during the day — always call ahead before you travel.',
        'medicine_cta'     => 'Search pharmacies',
        'blood_title'      => 'Blood',
        'blood_desc'       => 'Find hospitals near you that handle blood transfusion and donation.',
        'blood_limit'      => 'Availability is reported by facilities. In an urgent situation, call the hospital first — never travel on the listing alone.',
        'blood_cta'        => 'Find hospitals',
        'appointment_title' => 'Appointments',
        'appointment_desc' => 'Browse hospitals and clinics with their services, hours and phone numbers.',
        'appointment_limit' => 'Not every facility takes online bookings yet — where it does not, the listing gives you a number to call.',
        'appointment_cta'  => 'Browse facilities',
    ],

    // ── 05 · Facility owners — claim your listing ──────────────────────────
    'claim' => [
        'title' => 'Run a pharmacy or a clinic?',
        'desc'  => 'Claim your listing to keep your phone number, opening hours and stock up to date — so patients reach you with accurate information.',
        'hint'  => 'Search for your facility, open its page, and start the claim from there.',
        'cta'   => 'Find your listing',
    ],

    // ── 06 · Coverage — the ten regions ────────────────────────────────────
    'coverage' => [
        'title'   => 'Available in all :count regions',
        'desc'    => 'Facilities are listed in every region of Cameroon.',
        'regions' => [
            'Adamaoua',
            'Centre',
            'Est',
            'Extrême-Nord',
            'Littoral',
            'Nord',
            'Nord-Ouest',
            'Ouest',
            'Sud',
            'Sud-Ouest',
        ],
    ],

];
