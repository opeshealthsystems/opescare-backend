<?php

// Public page /health-id (resources/views/public/health-id.blade.php).
// Every claim here is backed by code: HealthIdGeneratorService (CM-HID format, check
// block, safe alphabet), Patient::findExistingIdentity + PatientIdentityService
// (duplicate refusal, never a merge), the sign-up / guardian / front-desk paths,
// HealthIdCardPayload + QrTokenService (card QR, 15-minute live code), VerifyController
// (/verify/qr states, /verify/health-id masked vs staff view, every lookup audited),
// Patient::requiresConsentForFullRecord / allowsEmergencyAccess, ClinicalRelationshipGate,
// EmergencyAccessService, routes/fhir.php + FhirIngestGuard, HealthIdLogin, the MPI desk.
// Do not add capabilities the code does not have.
return [
    'meta_title' => 'The OpesCare Health ID',
    'meta_desc' => 'Everything about the OpesCare Health ID in Cameroon: the CM-HID number, how to get one, the card and its secure QR, what it holds, privacy and consent, the access log, emergency access, duplicates and the Master Patient Index.',
    'breadcrumb' => 'Health ID',

    'hero' => [
        'kicker' => 'Health ID',
        'title_1' => 'One number for your care,',
        'title_2' => 'wherever you are seen.',
        'lead' => 'The OpesCare Health ID is a personal identifier, in the form CM-HID-XXXX-XXXX-XXXX, under which every facility on OpesCare files your care. You carry it on a card with a secure QR and in the app, and you decide who sees your full record.',
        'cta_primary' => 'Get your Health ID',
        'cta_secondary' => 'Download the app',
    ],

    'net' => [
        'label' => 'The Health ID at the centre of the patient\'s care',
        'core' => 'Health ID',
        'core_sub' => 'CM-HID · card · QR',
        'card' => 'Your card',
        'card_sub' => 'QR and live code',
        'desk' => 'Front desk',
        'desk_sub' => 'Finds you by ID',
        'verify' => 'Verifier',
        'verify_sub' => 'Confirms it is you',
        'log' => 'Access log',
        'log_sub' => 'Who opened what',
    ],

    'what' => [
        'eyebrow' => 'What it is',
        'title' => 'Your Health ID, in plain language.',
        'sub' => 'A Health ID is a number that belongs to one person. Clinics, hospitals, laboratories and pharmacies use it to file and find your care, so that a result or a prescription reaches you and not someone with the same name.',
        'b1_title' => 'A number built to be read correctly',
        'b1_desc' => 'CM for Cameroon, then two blocks and a final check block. Look-alike characters such as 0 and O, or 1 and I, are never used, and the check block catches a mistyped number.',
        'b2_title' => 'One per person',
        'b2_desc' => 'Each Health ID is unique. Before one is issued, OpesCare checks whether the person already has one.',
        'b3_title' => 'An identifier, not a password',
        'b3_desc' => 'Knowing a Health ID does not open a record. Signing in still needs your password, and a clinician still needs a care relationship or your consent.',
        'panel_label' => 'An example Health ID broken into its parts',
        'panel_title' => 'Reading a Health ID',
        'row1' => 'CM',
        'row1_pill' => 'Country: Cameroon',
        'row2' => 'HID',
        'row2_pill' => 'Health ID',
        'row3' => 'XXXX-XXXX-XXXX',
        'row3_pill' => 'Two blocks + a check block',
    ],

    'get' => [
        'eyebrow' => 'How to get one',
        'title' => 'Three ways to a Health ID.',
        'sub' => 'Whichever way you register, the same duplicate check runs first.',
        'items' => [
            'self' => ['title' => 'Sign up yourself', 'desc' => 'Create an account in the app, or on the website and then complete your profile, with your name and date of birth. Your Health ID is issued once those details are in.'],
            'desk' => ['title' => 'At a facility front desk', 'desc' => 'A receptionist at a facility on OpesCare can register you. If you already hold a Health ID, the desk is told so instead of creating a second one.'],
            'family' => ['title' => 'For a child, by a guardian', 'desc' => 'A parent or guardian registers a child from their own account, even a newborn. The child\'s Health ID starts as provisional, and the guardian manages it through a family link.'],
        ],
    ],

    'card' => [
        'eyebrow' => 'The card',
        'title' => 'A card anyone at the desk can check.',
        'sub' => 'Your Health ID card is in the app and can be downloaded as a PDF. It shows your name, Health ID, sex, date of birth, blood group and photo, with a QR code.',
        'items' => [
            'qr' => ['title' => 'A secure QR, not your data', 'desc' => 'The QR holds a link to the OpesCare verifier, not your details. The code on the app card is renewed every day.'],
            'live' => ['title' => 'Presentation Mode', 'desc' => 'At a desk, open Presentation Mode in the app. It shows a live code valid for 15 minutes, with a countdown, and removes the QR the moment it expires.'],
            'qrpage' => ['title' => 'The verifier at /verify/qr', 'desc' => 'Scanning the code opens a page that says verified, expired, revoked or invalid. A verified scan shows name, Health ID, date of birth, sex and blood group, and records a reference.'],
            'lookup' => ['title' => 'Typed lookup at /verify/health-id', 'desc' => 'Staff can type a Health ID and a purpose. The public sees a masked card; signed-in facility staff see the full identity. Every lookup is recorded.'],
        ],
    ],

    'holds' => [
        'eyebrow' => 'What it holds',
        'title' => 'What is filed under your Health ID.',
        'sub' => 'Your identity on the card, and the care facilities record against it.',
        'identity' => 'Identity',
        'identity_desc' => 'Name, date of birth, sex, phone, photo and an emergency contact. Date of birth and phone are stored encrypted.',
        'blood' => 'Blood group',
        'blood_desc' => 'Shown on the card and to a verifier, because it matters in an emergency.',
        'allergies' => 'Allergies',
        'allergies_desc' => 'Part of the preview a clinician with a care relationship sees first.',
        'care' => 'Care record',
        'care_desc' => 'Diagnoses, prescriptions, vital signs, laboratory results, immunisations and care plans, filed by the facilities that cared for you.',
    ],

    'flow' => [
        'eyebrow' => 'How it works',
        'title' => 'From registration to recorded care.',
        'sub' => 'A person registers, is issued a Health ID, and is later seen at a facility. This is what happens at each step.',
        'label' => 'The Health ID journey',
        'pause' => 'Pause',
        'play' => 'Play',
        'prev' => 'Previous step',
        'next' => 'Next step',
        'step_of' => 'Step :n of :total',
        'steps' => [
            ['who' => 'Patient', 'title' => 'Registration', 'desc' => 'The person signs up, is registered at a front desk, or is registered by a guardian, with name and date of birth.', 'chip' => 'Details entered'],
            ['who' => 'OpesCare', 'title' => 'Duplicate check', 'desc' => 'OpesCare looks for an existing Health ID with the same name and date of birth, or the same phone. If one exists, registration stops and points to the existing ID. Nothing is merged automatically.', 'chip' => 'No second ID'],
            ['who' => 'OpesCare', 'title' => 'Health ID issued', 'desc' => 'A unique CM-HID number is generated with its check block, and the card with its QR appears in the app.', 'chip' => 'CM-HID issued'],
            ['who' => 'Facility', 'title' => 'Presented and verified', 'desc' => 'At a facility the patient shows the card or a live code from Presentation Mode. The desk scans it and the verifier confirms the identity.', 'chip' => 'Identity verified'],
            ['who' => 'Patient', 'title' => 'Consent', 'desc' => 'A care relationship such as an appointment opens a preview. When the patient keeps the full record behind consent, as the app does by default, the facility asks and the patient approves in the app.', 'chip' => 'Consent grant'],
            ['who' => 'Facility + patient', 'title' => 'Care recorded', 'desc' => 'Results, prescriptions and notes are filed under the same Health ID. Every section opened writes an entry to the access log the patient reads.', 'chip' => 'Access log'],
        ],
    ],

    'privacy' => [
        'eyebrow' => 'Privacy and consent',
        'title' => 'You decide who sees your full record.',
        'sub' => 'Two switches in your privacy settings, and a log of everything that was opened.',
        'items' => [
            'consent' => ['title' => 'Require consent for my full record', 'desc' => 'On by default. A clinician with a care relationship then sees only your identity, allergies and current medicines; the full record needs your approval in the app.'],
            'glass' => ['title' => 'Allow emergency access', 'desc' => 'On by default. In an emergency a clinician can break glass with a written reason; access lasts four hours, you are alerted with the reason, and it is reviewed. Turn it off and break-glass is refused.'],
            'log' => ['title' => 'The access log', 'desc' => 'Each section of your record opened is its own entry: who, from which facility, and on what basis. You read it in the app.'],
            'grants' => ['title' => 'Grants you can withdraw', 'desc' => 'Each consent grant is to one facility, has an end date, and can be withdrawn by you at any time.'],
        ],
    ],

    'layer' => [
        'eyebrow' => 'The interoperability layer',
        'title' => 'How the Health ID connects facilities.',
        'sub' => 'The Health ID is what lets a clinic, a hospital, a laboratory and a pharmacy work on the same patient. The layer around it has four parts.',
        'items' => [
            'gate' => ['title' => 'A consent gate on every read', 'desc' => 'Before any clinician opens a record, OpesCare looks for a basis: an appointment, the care team, the treating clinician, a recent episode at the facility, or a consent grant. No basis, no record.'],
            'scope' => ['title' => 'Scoped reads', 'desc' => 'What opens depends on the basis and your settings: a preview of identity, allergies and current medicines, or the full record when you granted it.'],
            'fhir' => ['title' => 'FHIR R4 data coming in', 'desc' => 'Partner systems can send DiagnosticReport, Immunization and Observation resources, or a transaction bundle, with a signed token. Each must name an existing Health ID and needs your consent grant to that facility. Partners cannot create Health IDs or read records out.'],
            'log' => ['title' => 'Access logging', 'desc' => 'Every opening is written to the access log with its basis, for you in the app and for the OpesCare audit trail.'],
        ],
        'more' => 'How interoperability works',
    ],

    'mpi' => [
        'eyebrow' => 'Duplicates and the Master Patient Index',
        'title' => 'One person, one Health ID, even after mistakes.',
        'sub' => 'Sometimes the same person is registered twice, for example with a misspelt name. The Master Patient Index finds likely pairs on a schedule, a records officer reviews them, and a merge can be reversed. A retired Health ID keeps working: it leads to the surviving one.',
        'more' => 'How the Master Patient Index works',
    ],

    'lost' => [
        'eyebrow' => 'Lost card or forgotten password',
        'title' => 'Your Health ID does not change.',
        'items' => [
            'card' => ['title' => 'Lost the card', 'desc' => 'Sign in and download the card again from the app or the patient portal. The number stays the same.'],
            'signin' => ['title' => 'Sign in with your Health ID', 'desc' => 'You can sign in with your Health ID instead of your email or phone. It only names the account; your password is always asked.'],
            'password' => ['title' => 'Forgot your password', 'desc' => 'Reset it from the sign-in page. If you try to register again, OpesCare tells you a Health ID already exists instead of issuing a second one.'],
        ],
    ],

    'family' => [
        'eyebrow' => 'Family',
        'title' => 'Health IDs for the people you care for.',
        'sub' => 'A guardian can register a child, or invite an adult who already has a Health ID, through a family link. The invited person accepts or declines. A guardian then switches to the dependent in the app to see their card, records and appointments.',
        'cta' => 'Register a child',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Frequently asked questions',
        'categories' => [
            ['items' => [
                ['q' => 'What is the OpesCare Health ID?', 'a' => 'It is a unique personal identifier in the form CM-HID-XXXX-XXXX-XXXX. Every facility on OpesCare files your care under it, and you carry it on a card with a secure QR and in the app.'],
                ['q' => 'How do I get a Health ID?', 'a' => 'Sign up on the website or in the app, be registered at the front desk of a facility on OpesCare, or be registered by a parent or guardian if you are a child.'],
                ['q' => 'Can I end up with two Health IDs?', 'a' => 'OpesCare checks name and date of birth, and phone, before issuing one, and refuses a second ID for a match. If two records for one person slip through, the Master Patient Index finds them and a records officer merges them, reversibly. Nothing is merged without a person deciding.'],
                ['q' => 'Can someone read my record if they know my Health ID?', 'a' => 'No. The Health ID only identifies you. A clinician needs a care relationship or your consent, and with the consent switch on, the full record needs your approval. Every opening is logged.'],
                ['q' => 'What does the QR on my card show?', 'a' => 'It opens the OpesCare verifier, which confirms your name, Health ID, date of birth, sex and blood group. The QR itself holds no personal details, and the live code in Presentation Mode expires after 15 minutes.'],
                ['q' => 'I lost my card. What now?', 'a' => 'Your Health ID does not change. Sign in, with your Health ID if you like, and download the card again.'],
                ['q' => 'Is OpesCare available in French?', 'a' => 'Yes. Every page, the app and the facility desks work in English and French.'],
            ]],
        ],
    ],

    'cta' => [
        'title' => 'Get your Health ID today.',
        'sub' => 'Create your Health ID in a few minutes, then carry it in the app.',
        'primary' => 'Get your Health ID',
        'secondary' => 'Download the app',
    ],
];
