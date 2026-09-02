<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Homepage
    |--------------------------------------------------------------------------
    |
    | The homepage answers five questions and nothing more: what OpesCare is,
    | why it matters, how it works, what you can connect, and what to do next.
    | Anything that teaches a workflow, documents a module or sells to one
    | stakeholder belongs on its own page — see the Solutions, Network,
    | Interoperability and Trust pages.
    |
    */

    'hero' => [
        'badge'          => 'Health identity & interoperability',
        'title'          => 'Your Health ID. Your health record. Connected across healthcare.',
        'subtitle'       => 'A patient carries one verified identity and one longitudinal record. Facilities exchange what they are authorised to exchange — through the systems they already run.',
        'positioning'    => 'OpesCare connects health systems. It does not require every facility to use the same software.',
        'cta_primary'    => 'Connect a system',
        'cta_secondary'  => 'Get a Health ID',
        'fact_bilingual' => 'English and French throughout',
        'fact_standards' => 'HL7 FHIR R4',
        'fact_consent'   => 'Consent on every exchange',
    ],

    'problem' => [
        'title'    => 'Healthcare is fragmented.',
        'subtitle' => 'A patient moves between hospitals, clinics, laboratories and pharmacies. Their medical information does not move with them.',

        'identity_title'   => 'Fragmented patient identity',
        'identity_desc'    => 'The same person is a different patient at every facility. Nothing links those records back to one human being.',
        'records_title'    => 'Disconnected health records',
        'records_desc'     => 'Each system holds a fragment. Nobody holds the history, so care gets decided from a partial view.',
        'delay_title'      => 'Delayed access to critical information',
        'delay_desc'       => 'Allergies, current medication and recent results arrive after the decision that needed them — or never arrive at all.',
        'visibility_title' => 'Poor visibility and continuity',
        'visibility_desc'  => 'A patient cannot see who opened their record or why, and the next clinician starts again from nothing.',
    ],

    'answer' => [
        'title'    => 'One identity, one record, exchanged under consent.',
        'subtitle' => 'Five layers, in order. Each one is only possible because of the one before it.',

        'identity_title' => 'Health ID',
        'identity_desc'  => 'A portable, verified identity the patient owns and carries between facilities.',
        'index_title'    => 'Patient Index',
        'index_desc'     => 'The same person resolved to one record across systems — matched deliberately, never merged on a guess.',
        'trust_title'    => 'Trust & Access',
        'trust_desc'     => 'Who may see what, for what purpose, for how long — decided by the patient and recorded every time.',
        'interop_title'  => 'Interoperability',
        'interop_desc'   => 'Standards-based exchange with the systems a facility already runs. No replacement, no rip-out.',
        'care_title'     => 'Connected Care',
        'care_desc'      => 'The clinician in front of the patient has the history that patient\'s care depends on.',
    ],

    'exchange' => [
        'title'    => 'How information moves',
        'subtitle' => 'From an existing hospital system, through OpesCare, to another authorised system — and back again.',

        'source_title' => 'Existing hospital system',
        'source_desc'  => 'The HIS, EMR or register a facility already runs',
        'core_title'   => 'OpesCare',
        'core_desc'    => 'Identity, consent and exchange',
        'target_title' => 'Another authorised system',
        'target_desc'  => 'A laboratory, pharmacy, insurer or health authority',

        'step_identify_title'  => 'Identify',
        'step_identify_desc'   => 'The patient is resolved to their Health ID — by QR code, identifier or verified lookup.',
        'step_match_title'     => 'Match',
        'step_match_desc'      => 'That identity is matched against the patient index. An uncertain match goes to human review, never a silent merge.',
        'step_authorize_title' => 'Authorize',
        'step_authorize_desc'  => 'The requesting facility asks for the scope it needs. The patient approves, denies, or revokes later.',
        'step_exchange_title'  => 'Exchange',
        'step_exchange_desc'   => 'Only the approved scope crosses, in a standard format the receiving system can already read.',
        'step_record_title'    => 'Record',
        'step_record_desc'     => 'The exchange is written to the patient\'s timeline and to an audit trail neither side can quietly edit.',

        'transports_label' => 'Connect however you already work',
        'cta'              => 'Explore interoperability',
    ],

    'pillars' => [
        'title'    => 'What OpesCare gives the ecosystem',
        'subtitle' => 'Five capabilities. Everything else on this platform is built out of them.',

        'identity_title' => 'Health Identity',
        'identity_desc'  => 'One verified Health ID per patient, portable across every facility on the network.',
        'index_title'    => 'Patient Index',
        'index_desc'     => 'One person, one record — resolved across systems without probabilistic auto-merging.',
        'record_title'   => 'Longitudinal Record',
        'record_desc'    => 'Visits, results, prescriptions and referrals in one timeline that outlives any single facility.',
        'trust_title'    => 'Trust & Access',
        'trust_desc'     => 'Consent, purpose, scope and duration on every exchange, with an audit trail behind each one.',
        'interop_title'  => 'Interoperability',
        'interop_desc'   => 'HL7 FHIR R4 and a Connect surface that meets a facility on whatever system it already runs.',

        'also_label'     => 'Also connected',
        'also_referrals' => 'Interoperable referrals — the next provider receives the right clinical information, scoped.',
        'also_labs'      => 'Laboratory orders and verified results, linked to the patient record.',
        'also_pharmacy'  => 'Prescriptions and dispensing, connected to the patient\'s medication history.',
        'also_insurance' => 'Insurance coverage as an attribute of the Health ID — read-only, and it travels with the patient.',
        'also_more'      => 'Learn more',
    ],

    'network' => [
        'title'    => 'Network services',
        'subtitle' => 'Two things a patient needs to find urgently, answered by the network instead of by phoning around.',

        'medicine_title' => 'Medicine Finder',
        'medicine_desc'  => 'See which pharmacies on the network hold a medicine, and when they last said so.',
        'medicine_cta'   => 'How the Medicine Finder works',
        'blood_title'    => 'Blood Finder',
        'blood_desc'     => 'Find available blood by group and component across connected hospitals and blood banks.',
        'blood_cta'      => 'How the Blood Finder works',

        'note' => 'Availability is published by the facility that holds it and timestamped. It is information to act on, not a reservation.',
    ],

    'ecosystem' => [
        'title'    => 'Who connects to OpesCare',
        'subtitle' => 'Everyone in the patient\'s journey, each through the surface that fits them.',

        'chip_patients'      => 'Patients',
        'chip_providers'     => 'Providers',
        'chip_hospitals'     => 'Hospitals',
        'chip_labs'          => 'Laboratories',
        'chip_pharmacies'    => 'Pharmacies',
        'chip_insurers'      => 'Insurers',
        'chip_public_health' => 'Public Health',
        'chip_developers'    => 'Developers',

        'card_patients_title'   => 'For patients',
        'card_patients_desc'    => 'Carry one Health ID, decide who sees your record, and find care faster.',
        'card_facilities_title' => 'For health facilities',
        'card_facilities_desc'  => 'Register patients, document care and exchange records without replacing your system.',
        'card_orgs_title'       => 'For organizations',
        'card_orgs_desc'        => 'Insurers and health authorities, connected to verified identity and coverage data.',
        'card_devs_title'       => 'For developers',
        'card_devs_desc'        => 'FHIR R4, a Connect API, SDKs, an embeddable widget, and a sandbox to build against.',
    ],

    'trust' => [
        'title' => 'Patients stay in control.',
        'desc'  => 'Access is a decision, not a default. Before a facility sees anything, the patient is told exactly what is being asked for — and can refuse, or change their mind later.',

        'q_who'       => 'Who is asking?',
        'q_why'       => 'Why do they need it?',
        'q_what'      => 'What data, exactly?',
        'q_how_long'  => 'For how long?',
        'q_control'   => 'Approve, deny, or revoke.',
        'consent_cta' => 'How consent works',

        'emergency_title' => 'When normal access isn\'t possible',
        'emergency_desc'  => 'Break-glass access opens a limited emergency profile — identity, blood group, allergies, active conditions and an emergency contact. It requires a stated reason, alerts the patient, and is reviewed afterwards.',
        'emergency_cta'   => 'About emergency access',

        'pillar_private_title'   => 'Private by design',
        'pillar_private_desc'    => 'Minimum necessary access, enforced per role and per purpose.',
        'pillar_audit_title'     => 'Auditable',
        'pillar_audit_desc'      => 'Every access recorded, and visible to the patient it belongs to.',
        'pillar_standards_title' => 'Standards-based',
        'pillar_standards_desc'  => 'HL7 FHIR R4 and OAuth 2.0. No proprietary lock-in.',
        'pillar_local_title'     => 'Built for African health systems',
        'pillar_local_desc'      => 'Cameroon-first and MINSANTE-aligned, in English and French throughout.',

        'security_cta' => 'Security & Trust Center',
    ],

    'footer_cta' => [
        'title'         => 'Connect your health system to the OpesCare network.',
        'subtitle'      => 'Give patients one Health ID, and give the clinician in front of them the history their care depends on.',
        'cta_primary'   => 'Connect a system',
        'cta_secondary' => 'Get a Health ID',
        'faq_prompt'    => 'Still have questions?',
        'faq_cta'       => 'Read the FAQ',
    ],
    'nav' => [
        'security'          => 'Security',
        'contact'           => 'Contact',
        'how_it_works'      => 'How It Works',
        'demo'              => 'Request Demo',
        'product'           => 'Product',
        'solutions'         => 'Solutions',
        'interop'           => 'Interoperability',
        'resources'         => 'Resources',
        'sign_in'           => 'Sign In',
        'get_started'       => 'Create Health ID',
        // Link-level nav labels (mobile drawer + dropdown items)
        'company'           => 'Company',
        'about'             => 'About Opes Health Systems',
        'security_page'     => 'Security Standards',
        'privacy'           => 'Privacy Policy',
        'privacy_short'     => 'Privacy',
        'terms'             => 'Terms of Service',
        'terms_short'       => 'Terms',
        'contact_support'   => 'Contact Support',
        'faq'               => 'FAQ',
        'help_center'       => 'Help Center',
        'system_status'     => 'System Status',
        'how_it_works_link' => 'How OpesCare Works',
        'health_id'         => 'Health ID',
        'consent_access'    => 'Consent & Access',
        'care_map'          => 'Care Map',
        'emergency_access'  => 'Emergency Access',
        'for_patients'      => 'For Patients',
        'for_hospitals'     => 'For Hospitals & Clinics',
        'for_pharmacies'    => 'For Pharmacies',
        'for_labs'          => 'For Laboratories',
        'for_insurers'      => 'For Insurers',
        'for_public_health' => 'For Public Health',
        'interop_overview'  => 'Overview',
        'api_sdk'           => 'Connect API & SDK',
        'partnerships'      => 'Partnerships',
        // Platform / Network groups (restructured navigation)
        'platform'             => 'Platform',
        'health_record'        => 'Health Record',
        'trust_access'         => 'Trust & Access',
        'network'              => 'Network',
        'medicine_finder'      => 'Medicine Finder',
        'blood_finder'         => 'Blood Finder',
        'connected_facilities' => 'Connected Facilities',
        'developers'           => 'Developers',
    ],

    'footer' => [
        'desc'          => 'OpesCare is a digital Health ID and healthcare interoperability platform by Opes Health Systems Sarl.',
        'col_product'   => 'Product',
        'col_orgs'      => 'For Organizations',
        'col_devs'      => 'Developers',
        'col_company'   => 'Company',
        'copyright'     => '© ' . date('Y') . ' OpesCare. A digital Health ID and healthcare interoperability platform by Opes Health Systems Sarl. All rights reserved.',
        'product_links' => ['Health ID', 'Patient Timeline', 'Consent Control', 'Emergency Access', 'Medication Availability', 'Blood Network'],
        'org_links'     => ['Hospitals & Clinics', 'Laboratories', 'Pharmacies', 'Insurers', 'Public Health Organizations'],
        'dev_links'     => ['Connect API', 'Connect SDK', 'Connect Widget', 'Bridge Agent', 'Webhooks & Alerts'],
        'company_links' => ['About Opes Health Systems', 'Security Standards', 'Privacy Policy', 'Terms of Service', 'Partnerships'],
        // Individual link labels used in footer columns and bottom bar
        'link_how_it_works'  => 'How OpesCare Works',
        'link_health_id'     => 'Health ID',
        'link_timeline'      => 'Patient Timeline',
        'link_consent'       => 'Consent Control',
        'link_emergency'     => 'Emergency Access',
        'link_medication'    => 'Medicine Finder',
        'link_blood'         => 'Blood Finder',
        'link_hospitals'     => 'Hospitals & Clinics',
        'link_labs'          => 'Laboratories',
        'link_pharmacies'    => 'Pharmacies',
        'link_insurers'      => 'Insurers',
        'link_public_health' => 'Public Health Orgs',
        'link_api'           => 'Connect API',
        'link_sdk'           => 'Connect SDK',
        'link_widget'        => 'Connect Widget',
        'link_bridge'        => 'Bridge Agent',
        'link_webhooks'      => 'Webhooks & Alerts',
        'link_interop'       => 'Interoperability Overview',
        'link_about'         => 'About Opes Health Systems',
        'link_security'      => 'Security Standards',
        'link_privacy'       => 'Privacy Policy',
        'link_terms'         => 'Terms of Service',
        'link_faq'           => 'FAQ',
        'link_partnerships'  => 'Partnerships',
        'link_status'        => 'System Status',
    ],

    /* ── Hero identity object ───────────────────────── */
    'hero_card' => [
        'demo_id'         => 'CM-HID-7KQ9-MP42-X8D1',
        'label_health_id' => 'Health ID',
        'label_verified'  => 'Verified',
    ],

    /* ── Page meta ──────────────────────────────────── */
    'page_title_home' => 'OpesCare | Health Identity and Interoperability for Connected Care',
    'page_desc_home'  => 'OpesCare is a health identity and interoperability platform. Patients carry one verified Health ID and one longitudinal record; facilities exchange authorised information through the systems they already run.',
];
