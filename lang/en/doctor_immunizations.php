<?php

/*
|--------------------------------------------------------------------------
| Clinician mobile — the immunisation write path
|--------------------------------------------------------------------------
|
| Every message returned by MobileDoctorImmunizationController. Keys MUST stay
| 1:1 with lang/fr/doctor_immunizations.php (php scripts/i18n-audit.php).
|
| Wording rules:
|
|   • VACCINE NAMES ARE NEVER TRANSLATED. BCG is BCG in both files. Only the
|     labels around a vaccine are UI copy, and none of them appear here as a
|     placeholder that could interpolate a clinical term.
|
|   • A refusal never says whether a record exists. "Not found" answers a dose
|     belonging to a patient this clinician may not open exactly as it answers
|     an id that was never issued.
|
|   • Nothing here claims an action that did not happen. A severe reaction is
|     told plainly that reporting it onward is still a human step, rather than
|     letting a confirmation imply the authority was notified.
|
*/

return [

    // ── Identity and lookup ──────────────────────────────────────────────
    'facility_required' => 'This session is not bound to a facility. Sign in again.',
    'patient_not_found' => 'Patient not found.',
    'record_not_found'  => 'Immunisation record not found.',

    // ── Access ───────────────────────────────────────────────────────────
    'no_clinical_relationship' => 'You do not have a care relationship with this patient. Ask the patient for consent before recording a vaccination.',
    'consent_required'         => 'This patient has asked that their full record stay behind their consent. Ask for consent before writing to their vaccination card.',
    'not_recording_facility'   => 'Only the facility that recorded this dose can retract it. If the entry is wrong, record what actually happened as a new dose.',
    'audit_unavailable'        => 'This action cannot be recorded right now, so it has not been carried out. Try again shortly.',

    // ── Recording a dose ─────────────────────────────────────────────────
    'not_done_reason_required' => 'Say why this vaccine was not given. A dose marked as not given with no reason cannot be acted on by the next clinician.',
    'administered_in_future'   => 'A vaccine cannot be recorded as given at a future date and time.',
    'duplicate_dose'           => 'This patient already has a dose of this vaccine recorded for that date. Check the existing entry before recording another.',

    // ── Retraction ───────────────────────────────────────────────────────
    'voided'        => 'This dose has been marked as entered in error. It no longer counts on the patient\'s card, and the entry has been kept with your reason.',
    'already_voided' => 'This dose has already been marked as entered in error.',

    // ── Adverse events following immunisation ────────────────────────────
    'reaction_on_voided_dose'   => 'This dose was entered in error, so a reaction cannot be attached to it.',
    'authority_report_pending'  => 'This reaction has been recorded on the patient\'s record. It has NOT been reported to the health authority — that is still a step someone must take.',

];
