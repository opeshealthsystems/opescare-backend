<?php

/**
 * Patient write paths — the two things a patient may add to their own record:
 * a vital sign they measured at home, and a request to be referred.
 *
 * Every string here is served by the mobile API
 * (App\Http\Controllers\Api\Mobile\PatientRecordedVitalsController and
 * PatientReferralRequestController) in the locale the request asks for.
 *
 * Two rules govern the wording:
 *
 *   1. A self-reported reading is never described in clinical language. It is
 *      "recorded by you", not "your blood pressure"; the platform never grades
 *      it, and the only judgement it ever offers is "please contact a health
 *      worker", which is advice, not a finding.
 *   2. A referral request is never described as a referral. No string in the
 *      `referral_request` block says "referred", "approved" or "sent" of a
 *      request a clinician has not acted on.
 *
 * lang/fr/patient_writes.php must stay 1:1 with this file — same keys, same
 * :placeholders (php scripts/i18n-audit.php is a CI gate).
 */

return [

    /*
     * Provenance — the single most important thing on any reading.
     * See App\Enums\VitalProvenance.
     */
    'provenance' => [
        'self_reported' => [
            'label'       => 'Recorded by you',
            'description' => 'You measured this yourself. It has not been checked by a health worker and is not a clinical measurement.',
        ],
        'facility_measured' => [
            'label'       => 'Measured at a facility',
            'description' => 'Taken by health staff during a visit.',
        ],
        'laboratory_result' => [
            'label'       => 'Laboratory result',
            'description' => 'Produced by a laboratory from a sample.',
        ],
        'partner_reported' => [
            'label'       => 'Sent by another system',
            'description' => 'Pushed in over the interoperability API by an outside system. Nobody here observed it.',
        ],
    ],

    /* What a patient may log, and what each number is called. */
    'measures' => [
        'blood_pressure' => [
            'label'     => 'Blood pressure',
            'primary'   => 'Systolic (upper number)',
            'secondary' => 'Diastolic (lower number)',
        ],
        'weight' => [
            'label'   => 'Weight',
            'primary' => 'Weight',
        ],
        'temperature' => [
            'label'   => 'Temperature',
            'primary' => 'Temperature',
        ],
        'blood_sugar' => [
            'label'   => 'Blood sugar',
            'primary' => 'Blood sugar',
        ],
    ],

    /*
     * When the reading was taken, for blood sugar. This is the fasting state
     * the clinical tables cannot record — the patient is the only person who
     * knows it, so they are asked directly.
     */
    'contexts' => [
        'fasting'     => 'Fasting',
        'before_meal' => 'Before a meal',
        'after_meal'  => 'After a meal',
        'bedtime'     => 'At bedtime',
        'random'      => 'Any other time',
    ],

    /* Field names, as they appear inside a refusal message. */
    'fields' => [
        'value'       => 'The value',
        'systolic'    => 'The systolic (upper) number',
        'diastolic'   => 'The diastolic (lower) number',
        'measured_at' => 'The time of the reading',
    ],

    /*
     * Refusals. Every one of these names what was rejected and why. Nothing is
     * corrected, rounded into range or stored: a value the platform will not
     * accept is a value the patient is told about.
     */
    'errors' => [
        'out_of_range_title' => 'That reading was not saved.',
        'out_of_range'       => ':field must be between :min and :max :unit. You entered :submitted, so nothing was saved. Check your device and enter the reading again.',
        'reading_rejected'   => 'That reading was not saved.',
        'missing_value'      => ':field is needed before this reading can be saved.',
        'unsupported_unit'   => ':measure can be recorded in :units. Nothing was saved.',
        'unsupported_context' => 'Choose one of: :contexts. Nothing was saved.',
        'context_not_applicable' => ':measure is not recorded with a time of day. Nothing was saved.',
        'diastolic_not_below_systolic' => 'The lower number must be smaller than the upper number. It looks like the two were entered the wrong way round, so nothing was saved.',
        'measured_at_range'  => 'A reading cannot be in the future, and the date entered is not one this app can accept. Nothing was saved.',
        'reading_not_found'  => 'That reading is not in your log.',
        'already_voided'     => 'You have already removed that reading.',
        'invalid_filter'     => 'That filter is not one this list understands.',

        'request_rejected'   => 'That request was not sent.',
        'too_many_open_requests' => 'You already have :max requests waiting for an answer. Please wait for one of them before sending another.',
        'duplicate_open_request' => 'You already have a request for :specialty waiting for an answer (:reference).',
        'facility_not_found' => 'That facility is not in the directory, so the request was not sent.',
        'request_not_found'  => 'That request is not one of yours.',
        'not_withdrawable'   => 'This request is :status, so it can no longer be withdrawn.',
    ],

    /*
     * The only judgement this platform offers on a self-reported reading. It is
     * advice to speak to someone, never an interpretation of the value.
     */
    'advisory' => [
        'seek_care' => 'Your reading was saved as you entered it. It is outside the range most people can safely wait on, so please contact a health worker.',
    ],

    /* Suggested specialties. A patient may also type their own. */
    'specialties' => [
        'cardiology'        => 'Cardiology (heart)',
        'dermatology'       => 'Dermatology (skin)',
        'ent'               => 'Ear, nose and throat',
        'gastroenterology'  => 'Gastroenterology (stomach and gut)',
        'general_surgery'   => 'General surgery',
        'gynaecology'       => 'Gynaecology',
        'internal_medicine' => 'Internal medicine',
        'neurology'         => 'Neurology (nerves and brain)',
        'oncology'          => 'Oncology (cancer care)',
        'ophthalmology'     => 'Ophthalmology (eyes)',
        'orthopaedics'      => 'Orthopaedics (bones and joints)',
        'paediatrics'       => 'Paediatrics (children)',
        'psychiatry'        => 'Mental health',
        'pulmonology'       => 'Pulmonology (lungs)',
        'urology'           => 'Urology',
        'dentistry'         => 'Dentistry',
        'nutrition'         => 'Nutrition',
        'physiotherapy'     => 'Physiotherapy',
        'other'             => 'Something else',
    ],

    'durations' => [
        'less_than_week'       => 'Less than a week',
        'one_to_four_weeks'    => 'One to four weeks',
        'one_to_six_months'    => 'One to six months',
        'more_than_six_months' => 'More than six months',
        'ongoing'              => 'It comes and goes',
    ],

    'referral_request' => [

        /*
         * Said before the patient fills anything in, and again on every request
         * in the list.
         */
        'disclaimer' => 'This asks a clinician to consider referring you. It is not a referral, and it does not book anything. A clinician has to review it first.',

        'status' => [
            'submitted'    => 'Waiting to be reviewed',
            'under_review' => 'Being reviewed',
            'accepted'     => 'Referral issued',
            'declined'     => 'Not referred',
            'withdrawn'    => 'Withdrawn by you',
        ],

        'status_detail' => [
            'submitted'    => 'Your request has been received. No clinician has looked at it yet.',
            'under_review' => 'A clinician has your request and is looking at it. There is no referral yet.',
            'accepted'     => 'A clinician reviewed your request and issued a referral. You can see it with your referrals.',
            'declined'     => 'A clinician reviewed your request and decided a referral is not the right step right now.',
            'withdrawn'    => 'You took this request back. Nothing further will happen with it.',
        ],

        'next_step' => [
            'submitted'    => 'Nothing to do for now. You can withdraw the request while it is still waiting.',
            'under_review' => 'Nothing to do for now. You will see the outcome here.',
            'accepted'     => 'Open your referrals to see where you have been referred and what to bring.',
            'declined'     => 'If your symptoms change or get worse, speak to a health worker or send a new request.',
            'withdrawn'    => 'You can send a new request whenever you need one.',
        ],

        /*
         * The PATIENT's sense of urgency, never clinical triage. The values are
         * deliberately not routine/urgent/emergency, which is what a clinician
         * sets on an issued referral.
         */
        'reported_urgency' => [
            'routine' => [
                'label'       => 'It can wait',
                'description' => 'I am managing, but I would like to be seen.',
            ],
            'soon' => [
                'label'       => 'I would like to be seen soon',
                'description' => 'It is affecting my daily life.',
            ],
            'urgent' => [
                'label'       => 'It feels urgent to me',
                'description' => 'It is getting worse or I am worried about it.',
            ],
        ],

        'urgency_caveat' => 'This is how urgent it feels to you. A clinician decides the clinical priority.',
    ],

];
