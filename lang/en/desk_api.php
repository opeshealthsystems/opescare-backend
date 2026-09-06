<?php

/*
|--------------------------------------------------------------------------
| Front desk mobile — the facility's appointment book, check-in and walk-ins
|--------------------------------------------------------------------------
|
| Every message returned by the /api/mobile/desk controllers and written by
| App\Services\FrontDesk\FrontDeskService. Keys MUST stay 1:1 with
| lang/fr/desk_api.php (php scripts/i18n-audit.php).
|
| Wording rule: a refusal never says whether a record exists at another
| facility. "Not found" is the answer for someone else's appointment as much
| as for an id that was never issued, and for a Health ID that is not exact.
|
| Patient-facing text (notify_*) lands on the patient's phone. It names the
| facility, the date and the time — never a diagnosis, a clinician's note or
| anything else from the record.
|
*/

return [

    // ── Lookup ───────────────────────────────────────────────────────────
    'patient_not_found'        => 'No patient holds that Health ID. Check the ID exactly as printed on the card.',
    'appointment_not_found'    => 'Appointment not found.',
    'provider_not_found'       => 'Staff account not found.',
    'provider_not_at_facility' => 'That clinician is not posted at this facility, so the appointment cannot be routed to them.',

    // ── Transition refusals ──────────────────────────────────────────────
    'appointment_closed'    => 'This appointment is closed and can no longer be changed.',
    'already_confirmed'     => 'This appointment is already confirmed.',
    'not_confirm_eligible'  => 'Only a booked appointment can be confirmed.',
    'already_checked_in'    => 'This patient is already checked in.',
    'not_check_in_eligible' => 'Only a booked or confirmed appointment can be checked in.',
    'already_no_show'       => 'This appointment is already marked as a no-show.',
    'not_no_show_eligible'  => 'Only a booked, confirmed or checked-in appointment can be marked as a no-show.',
    'already_cancelled'     => 'This appointment is already cancelled.',

    // ── Booking refusals ─────────────────────────────────────────────────
    'facility_not_available' => 'The facility is closed at that time. Choose a time within its opening hours.',
    'provider_not_available' => 'That clinician is not available at that time. Choose another time or leave the appointment unassigned.',

    // ── Failures ─────────────────────────────────────────────────────────
    'booking_failed'      => 'The appointment could not be booked. Please try again.',
    'reschedule_failed'   => 'The appointment could not be moved. Please try again.',
    'route_failed'        => 'The appointment could not be routed. Please try again.',
    'confirm_failed'      => 'The confirmation could not be recorded. Please try again.',
    'check_in_failed'     => 'The check-in could not be recorded. Please try again.',
    'no_show_failed'      => 'The no-show could not be recorded. Please try again.',
    'cancel_failed'       => 'The appointment could not be cancelled. Please try again.',
    'registration_failed' => 'The patient could not be registered. Please try again.',

    // ── Walk-in registration ─────────────────────────────────────────────
    'duplicate_identity' => 'Someone matching these details already holds a Health ID. Look them up by that Health ID instead of registering them again; if the ID is lost, send them to Health ID recovery.',

    // ── Recorded reasons (written to the audit trail) ─────────────────────
    'reason_booked'      => 'Appointment booked by the front desk from the mobile app.',
    'reason_rescheduled' => 'Appointment moved by the front desk from the mobile app.',
    'reason_routed'      => 'Appointment routed to a clinician by the front desk from the mobile app.',
    'reason_confirmed'   => 'Appointment confirmed by the front desk from the mobile app.',
    'reason_checked_in'  => 'Patient checked in by the front desk from the mobile app.',
    'reason_no_show'     => 'Marked as a no-show by the front desk from the mobile app.',
    'reason_cancelled'   => 'Cancelled by the front desk from the mobile app.',
    'reason_follow_up'   => 'Follow-up booked by the clinician from the mobile app.',

    // ── Patient notifications (the bell, and the push when one is sent) ──
    'the_facility'               => 'the facility',
    'notify_action_label'        => 'View appointment',
    'notify_booked_title'        => 'Appointment booked',
    'notify_booked_message'      => 'An appointment has been booked for you at :facility on :date at :time.',
    'notify_confirmed_title'     => 'Appointment confirmed',
    'notify_confirmed_message'   => 'Your appointment at :facility on :date at :time is confirmed.',
    'notify_rescheduled_title'   => 'Appointment moved',
    'notify_rescheduled_message' => 'Your appointment at :facility has been moved to :date at :time.',
    'notify_routed_title'        => 'Your clinician is assigned',
    'notify_routed_message'      => 'Your appointment at :facility on :date at :time is now with :provider.',
    'notify_cancelled_title'     => 'Appointment cancelled',
    'notify_cancelled_message'   => 'Your appointment at :facility on :date at :time has been cancelled. Contact the facility to rebook.',
    'notify_follow_up_title'     => 'Follow-up appointment booked',
    'notify_follow_up_message'   => ':provider has booked your follow-up at :facility on :date at :time.',

];
