<?php

/*
|--------------------------------------------------------------------------
| Laboratory desk — API messages
|--------------------------------------------------------------------------
|
| Every string the clinician app's LAB DESK (/api/mobile/lab) can receive from
| the server, plus the bells it raises for the patient and the ordering
| clinician. Keep this file 1:1 with lang/fr/lab_api.php — enforced by
| `php scripts/i18n-audit.php` (keys, UTF-8 encoding AND :placeholder parity).
|
| Wording rule: a bell lands on a lock screen, which is a public surface. The
| patient's bell names the TEST, never a value, a flag or an interpretation.
|
*/

return [

    // Refusals. "Not found" and "not this facility's" are deliberately the same
    // answer — confirming that an order exists elsewhere is a disclosure.
    'order_not_found'   => "That order is not on this laboratory's worklist.",
    'request_not_found' => 'That test request was not submitted to this laboratory.',
    'already_collected' => 'A sample has already been recorded for this order.',
    'already_released'  => 'This order has already been released. Enter a corrected result instead of releasing it again.',
    'order_closed'      => 'This order is :status and cannot be worked further.',
    'no_results'        => 'Enter at least one result before releasing this order.',
    'already_decided'   => 'This request has already been :status.',
    'no_facility'       => 'This session is not bound to a facility. Sign in again.',

    // The words dropped into :status above.
    'order_status'      => [
        'cancelled'        => 'cancelled',
        'canceled'         => 'cancelled',
        'voided'           => 'voided',
        'entered_in_error' => 'marked entered in error',
        'rejected'         => 'rejected',
    ],
    'request_status'    => [
        'approved'  => 'approved',
        'declined'  => 'declined',
        'cancelled' => 'cancelled by the patient',
    ],

    'validation'        => [
        'results_required' => 'Enter at least one result.',
        'value_scalar'     => 'A result value must be a number or a short text (100 characters at most).',
        'flag'             => 'A flag must be H (high), L (low), N (normal) or C (critical).',
        'reason_required'  => 'Give the patient a reason for declining their request.',
    ],

    // Bells (notifications) raised by the desk.
    'notify'            => [
        'result_ready_title'      => 'Your :test result is ready',
        'result_ready_body'       => 'The laboratory at :facility has released your result. Open Lab Results to see the full report.',
        'result_ready_action'     => 'View result',
        'clinician_title'         => 'Result released — :test',
        'clinician_body'          => ':patient — :test has been released by the laboratory at :facility.',
        'clinician_body_critical' => 'CRITICAL — :patient — :test has been released by the laboratory at :facility. Review it now.',
        'request_approved_title'  => 'Your test request was approved',
        'request_approved_body'   => ':facility has approved your request for :test. The laboratory will tell you when a sample is needed.',
        'request_declined_title'  => 'Your test request was not approved',
        'request_declined_body'   => ':facility did not approve your request for :test. Reason: :reason',
        'request_action'          => 'View request',
    ],

];
