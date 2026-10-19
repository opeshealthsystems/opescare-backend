<?php

/*
|--------------------------------------------------------------------------
| Access log — "who opened my record, and why?"
|--------------------------------------------------------------------------
|
| Resolved by App\Support\PatientVocabulary and put on the wire by
| MobileGovernanceController::listAccessLogs, so the patient's access log
| arrives already in their language.
|
| Before this file existed the screen printed the audit column itself:
| `Treatment:treating clinician` — the raw `access_logs.purpose` value with its
| basis suffix, title-cased by the client, in English in both locales. The
| access log is the one screen whose whole purpose is telling a patient who
| read their record and on what authority; printing the shorthand the system
| writes to itself answers neither question.
|
| `purpose.*` are the reason heads written by AccessLogService::log() callers.
| `basis.*` are the authorities MobileDoctorChartController::relationshipBasis()
| returns, plus `break_glass` from EmergencyAccessService. `category.*` are the
| `data_category` values those same calls record.
|
| Nothing here is a guess: a value with no entry is shown to the patient
| exactly as it was stored, with `purpose_unrecognised` beside it. Adding a new
| purpose, basis or category in code means adding it here and in
| lang/fr/access_log.php — the two files MUST stay 1:1
| (`php scripts/i18n-audit.php` is a CI gate on keys, encoding and
| :placeholders).
*/

return [

    // ── Why the record was opened ────────────────────────────────────────
    'purpose' => [
        'treatment'                     => 'Your care and treatment',
        'emergency'                     => 'Emergency access',
        'clinical_messaging'            => 'A secure message about your care',
        'results_inbox_review'          => 'Reviewing your test results',
        'results_inbox_acknowledgement' => 'Signing off your test results',
        'prescription_dispensing'       => 'Dispensing your prescription',
        'patient_consent_review'        => 'You looked at who has access',
        'patient_request'               => 'You asked for this',
        'patient_self_export'           => 'You exported your own record',
        'data_subject_rights'           => 'Acting on your data-rights request',
    ],

    // ── On what authority ────────────────────────────────────────────────
    'basis' => [
        'appointment'        => 'they had an appointment with you',
        'care_team'          => 'they are on your care team',
        'treating_clinician' => 'they had treated you',
        'facility_episode'   => 'you were being cared for at their facility',
        'consent_grant'      => 'you had granted them access',
        'break_glass'        => 'an emergency override, which you were told about',
        // Written when a chart read was REFUSED. It belongs in the patient's
        // log as plainly as a successful read does.
        'no_relationship'    => 'no care relationship was on record',
    ],

    'purpose_with_basis'   => ':purpose — :basis',
    'purpose_unrecognised' => 'OpesCare has no plain-language name for this reason code, so it is shown exactly as it was recorded. Ask the facility what it means.',

    // ── Which part of the record ─────────────────────────────────────────
    'category' => [
        'account'            => 'Your account',
        'allergies'          => 'Allergies',
        'clinical_summary'   => 'Record summary',
        'communication'      => 'Messages',
        'consent'            => 'Consent and access',
        'correction_filing'  => 'A correction you asked for',
        'data_export'        => 'A copy of your record',
        'demographics'       => 'Your personal details',
        'diagnoses'          => 'Diagnoses',
        'diagnostic_results' => 'Test results',
        'emergency_profile'  => 'Emergency profile',
        'encounters'         => 'Visits and consultations',
        'full_record'        => 'Your whole record',
        'imaging'            => 'Imaging',
        'immunizations'      => 'Immunizations',
        'lab_results'        => 'Laboratory results',
        'medication'         => 'Medicines',
        'medications'        => 'Medicines and prescriptions',
        'orders'             => 'Laboratory and imaging orders',
        'orders_imaging'     => 'Imaging orders',
        'patient_identity'   => 'Who you are',
        'prescriptions'      => 'Prescriptions',
        'problem_list'       => 'Ongoing problems',
        'vitals'             => 'Vital signs',
        'documents'          => 'Documents',
        'immunization'       => 'Immunizations',
        'patient_reported_intake' => 'Your pre-consult answers',
        'triage'             => 'Triage',
        'pain_sites'         => 'Where it hurts (pain sites)',
        'referrals'          => 'Referrals',
        'family_links'       => 'Family and emergency contacts',
        'access_log'         => 'Who opened your record',
        'care_team'          => 'Your care team',
    ],

];
