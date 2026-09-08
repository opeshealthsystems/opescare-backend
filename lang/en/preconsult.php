<?php

/**
 * The pre-consult questionnaire — every string a patient or a clinician sees.
 *
 * The question set itself is config/preconsult.php, which carries ONLY the
 * keys below. Not one question or option label is written in PHP anywhere in
 * this feature; a form that ships English to a French speaker is not a form.
 *
 * lang/fr/preconsult.php must stay 1:1 with this file — same keys, same
 * :placeholders (php scripts/i18n-audit.php is a CI gate).
 *
 * Three rules govern the wording:
 *
 *   1. Nothing here is clinical language. The patient is asked what is
 *      happening to them; they are never told what it means, and no string
 *      offers a finding, a grade or advice.
 *   2. Nothing here implies obligation. No "required", no "you must", no
 *      "incomplete", no "overdue". A patient who does not answer has failed at
 *      nothing, and every screen says so.
 *   3. Every clinician-facing string names the source. What the patient typed
 *      is described as what the patient said, never as what is the case.
 */

return [

    'title' => 'Before your appointment',

    'intro' => 'A few short questions, so your health worker already knows why you are coming. It takes about two minutes.',

    /*
     * Shown prominently, to the patient, on the form itself — not buried in a
     * policy page. The promise is only real if the person it protects reads it.
     */
    'never_gated' => 'Answering is completely optional. Your appointment goes ahead exactly the same whether you answer everything, some of it, or none of it.',

    'optional_hint' => 'Optional',

    'saved' => 'Your answers have been saved. You can change them any time before you send them.',

    'submitted' => 'Sent to the facility. Your health worker will see this before your appointment.',

    'status' => [
        'draft'     => 'Not sent yet',
        'submitted' => 'Sent',
    ],

    /*
     * The provenance block, carried on every answer in every payload.
     * See App\Enums\VitalProvenance for why this platform marks source
     * structurally rather than trusting a reader to remember.
     */
    'provenance' => [
        'label'       => 'Reported by the patient',
        'description' => 'The patient wrote this themselves before the appointment. It has not been checked by a health worker and it is not a clinical record.',
    ],

    /* Shown at the top of the clinician's view of the questionnaire. */
    'clinician_notice' => 'Patient-reported history, given before the consultation. Confirm anything you intend to act on.',

    'window' => [
        'not_open_yet'             => 'This opens closer to your appointment date.',
        'closed'                   => 'This appointment has passed, so the questionnaire is closed.',
        'appointment_not_upcoming' => 'This questionnaire is only for an upcoming appointment.',
    ],

    'questions' => [

        'presenting_complaint' => [
            'label' => 'What is the main problem you are coming about?',
            'help'  => 'In your own words. There is no wrong way to say it.',
        ],

        'symptom_duration' => [
            'label' => 'How long has it been going on?',
            'help'  => 'Your best guess is fine.',
        ],

        'symptom_severity' => [
            'label' => 'How much is it affecting you?',
            'help'  => 'How it feels to you today.',
        ],

        'current_medicines' => [
            'label' => 'What medicines are you taking at the moment?',
            'help'  => 'Include anything you buy yourself, traditional medicine, and anything you take only sometimes.',
        ],

        'allergy_status' => [
            'label' => 'Has any medicine or anything else ever caused a bad reaction?',
            'help'  => 'If you are not sure, say so. It is safer than guessing.',
        ],

        'allergy_details' => [
            'label' => 'What reacted, and what happened?',
            'help'  => 'For example the name of the medicine, and what your body did.',
        ],

        'pregnancy_status' => [
            'label' => 'Could you be pregnant?',
            'help'  => 'This changes which medicines and which scans are safe for you. Choose "Does not apply to me" if it does not.',
        ],
    ],

    'options' => [

        'symptom_duration' => [
            'today'        => 'It started today',
            'days_1_3'     => '1 to 3 days',
            'days_4_7'     => '4 to 7 days',
            'weeks_1_4'    => '1 to 4 weeks',
            'over_a_month' => 'More than a month',
        ],

        'symptom_severity' => [
            'mild'     => 'A little — I am carrying on as usual',
            'moderate' => 'Quite a lot — some things are harder',
            'severe'   => 'A great deal — I cannot do my usual activities',
        ],

        'allergy_status' => [
            'none_known' => 'Not that I know of',
            'yes'        => 'Yes',
            'not_sure'   => 'I am not sure',
        ],

        'pregnancy_status' => [
            'not_applicable' => 'Does not apply to me',
            'no'             => 'No',
            'yes'            => 'Yes',
            'not_sure'       => 'I am not sure',
        ],
    ],

    'errors' => [
        'appointment_not_found'    => 'We could not find that appointment.',
        'already_submitted'        => 'You have already sent this questionnaire, so it can no longer be changed.',
        'patient_not_found'        => 'Patient not found.',
        'no_clinical_relationship' => 'You do not have a care relationship with this patient.',
        'consent_required'         => 'This patient has asked that their history stay behind their consent. Request access to read it.',
        'audit_unavailable'        => 'This record cannot be opened right now because the access trail could not be written.',
    ],
];
