<?php

return [
    // Flash
    'issued'          => 'Prescription issued.',
    'voided'          => 'Prescription voided.',
    'issue_failed'    => 'The prescription could not be issued. Please check the details and try again.',
    'void_failed'     => 'This prescription could not be voided.',
    'needs_one_item'  => 'Add at least one medicine to the prescription.',

    // Index
    'title'           => 'Prescriptions',
    'subtitle'        => 'Prescriptions written at this facility.',
    'new'             => 'New prescription',
    'empty'           => 'No prescriptions have been written here yet.',
    'empty_desc'      => 'Issued prescriptions appear here and go straight to the pharmacy queue.',
    'col_patient'     => 'Patient',
    'col_items'       => 'Medicines',
    'col_status'      => 'Status',
    'col_issued'      => 'Issued',
    'col_prescriber'  => 'Prescriber',
    'filter_all'      => 'All',
    'view'            => 'View',

    // Create
    'create_title'    => 'New prescription',
    'create_subtitle' => 'Find the patient by Health ID, then add each medicine.',
    'find_patient'    => 'Find the patient',
    'health_id'       => 'Health ID',
    'health_id_hint'  => 'Enter the exact Health ID — patient names are not searchable.',
    'lookup'          => 'Look up',
    'not_found'       => 'No patient matches that Health ID.',
    'patient'         => 'Patient',
    'change_patient'  => 'Change patient',
    'medicines'       => 'Medicines',
    'add_item'        => 'Add another medicine',
    'remove_item'     => 'Remove',
    'drug_name'       => 'Medicine',
    'dose'            => 'Dose',
    'dose_ph'         => 'e.g. 500 mg',
    'frequency'       => 'Frequency',
    'frequency_ph'    => 'e.g. 3 times daily',
    'route'           => 'Route',
    'duration_days'   => 'Duration (days)',
    'quantity'        => 'Quantity',
    'notes'           => 'Notes for the pharmacy',
    'notes_ph'        => 'Optional instructions or context',
    'validity_days'   => 'Valid for (days)',
    'issue'           => 'Issue prescription',
    'cancel'          => 'Cancel',

    // Show
    'detail_title'    => 'Prescription',
    'issued_at'       => 'Issued',
    'expires_at'      => 'Expires',
    'dispensed_at'    => 'Dispensed',
    'status'          => 'Status',
    'void'            => 'Void prescription',
    'void_reason'     => 'Reason',
    'void_confirm'    => 'Void this prescription? The record is kept with your reason.',
    'back'            => 'Back to prescriptions',
    'item_status'     => 'Line status',

    // Statuses
    'st_active'              => 'Active',
    'st_dispensed'           => 'Dispensed',
    'st_partially_dispensed' => 'Partially dispensed',
    'st_pending'             => 'Pending',
    'st_cancelled'           => 'Cancelled',
    'st_voided'              => 'Voided',
    'st_expired'             => 'Expired',

    // Counter dispensing
    'counter_title'   => 'Dispense at the counter',
    'counter_sub'     => 'Enter the Health ID the patient presents to see their active prescriptions.',
    'counter_lookup'  => 'Find prescriptions',
    'counter_none'    => 'No active prescriptions on that Health ID.',
    'counter_nopat'   => 'No patient matches that Health ID.',
    'counter_from'    => 'Issued at',
    'counter_dispense'=> 'Dispense',
    'counter_notes'   => 'Dispense notes',
    'counter_link'    => 'Counter lookup',

    // Nav
    'nav'             => 'Prescriptions',
];
