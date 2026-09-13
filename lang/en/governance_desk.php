<?php

/**
 * Patient-rights desk — data export and record correction requests.
 *
 * The legal wording here is load-bearing. These strings are what a patient is
 * shown when a statutory request under Cameroon Law No. 2010/012 is granted or
 * refused, so the French is a real translation and not a gloss.
 */
return [

    // Navigation and page chrome
    'nav'                => 'Patient rights',
    'breadcrumb_home'    => 'Admin',
    'index_title'        => 'Patient rights requests',
    'index_subtitle'     => 'Data export and record correction requests filed by patients under Law No. 2010/012. Every decision requires a reason and is recorded.',
    'nav_log'            => 'Decision log',
    'log_title'          => 'Governance decision log',
    'log_subtitle'       => 'Every approval and refusal taken at this desk, with the reason given and the person who gave it.',
    'back_to_queue'      => 'Back to the queue',

    // Counters
    'kpi_export_pending'     => 'Exports awaiting a decision',
    'kpi_export_approved'    => 'Exports approved',
    'kpi_correction_pending' => 'Corrections awaiting a decision',
    'kpi_correction_refused' => 'Corrections refused',
    'oldest_pending'         => 'Oldest request still waiting: :days day(s)',

    // Filters
    'filter_status'      => 'Status',
    'filter_all'         => 'All statuses',
    'btn_filter'         => 'Filter',
    'btn_clear'          => 'Clear',

    // Sections
    'section_exports'     => 'Data export requests',
    'section_corrections' => 'Record correction requests',

    // Table columns
    'col_filed'          => 'Filed',
    'col_type'           => 'Type',
    'col_status'         => 'Status',
    'col_patient'        => 'Patient (Health ID)',
    'col_resource'       => 'Disputed record',
    'col_reason'         => 'Reason given by the patient',
    'col_decision'       => 'Decision',
    'col_decided_by'     => 'Decided by',
    'col_action'         => 'Action',
    'col_actor'          => 'Actor',
    'col_when'           => 'When',

    // Statuses
    'status_pending'     => 'Awaiting a decision',
    'status_approved'    => 'Approved',
    'status_rejected'    => 'Refused',
    'status_downloaded'  => 'Downloaded by the patient',
    'status_expired'     => 'Expired',

    // Export detail
    'export_title'       => 'Data export request',
    'export_subtitle'    => 'A full copy of this patient\'s record. Approving it opens a download for 24 hours, available to the patient who filed it and to nobody else.',
    'export_type'        => 'Requested export',
    'export_scope'       => 'Requested scope',
    'export_expires'     => 'Download window closes',
    'export_no_expiry'   => 'No download window opened yet',
    'export_decision_reason' => 'Reason recorded with the decision',

    // Correction detail
    'correction_title'      => 'Record correction request',
    'correction_subtitle'   => 'A correction is an amendment, never an overwrite. What the clinician recorded stays on the record and stays readable; approving marks it as entered in error and adds an amendment beside it.',
    'correction_target'     => 'The record in dispute',
    'correction_target_gone' => 'The disputed record can no longer be found.',
    'correction_current'    => 'Currently recorded',
    'correction_decisions'  => 'Decisions taken',
    'correction_amendment_note' => 'Approving does not delete or edit any recorded value. The original row keeps every field and the clinician who entered it.',

    // Decision forms
    'form_reason'            => 'Reason for the decision',
    'form_reason_help'       => 'Required, and recorded permanently. This is what the patient is told.',
    'form_reason_placeholder' => 'Explain the decision in plain language…',
    'btn_approve'            => 'Approve',
    'btn_refuse'             => 'Refuse',
    'btn_open'               => 'Open',
    'btn_view_history'       => 'History',

    // Refusals this desk enforces
    'refusal_not_pending'      => 'This request has already been decided.',
    'refusal_type_unsupported' => 'This desk cannot amend a record of this type safely, so it will not pretend to. The request must be handled by the facility that holds the record, and refused here with that explanation.',
    'refusal_record_missing'   => 'The record this request points at no longer exists, so there is nothing to amend.',
    'refusal_heading'          => 'This request cannot be approved here',

    // History
    'history_title'      => 'History',
    'history_empty'      => 'No decision has been recorded on this request yet.',

    // Flashes
    'flash_export_approved'     => 'Export approved. The patient can now download their record and has been notified.',
    'flash_export_refused'      => 'Export refused. The reason has been recorded and the patient has been notified.',
    'flash_correction_approved' => 'Correction approved. The record has been amended, the original is preserved, and the patient has been notified.',
    'flash_correction_refused'  => 'Correction refused. The reason has been recorded and the patient has been notified.',
    'flash_not_pending'         => 'This request has already been decided.',
    'flash_actor_unresolved'    => 'Your account could not be resolved, so no decision was recorded.',

    // Empty states
    'empty_exports_heading'     => 'No export requests',
    'empty_exports_body'        => 'No patient has asked for a copy of their record yet.',
    'empty_corrections_heading' => 'No correction requests',
    'empty_corrections_body'    => 'No patient has disputed a record yet.',
    'empty_log_heading'         => 'No decisions yet',
    'empty_log_body'            => 'Nothing has been approved or refused at this desk.',

    // What the patient is told
    'notify_export_approved_title'     => 'Your record export is ready',
    'notify_export_approved_body'      => 'Your request for a copy of your health record has been approved. You can download it from the privacy section of the app within the next 24 hours.',
    'notify_export_refused_title'      => 'Your record export was refused',
    'notify_export_refused_body'       => 'Your request for a copy of your health record was refused. Reason: :reason',
    'notify_correction_approved_title' => 'Your correction request was accepted',
    'notify_correction_approved_body'  => 'The record you disputed has been marked as entered in error and an amendment has been added. What was originally recorded is kept, as the law requires.',
    'notify_correction_refused_title'  => 'Your correction request was refused',
    'notify_correction_refused_body'   => 'The record you disputed has not been changed. Reason: :reason',

    // Written into the amendment itself
    'amendment_stamp' => "Amendment recorded :date following a patient correction request.\nDisputed by the patient: :disputed\nDecision of the reviewer: :decision\nThe original note is retained and marked as entered in error.",

];
