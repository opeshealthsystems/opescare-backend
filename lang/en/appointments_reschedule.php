<?php

/*
|--------------------------------------------------------------------------
| Mobile appointment reschedule — patient-facing messages
|
| Its own file rather than new keys in lang/en/api.php + lang/fr/api.php:
| that file is shared by every API surface and is edited constantly, so a
| reschedule string landing in it is a merge conflict waiting to happen.
| lang/en and lang/fr must stay
| 1:1 — `php scripts/i18n-audit.php` is a CI gate and checks keys, encoding
| and :placeholder parity, not just key names.
|
| Every message answers the patient's real question: *what happened to my
| appointment?* A refusal therefore names the specific reason (cancelled,
| completed, already past …) instead of a generic "cannot reschedule", and
| every failure path states plainly that the ORIGINAL appointment still
| stands — the whole point of an atomic swap is that a patient never ends up
| with nothing.
|--------------------------------------------------------------------------
*/

return [

    // ── Outcome ─────────────────────────────────────────────────────────────
    'success' => 'Your appointment has been moved to :when.',

    // The line every failure carries. A patient who tried to move an
    // appointment and saw an error must be told, in the same breath, that they
    // have not lost the slot they already held.
    'unchanged' => 'Your original appointment on :when is unchanged.',

    // ── The appointment cannot be moved ─────────────────────────────────────
    'not_found'           => 'Appointment not found.',
    'cancelled'           => 'This appointment was cancelled, so it can no longer be moved. Book a new appointment instead.',
    'completed'           => 'This appointment has already been completed, so it can no longer be moved.',
    'no_show'             => 'This appointment was recorded as a missed visit, so it can no longer be moved. Book a new appointment instead.',
    'checked_in'          => 'You are already checked in for this appointment, so it can no longer be moved. Please speak to the reception desk.',
    'already_rescheduled' => 'This appointment has already been moved. Open the replacement appointment to change it again.',
    'not_reschedulable'   => 'An appointment with the status ":status" cannot be moved.',
    'in_past'             => 'This appointment was on :when and is now in the past, so it can no longer be moved.',

    // ── The chosen slot cannot be taken ─────────────────────────────────────
    'slot_not_found'          => 'That appointment time no longer exists.',
    'same_slot'               => 'That is the time you are already booked for. Choose a different time.',
    'facility_slot_mismatch'  => 'That time belongs to a different facility. To change facility, cancel this appointment and book a new one.',
    'slot_not_bookable'       => 'That appointment time is no longer available.',
    'facility_not_bookable'   => 'This facility is not accepting appointments at the moment.',
    'slot_taken'              => 'That appointment time was taken while you were choosing it.',

];
