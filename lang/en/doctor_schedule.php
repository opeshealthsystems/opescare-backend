<?php

/*
|--------------------------------------------------------------------------
| Clinician mobile — today, schedule, appointments and appointment requests
|--------------------------------------------------------------------------
|
| Every message returned by MobileDoctorScheduleController. Keys MUST stay
| 1:1 with lang/fr/doctor_schedule.php (php scripts/i18n-audit.php).
|
| Wording rule: a refusal never says whether a record exists at another
| facility. "Not found" is the answer for someone else's appointment as much
| as for an id that was never issued.
|
*/

return [

    // ── Lookup ───────────────────────────────────────────────────────────
    'appointment_not_found' => 'Appointment not found.',
    'request_not_found'     => 'Appointment request not found.',
    'provider_not_found'    => 'Clinician account not found.',
    'facility_required'     => 'This session is not bound to a facility. Sign in again.',

    // ── Transition refusals ──────────────────────────────────────────────
    'not_check_in_eligible' => 'Only a booked or confirmed appointment can be checked in.',
    'already_checked_in'    => 'This appointment is already checked in.',
    'already_no_show'       => 'This appointment is already marked as a no-show.',
    'not_no_show_eligible'  => 'Only a booked, confirmed or arrived appointment can be marked as a no-show.',
    'already_cancelled'     => 'This appointment is already cancelled.',
    'appointment_closed'    => 'This appointment is closed and can no longer be changed.',
    'not_pending'           => 'This appointment request has already been decided.',
    'unsupported_status'    => 'That status is not one this appointment can move to.',

    // ── Failures ─────────────────────────────────────────────────────────
    'cancel_failed'     => 'The appointment could not be cancelled. Please try again.',
    'check_in_failed'   => 'The check-in could not be recorded. Please try again.',
    'no_show_failed'    => 'The no-show could not be recorded. Please try again.',
    'reschedule_failed' => 'The appointment could not be moved. Please try again.',
    'decision_failed'   => 'The decision could not be recorded. Please try again.',

    // ── Critical lab values on the home screen ───────────────────────────
    'critical_value_category' => 'Critical lab value',
    'critical_value_body'     => 'Result :value against a critical threshold of :threshold.',

    // ── Recorded reasons (written to the audit trail) ─────────────────────
    'reason_cancelled'   => 'Cancelled by the clinician from the mobile app.',
    'reason_declined'    => 'Request declined by the clinician from the mobile app.',
    'reason_accepted'    => 'Request accepted by the clinician from the mobile app.',
    'reason_checked_in'  => 'Patient checked in by the clinician from the mobile app.',
    'reason_no_show'     => 'Marked as a no-show by the clinician from the mobile app.',
    'reason_rescheduled' => 'Appointment moved by the clinician from the mobile app.',
    'reason_status'      => 'Status changed by the clinician from the mobile app.',

    // ── Follow-up booking (POST /doctor/patients/{id}/follow-up) ─────────
    'patient_not_found'      => 'Patient not found.',
    'follow_up_failed'       => 'The follow-up could not be booked. Please try again.',
    'facility_not_available' => 'The facility is closed at that time. Choose a time within its opening hours.',
    'provider_not_available' => 'You are not available at that time according to your posted hours. Choose another time.',
    'reason_follow_up'       => 'Follow-up booked by the clinician from the mobile app.',

];
