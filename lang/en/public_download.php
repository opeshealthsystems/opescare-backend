<?php

// Public page /download (resources/views/public/download.blade.php).
// Facts (version, build, date, size, SHA-256) come from config('mobile.apk'),
// never from this file. Do not claim a Play Store or App Store listing exists.
return [
    'meta_title' => 'Get the OpesCare app for Android',
    'meta_desc' => 'Download the OpesCare Android app: your Health ID, records and results, consent and access log, family, appointments, and medicine and blood finders. Free.',
    'breadcrumb' => 'Get the app',

    'hero' => [
        'kicker' => 'OpesCare for Android',
        'title_1' => 'Get the OpesCare app',
        'title_2' => 'Your Health ID in your pocket.',
        'lead' => 'Carry your Health ID, see your results and prescriptions, decide who can open your record, and find medicine or blood near you. Free.',
        'version' => 'Version :version (build :build)',
        'size' => ':size MB',
        'released' => 'Released :date',
        'cta_download' => 'Download for Android (opescare.apk)',
        'cta_web' => 'Open the web app',
        'android_only' => 'For Android phones. Installs directly from opescare.com.',
        'qr_title' => 'On a computer?',
        'qr_note' => 'Scan with your phone camera to open this page there.',
        'qr_label' => 'QR code linking to the OpesCare download page',
    ],

    'install' => [
        'eyebrow' => 'Install',
        'title' => 'Four steps on Android',
        'sub' => 'The app is installed straight from opescare.com.',
        'steps' => [
            'download' => ['title' => 'Download', 'desc' => 'Tap the download button. Your phone saves opescare.apk.'],
            'allow' => ['title' => 'Allow this source', 'desc' => 'When Android asks, allow your browser to install apps from this source. You only do this once.'],
            'open' => ['title' => 'Open', 'desc' => 'Open the downloaded file and tap Install, then Open.'],
            'sign_in' => ['title' => 'Sign in', 'desc' => 'Sign in with your Health ID, or create one in a few minutes.'],
        ],
        'play_note' => 'A Google Play listing is on its way. Until then, this page is the official source of the app.',
    ],

    'features' => [
        'eyebrow' => 'In the app',
        'title' => 'What the app does',
        'cards' => [
            'health_id' => ['title' => 'Health ID', 'desc' => 'Your digital Health ID card with a QR code a facility can verify.'],
            'records' => ['title' => 'Records and results', 'desc' => 'Your visits, lab results, prescriptions and vaccinations in one place.'],
            'consent' => ['title' => 'Consent and access log', 'desc' => 'Approve or refuse requests to open your record, and see every time it was opened.'],
            'family' => ['title' => 'Family', 'desc' => 'Look after a child or a parent from your own account, as their guardian.'],
            'appointments' => ['title' => 'Appointments', 'desc' => 'Book a visit with a facility and keep track of what is coming up.'],
            'finders' => ['title' => 'Find medicine and blood', 'desc' => 'Search for a medicine in stock or blood availability near you.'],
        ],
    ],

    'verify' => [
        'eyebrow' => 'Verify your download',
        'title' => 'Check the file is the real one',
        'sub' => 'The SHA-256 fingerprint of the official file is below. If yours matches, the file is exactly the one we published.',
        'label' => 'SHA-256',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'how_title' => 'How to check',
        'how_windows' => 'Windows (PowerShell): Get-FileHash opescare.apk -Algorithm SHA256',
        'how_mac' => 'macOS or Linux: shasum -a 256 opescare.apk',
        'how_android' => 'On Android, a file manager or checksum app that shows SHA-256 works too.',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Frequently asked',
        'categories' => [
            [
                'items' => [
                    ['q' => 'Is there an iPhone app?', 'a' => 'Not yet. On an iPhone, open the OpesCare web app in your browser: it does the same things and needs no install.'],
                    ['q' => 'Is it safe to install an app from outside the Play Store?', 'a' => 'This file is published by OPES Health Systems on opescare.com and served over HTTPS. Compare its SHA-256 fingerprint with the one on this page to be sure it was not changed.'],
                    ['q' => 'How do I get updates?', 'a' => 'The app checks at startup. When a newer build is required, it tells you and sends you here to download it.'],
                    ['q' => 'Does the app cost anything?', 'a' => 'No. The app is free to download and use.'],
                ],
            ],
        ],
    ],
];
