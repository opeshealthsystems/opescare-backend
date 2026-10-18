<?php

/*
|--------------------------------------------------------------------------
| Hospital / clinic / health-centre desk — completeness strings
|--------------------------------------------------------------------------
| Shared by the operational desks a hospital, clinic or health centre runs
| from the web (queue, triage, appointments, waitlist, referrals, team,
| blood, tasks, help). Every irreversible action asks first (confirm_*),
| every empty list says what to do next (empty_*), and every row or tile
| that has a next step links to it (open_*).
|
| lang/fr/hospital_desk.php MUST stay 1:1 with this file
| (php scripts/i18n-audit.php).
*/

return [
    // ── Confirmations (window.confirm via data-confirm, public/js/op-actions.js) ──
    'confirm_queue_complete'      => 'Mark this consultation as completed? The ticket leaves the queue.',
    'confirm_queue_no_show'       => 'Mark this patient as not present? The ticket is closed as a no-show.',
    'confirm_queue_cancel'        => 'Cancel this ticket? The patient is removed from the queue.',
    'confirm_appt_check_in'       => 'Check this patient in? A queue ticket is opened for today.',
    'confirm_appt_confirm'        => 'Confirm this appointment? The patient is notified.',
    'confirm_appt_no_show'        => 'Mark this appointment as a no-show? This cannot be undone.',
    'confirm_team_approve'        => 'Approve this join request with the selected role?',
    'confirm_team_decline'        => 'Decline this join request?',
    'confirm_invite_reissue'      => 'Send this invitation again? The previous link stops working.',
    'confirm_invite_revoke'       => 'Revoke this invitation? The link stops working immediately.',
    'confirm_member_role'         => 'Change this member\'s role at this facility?',
    'confirm_member_deactivate'   => 'Deactivate this member? They lose access to this facility\'s desks.',
    'confirm_member_reactivate'   => 'Reactivate this member at this facility?',
    'confirm_referral_send'       => 'Send this referral to the receiving facility?',
    'confirm_referral_accept'     => 'Accept this referral? The referring facility and the patient are told.',
    'confirm_referral_reject'     => 'Reject this referral with the reason given?',
    'confirm_referral_complete'   => 'Mark this referral as completed?',
    'confirm_referral_cancel'     => 'Cancel this referral with the reason given?',
    'confirm_blood_confirm'       => 'Confirm this blood request? Units are reserved for the patient.',
    'confirm_blood_reject'        => 'Reject this blood request?',
    'confirm_blood_ready'         => 'Mark the units as ready for collection?',
    'confirm_blood_fulfil'        => 'Mark this request as fulfilled? Stock is decremented.',
    'confirm_task_complete'       => 'Mark this task as completed?',
    'confirm_task_escalate'       => 'Escalate this task to the facility administrators?',
    'confirm_support_close'       => 'Mark this ticket as resolved?',
    'confirm_immunisation_void'   => 'Void this immunisation record? It stays on file, marked as voided.',
    'confirm_bed_service'         => 'Change this bed\'s service state?',

    // ── Next steps from a row, a ticket or a tile ──
    'open_chart'                  => 'Open chart',
    'open_triage'                 => 'Record triage',
    'open_appointment'            => 'Open the booking',
    'open_book'                   => 'Open the appointment book',
    'open_queue'                  => 'Open the queue',
    'open_reports'                => 'Open the reports',
    'open_invoices'               => 'Open the invoices',
    'open_wards'                  => 'Open wards & beds',
    'open_billing'                => 'Open billing',
    'patient_next_steps'          => 'Next steps for this patient',
    'patient_next_steps_hint'     => 'The chart opens only with a consent basis; triage records vitals against this visit.',

    // ── Empty states: what to do next ──
    'empty_triage_hint'           => 'Nobody has been triaged today. Vitals recorded here order the queue.',
    'empty_triage_action'         => 'Record a triage assessment',
    'empty_referrals_sent_hint'   => 'This facility has not referred anyone out yet.',
    'empty_referrals_received_hint' => 'No other facility has referred a patient here yet.',
    'empty_referrals_action'      => 'Refer a patient',
    'empty_prescriber_action'     => 'Write a prescription',
    'empty_messages_hint'         => 'Patients start a thread from their own portal; it appears here the moment it is sent.',
    'empty_blood_requests_action' => 'Raise a blood request',
    'empty_tasks_hint'            => 'Tasks are assigned by the facility administrators and by the platform; nothing is waiting for you.',
    'empty_support_hint'          => 'Describe a problem with the form above and the OpesCare team answers here.',
    'empty_appointments_action'   => 'Book an appointment',
    'empty_waitlist_hint'         => 'Patients join the waitlist from their own portal when no slot suits them.',
    'empty_queue_action'          => 'Check a patient in',

    // ── Waitlist ──
    'waitlist_showing'            => 'Showing :from–:to of :total',

    // ── Facility dashboard (/facility) ──
    'dash_welcome' => 'Welcome back',
    'dash_kpi_queue' => 'Queue waiting',
    'dash_kpi_queue_urgent' => ':count urgent waiting',
    'dash_kpi_rx' => 'Prescriptions today',
    'dash_kpi_rx_sub' => 'Issued at this facility today',
    'dash_quick' => 'Quick actions',
    'dash_qa_book' => 'Book appointment',
    'dash_qa_checkin' => 'Check in patient',
    'dash_qa_triage' => 'Triage',
    'dash_qa_rx' => 'New prescription',
    'dash_qa_referral' => 'New referral',
    'dash_qa_search' => 'Patient search',
    'dash_queue_title' => 'Today\'s queue',
    'dash_queue_empty' => 'Nobody waiting',
    'dash_col_arrived' => 'Arrived',
    'dash_col_priority' => 'Priority',
    'dash_col_status' => 'Status',
    'dash_col_action' => 'Action',
    'dash_open' => 'Open',
    'dash_alerts' => 'Alerts',
    'dash_alert_urgent' => 'Urgent patients waiting',
    'dash_alert_urgent_sub' => 'Emergency or very urgent priority, not yet seen',
    'dash_appts_title' => 'Today\'s appointments',
    'dash_appts_empty' => 'No appointments today',
    'dash_appts_empty_text' => 'Nothing is booked at this facility for today.',

    // ── Clinic desk restyle (2026-10-18) ──
    'b_action_edit' => 'Edit',
    'c_count_paren' => '(:count)',
    'd_ref_sec_patient' => 'Patient and urgency',
    'd_ref_sec_destination' => 'Where the patient is going',
    'd_ref_sec_clinical' => 'Clinical handover',
    'd_imm_doses' => '{0} No doses recorded|{1} :count dose recorded|[2,*] :count doses recorded',
];
