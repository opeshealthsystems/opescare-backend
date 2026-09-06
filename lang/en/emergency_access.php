<?php

/*
|--------------------------------------------------------------------------
| Break-glass — emergency access to a patient record
|--------------------------------------------------------------------------
|
| Messages returned by MobileEmergencyAccessController (clinician) and
| MobilePatientEmergencyAccessController (patient). They are read at the
| bedside, often on a phone, sometimes while someone is being resuscitated —
| so each one says what happened AND what to do next. A refusal that leaves a
| clinician guessing is a refusal that gets worked around.
|
| lang/fr/emergency_access.php must stay 1:1 with this file, same keys, same
| :placeholders (php scripts/i18n-audit.php).
|
*/

return [

    // ── Refusals ─────────────────────────────────────────────────────────
    'actor_unresolved' => 'Your session does not identify a clinician and a facility. Sign in again from the facility where you are working.',
    'patient_not_found' => 'No record matches that Health ID. Check it with the patient or their escort, or with the referring team.',
    'patient_required' => 'Enter the patient’s Health ID. Emergency access opens one named record, never a list.',

    // ── The reason, and the acknowledgement ──────────────────────────────
    'reason_required' => 'State why emergency access is needed. This is recorded and shown to the patient.',
    'reason_too_short' => 'Describe the emergency in your own words — at least :min characters. This is recorded, shown to the patient, and reviewed.',
    'acknowledgement_required' => 'Emergency access can only be opened after you confirm that it is logged, that the patient is told, and that it is reviewed.',

    // ── The grant's life ─────────────────────────────────────────────────
    'grant_not_found' => 'That emergency access grant does not exist, or it was not opened by you.',
    'grant_expired' => 'This emergency access has expired. If care is still under way, open a new one and state the current reason.',
    'grant_revoked' => 'The patient has closed this emergency access.',
    'grant_already_ended' => 'This emergency access has already ended, so there is nothing to close.',
    'already_revoked' => 'You have already closed this emergency access.',
    'revoked' => 'Emergency access closed. The clinician can no longer read your record under it, and the closure has been recorded.',

    // ── Limits and abuse controls ────────────────────────────────────────
    'rate_limited' => 'Too many emergency overrides in a short time. This one has been referred to your facility’s governance team — contact them or the on-call medical officer.',
    'revoked_by_patient' => 'The patient closed an earlier emergency access on this record and it cannot be reopened yet. Escalate to your facility’s governance team or the on-call medical officer.',

    // ── Infrastructure ───────────────────────────────────────────────────
    'audit_unavailable' => 'Emergency access could not be recorded, so nothing has been opened. Access that leaves no trail is never granted. Try again, and use the paper emergency procedure if this persists.',

    'withheld_by_patient' => 'This patient has turned off emergency access to their record. Ask the patient, their family or their guardian; this attempt has been recorded.',
];
