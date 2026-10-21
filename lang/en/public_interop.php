<?php

// Public page /interoperability (resources/views/public/interoperability.blade.php).
// Every claim here is backed by code: ClinicalRelationshipGate, the consent-request
// routes, access_logs, EmergencyAccessService, routes/fhir.php + FhirIngestGuard,
// MedicalRecordExportController::exportFhir, /verify/doc. Do not add capabilities
// the code does not have.
return [
    'meta_title' => 'Healthcare interoperability in Cameroon',
    'meta_desc' => 'How OpesCare lets clinics, hospitals, laboratories and pharmacies in Cameroon share what care needs through one Health ID, with the patient\'s consent and an access log the patient can read.',
    'breadcrumb' => 'Interoperability',

    'hero' => [
        'kicker' => 'Interoperability',
        'title_1' => 'Records that follow the patient,',
        'title_2' => 'only with their consent.',
        'lead' => 'Interoperability means a clinic, a hospital, a laboratory and a pharmacy can share what a patient\'s care needs, about the same person, without paper letters, phone calls or repeated tests. OpesCare does it through the Health ID, the patient\'s consent and an access log both sides can rely on.',
        'cta_register' => 'Register your facility',
        'cta_solutions' => 'See the systems',
    ],

    'net' => [
        'label' => 'Facilities connected through the OpesCare layer',
        'core' => 'OpesCare layer',
        'core_sub' => 'Health ID · consent · access log',
        'hospital' => 'Hospital',
        'hospital_sub' => 'Opens what is granted',
        'clinic' => 'Clinic',
        'clinic_sub' => 'Sends a referral',
        'lab' => 'Laboratory',
        'lab_sub' => 'Returns results',
        'pharmacy' => 'Pharmacy',
        'pharmacy_sub' => 'Dispenses the prescription',
    ],

    'what' => [
        'eyebrow' => 'What it is',
        'title' => 'Interoperability, in plain language.',
        'sub' => 'Today a patient who moves between facilities often carries their history on paper, or not at all. Interoperability is the ability of different facilities to exchange information about the same patient in a way each of them can trust.',
        'b1_title' => 'One patient, one identifier',
        'b1_desc' => 'Every facility files care under the same Health ID (CM-HID), so a referral or a result reaches the right person.',
        'b2_title' => 'Shared on purpose, not by default',
        'b2_desc' => 'Information moves when there is a care relationship or the patient\'s consent, and only as far as that allows.',
        'b3_title' => 'Every opening is recorded',
        'b3_desc' => 'Each time a clinician opens part of a record, an entry is written to an access log the patient can read in their app.',
        'panel_label' => 'Before and after interoperability',
        'panel_title' => 'What changes',
        'row1' => 'Handwritten referral letter',
        'row1_pill' => 'Referral on the Health ID',
        'row2' => 'Results lost between facilities',
        'row2_pill' => 'Filed on the same record',
        'row3' => 'No idea who looked',
        'row3_pill' => 'Access log in the app',
    ],

    'flow' => [
        'eyebrow' => 'How it works',
        'title' => 'One referral, step by step.',
        'sub' => 'A patient is referred from a clinic to a hospital, and a laboratory result comes back. This is what happens at each step.',
        'label' => 'The referral journey',
        'pause' => 'Pause',
        'play' => 'Play',
        'prev' => 'Previous step',
        'next' => 'Next step',
        'step_of' => 'Step :n of :total',
        'steps' => [
            ['who' => 'Clinic', 'title' => 'The clinic refers', 'desc' => 'A clinician creates a referral to a hospital from the patient\'s chart. The referring facility is taken from the clinician\'s signed-in session, never from a form field.', 'chip' => 'Referral sent'],
            ['who' => 'Patient', 'title' => 'The patient approves', 'desc' => 'The hospital asks for access using the exact Health ID. The request appears in the patient\'s app, where they approve or decline. An approval becomes a consent grant to that facility, with an end date, which the patient can withdraw.', 'chip' => 'Consent grant'],
            ['who' => 'OpesCare layer', 'title' => 'OpesCare checks', 'desc' => 'Before any part of the record opens, OpesCare looks for a care relationship: an appointment, the care team, the treating clinician, a recent episode at that facility, or a consent grant. No relationship, no record.', 'chip' => 'Basis recorded'],
            ['who' => 'Hospital', 'title' => 'The hospital opens what is granted', 'desc' => 'When the patient keeps their record behind consent, as the app does by default, a relationship alone opens only a preview: identity, allergies and current medicines. The full record needs the patient\'s grant.', 'chip' => 'Preview or full record'],
            ['who' => 'Laboratory', 'title' => 'The result comes back', 'desc' => 'The laboratory files the result against the same Health ID. A lab\'s own system can send it as a FHIR R4 DiagnosticReport or Observation, accepted only when the patient has granted that lab access.', 'chip' => 'Filed on the Health ID'],
            ['who' => 'Both sides', 'title' => 'Both sides keep the log', 'desc' => 'Every section opened writes an access-log entry: who, from which facility, and on what basis. The patient reads it in the app, and the entry stays in the OpesCare audit trail.', 'chip' => 'Access log'],
        ],
    ],

    'how' => [
        'eyebrow' => 'How OpesCare makes it possible',
        'title' => 'The pieces behind every exchange.',
        'sub' => 'Each of these is part of OpesCare today.',
        'cards' => [
            'id' => ['title' => 'One Health ID', 'desc' => 'One identifier per person, carried on a card with a QR code and in the app. Facilities ask for a record by the exact Health ID, never by browsing a list.'],
            'gate' => ['title' => 'Five care-relationship checks', 'desc' => 'Appointment, care team, treating clinician, a recent episode at the same facility, or a consent grant. One rule, applied to every clinician read.'],
            'consent' => ['title' => 'Consent requests and grants', 'desc' => 'A facility asks; the patient approves or declines in the app. Grants are per facility, carry an end date and can be withdrawn.'],
            'log' => ['title' => 'An access log the patient reads', 'desc' => 'Each section of a record opened is its own entry, with the basis it was opened on.'],
            'glass' => ['title' => 'Emergency access, accounted for', 'desc' => 'Break-glass needs a written reason, lasts four hours, alerts the patient with that reason and is reviewed. A patient can switch emergency access off.'],
            'fhir' => ['title' => 'FHIR R4 for partner systems', 'desc' => 'Partner systems can send DiagnosticReport, Immunization and Observation resources, or a transaction bundle, with a signed token. The patient must already hold a Health ID; a partner cannot create one.'],
            'docs' => ['title' => 'Documents anyone can verify', 'desc' => 'PDFs issued from OpesCare carry a QR code that opens a verification page for that document.'],
            'export' => ['title' => 'The patient\'s own copy', 'desc' => 'A patient can export their record from the app as a FHIR file and decide whom to give it to.'],
        ],
    ],

    'means' => [
        'eyebrow' => 'What it means',
        'title' => 'For patients and for facilities.',
        'patients' => 'For patients',
        'facilities' => 'For facilities',
        'p1_title' => 'You decide who sees your full record',
        'p1_desc' => 'Keep the consent switch on and only facilities you approve go beyond the preview.',
        'p2_title' => 'You see who opened it',
        'p2_desc' => 'Your access history lists every opening, the facility and the reason.',
        'p3_title' => 'Less paper, fewer repeated tests',
        'p3_desc' => 'Referrals and results travel on your Health ID between the facilities you use.',
        'f1_title' => 'Referrals that arrive ready',
        'f1_desc' => 'The patient, the referral and the consent are all on the same Health ID.',
        'f2_title' => 'Results on the right patient',
        'f2_desc' => 'Every write is matched to an existing Health ID, never to a name typed again.',
        'f3_title' => 'A recorded basis for every access',
        'f3_desc' => 'When someone asks why your staff opened a record, the answer is already written down.',
    ],

    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'Frequently asked questions',
        'categories' => [
            ['items' => [
                ['q' => 'What is healthcare interoperability?', 'a' => 'It is the ability of different health facilities to exchange information about the same patient in a form each can use and trust. On OpesCare that exchange runs through the patient\'s Health ID, with consent and an access log.'],
                ['q' => 'Can a hospital read my whole record without asking me?', 'a' => 'Not while the "require consent for full record" switch in your app is on, and it is on by default. A care relationship such as an appointment then opens only your identity, allergies and current medicines. The full record needs a consent grant from you, and every opening is logged.'],
                ['q' => 'What happens in an emergency?', 'a' => 'A clinician can use emergency access (break-glass) by writing a reason. The access lasts four hours, you are alerted with that reason, and the event is reviewed. You can turn emergency access off in your privacy settings.'],
                ['q' => 'Can our hospital\'s own system send data to OpesCare?', 'a' => 'Yes. OpesCare accepts FHIR R4 DiagnosticReport, Immunization and Observation resources and transaction bundles from partner systems, with a signed token limited to those resources. Each write must name an existing Health ID and needs an active consent grant from that patient to your facility.'],
                ['q' => 'Can another system read patient records out of OpesCare?', 'a' => 'No. The partner interface accepts data in; it does not answer queries for patient records. A patient can export their own record from the app as a FHIR file and choose whom to give it to.'],
                ['q' => 'Is OpesCare available in French?', 'a' => 'Yes. Every page, the app and the facility desks work in English and French.'],
            ]],
        ],
    ],

    'cta' => [
        'title' => 'Connect your facility to the Health ID.',
        'sub' => 'Register your facility, verify it, and start receiving referrals and results on the same record as everyone else.',
    ],
];
