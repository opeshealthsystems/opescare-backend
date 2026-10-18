<?php

// Body of the public Terms and Conditions (resources/views/public/terms.blade.php).
// Strings are trusted, author-controlled HTML (inline <strong>/<em>/<a> only)
// and are rendered unescaped by that view. Never put user input here.
return [
    'effective_date_value' => '1 June 2026',
    'last_updated_value'   => '7 June 2026',
    'governing_law_value'  => 'Republic of Cameroon',
    'jurisdiction_value'   => 'Courts of Yaoundé, Cameroon',
    'languages_value'      => 'English (French available on request)',

    'nav' => [
        'parties'              => 'Parties',
        'platform'             => 'Platform Scope',
        'regulatory'           => 'Regulatory Framework',
        'eligibility'          => 'Eligibility',
        'provider_obligations' => 'Provider Obligations',
        'patient_obligations'  => 'Patient Obligations',
        'data'                 => 'Data Processing',
        'aup'                  => 'Acceptable Use',
        'ip'                   => 'Intellectual Property',
        'availability'         => 'Availability',
        'liability'            => 'Liability',
        'indemnity'            => 'Indemnity',
        'governing_law'        => 'Governing Law',
        'termination'          => 'Termination',
        'contact'              => 'Contact',
    ],

    'acceptance' => '<strong>By creating an account, registering a facility, or using any OpesCare service, you confirm that you have read, understood and agree to be bound by these Terms and Conditions in their entirety.</strong> If you do not agree, you must not use the OpesCare platform. Institutions using the platform on behalf of an employer or healthcare organisation confirm that a duly authorised representative has accepted these terms on behalf of that entity.',

    's1_title' => '1. Parties to This Agreement',
    's1_provider' => '<strong>Service Provider:</strong> Opes Health Systems Sarl, a company incorporated under the laws of the Republic of Cameroon, operating the OpesCare health interoperability platform ("OpesCare", "we", "us", "our").',
    's1_user'  => '<strong>User:</strong> Any individual, healthcare professional, institution, organisation, or developer ("you", "your") who accesses or uses the OpesCare platform in any capacity, including as:',
    's1_items' => [
        'A <strong>Patient</strong> — an individual registering to obtain or manage an OpesCare Health ID',
        'A <strong>Healthcare Provider</strong> — a licensed medical professional or staff member of a registered healthcare facility',
        'An <strong>Institutional Administrator</strong> — an entity registering a hospital, clinic, pharmacy, laboratory, or insurer on the platform',
        'A <strong>Developer or Technology Partner</strong> — an individual or organisation integrating with the OpesCare API, SDK, or related services',
    ],

    's2_title' => '2. Nature and Scope of the Platform',
    's2_intro' => 'OpesCare is a <strong>digital health interoperability and identity platform</strong>. Our services include:',
    's2_items' => [
        'Issuance and management of digital OpesCare Health IDs',
        'Secure, consent-governed exchange of patient health records between registered healthcare facilities',
        'Patient portal for health record review, consent management, and access auditing',
        'Staff and facility portals for clinical workflow support',
        'API, SDK, Widget, Bridge Agent, and Webhooks for third-party integration',
        'Medicine and blood availability verification tools',
        'Anonymous aggregated public health data reporting',
    ],
    's2_not_provider' => '<strong>OpesCare is not a healthcare provider.</strong> We do not diagnose, prescribe, treat, or offer any clinical advice. All clinical decisions are the sole responsibility of qualified, licensed healthcare professionals. We facilitate the exchange of health information to support — not replace — clinical judgment.',
    's2_strategy' => 'OpesCare operates in support of the <strong>Cameroon Ministry of Public Health (MINSANTE) National Digital Health Strategy 2026–2030</strong> and the WHO Global Strategy on Digital Health 2020–2025, with the objective of contributing to universal health coverage through interoperable, patient-centred digital health infrastructure.',

    's3_title' => '3. Applicable Regulatory Framework',
    's3_intro' => 'OpesCare operates under and in compliance with the following legal and regulatory instruments:',
    's3_laws' => [
        ['law' => 'Cameroon Law No. 2010/012 of 21 December 2010', 'desc' => 'On cybersecurity and cybercriminality — governs personal data protection, electronic evidence, and cybercrime.'],
        ['law' => 'Cameroon Law No. 2010/021 of 21 December 2010', 'desc' => 'On electronic commerce — governs online service provision, electronic contracts, and digital consumer rights.'],
        ['law' => 'Cameroon Law No. 2010/013 of 21 December 2010', 'desc' => 'On electronic communications — governs telecommunications and electronic service infrastructure.'],
        ['law' => 'Cameroon Law No. 96/03 of 4 January 1996', 'desc' => 'Framework Law on Health — defines the health system structure, patient rights, and obligations of health actors in Cameroon.'],
        ['law' => 'CNAMGS Regulations', 'desc' => 'Caisse Nationale d\'Assurance Maladie et de Garantie Sociale — governing health insurance and social coverage data in Cameroon.'],
        ['law' => 'WHO Global Strategy on Digital Health 2020–2025', 'desc' => 'International framework for digital health implementation, interoperability, data governance, and patient rights.'],
        ['law' => 'HL7 FHIR R4 / ISO 27799', 'desc' => 'International standards for health data interoperability and information security management in health organisations.'],
        ['law' => 'African Union Malabo Convention (2014)', 'desc' => 'African Union Convention on Cyber Security and Personal Data Protection (EX.CL/846(XXV)) — regional data protection framework.'],
        ['law' => 'MINSANTE National Digital Health Strategy 2026–2030', 'desc' => 'Cameroon\'s national roadmap for health digitalization, EHR implementation, interoperability, and telemedicine regulation.'],
    ],

    's4_title' => '4. Eligibility and Registration',
    's4_intro' => 'To register on and use the OpesCare platform:',
    's4_items' => [
        'You must be at least 18 years of age, or a parent or guardian registering a minor.',
        'You must provide accurate, complete, and current registration information. You agree to update this information promptly if it changes.',
        'Healthcare providers must hold a valid licence to practice issued by the competent Cameroonian professional authority (Ordre des Médecins, Ordre des Pharmaciens, Ordre National des Infirmiers, or equivalent).',
        'Healthcare institutions must hold a valid operating permit issued by MINSANTE or the competent regional health authority.',
        'Developers and technology partners must execute a separate OpesCare Partner Agreement before accessing the production API environment.',
        'You must not be prohibited by any applicable law from using the platform.',
    ],
    's4_outro' => 'We reserve the right to verify the credentials and licencing status of any user or institution at any time and to suspend or terminate accounts found to be non-compliant with eligibility requirements.',

    's5_title' => '5. Healthcare Provider and Institutional Obligations',
    's5_intro' => 'Healthcare providers and institutional administrators who access patient data through OpesCare agree to:',
    's5_items' => [
        '<strong>Authorised Purpose Only:</strong> Access patient records solely for direct, legitimate clinical or administrative purposes related to the care of the patient for whom consent has been granted.',
        '<strong>Staff Management:</strong> Ensure all staff with platform access are individually registered, appropriately trained, and authorised. You are responsible for revoking access for staff who leave or change roles.',
        '<strong>Consent Integrity:</strong> Submit consent requests only when there is a genuine and documented clinical need. Fabrication or misrepresentation of clinical purpose is a criminal offence under Cameroon law.',
        '<strong>Record Accuracy:</strong> Ensure that clinical data submitted to OpesCare is accurate, complete, and recorded in a timely manner consistent with professional obligations.',
        '<strong>Data Minimisation:</strong> Request and access only the categories of clinical data necessary for the stated clinical purpose.',
        '<strong>Emergency Access Accountability:</strong> Providers who invoke emergency access must record a documented clinical justification and are subject to post-event review by their facility\'s clinical governance team and by OpesCare compliance officers.',
        '<strong>Breach Reporting:</strong> Notify OpesCare immediately (and in any case within 24 hours) upon becoming aware of any actual or suspected data breach, unauthorised access, or misuse of platform credentials.',
        '<strong>Regulatory Compliance:</strong> Maintain all professional licences, facility permits, and regulatory registrations in good standing for the duration of platform use.',
        '<strong>No Onward Sharing:</strong> Not transfer, copy, print, photograph, or otherwise disclose patient data obtained through OpesCare to any unauthorised third party.',
    ],

    's6_title' => '6. Patient User Obligations',
    's6_intro' => 'Patients using the OpesCare platform agree to:',
    's6_items' => [
        '<strong>Accurate Identity Information:</strong> Provide accurate identity information during registration. You must not create a Health ID using another person\'s identity.',
        '<strong>Credential Security:</strong> Keep your login credentials, Health ID, and QR code secure. You must not share your account access with any other person. Notify us immediately at <a href="mailto:support@opescare.com">support@opescare.com</a> if you suspect your account has been compromised.',
        '<strong>Responsible Consent Management:</strong> Exercise your consent rights responsibly. You acknowledge that revoking consent for a provider currently involved in your active care may affect the quality or continuity of your treatment.',
        '<strong>Truthful Reporting:</strong> Not submit false reports, claims, or complaints about facilities or providers through the platform.',
        '<strong>One Account Per Person:</strong> Maintain only one OpesCare Health ID per individual. The creation of duplicate identities undermines patient safety and is prohibited.',
    ],

    's7_title' => '7. Health Data Processing',
    's7_privacy' => 'The processing of personal and health data through OpesCare is governed in detail by our <a href=":url" class="text-primary font-bold">Privacy Policy</a>, which forms an integral part of these Terms. By accepting these Terms, you acknowledge and consent to the processing described in the Privacy Policy.',
    's7_dpa'   => '<strong>Institutional Data Processing Agreement:</strong> Registered healthcare institutions are deemed to have executed a Data Processing Agreement (DPA) with Opes Health Systems Sarl by accepting these Terms. The DPA governs OpesCare\'s role as data processor for facility-sourced clinical records. Institutions that require a separate signed DPA for their own compliance purposes should contact <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a>.',
    's7_ownership' => '<strong>Record Ownership:</strong> The clinical records held on OpesCare remain the property of the patient. The originating healthcare facility retains responsibility for the accuracy and completeness of records it has submitted. OpesCare acts as a data intermediary and does not own or claim intellectual property rights over patient health data.',

    's8_title'  => '8. Acceptable Use Policy',
    's81_title' => '8.1 Permitted Uses',
    's81_items' => [
        'Accessing and managing your own Health ID and medical records as a patient',
        'Requesting, granting, or revoking consent for clinical record access',
        'Accessing patient records for which you hold a valid, current consent grant in your capacity as an authorised healthcare provider',
        'Integrating OpesCare services into healthcare applications under a valid Partner Agreement',
        'Using the medication and blood availability tools for clinical decision support',
        'Reviewing and responding to access logs and audit trail entries',
    ],
    's82_title' => '8.2 Prohibited Uses',
    's82_intro' => 'The following activities are strictly prohibited and may result in immediate account suspension, permanent termination, and referral to the relevant authorities including ANTIC, MINSANTE, professional regulatory bodies, and the Cameroonian judiciary:',
    's82_items' => [
        'Accessing patient records without a valid consent grant, outside of the documented emergency access protocol',
        'Using another person\'s credentials, QR code, or Health ID without their explicit authorisation',
        'Creating or using false clinical justifications to access patient data',
        'Attempting to reverse-engineer, circumvent, or attack the OpesCare platform or its security controls',
        'Scraping, bulk-downloading, or systematically extracting patient data beyond what is necessary for individual clinical encounters',
        'Using patient data obtained through OpesCare for insurance underwriting, employment decisions, law enforcement purposes, or marketing without lawful basis and explicit patient consent',
        'Sharing API credentials, access tokens, or authentication data with unauthorised parties',
        'Submitting false, misleading, or fabricated medical records to the platform',
        'Using the platform to facilitate any activity that violates Cameroon law, international health data standards, or professional medical ethics',
        'Reproducing, publishing, or distributing any part of the OpesCare platform, documentation, or patient data without written authorisation',
    ],

    's9_title' => '9. Intellectual Property',
    's9_paras' => [
        'All rights in the OpesCare platform, including software, designs, trademarks, logos, documentation, databases, and user interface elements, are the exclusive intellectual property of Opes Health Systems Sarl or its licensors, protected under the laws of the Republic of Cameroon, the Organisation Africaine de la Propriété Intellectuelle (OAPI), and applicable international intellectual property treaties.',
        'No use, reproduction, distribution, or creation of derivative works of OpesCare intellectual property is permitted without the prior written consent of Opes Health Systems Sarl.',
        'Patient health data and clinician-generated records are expressly excluded from the above; they remain the property of patients and originating facilities respectively.',
        'Open-source components used in the OpesCare platform are subject to their respective open-source licences. A list is available upon written request.',
    ],

    's10_title' => '10. Service Availability and Maintenance',
    's10_target' => 'OpesCare targets a minimum of 99.5% platform availability for core services (Health ID Registry, Consent Engine, Patient Portal, and API). Planned maintenance windows will be communicated at least 48 hours in advance via the <a href=":url" class="text-primary">System Status</a> page and in-platform notifications.',
    's10_paras' => [
        'We reserve the right to perform emergency maintenance at any time where necessary to protect the security or integrity of the platform or patient data.',
        'OpesCare does not guarantee continuous, uninterrupted availability. We are not liable for service interruptions caused by third-party infrastructure providers, telecommunications networks, force majeure events, or factors outside our reasonable control.',
    ],

    's11_title' => '11. Limitation of Liability',
    's11_intro' => 'To the fullest extent permitted by Cameroon Law and applicable regulations:',
    's11_items' => [
        '<strong>Platform Role:</strong> OpesCare provides infrastructure for health data exchange and is not responsible for the clinical accuracy, completeness, or currency of medical records submitted by healthcare facilities. All clinical decisions must be made by qualified, licensed healthcare professionals based on their professional judgment.',
        '<strong>No Clinical Liability:</strong> OpesCare expressly disclaims any liability for clinical outcomes, whether arising from the use of, or inability to use, patient records exchanged through the platform.',
        '<strong>Indirect Damages:</strong> OpesCare shall not be liable for any indirect, consequential, incidental, special, or exemplary damages arising from use or inability to use the platform, including loss of profit, data loss, or business interruption.',
        '<strong>Maximum Liability Cap:</strong> To the extent permitted by law, our total liability to any user in connection with these Terms shall not exceed the fees paid by that user to OpesCare in the twelve (12) months preceding the event giving rise to the claim.',
        '<strong>Patient Safety Exception:</strong> Nothing in this clause limits our liability for death or personal injury caused by our gross negligence or wilful misconduct, or for fraud or fraudulent misrepresentation, or for any liability that cannot be excluded or limited under Cameroon law.',
    ],
    's11_outro' => 'Healthcare providers bear sole and full professional responsibility for clinical decisions made in reliance on information accessed through OpesCare, consistent with their obligations under Cameroon Law No. 96/03 and the standards of their relevant professional regulatory body.',

    's12_title' => '12. Indemnification',
    's12_intro' => 'You agree to defend, indemnify and hold harmless Opes Health Systems Sarl, its officers, directors, employees, agents, and sub-processors from and against any claims, liabilities, damages, judgements, awards, losses, costs, expenses, and legal fees arising out of or relating to:',
    's12_items' => [
        'Your violation of these Terms',
        'Your misuse of patient data accessed through the platform',
        'Your unauthorised access to patient records',
        'Any breach of your professional obligations as a healthcare provider',
        'Any claim by a third party arising from your use of OpesCare services',
    ],

    's13_title' => '13. Force Majeure',
    's13_body'  => 'Neither party shall be in breach of these Terms for any delay or failure in performance caused by circumstances beyond their reasonable control, including but not limited to: acts of God, natural disasters, war, terrorism, civil unrest, actions of government authorities, public health emergencies declared by WHO or MINSANTE, telecommunications failures, or widespread internet infrastructure outages. Force majeure events will be notified promptly and performance obligations will be suspended for the duration of the event.',

    's14_title' => '14. Governing Law and Dispute Resolution',
    's14_paras' => [
        '<strong>Governing Law:</strong> These Terms are governed by and construed in accordance with the laws of the <strong>Republic of Cameroon</strong>, including Law No. 2010/012, Law No. 2010/021, and the Framework Law on Health No. 96/03.',
        '<strong>Amicable Resolution:</strong> In the event of a dispute arising from or in connection with these Terms, the parties agree to first attempt to resolve the matter amicably through direct negotiation for a period of thirty (30) days from the date of written notice of the dispute.',
        '<strong>Jurisdiction:</strong> If the dispute is not resolved amicably within that period, it shall be submitted to the exclusive jurisdiction of the competent courts of <strong>Yaoundé, Cameroon</strong>. Both parties irrevocably submit to the jurisdiction of those courts.',
        '<strong>Language:</strong> Proceedings shall be conducted in French or English, as determined by the presiding court.',
    ],

    's15_title'  => '15. Suspension and Termination',
    's151_title' => '15.1 By OpesCare',
    's151_intro' => 'We reserve the right to suspend or terminate your access to OpesCare immediately and without prior notice if:',
    's151_items' => [
        'You breach any provision of these Terms',
        'We receive a lawful order from ANTIC, MINSANTE, or a court requiring suspension or closure of your account',
        'You engage in any conduct that poses a risk to the security or integrity of the platform or patient data',
        'Your professional licence or facility permit is suspended or revoked by the relevant authority',
        'We reasonably suspect fraudulent, abusive, or unlawful use of the platform',
    ],
    's151_outro' => 'For less urgent matters, we will endeavour to provide 14 days\' notice before termination.',
    's152_title' => '15.2 By You',
    's152_body'  => 'Patients may request account closure at any time by contacting <a href="mailto:support@opescare.com">support@opescare.com</a>. Healthcare institutions may terminate their platform agreement by providing 60 days\' written notice to <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a>.',
    's153_title' => '15.3 Effect of Termination',
    's153_intro' => 'Upon termination:',
    's153_items' => [
        'Your access to the platform and any associated data will be revoked',
        'Patient health records remain accessible to the patient in a portable format (HL7 FHIR R4 / JSON export) for 90 days following account closure',
        'Data is retained for the minimum periods required by law (see Privacy Policy Section 6) before secure destruction',
        'Clauses relating to intellectual property, data protection, liability, indemnity, and governing law survive termination',
    ],

    's16_title' => '16. Changes to These Terms',
    's16_body'  => 'We may update these Terms from time to time to reflect changes in law, our services, or regulatory requirements. We will provide at least <strong>30 days\' notice</strong> of material changes via email and in-platform notification before they take effect. Your continued use of OpesCare after the effective date of updated Terms constitutes acceptance. If you do not accept the updated Terms, you must discontinue use and request account closure.',

    's17_title' => '17. Miscellaneous',
    's17_paras' => [
        '<strong>Severability:</strong> If any provision of these Terms is found by a competent court to be invalid, unlawful, or unenforceable, the remaining provisions shall remain in full force and effect.',
        '<strong>Waiver:</strong> Failure or delay by OpesCare in enforcing any right under these Terms shall not constitute a waiver of that right.',
        '<strong>Entire Agreement:</strong> These Terms, together with the Privacy Policy, the OpesCare Consent Framework, and any applicable Partner Agreement, constitute the entire agreement between you and Opes Health Systems Sarl in respect of the OpesCare platform and supersede all prior agreements, representations, and understandings.',
        '<strong>Assignment:</strong> You may not assign your rights or obligations under these Terms without our prior written consent. OpesCare may assign its rights to a successor entity in the event of a merger, acquisition, or restructuring, provided that the successor assumes all obligations under these Terms.',
        '<strong>Language:</strong> These Terms are published in English. A French translation is available upon request. In the event of any conflict between the English and French versions, the English version shall prevail unless required otherwise by Cameroonian courts.',
    ],

    's18_title' => '18. Contact',
    's18_card'  => '<strong>Legal Enquiries:</strong> <a href="mailto:legal@opeshealthsystems.com">legal@opeshealthsystems.com</a><br><strong>Data Protection:</strong> <a href="mailto:privacy@opeshealthsystems.com">privacy@opeshealthsystems.com</a><br><strong>Technical Support:</strong> <a href="mailto:support@opescare.com">support@opescare.com</a><br><strong>Partnership:</strong> <a href="mailto:partners@opescare.com">partners@opescare.com</a><br><strong>Security Vulnerability Disclosure:</strong> <a href="mailto:security@opeshealthsystems.com">security@opeshealthsystems.com</a>',
];
