<?php

/*
|--------------------------------------------------------------------------
| Patient messaging — who a patient may write to
|--------------------------------------------------------------------------
|
| Every string the /api/mobile/messages/recipients listing and the three
| POST /api/mobile/messages/threads paths (appointment / facility / clinician)
| put in front of a patient. Keep 1:1 with lang/fr/messaging_recipients.php —
| `php scripts/i18n-audit.php` is a CI gate.
|
| Tone rule for the refusals: say what is true and what the patient can do
| instead. None of them may read as "you did something wrong".
|
*/

return [

    // ── The standing warning ────────────────────────────────────────────
    // Rendered above every composer. Secure messaging is answered in working
    // hours by a person, which is not what an emergency needs.
    'not_emergency'       => 'This is not an emergency channel. Messages are read during opening hours. For an emergency, call the facility or go there in person.',

    // ── Recipient kinds ─────────────────────────────────────────────────
    'facility_desk'       => 'Front desk',
    'facility_desk_hint'  => 'Opening hours, booking help, results, whether a medicine is in stock.',
    'clinician_hint'      => 'Only clinicians who are already looking after you.',

    // ── Why a clinician is reachable — the relationship basis ───────────
    // Mirrors MobileDoctorChartController::relationshipBasis(). The patient
    // sees WHY this name is on the list, never a bare list of names.
    'basis' => [
        'appointment'        => 'You have an appointment with this clinician',
        'care_team'          => 'On your care team',
        'treating_clinician' => 'Has treated you',
        'facility_episode'   => 'Involved in your care at this facility',
        'consent_grant'      => 'You granted this facility access to your record',
    ],

    // The same five ideas asked of a PLACE, not a person. A facility listed
    // under `basis.appointment` would read "you have an appointment with this
    // clinician", which is not what the row says.
    'facility_basis' => [
        'appointment'      => 'You have an appointment here',
        'facility_episode' => 'You have been treated here',
        'consent_grant'    => 'You granted this facility access to your record',
    ],

    // ── Refusals ────────────────────────────────────────────────────────
    'appointment_not_found'   => 'Appointment not found.',
    'appointment_no_provider' => 'This appointment has no assigned provider to message yet.',

    'recipient_required'      => 'Choose who to write to: a facility, a clinician who is already looking after you, or one of your appointments.',
    'recipient_ambiguous'     => 'Choose one recipient — a facility, a clinician or an appointment, not several.',

    'facility_not_found'      => 'Facility not found.',
    'facility_unreachable'    => ':facility has no messaging desk yet, so a message here would reach nobody. Call them instead.',

    'clinician_not_found'     => 'Clinician not found.',
    'no_relationship'         => 'You can only write to a clinician who is already looking after you. Write to the facility instead, or book an appointment.',
    'clinician_unreachable'   => ':clinician cannot receive messages directly yet. Write to :facility instead — the desk will pass it on.',

    'thread_not_created'      => 'The conversation could not be started. Try again in a moment.',

];
