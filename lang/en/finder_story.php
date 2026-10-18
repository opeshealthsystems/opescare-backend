<?php

/*
|--------------------------------------------------------------------------
| Finder story pages — about, how it works, FAQ, contact, facility pitches,
| and the short "this belongs to the platform" pages.
|--------------------------------------------------------------------------
| Consumer copy. Tone: a public utility — calm, direct, no pitch.
| Two hard rules, enforced by review not by tooling:
|   1. Never the word "verified". The true claim is "listed in Cameroon's
|      national facility registry" — listing is a fact, verification is not.
|   2. Honest limits stay in: stock and blood availability are reported by
|      facilities, so every page says "call ahead" wherever that matters.
| Must stay 1:1 with lang/fr/finder_story.php (php scripts/i18n-audit.php).
*/

return [

    // ── About — what Finder is, the data story told honestly ───────────────
    'about' => [
        'page_title'       => 'About OpesCare',
        'meta_description' => 'What OpesCare is, where its facility data comes from, and who it serves — a free directory of pharmacies, hospitals, clinics and laboratories across Cameroon.',
        'eyebrow'          => 'About',
        'title'            => 'A free way to find care in Cameroon',
        'lead'             => 'OpesCare helps you locate a pharmacy, a hospital, blood or an appointment — and gives you a phone number so you can confirm before you travel.',

        'what_title' => 'What Finder is',
        'what_p1'    => 'Finder is a public directory of health facilities across all ten regions of Cameroon — pharmacies, hospitals, clinics and laboratories — each with its contact details and, where facilities report them, opening hours, services, medicine stock and blood availability.',
        'what_p2'    => 'It is free to use, in English and in French, on any phone browser. There is nothing to install, and no account is needed to search.',

        'free_title' => 'Free means free',
        'free_p'     => 'Searching, browsing the directory and booking are free for patients and families. Listing is free for facilities too: a pharmacy or clinic claims its page and keeps it up to date at no cost.',

        'data_title'          => 'Where the data comes from',
        'data_lead'           => 'Three sources, each doing a different job — and it matters that you know which is which.',
        'data_registry_title' => "Cameroon's national facility registry",
        'data_registry_p'     => 'The backbone of the directory. Facilities appear on Finder because they are listed in the national registry of health facilities — that is what puts a hospital or a pharmacy on the map in the first place.',
        'data_osm_title'      => 'OpenStreetMap contributors',
        'data_osm_p'          => 'Map positions and address details are improved with data contributed by volunteers to OpenStreetMap — © OpenStreetMap contributors.',
        'data_self_title'     => 'What facilities report themselves',
        'data_self_p'         => 'Opening hours, corrected phone numbers, services, medicine stock and blood availability come from facilities that have claimed their listing and report them. Nothing else feeds those details: if a pharmacy has not reported a medicine, Finder will not show it — an empty result means "not reported", not "not available".',

        'honest_title' => 'What a listing is — and is not',
        'honest_p1'    => 'A listing means the facility appears in the sources above. It is not a licence check, an inspection, or a recommendation by OpesCare.',
        'honest_p2'    => 'Details change: phone numbers are reassigned, hours shift, stock runs out during the day. Call ahead whenever the trip matters.',

        'who_title'            => 'Who Finder is for',
        'who_patients_title'   => 'Patients and families',
        'who_patients_p'       => 'Find the nearest pharmacy with the medicine you need, a hospital with transfusion services, or a clinic you can book or call.',
        'who_caregivers_title' => 'Caregivers',
        'who_caregivers_p'     => 'Search on behalf of a parent or a child, compare facilities near them, and call before anyone travels.',
        'who_facilities_title' => 'Pharmacies and facilities',
        'who_facilities_p'     => 'Claim your listing to keep your phone number, hours and stock up to date — so patients reach you with accurate information.',
        'who_facilities_cta'   => 'Claim your listing',

        'cta_title' => 'Start with what you need',
    ],

    // ── How it works — the patient walkthrough, honest limits included ─────
    'how' => [
        'page_title'       => 'How it works',
        'meta_description' => 'How to find medicine, blood, facilities and appointments with OpesCare — and the honest limits of the data: stock is reported by pharmacies, so call ahead.',
        'eyebrow'          => 'How it works',
        'title'            => 'From "I need…" to a confirmed visit',
        'lead'             => 'Finder does one job: it shows you facilities near you, tells you what they have reported, and gives you a number to call. Here is each tool, step by step.',

        'medicine_title' => 'Finding a medicine',
        'medicine_step1' => 'Search for the medicine, or open the pharmacy directory for your area.',
        'medicine_step2' => 'Check the listing — reported stock is timestamped, so you can see how fresh the information is.',
        'medicine_step3' => 'Call the pharmacy to confirm stock and price, then travel.',
        'medicine_note'  => 'Only pharmacies that report their stock appear in stock results. A medicine missing from the results may still be on a shelf nearby — the pharmacy simply has not reported it.',

        'blood_title' => 'Finding blood',
        'blood_step1' => 'Open the blood finder and choose the blood group you need.',
        'blood_step2' => 'See hospitals with transfusion services near you, and what they have reported.',
        'blood_step3' => 'Call the hospital first. In an urgent situation, never travel on a listing alone.',

        'facility_title' => 'Finding a facility',
        'facility_step1' => 'Browse the directory, or filter by type — pharmacy, hospital, clinic, laboratory.',
        'facility_step2' => 'Open a facility page: address, phone number, and the services and hours it has reported.',
        'facility_step3' => 'In an emergency, the emergency view shows the nearest facilities with emergency services — but go, do not browse.',

        'booking_title' => 'Booking an appointment',
        'booking_step1' => 'Create a free account — booking is the one thing that needs one.',
        'booking_step2' => 'Open the facility page and pick a time where online booking is offered.',
        'booking_step3' => 'Not every facility takes online bookings yet. Where one does not, its listing gives you a phone number to call instead.',

        'limits_title' => 'The honest limits',
        'limits_lead'  => 'Finder is only as good as what is reported to it. Keep four things in mind:',
        'limit_1'      => 'Details change. Phone numbers, hours and services can be out of date — call ahead whenever the trip matters.',
        'limit_2'      => 'Stock is reported by pharmacies themselves and moves during the day. Timestamps show when it was last reported.',
        'limit_3'      => 'A listing is not a recommendation or an inspection — it means the facility appears in the national registry and map sources.',
        'limit_4'      => 'In an emergency, do not rely on any app. Go to the nearest emergency service — use the emergency view only to find it faster.',

        'cta_title' => 'Try it with what you need today',
    ],

    // ── FAQ — a consumer's questions, answered plainly ─────────────────────
    'faq' => [
        'page_title'       => 'Frequently asked questions',
        'meta_description' => 'Is OpesCare free? Where does the facility data come from? Are facilities verified? How do pharmacies claim a listing? Plain answers, in English and French.',
        'eyebrow'          => 'FAQ',
        'title'            => 'Questions people actually ask',
        'lead'             => 'Short, honest answers. If yours is not here, the contact page reaches a person.',

        'items' => [
            [
                'q' => 'Is OpesCare free?',
                'a' => 'Yes. Searching for medicine, blood and facilities, browsing the directory and booking appointments are free for patients and families. Listing is free for facilities too — claiming a page and reporting stock cost nothing.',
            ],
            [
                'q' => 'Do I need an account?',
                'a' => 'Not to search. Every finder, every listing and every phone number works without an account. Booking an appointment is the one thing that needs a free account, so the facility knows who to expect.',
            ],
            [
                'q' => 'Where does the facility information come from?',
                'a' => "Three places: Cameroon's national registry of health facilities, which puts each facility on the map; OpenStreetMap contributors, who improve locations and addresses; and the facilities themselves — hours, services, medicine stock and blood availability come only from facilities that claim their listing and report them.",
            ],
            [
                'q' => 'Are the facilities verified?',
                'a' => 'No — listed, not verified. A listing means the facility appears in the national registry and map sources. It is not a licence check, an inspection, or a recommendation by OpesCare. Treat Finder like any directory: call ahead and use your own judgement.',
            ],
            [
                'q' => 'How up to date is the medicine stock information?',
                'a' => 'Stock is reported by pharmacies themselves, and every report is timestamped so you can see how fresh it is. Stock moves during the day — always call the pharmacy before you travel.',
            ],
            [
                'q' => 'I searched for a medicine and found nothing. Does that mean no pharmacy has it?',
                'a' => 'No. Only pharmacies that report their stock appear in stock results, and not every pharmacy reports yet. An empty result means "not reported", not "not available" — open the pharmacy directory for your area and call around.',
            ],
            [
                'q' => 'How do I report a wrong phone number or a closed facility?',
                'a' => 'Use the contact page and tell us the facility name and what is wrong. Corrections are reviewed and the listing is updated. If you work at the facility, claim the listing and correct it yourself — that keeps it right permanently.',
            ],
            [
                'q' => 'How does a pharmacy or clinic claim its listing?',
                'a' => 'Create a free account, find your facility in the directory and start the claim from its page. A reviewer checks that you are connected to the facility; once approved, you can update the phone number, hours and services, and report stock. See the "For pharmacies" page for the full picture.',
            ],
            [
                'q' => 'Is my personal data private?',
                'a' => 'Searching needs no account and asks for no personal details. If you create an account to book, you stay in control of your information: your account\'s privacy settings decide who can see what, and the privacy policy explains exactly what is collected and why.',
            ],
            [
                'q' => 'Does Finder work in French?',
                'a' => 'Yes, completely. Every page exists in both French and English — switch languages at any time from the header of any page.',
            ],
            [
                'q' => 'Can I book an appointment at any facility?',
                'a' => 'Not yet. Each facility chooses whether to take online bookings. Where one does not, its listing gives you a phone number, so you can still call and arrange a visit.',
            ],
            [
                'q' => 'What should I do in an emergency?',
                'a' => 'Do not rely on any app in an emergency. Call or go to the nearest emergency service. The emergency view exists only to help you find the nearest facility with emergency services faster.',
            ],
        ],

        'still_title' => 'Still have a question?',
        'still_p'     => 'The contact page reaches a person, in French or English.',
        'still_cta'   => 'Contact us',
    ],

    // ── Contact — hero + the "report an error" framing ─────────────────────
    'contact' => [
        'page_title'       => 'Contact',
        'meta_description' => 'Ask a question or report a listing error — a wrong phone number, changed hours, a closed facility. Free, answered in French or English.',
        'eyebrow'          => 'Contact',
        'title'            => 'Questions and corrections',
        'lead'             => 'A wrong phone number, a closed facility, a question about a listing or about Finder itself — this is the right place. Messages are read in French and in English.',

        'report_title' => 'Reporting a listing error?',
        'report_p'     => 'Tell us which facility and what is wrong — the phone number, the hours, the address. Corrections are reviewed and the listing is updated.',

        'email_title' => 'Email',
        'email_p'     => 'Prefer email? Write to us directly and we will get back to you.',

        'privacy_title' => 'Your message stays private',
        'privacy_p'     => 'What you send here is used only to answer you and to fix listings. It is never published and never added to any marketing list.',
    ],

    // ── For pharmacies — the claim-your-listing pitch ──────────────────────
    'pharmacies' => [
        'page_title'       => 'For pharmacies',
        'meta_description' => 'Claim your pharmacy on OpesCare — free. Be found by patients searching for medicine, correct your phone number and hours, and report stock from any phone.',
        'eyebrow'          => 'For pharmacies',
        'title'            => 'Be the pharmacy patients find',
        'lead'             => 'Patients search Finder for medicines every day. A claimed listing means they see your real hours, call a number that answers — and know you have what they need before they travel.',

        'found_title' => 'Be found',
        'found_p'     => 'Your pharmacy is already listed from the national facility registry. Claiming it makes the page yours: accurate location, phone, hours and services.',
        'edit_title'  => 'Correct your details yourself',
        'edit_p'      => 'A wrong number or old hours sends patients elsewhere. Once your claim is approved, you edit your page directly and changes go live immediately.',
        'stock_title' => 'Report stock from your phone',
        'stock_p'     => 'Report the medicines you have in about a minute, from any phone browser — no app to install. Reports are timestamped, and patients are always told to call ahead.',
        'free_title'  => 'Free, and it stays free',
        'free_p'      => 'Claiming your listing, editing your page and reporting stock cost nothing.',

        'steps_title' => 'How to claim your listing',
        'step1'       => 'Create a free account.',
        'step2'       => 'Find your pharmacy in the directory and open its page.',
        'step3'       => 'Start the claim and show you are connected to the pharmacy.',
        'step4'       => 'Once a reviewer approves the claim, update your page and start reporting stock.',

        'note' => 'Claims are reviewed before approval, and only what you report is shown. Finder never invents stock — which is exactly why a claimed, reporting pharmacy stands out.',

        'cta_primary'   => 'Find your pharmacy',
        'cta_secondary' => 'Create an account',
    ],

    // ── For hospitals & clinics — listing + booking + the platform box ─────
    'hospitals' => [
        'page_title'       => 'For hospitals and clinics',
        'meta_description' => 'Claim your facility on OpesCare — free. Keep your phone, hours and services accurate, report blood availability, and take appointment bookings.',
        'eyebrow'          => 'For hospitals and clinics',
        'title'            => 'Keep your facility reachable',
        'lead'             => 'Patients use Finder to choose a facility, check blood availability and book or call. A claimed listing keeps what they see accurate — and keeps your phone ringing for the right reasons.',

        'listing_title' => 'An accurate listing',
        'listing_p'     => 'Your facility is already listed from the national facility registry. Claim it to correct the phone number, hours, address and services yourself.',
        'blood_title'   => 'Reported blood availability',
        'blood_p'       => 'If you run transfusion services, report availability so patients and clinicians searching for blood find you — with a clear instruction to call first.',
        'booking_title' => 'Appointment bookings',
        'booking_p'     => 'Take online bookings where you choose to. Where you do not, your listing still gives patients the right number to call.',

        'steps_title' => 'How to claim your facility',
        'step1'       => 'Create a free account.',
        'step2'       => 'Find your facility in the directory and open its page.',
        'step3'       => 'Start the claim and show you are connected to the facility.',
        'step4'       => 'Once approved, update your page, report availability and manage bookings.',

        'platform_title' => 'Looking for the OpesCare platform?',
        'platform_p'     => 'The full OpesCare health platform for hospitals and institutions — health records, interoperability, developer APIs and institutional agreements — is a separate product. It lives at opescare.cloud.',
        'platform_cta'   => 'Visit opescare.cloud',

        'cta_primary'   => 'Find your facility',
        'cta_secondary' => 'Create an account',
    ],

    // ── For patients — a short signpost to the finders ─────────────────────
    'patients' => [
        'page_title'       => 'For patients and families',
        'meta_description' => 'What you can do with OpesCare: find medicine at pharmacies, blood at hospitals, and facilities you can book or call — free, in French and English.',
        'eyebrow'          => 'For patients and families',
        'title'            => 'Finder is built for you',
        'lead'             => 'Find a medicine, blood or a facility near you, see what it has reported, and call before you travel. Free, with no account needed to search.',
        'honest_p'         => 'One habit makes Finder work well: call ahead. Details and stock are reported by facilities and can change during the day.',
    ],

    // ── Platform pages that no longer belong on this product ───────────────
    'redirect' => [
        'meta_description' => 'This page belongs to the OpesCare health platform for hospitals and institutions. OpesCare is the free tool for finding care in Cameroon.',
        'eyebrow'          => 'OpesCare',
        'heading'          => 'This page belongs to the OpesCare platform',
        'lead'             => 'You have reached OpesCare — the free tool for finding medicine, blood and care in Cameroon. What you were looking for is part of the OpesCare health platform for hospitals and institutions, which is a separate product.',
        'platform_title'   => 'Looking for the OpesCare platform?',
        'platform_p'       => 'Interoperability, developer APIs and documentation, institutional pricing, demos and service agreements live on the platform site.',
        'platform_cta'     => 'Visit opescare.cloud',
        'consumer_title'   => 'Looking for care?',
        'consumer_p'       => 'Finder helps you locate facilities, medicine and blood near you — free, in English and in French.',
        'privacy_note'     => 'Wondering how Finder handles your data? The privacy policy explains it in plain language.',
        'privacy_cta'      => 'Read the privacy policy',
        'titles'           => [
            'interoperability' => 'Interoperability',
            'developers'       => 'Developers',
            'api_pricing'      => 'API pricing',
            'request_demo'     => 'Request a demo',
            'pricing'          => 'Pricing',
            'security'         => 'Security',
            'sla'              => 'Service-level agreement',
            'insurers'         => 'For insurers',
            'laboratories'     => 'For laboratories',
            'public_health'    => 'For public health',
        ],
    ],

    // ── Shared consumer CTAs ───────────────────────────────────────────────
    'cta' => [
        'medicine'  => 'Find medicine',
        'blood'     => 'Find blood',
        'directory' => 'Browse facilities',
        'how'       => 'How Finder works',
    ],

];
