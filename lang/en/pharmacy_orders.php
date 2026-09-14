<?php

/*
|--------------------------------------------------------------------------
| Pharmacy orders desk (web) — patient medicine reservations
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/pharmacy_orders.php (scripts/i18n-audit.php).
| Status words come from enums.reservation_status via @enum(..., 'reservation_status').
*/

return [

    'title'    => 'Orders',
    'subtitle' => 'Medicines patients have reserved at this pharmacy. Accept a hold, mark it ready, then hand it over or send it out — every step tells the patient.',

    'no_facility_context' => 'No active pharmacy is selected for this session.',

    // ── Tabs ─────────────────────────────────────────────────────────────
    'tab_pending'     => 'Needs action',
    'tab_in_progress' => 'In progress',
    'tab_done'        => 'Done',

    // ── Table columns ────────────────────────────────────────────────────
    'col_reference' => 'Reference',
    'col_patient'   => 'Patient',
    'col_medicine'  => 'Medicine',
    'col_qty'       => 'Qty',
    'col_pack'      => 'Pack',
    'col_total'     => 'Total',
    'col_status'    => 'Status',
    'col_placed'    => 'Placed',
    'col_expires'   => 'Expires',
    'col_action'    => 'Action',

    'empty_pending'     => 'No order is waiting for you.',
    'empty_in_progress' => 'Nothing is being prepared right now.',
    'empty_done'        => 'No order has been closed yet.',

    'mode_pickup'   => 'Pickup',
    'mode_delivery' => 'Delivery',
    'no_price'      => 'Price not set',
    'no_expiry'     => 'No expiry',
    'expired'       => 'Expired',

    // ── Actions ──────────────────────────────────────────────────────────
    'btn_accept'    => 'Accept',
    'btn_ready'     => 'Mark ready',
    'btn_collected' => 'Handed over',
    'btn_dispatch'  => 'Send out',
    'btn_delivered' => 'Delivered',
    'btn_cancel'    => 'Cancel order',
    'btn_open'      => 'Open',
    'btn_back'      => 'Back to orders',

    'confirm_accept'    => 'Accept this order? The patient will be told you are preparing it.',
    'confirm_ready'     => 'Mark this order ready? The patient will be told to come and collect it.',
    'confirm_collected' => 'Confirm the patient has collected this order?',
    'confirm_dispatch'  => 'Mark this order as out for delivery? The patient will be told it is on its way.',
    'confirm_delivered' => 'Confirm this order has been delivered?',
    'confirm_cancel'    => 'Cancel this order? The patient will see the reason you give.',

    // ── Detail page ──────────────────────────────────────────────────────
    'show_title'          => 'Order :reference',
    'section_order'       => 'Order',
    'section_patient'     => 'Patient',
    'section_delivery'    => 'Delivery',
    'section_timeline'    => 'Timeline',
    'section_actions'     => 'Next step',
    'lbl_health_id'       => 'Health ID',
    'lbl_phone'           => 'Phone',
    'lbl_fulfilment'      => 'Fulfilment',
    'lbl_delivery_address' => 'Delivery address',
    'lbl_delivery_phone'  => 'Delivery phone',
    'lbl_patient_note'    => 'Note from the patient',
    'lbl_pharmacy_note'   => 'Pharmacy note',
    'lbl_cancel_reason'   => 'Reason given',
    'lbl_unit_price'      => 'Unit price',
    'lbl_prescription'    => 'Prescription',
    'lbl_reason'          => 'Reason (the patient reads this)',
    'reason_placeholder'  => 'e.g. out of stock until Thursday',
    'not_provided'        => 'Not provided',
    'closed_hint'         => 'This order is closed. Nothing more can be done with it.',

    'ts_placed'     => 'Placed',
    'ts_accepted'   => 'Accepted',
    'ts_ready'      => 'Ready',
    'ts_dispatched' => 'Dispatched',
    'ts_delivered'  => 'Delivered',
    'ts_collected'  => 'Collected',
    'ts_cancelled'  => 'Cancelled',

    // ── Flash messages ───────────────────────────────────────────────────
    'flash_accept'    => 'Order :reference accepted.',
    'flash_ready'     => 'Order :reference is ready — the patient has been told.',
    'flash_collected' => 'Order :reference handed over.',
    'flash_dispatch'  => 'Order :reference is out for delivery — the patient has been told.',
    'flash_delivered' => 'Order :reference delivered.',
    'flash_cancel'    => 'Order :reference cancelled.',
];
