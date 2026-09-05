<?php

/*
|--------------------------------------------------------------------------
| Consent requests — a facility ASKING a patient for access to their record
|--------------------------------------------------------------------------
|
| Served by App\Http\Controllers\Api\Mobile\MobileConsentRequestController and
| App\Modules\Governance\Services\ConsentService, and read by the patient in
| App\Notifications\ConsentAccessRequestedNotification.
|
| `purpose.*` and `scope.*` are the wire vocabularies, not free text — the
| controller's PURPOSES and SCOPES constants are the source of truth and every
| value there MUST have a label on both sides of lang/. The nesting under
| `scope` mirrors the dots in the scope values ('patient.summary' →
| scope.patient.summary), because Laravel resolves a dotted key by walking
| segments.
|
| lang/en/consent_requests.php and lang/fr/consent_requests.php must stay 1:1 —
| `php scripts/i18n-audit.php` is a CI gate on keys, encoding and :placeholders.
*/

return [

    // ── API messages ─────────────────────────────────────────────────────
    'created'               => 'Access request sent to the patient.',
    'withdrawn'             => 'Access request withdrawn.',
    'not_found'             => 'This access request does not exist, or it was not raised by your facility.',
    'patient_not_found'     => 'No patient holds that Health ID. Check the full ID with the patient — it cannot be searched for.',
    'identity_unresolvable' => 'Your facility could not be resolved from your session. Sign in again.',
    'already_pending'       => 'Your facility has already asked this patient for access and is waiting for their answer.',
    'already_granted'       => 'This patient has already granted your facility access. There is nothing to ask for.',
    'not_open'              => 'This access request is no longer open — it is :status.',
    'unknown_facility'      => 'A health facility',

    // ── The patient's notification ───────────────────────────────────────
    //
    // `:purpose` is a purpose LABEL from the list below, and those labels are
    // noun phrases written to stand alone in the clinician's picker ("Treating
    // this patient", "A second opinion"). Dropped mid-sentence, one produced
    // "…access to your health record for Treating this patient." — broken in
    // both languages, and worse in French, where every label carries its own
    // article ("pour La prise en charge de ce patient"). The reason now gets
    // its own clause, where any label in the list reads correctly and a label
    // added later still will.
    'notify_title'  => 'A facility is asking to see your record',
    'notify_body'   => ':facility is asking for access to your health record. Reason given: :purpose. You decide — nothing is shared until you approve.',
    'notify_action' => 'Review the request',

    // ── Request status ───────────────────────────────────────────────────
    'status' => [
        'pending'                    => 'Waiting for the patient',
        'pending_patient_approval'   => 'Waiting for the patient',
        'approved'                   => 'Approved',
        'denied'                     => 'Refused',
        'expired'                    => 'Expired',
        'withdrawn'                  => 'Withdrawn',
    ],

    /*
     * ── How long the access would last ───────────────────────────────────
     *
     * The patient used to read the stored column: "1440 min of access". The
     * clinician chose "24 hours" — the picker is denominated in hours — so
     * that is the unit it reads back in. Selected by
     * App\Support\PatientVocabulary::duration(), which uses days only from two
     * whole days up.
     *
     * Bare quantities on purpose: the client frames them ("… of access",
     * "for …"), so one label serves both the request card and the approve
     * confirmation.
     */
    'duration' => [
        'minutes' => ':count minute|:count minutes',
        'hours'   => ':count hour|:count hours',
        'days'    => ':count day|:count days',
    ],

    /*
     * ── When a code cannot be resolved ───────────────────────────────────
     *
     * Shown INSTEAD of a friendly name, never beside one. `consent_grants.scope`
     * holds values from outside this vocabulary today (`patients:read`, the
     * B2B Connect token scope), and title-casing one into "Patients: Read"
     * would be a guess about how much of a medical record is being handed
     * over. The raw code is shown and the patient is told to ask.
     */
    'scope_unrecognised'   => 'One of the codes above is not part of OpesCare\'s data vocabulary, so it is shown exactly as it was recorded rather than given a name. Ask the facility what it covers — it may cover more than it appears to.',
    'purpose_unrecognised' => 'This reason code is not part of OpesCare\'s vocabulary, so it is shown exactly as it was recorded. Ask the facility what it means.',

    // ── Why access is being asked for ────────────────────────────────────
    'purpose' => [
        'treatment'          => 'Treating this patient',
        'referral'           => 'A referral',
        'second_opinion'     => 'A second opinion',
        'continuity_of_care' => 'Continuity of care',
        'emergency_followup' => 'Follow-up after emergency care',
    ],

    // ── What is being asked for ──────────────────────────────────────────
    'scope' => [
        'patient' => [
            'summary' => 'Record summary',
        ],
        'allergies' => [
            'read' => 'Allergies',
        ],
        'problems' => [
            'read' => 'Ongoing problems',
        ],
        'encounters' => [
            'read' => 'Visits and consultations',
        ],
        'vitals' => [
            'read' => 'Vital signs',
        ],
        'medications' => [
            'read' => 'Medicines and prescriptions',
        ],
        'diagnoses' => [
            'read' => 'Diagnoses',
        ],
        'orders' => [
            'read' => 'Laboratory and imaging orders',
        ],
    ],

];
