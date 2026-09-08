<?php

/*
|--------------------------------------------------------------------------
| Clinician mobile — clinician-to-clinician referrals
|--------------------------------------------------------------------------
|
| Every message returned by MobileDoctorReferralController. Keys MUST stay
| 1:1 with lang/fr/doctor_referrals.php (php scripts/i18n-audit.php).
|
| Wording rule: a refusal never says whether a record exists at another
| facility. "Not found" is the answer for another facility's referral as much
| as for an id that was never issued. And a referral that carries no record
| access says so plainly — a clinician who is not told the package is empty
| will assume the other end can read the chart.
|
*/

return [

    // ── Identity and lookup ──────────────────────────────────────────────
    'facility_required'             => 'This session is not bound to a facility. Sign in again.',
    'patient_not_found'             => 'Patient not found.',
    'referral_not_found'            => 'Referral not found.',
    'receiving_facility_not_found'  => 'The receiving facility was not found.',

    // ── Access ───────────────────────────────────────────────────────────
    'no_clinical_relationship' => 'You do not have a care relationship with this patient. Ask the patient for consent before referring them.',
    'consent_required'         => 'This patient has asked that their full record stay behind their consent. Ask for consent before referring them.',
    'wrong_party'              => 'This move belongs to the other facility on this referral.',
    'audit_unavailable'        => 'This action cannot be recorded right now, so it has not been carried out. Try again shortly.',

    // ── Raising a referral ───────────────────────────────────────────────
    'destination_required'   => 'Say where this referral is going: a facility, a specialty, or a named clinician.',
    'self_referral'          => 'A referral cannot be addressed to your own facility.',
    'request_not_actionable' => 'That referral request is not open, or does not belong to this patient.',

    // ── The lifecycle ────────────────────────────────────────────────────
    'illegal_transition' => 'This referral cannot move to that state from where it is now.',

    // ── What actually travels ────────────────────────────────────────────
    'records_shared'     => 'The receiving facility can read the record types listed, under the patient\'s consent.',
    'records_not_shared' => 'This referral carries no record access. The receiving facility will see the reason and summary only, until the patient grants them consent.',

];
