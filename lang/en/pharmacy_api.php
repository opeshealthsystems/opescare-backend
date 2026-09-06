<?php

/*
|--------------------------------------------------------------------------
| Pharmacy desk — API messages
|--------------------------------------------------------------------------
|
| Every string the pharmacy desk (the pharmacist's app) can receive from the
| server, and every notification it raises to a patient. Keep this file 1:1
| with lang/fr/pharmacy_api.php — enforced by `php scripts/i18n-audit.php`,
| which checks keys, UTF-8 encoding AND :placeholder parity.
|
| Wording rules:
|   - A refusal never confirms that a record exists somewhere else. "Not at
|     this counter" is the answer for a prescription that is missing AND for
|     one routed to another pharmacy.
|   - A patient notification lands on a lock screen. Name the pharmacy and
|     the kind of event; never the medicine, the dose or the diagnosis.
|
*/

return [

    'errors' => [
        'facility_required'        => 'Your session is not bound to a facility. Sign in again and choose your pharmacy.',
        'prescription_not_found'   => 'That prescription is not at this counter.',
        'reservation_not_found'    => 'That reservation is not at this pharmacy.',
        'stock_not_found'          => 'That line is not on this pharmacy\'s stock list.',
        'invalid_transition'       => 'That step is not possible from where this record is now.',
        'not_dispensable'          => 'This prescription can no longer be dispensed.',
        'item_not_on_prescription' => 'One of the items is not on this prescription.',
        'stock_negative'           => 'Stock cannot go below zero. The line holds :quantity; the adjustment was :delta.',
        'listing_unlinked'         => 'This facility has no pharmacy listing to keep stock against. Ask an administrator to link it in the directory.',
        'collect_is_for_pickup'    => 'A delivery is marked delivered, not collected.',
        'dispatch_is_for_delivery' => 'Only a delivery can be dispatched.',
    ],

    // Written onto a prescription item when the pharmacist records how much
    // actually left the shelf.
    'dispense_note' => 'Quantity dispensed: :quantity',

    // Stock ledger entries (stock_movements.reason is prefixed with this so a
    // supply-chain reader can tell a desk adjustment from a goods receipt).
    'movement_reason' => 'Pharmacy desk adjustment: :reason',

    // The bell + push a patient receives. :pharmacy is the pharmacy's name,
    // :reference the handle they can quote at the counter.
    'notifications' => [
        'prescription_ready' => [
            'title'   => 'Your prescription is ready',
            'message' => ':pharmacy has prepared prescription :reference. Bring your Health ID to collect it.',
            'action'  => 'View prescription',
        ],
        'prescription_dispensed' => [
            'title'   => 'Your prescription has been dispensed',
            'message' => ':pharmacy has dispensed prescription :reference. It is now recorded in your health record.',
            'action'  => 'View prescription',
        ],
        'reservation_ready' => [
            'title'   => 'Your order is ready for pickup',
            'message' => ':pharmacy has your reservation :reference ready at the counter. Show the reference when you arrive.',
            'action'  => 'View reservation',
        ],
        'reservation_dispatched' => [
            'title'   => 'Your order is on its way',
            'message' => ':pharmacy has dispatched reservation :reference for delivery. Keep your phone nearby.',
            'action'  => 'Track order',
        ],
        'reservation_delivered' => [
            'title'   => 'Your order has been delivered',
            'message' => ':pharmacy has marked reservation :reference as delivered. If you did not receive it, contact the pharmacy.',
            'action'  => 'View reservation',
        ],
    ],

];
