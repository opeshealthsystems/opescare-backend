<?php

// Public page /developers (resources/views/public/developers.blade.php).
// Every claim maps to code: routes/api.php (v1/connect, v1/care-map),
// routes/fhir.php + App\Services\Fhir\FhirIngestGuard, routes/web.php
// (/signup/developer, /developer portal, production requests), api_plans.
// Ingest only: no FHIR reads, no outbound FHIR server, no HL7 v2.
return [
    'meta_title' => 'OpesCare API for developers',
    'meta_desc' => 'Build on OpesCare in Cameroon: the Connect API, FHIR R4 ingest against an existing Health ID with the patient\'s consent, stock sync, the care map API, webhooks and a sandbox.',
    'breadcrumb' => 'Developers',

    'hero' => [
        'kicker' => 'Developers',
        'title_1' => 'Build on OpesCare,',
        'title_2' => 'with consent built in.',
        'lead' => 'Connect a hospital system, a laboratory middleware or a pharmacy stock tool to OpesCare. Every clinical write is tied to an existing Health ID, to the facility on your token, and to the patient\'s consent.',
        'cta_access' => 'Request developer access',
        'cta_docs' => 'Read the documentation',
        'cta_portal' => 'Sign in to the developer portal',
        'panel_label' => 'Endpoints available today',
        'panel_title' => 'opescare.com/api',
        'row_token' => 'Client-credentials token',
        'row_fhir' => 'FHIR R4 ingest',
        'row_stock' => 'Pharmacy stock sync',
        'row_map' => 'Facility directory',
    ],

    'offer' => [
        'eyebrow' => 'Available today',
        'title' => 'What the API offers',
        'sub' => 'Only what runs on opescare.com today. Each item is covered in the documentation.',
        'cards' => [
            'auth' => ['title' => 'Connect API tokens', 'desc' => 'Exchange your client ID and secret for a short-lived bearer token, and introspect it. The facility your app acts for comes from the token, never from the request.'],
            'fhir' => ['title' => 'FHIR R4 ingest', 'desc' => 'Push Observation, DiagnosticReport and Immunization resources, or a transaction Bundle of them, into the record of an existing Health ID.'],
            'stock' => ['title' => 'Stock sync', 'desc' => 'Send pharmacy and blood stock from your own system so patients searching the finders see what you hold.'],
            'map' => ['title' => 'Care map API', 'desc' => 'Search the national facility directory: nearby facilities, medicine, laboratory tests and blood availability.'],
            'widget' => ['title' => 'Embeddable widget', 'desc' => 'Open a widget session from your server and embed OpesCare search in your own site.'],
            'webhooks' => ['title' => 'Webhooks', 'desc' => 'Receive events at your endpoint and inspect every delivery attempt from the developer portal.'],
            'sandbox' => ['title' => 'Sandbox', 'desc' => 'Every new app gets sandbox credentials, so you can build and test before you touch real patients.'],
            'docs' => ['title' => 'Documentation', 'desc' => 'Authentication, the API reference, SDK notes, webhooks, error codes and a playground, in one place.'],
        ],
    ],

    'fhir' => [
        'eyebrow' => 'FHIR R4',
        'title' => 'FHIR R4 inbound ingest',
        'sub' => 'A door for partner systems to write clinical data into OpesCare, guarded on three sides.',
        'b1_title' => 'Scoped bearer token',
        'b1_desc' => 'Each resource needs its SMART system scope: system/Observation.write, system/DiagnosticReport.write or system/Immunization.write. In a transaction Bundle, each entry is checked against your scopes.',
        'b2_title' => 'An existing Health ID',
        'b2_desc' => 'The subject is identified by the system https://opescare.com/fhir/health-id and a Health ID such as CM-HID-XXXX-XXXX-XXXX. A partner can never create a patient: unknown IDs are refused.',
        'b3_title' => 'The patient\'s consent',
        'b3_desc' => 'The patient must have granted your facility an active consent. Without it the write is refused with an OperationOutcome, and nothing is stored.',
        'b4_title' => 'Ingest only',
        'b4_desc' => 'This surface writes; it does not serve reads. There is no outbound FHIR server and no HL7 v2 interface.',
        'panel_label' => 'FHIR R4 endpoints',
        'panel_title' => '/api/fhir/R4',
        'row_metadata' => 'CapabilityStatement',
        'row_observation' => 'Observation',
        'row_report' => 'DiagnosticReport',
        'row_immunization' => 'Immunization',
        'row_bundle' => 'Transaction Bundle',
    ],

    'steps' => [
        'eyebrow' => 'Getting access',
        'title' => 'From request to production',
        'request_title' => 'Request access',
        'request_desc' => 'Tell us who you are and what you want to connect, on the developer sign-up form.',
        'review_title' => 'Review',
        'review_desc' => 'The OpesCare team reviews your request and gets back to you.',
        'portal_title' => 'Developer portal',
        'portal_desc' => 'Sign in, accept the API terms and open your developer account.',
        'keys_title' => 'Create an app',
        'keys_desc' => 'Register an app to get its client ID and secret. Rotate the secret whenever you need.',
        'sandbox_title' => 'Build in the sandbox',
        'sandbox_desc' => 'Test against sandbox credentials and watch your webhook deliveries.',
        'prod_title' => 'Go to production',
        'prod_desc' => 'Submit a production request. Once an OpesCare administrator approves it, your app gets production access.',
    ],

    'plans' => [
        'eyebrow' => 'Plans',
        'title' => 'API plans',
        'sub' => 'Prices in FCFA (XAF), billed monthly via MTN Mobile Money or Orange Money. The sandbox is free.',
    ],

    'cta' => [
        'title' => 'Ready to connect?',
        'sub' => 'Request access, or read the documentation first.',
        'interop' => 'How interoperability works',
    ],
];
