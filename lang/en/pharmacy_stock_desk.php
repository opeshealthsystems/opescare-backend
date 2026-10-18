<?php

/*
|--------------------------------------------------------------------------
| Pharmacy stock desk — item detail, movement detail, suppliers
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/pharmacy_stock_desk.php (scripts/i18n-audit.php).
| Column labels shared with the other stock screens live in pharmacy_ops.php.
*/

return [

    // ── Navigation ────────────────────────────────────────────────────────
    'nav_suppliers'     => 'Suppliers',
    'link_ledger'       => 'Open the ledger',
    'back_to_batches'   => 'Back to batches',
    'back_to_log'       => 'Back to the movement log',
    'back_to_suppliers' => 'Back to suppliers',
    'sections'          => 'Sections of this page',

    // ── Item detail ───────────────────────────────────────────────────────
    'item_title'        => ':name — stock ledger',
    'usable_sub'        => 'Packs patients are told about',
    'finder_status'     => 'Medicine Finder',
    'never_reported'    => 'never reported',
    'finder'            => [
        'published'     => 'Published',
        'not_reported'  => 'Not published',
        'synthetic'     => 'Not published',
        'no_listing'    => 'Not published',
        'listing_issue' => 'Published, but hidden',
    ],
    'finder_reason'     => [
        'published'     => 'Patients can see this medicine.',
        'not_reported'  => 'No availability has been reported for this medicine yet. Receive a delivery or report it on the public availability page.',
        'synthetic'     => 'The finder row comes from a seed (:source), which is never shown to patients. The next movement replaces it with the ledger.',
        'no_listing'    => 'This pharmacy has no linked public listing, so nothing it stocks can be found by patients.',
        'listing_issue' => 'The listing is inactive or has no map coordinates, so patients cannot find it.',
    ],
    'price'             => 'Selling price',
    'price_history'     => 'Price history',

    'btn_receive'       => 'Receive a delivery',
    'btn_adjust'        => 'Record a movement',
    'btn_reorder_rule'  => 'Set the minimum',

    'tab_batches'       => 'Batches',
    'tab_movements'     => 'Movements',
    'tab_reorder'       => 'Reorder rule',
    'tab_suppliers'     => 'Suppliers',

    'col_location'      => 'Location',
    'location_unknown'  => 'No location',
    'batch_state'       => [
        'usable'   => 'Usable',
        'expiring' => 'Expiring',
        'expired'  => 'Expired',
        'closed'   => 'Closed',
        'empty'    => 'Empty',
    ],
    'batches_empty'     => 'No lot has been recorded for this medicine yet.',

    'adjust_title'      => 'Record a movement on this medicine',
    'confirm_adjust'    => 'Record this movement? The ledger is append-only: it cannot be edited afterwards.',

    'col_kind'          => 'Kind',
    'kind'              => [
        'receipt'     => 'Goods receipt',
        'reservation' => 'Patient order',
        'adjustment'  => 'Movement log',
        'other'       => 'Other',
    ],
    'movement_type'     => [
        'receipt'       => 'Receipt',
        'dispense'      => 'Dispense',
        'transfer'      => 'Transfer',
        'adjustment'    => 'Adjustment',
        'return'        => 'Return',
        'write_off'     => 'Write-off',
        'opening_stock' => 'Opening stock',
    ],
    'movements_empty'   => 'No movement has been recorded for this medicine yet.',

    'rule_needs_medicine' => 'A minimum level can only be set for a catalogue medicine.',

    'col_last_delivery' => 'Last delivery',
    'col_deliveries'    => 'Deliveries',
    'suppliers_for_item_empty' => 'No supplier has delivered this medicine yet.',

    // ── Movement detail ───────────────────────────────────────────────────
    'adj_title'         => 'Movement',
    'adj_facts'         => 'What was recorded',
    'col_before'        => 'Lot before',
    'col_after'         => 'Lot after',
    'lot_packs'         => 'packs in the lot',
    'col_reference'     => 'Recorded from',

    // ── Suppliers ─────────────────────────────────────────────────────────
    'suppliers_title'    => 'Suppliers',
    'suppliers_subtitle' => 'Who delivers to this pharmacy, what they delivered and what is still on order.',
    'suppliers_empty'    => 'No supplier has been added yet. Add one above, then name it on a goods receipt.',
    'supplier_subtitle'  => 'Contact, deliveries and items supplied.',
    'col_contact'        => 'Contact',
    'col_open_orders'    => 'Open orders',
    'supplier_status'    => [
        'active'      => 'Active',
        'inactive'    => 'Inactive',
        'blacklisted' => 'Blacklisted',
    ],
    'contact_title'      => 'Contact',
    'lbl_address'        => 'Address',
    'btn_save_supplier'  => 'Save supplier',
    'flash_supplier_saved' => 'Supplier ":name" saved.',
    'no_delivery_yet'    => 'No delivery yet',
    'deliveries_title'   => 'Deliveries',
    'deliveries_empty'   => 'No goods receipt names this supplier yet.',
    'items_supplied'     => 'Items supplied',
    'items_supplied_empty' => 'Nothing has been received from this supplier yet.',
    'open_orders_title'  => 'Orders awaiting delivery',
    'open_orders_empty'  => 'No order to this supplier is waiting for a delivery.',
    'col_expected'       => 'Expected',
    'order_status'       => [
        'approved' => 'Approved',
        'sent'     => 'Sent',
        'partial'  => 'Partly received',
    ],

    // ── Queue-pattern chrome (restyle 2026-10-13) ─────────────────────────
    'btn_open'              => 'Open',
    'all_items'             => 'All medicines',
    'ledger_title'          => 'Stock ledger',
    'at_a_glance'           => 'At a glance',
    'reports_window_title'  => 'Report window',
    'controlled_kpi_items'  => 'Controlled medicines',
    'controlled_kpi_alerts' => 'Low or out of stock',
    'controlled_kpi_log'    => 'Recent dispensing entries',
    'controlled_export_title' => 'Monthly register export',
    'controlled_export_hint'  => 'Dispense, witness and reconciliation entries for one month, as the secure-sheet PDF or a CSV.',
];
