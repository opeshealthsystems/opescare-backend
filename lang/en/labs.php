<?php

/**
 * Server-side strings for the mobile laboratory endpoints.
 *
 * Only messages the API itself emits live here — validation failures and the
 * words the payload uses for a state. Everything the SCREENS render comes from
 * the app's own catalogue (lib/i18n), not from this file.
 *
 * Must stay 1:1 with lang/fr/labs.php — enforced by `php scripts/i18n-audit.php`.
 */
return [

    'request' => [
        // POST /api/mobile/labs/requests, when test_code names nothing a
        // laboratory has published.
        'unknown_test'  => 'That test is not on any laboratory’s published list. Choose a test from the catalogue.',
        'submitted'     => 'Your request has been sent to your care team.',
        'not_an_order'  => 'This is a request, not a laboratory order. A clinician has to approve it before the test is carried out.',
    ],

    'status' => [
        'submitted' => 'Submitted',
        'approved'  => 'Approved',
        'declined'  => 'Declined',
        'cancelled' => 'Cancelled',
    ],

    'priority' => [
        'routine' => 'Routine',
        'urgent'  => 'Urgent',
        'stat'    => 'Immediate',
    ],

    'order_status' => [
        'ordered'     => 'Ordered',
        'in_progress' => 'In progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ],

    'flag' => [
        'normal'    => 'Normal',
        'low'       => 'Low',
        'high'      => 'High',
        'abnormal'  => 'Abnormal',
        'critical'  => 'Critical',
        // A value the laboratory published without a flag, and for which no
        // reference range exists anywhere. It is NOT normal — nobody compared
        // it to anything.
        'unflagged' => 'Not recorded',
        // An order with no released results yet.
        'pending'   => 'Awaiting results',
    ],

    'report' => [
        'no_range'    => 'Not provided',
        'no_document' => 'The laboratory has not attached a signed report to this order.',
        'link_expired' => 'This download link has expired. Open the report again to get a new one.',
    ],

];
