<?php

/*
|--------------------------------------------------------------------------
| Patient queue desk (/facility/queue)
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/queue_desk.php (scripts/i18n-audit.php).
|
| A separate namespace from lang/queue.php, which is an older, unused stub for
| a public queue display that was never built.
*/

return [

    'nav'   => 'Patient queue',
    'title' => 'Patient queue',

    // ── The live queue ────────────────────────────────────────────────────
    'queue_title'     => 'Waiting room',
    'queue_subtitle'  => 'Everyone active at this facility, most urgent first, then longest waiting.',
    'empty_queue'     => 'Nobody is waiting at this facility right now.',
    'check_in'        => 'Check in a patient',
    'back_to_queue'   => 'Back to the queue',
    'closed_title'    => 'Finished today',
    'closed_empty'    => 'No ticket has been closed today.',

    'col_number'      => 'No.',
    'col_patient'     => 'Patient',
    'col_priority'    => 'Priority',
    'col_status'      => 'Status',
    'col_checked_in'  => 'Checked in',
    'col_waited'      => 'Waited',
    'col_served'      => 'In room',
    'col_with'        => 'With',
    'col_actions'     => 'Actions',

    // ── The day's figures ─────────────────────────────────────────────────
    'summary_title'          => 'Today, measured',
    'summary_hint'           => 'Every figure is computed from timestamps written once and never rewritten.',
    'summary_active'         => 'In the room now',
    'summary_waiting'        => 'Waiting to be called',
    'summary_seen'           => 'Seen today',
    'summary_left'           => 'No-show or cancelled',
    'summary_checked_in'     => 'Checked in today',
    'summary_median_wait'    => 'Median wait to be called',
    'summary_longest_wait'   => 'Longest wait to be called',
    'summary_median_service' => 'Median time in the room',
    'summary_none_yet'       => 'Nobody has been called yet today.',

    // ── Durations ─────────────────────────────────────────────────────────
    'duration_minutes' => ':n min',
    'duration_hours'   => ':h h :m min',
    'still_waiting'    => 'still waiting',
    'still_in_room'    => 'in progress',

    // ── Priority ──────────────────────────────────────────────────────────
    'priority_from_triage'  => 'Triage',
    'priority_from_arrival' => 'Arrival',
    'arrival_priority'      => 'Arrival priority',
    'arrival_priority_hint' => 'This is not a clinical acuity. If a nurse triages the patient, the triage priority replaces this in the queue order.',
    'arrival_levels'        => [
        1 => 'Emergency — send to triage now',
        3 => 'Priority lane — pregnant, infant, elderly, disabled',
        5 => 'Standard',
    ],
    'priority_reason'       => 'Reason',
    'priority_reason_hint'  => 'Required for anything above standard.',

    // ── Statuses ──────────────────────────────────────────────────────────
    'status' => [
        'waiting'         => 'Waiting',
        'called'          => 'Called',
        'service_started' => 'In consultation',
        'completed'       => 'Completed',
        'no_show'         => 'No-show',
        'cancelled'       => 'Cancelled',
    ],

    // ── Actions ───────────────────────────────────────────────────────────
    'action_call'      => 'Call',
    'action_recall'    => 'Call again',
    'action_start'     => 'Start',
    'action_complete'  => 'Complete',
    'action_no_show'   => 'No-show',
    'action_cancel'    => 'Cancel',
    'action_open'      => 'Open',
    'cancel_reason'    => 'Reason for cancelling',

    // ── Check-in ──────────────────────────────────────────────────────────
    'create_title'      => 'Check in a patient',
    'find_patient'      => 'Find the patient',
    'health_id'         => 'Health ID',
    'health_id_hint'    => 'Enter the full Health ID exactly. Patients cannot be searched by name.',
    'lookup'            => 'Look up',
    'not_found'         => 'No patient holds that Health ID.',
    'cancel'            => 'Cancel',
    'patient'           => 'Patient',
    'change_patient'    => 'Change patient',
    'already_in_queue'  => 'This patient is already in the queue as number :number.',
    'confirm_check_in'  => 'Check in',
    'walk_in_note'      => 'Walk-in. A visit is opened at this facility, or the open one is reused.',
    'todays_bookings'   => 'Today’s bookings not yet checked in',
    'bookings_empty'    => 'No booking for today is waiting to be checked in.',
    'col_time'          => 'Time',
    'col_clinician'     => 'Clinician',
    'col_booking_status'=> 'Booking',
    'check_in_booking'  => 'Check in',
    'checked_in'        => 'Checked in — ticket :number.',

    // ── Ticket page ───────────────────────────────────────────────────────
    'ticket_title'      => 'Ticket :number',
    'timeline'          => 'Timeline',
    'checked_in_at'     => 'Checked in',
    'called_at'         => 'Called',
    'service_started_at'=> 'Consultation started',
    'completed_at'      => 'Completed',
    'cancelled_at'      => 'Closed',
    'history_title'     => 'History',
    'history_hint'      => 'Append-only. Every transition is a row; nothing is edited.',
    'col_from'          => 'From',
    'col_to'            => 'To',
    'col_by'            => 'By',
    'col_at'            => 'At',
    'col_reason'        => 'Reason',
    'not_yet'           => 'not yet',
    'booking'           => 'Booking',
    'walk_in'           => 'Walk-in',
    'waited_total'      => 'Waited before being called',
    'served_total'      => 'Time in the room',

    // ── Outcomes ──────────────────────────────────────────────────────────
    'done' => [
        'called'    => 'Ticket :number called.',
        'started'   => 'Ticket :number is in consultation.',
        'completed' => 'Ticket :number completed.',
        'no_show'   => 'Ticket :number marked as a no-show.',
        'cancelled' => 'Ticket :number cancelled.',
    ],

    'refusal' => [
        'already_in_queue'                 => 'This patient is already in the queue.',
        'appointment_not_checkable'        => 'This booking cannot be checked in from its current status.',
        'number_exhausted'                 => 'Could not allocate a ticket number. Try again.',
        'not_allowed_from_waiting'         => 'That step is not possible for a patient who is still waiting.',
        'not_allowed_from_called'          => 'That step is not possible for a patient who has been called.',
        'not_allowed_from_service_started' => 'That step is not possible during a consultation. Complete it instead.',
        'not_allowed_from_completed'       => 'This ticket is already completed.',
        'not_allowed_from_no_show'         => 'This ticket is already closed as a no-show.',
        'not_allowed_from_cancelled'       => 'This ticket is already cancelled.',
    ],

    'no_facility_context' => 'No facility is selected for this session.',
];
