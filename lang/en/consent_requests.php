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
    'notify_title'  => 'A facility is asking to see your record',
    'notify_body'   => ':facility is asking for access to your health record for :purpose. You decide — nothing is shared until you approve.',
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
