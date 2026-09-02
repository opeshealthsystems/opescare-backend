<?php

return [
    // Used in schema.org MedicalOrganization and as the meta-description
    // fallback. Written to be quotable verbatim by an answer engine: it says
    // what OpesCare is AND what it is not, because the thing it is most often
    // mistaken for is a hospital management system.
    'org_description' => 'OpesCare is a patient-centred digital health identity and interoperability platform for Cameroon. Patients hold one portable Health ID and one longitudinal medical record that travels between healthcare facilities under their consent. It connects the systems facilities already run rather than replacing them, and is not a hospital management system.',

    // Per-page meta descriptions. Six pages previously inherited the
    // generic site description, which made them compete for one snippet
    // and gave answer engines nothing page-specific to quote.
    'meta' => [
        'how_it_works' => 'How OpesCare works: a patient is identified by Health ID, matched against the patient index, consent is requested and scoped, approved records are exchanged in HL7 FHIR R4, and every access is written to a log the patient can read.',
        'sol_patients' => 'For patients: carry one portable Health ID across every OpesCare facility in Cameroon, decide who may see your record, review who has accessed it, and find medicines, blood and care nearby.',
        'sol_hospitals' => 'For hospitals and clinics: connect the system you already run to the OpesCare network over FHIR, the Connect API or an on-premises Bridge Agent. Register patients, exchange records under consent, and keep your existing software.',
        'sol_laboratories' => 'For laboratories: send verified results straight into the patient record and receive orders from connected facilities, without replacing your LIS.',
        'sol_pharmacies' => 'For pharmacies: verify prescriptions, record dispensing against the patient medication history, and publish medicine availability so patients can find stock before travelling.',
        'sol_insurers' => 'For insurers: read verified coverage as an attribute of the patient Health ID, with minimum-necessary access, purpose limitation and a full audit trail.',
    ],
];
