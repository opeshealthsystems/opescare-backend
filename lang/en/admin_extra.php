<?php

return [
    // bridge/index.blade.php — API documentation prose
    'bridge_api_desc_sync'      => 'Post a batch of records. Supports ehr_records, appointments, pharmacy_stock, blood_stock.',
    'bridge_api_desc_heartbeat' => 'Announce agent version, hostname, and capabilities. Updates last-seen timestamp.',
    'bridge_api_desc_status'    => 'Query recent batch results and sync health for this agent.',

    // Recurring control labels
    'btn_filter' => 'Filter',
    'btn_reset'  => 'Reset',
    'btn_apply'  => 'Apply',

    // aria-labels / titles
    'aria_type'  => 'Type',
    'aria_role'  => 'Role',
    'title_view' => 'View',

    // legal — boolean badge / placeholder
    'yes'            => 'Yes',
    'no'             => 'No',
    'legal_terms_of_use' => 'Terms of Use',

    // breadcrumb home label
    'breadcrumb_admin' => 'Admin',

    // parameterised count nouns
    'count_batches'      => ':n batches',
    'count_invoices'     => ':n invoices',
    'count_transactions' => ':n transactions',
    'count_facilities'   => ':n facilities',
    'count_users'        => ':n users',

    // payments — refund + filtered note
    'pay_refund'   => 'refund',
    'pay_filtered' => '(filtered)',

    // payments — placeholder
    'pay_ph_reference' => 'Reference or phone...',

    // report by service — period hint
    'rbs_period' => 'Period:',

    // payment method / gateway option labels
    'opt_cash'          => 'Cash',
    'opt_card'          => 'Card',
    'opt_insurance'     => 'Insurance',
    'opt_bank_transfer' => 'Bank Transfer',
    'opt_wallet'        => 'Wallet',

    // payment status option labels
    'opt_successful' => 'Successful',
    'opt_pending'    => 'Pending',
    'opt_failed'     => 'Failed',
    'opt_refunded'   => 'Refunded',
    'opt_completed'  => 'Completed',

    // support/index — table data-labels
    'sup_col_ticket'   => 'Ticket #',
    'sup_col_subject'  => 'Subject',
    'sup_col_category' => 'Category',
    'sup_col_priority' => 'Priority',
    'sup_col_status'   => 'Status',
    'sup_col_assignee' => 'Assignee',
    'sup_col_created'  => 'Created',
    'sup_col_actions'  => 'Actions',

    // support/index — fallback values
    'sup_fallback_general' => 'General',
    'sup_fallback_medium'  => 'Medium',
    'sup_fallback_open'    => 'Open',

    // bridge/index — table data-labels
    'bridge_col_agent_name' => 'Agent Name',
    'bridge_col_key_prefix' => 'Key Prefix',
    'bridge_col_status'     => 'Status',
    'bridge_col_version'    => 'Version',
    'bridge_col_last_seen'  => 'Last Seen',
    'bridge_col_last_sync'  => 'Last Sync',
    'bridge_col_batches'    => 'Batches',
    'bridge_col_actions'    => 'Actions',

    // bridge/index — modal trailing word
    'bridge_agent' => 'agent',

    // users/index — role fallback
    'users_role_none' => 'none',

    // payments/payments — actions column data-label
    'col_actions' => 'Actions',

    // legal/show — change-summary input placeholder
    'ph_change_summary' => 'What changed in this version?',

    // users — primary facility assignment
    'users_col_facility'      => 'Facility',
    'users_facility_none'     => 'No facility',
    'users_stat_facility'     => 'Primary facility',
    'users_lbl_facility'      => 'Primary facility',
    'users_ph_facility'       => 'No facility (platform account)',
    'users_facility_help'     => 'A staff account with no facility cannot open any portal.',
    'users_facility_find'     => 'Find a facility',
    'users_facility_find_ph'  => 'Type part of a facility name',
    'users_facility_find_btn' => 'Search',
    'users_facility_hint'     => 'Showing up to :n facilities. Search by name to reach another one.',
    'users_facility_no_match' => 'No facility matches that search.',
    'users_facility_locked'   => 'You can only assign staff to the facility you administer.',

    // users/index — create form
    'users_create_title'   => 'Create user',
    'users_create_name'    => 'Full name',
    'users_create_email'   => 'Email',
    'users_create_pw'      => 'Temporary password',
    'users_create_role'    => 'Role',
    'users_create_role_ph' => 'Select a role',
    'users_create_btn'     => 'Create user',

    // users/show — status control
    'users_lbl_status' => 'Status',
];
