<?php

/*
|--------------------------------------------------------------------------
| Blood-bank staff desk — mobile provider surface
|--------------------------------------------------------------------------
|
| Every message returned by MobileBloodDeskController. Keys MUST stay 1:1
| with lang/fr/blood_desk.php (php scripts/i18n-audit.php).
|
| Wording rule: a refusal never confirms that a patient asked a DIFFERENT
| blood bank for blood. "Not found" is the answer for another facility's
| request exactly as it is for an id that was never issued.
|
| Second wording rule: a confirmation is this blood bank saying "yes, come" —
| it never claims units have been set aside, because nothing on this surface
| reserves or decrements stock.
|
*/

return [

    'facility_required'       => 'Your session is not linked to a facility.',
    'request_not_found'       => 'Blood request not found for this facility.',
    'audit_unavailable'       => 'This action was refused: it could not be recorded in the access log.',
    'transition_not_allowed'  => 'This blood request can no longer move to that status.',

    'decision_recorded'       => 'Blood request updated.',
    'confirmed_recorded'      => 'Request confirmed. The patient has been told to come to the counter; no units are held by this confirmation.',
    'rejected_recorded'       => 'Request declined.',
    'ready_recorded'          => 'Marked ready for collection.',
    'fulfilled_recorded'      => 'Marked collected.',

    /*
    |----------------------------------------------------------------------
    | Staff WEB desk — /facility/blood-requests
    |----------------------------------------------------------------------
    |
    | The browser queue (BloodRequestDeskController). It shares every message
    | above with the mobile desk rather than restating it; only what a screen
    | needs and an API response does not is added here.
    |
    | Status and urgency labels live here and not in
    | App\Enums\BloodRequestStatus, whose own label() is hardcoded English and
    | cannot be translated.
    |
    */

    'nav'                     => 'Blood Requests',
    'title'                   => 'Blood Requests',
    'breadcrumb'              => 'Blood Requests',
    'subtitle'                => 'Requests patients have sent to this blood bank. Unanswered ones are listed first.',

    'stat_open'               => 'Open',
    'stat_pending'            => 'Awaiting an answer',
    'stat_confirmed'          => 'Confirmed',
    'stat_ready'              => 'Ready for collection',

    'filter_open_only'        => 'Open requests',
    'filter_every_status'     => 'Every request',
    'filter_btn'              => 'Filter',
    'clear_btn'               => 'Clear',

    'col_reference'           => 'Reference',
    'col_patient'             => 'Patient',
    'col_request'             => 'Request',
    'col_urgency'             => 'Urgency',
    'col_status'              => 'Status',
    'col_filed'               => 'Filed',
    'col_actions'             => 'Actions',

    'empty_title'             => 'No requests to answer',
    'empty_desc'              => 'When a patient asks this blood bank for units, the request appears here.',
    'no_facility_notice'      => 'No facility is selected for this session, so there is no queue to show. Select a facility first.',
    'no_listing_notice'       => 'This facility has no public directory listing yet, so no patient can address a blood request to it.',
    'audit_blocked_notice'    => 'The queue is hidden: this disclosure could not be recorded in the access log.',

    'view_btn'                => 'Open',
    'back_to_queue'           => 'Back to the queue',
    'detail_title'            => 'Blood request :reference',

    'section_request'         => 'What was asked for',
    'section_patient'         => 'Who is collecting',
    'section_decision'        => 'This blood bank’s answer',
    'section_actions'         => 'Answer this request',

    'lbl_group'               => 'Blood group',
    'lbl_component'           => 'Component',
    'lbl_quantity'            => 'Units requested',
    'lbl_urgency'             => 'Urgency',
    'lbl_needed_by'           => 'Needed by',
    'lbl_expires'             => 'Hold lapses',
    'lbl_filed'               => 'Filed',
    'lbl_health_id'           => 'Health ID',
    'lbl_name'                => 'Name',
    'lbl_phone'               => 'Contact phone',
    'lbl_patient_note'        => 'Patient note',
    'lbl_facility_note'       => 'Note recorded by this desk',
    'lbl_decided_by'          => 'Answered by',
    'lbl_decided_at'          => 'Answered at',
    'lbl_status'              => 'Status',
    'units_label'             => 'units',
    'not_recorded'            => 'Not recorded',

    'note_label'              => 'Note for the record (optional)',
    'note_placeholder'        => 'Anything the next person at this desk should know.',
    'action_confirm'          => 'Confirm — tell the patient to come',
    'action_reject'           => 'Decline this request',
    'action_ready'            => 'Units are at the counter',
    'action_fulfil'           => 'Collected',
    'no_moves'                => 'This request is closed. Its history stays exactly as recorded — nothing here can reopen or edit it.',
    'no_hold_notice'          => 'Confirming sets no units aside. It tells the patient to come; blood leaves the shelf only when it is issued on the inventory screen.',

    'status_pending'          => 'Awaiting Blood Bank',
    'status_confirmed'        => 'Confirmed',
    'status_ready'            => 'Ready for Collection',
    'status_fulfilled'        => 'Collected',
    'status_rejected'         => 'Declined by Blood Bank',
    'status_cancelled'        => 'Cancelled by the patient',
    'status_expired'          => 'Expired unanswered',

    'urgency_routine'         => 'Routine',
    'urgency_urgent'          => 'Urgent',
    'urgency_emergency'       => 'Emergency',

];
