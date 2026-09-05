<?php

/*
|--------------------------------------------------------------------------
| Consent ledger — "who can see my record right now?"
|--------------------------------------------------------------------------
|
| Every string MobileConsentLedgerController puts on the wire. The controller
| honours ?lang= and Accept-Language, so a French-speaking patient reads their
| own consent list in French.
|
| lang/en/consent_ledger.php and lang/fr/consent_ledger.php MUST stay 1:1 —
| identical keys, valid UTF-8, and the same :placeholders on both sides.
| Verify with: php scripts/i18n-audit.php
|
| The sentences here are the honest ones. Two of them carry most of the
| weight:
|
|   state_detail.expired      — a grant can read `active` in the database long
|                               after its clock ran out, because nothing sweeps
|                               the column. The patient is told it has ended.
|   relationship_note         — revoking every grant does NOT cut off a
|                               clinician who is treating the patient. Saying
|                               otherwise, or staying silent, would let a
|                               patient believe they had closed a door that is
|                               still open.
|
*/

return [

    // ── What state a grant is in ─────────────────────────────────────────

    'state' => [
        'active'  => 'Active',
        'expired' => 'Expired',
        'revoked' => 'Withdrawn',
        'ended'   => 'Ended',
    ],

    'state_detail' => [
        'active'  => 'This access is live right now. This facility can open your record until it expires, or until you withdraw it.',
        'expired' => 'This access has run out. It ended on its own on the date shown, and nobody can use it to open your record.',
        'revoked' => 'You took this access away. It ended on the date shown and cannot be used again.',
        'ended'   => 'This access is no longer live. It is kept here so you can see it existed.',
    ],

    // ── On whose authority a grant exists ────────────────────────────────

    'basis' => [
        'patient'         => 'You granted this',
        'guardian'        => 'Your guardian granted this',
        'facility_policy' => 'Granted under facility policy',
        'unknown'         => 'Authority not recorded',
    ],

    'basis_detail' => [
        'patient'         => 'You approved this access yourself.',
        'guardian'        => 'The person legally responsible for you approved this access on your behalf.',
        'facility_policy' => 'You did not approve this one. It was created under the facility\'s own policy rather than by your decision — ask them why it exists if you did not expect it.',
        'unknown'         => 'The record does not say who authorised this access. That is a gap in the record, not a decision you made.',
    ],

    'source' => [
        'consent_request' => 'From a request you were asked to approve',
        'direct'          => 'Created directly, with no request sent to you',
    ],

    // ── Withdrawing ──────────────────────────────────────────────────────

    'revoke_available' => 'You can withdraw this access now. It stops immediately.',

    'revoke_unavailable' => [
        'not_active'     => 'This access has already ended, so there is nothing left to withdraw.',
        'no_linked_user' => 'This access cannot be withdrawn from the app yet, because your patient record is not linked to a sign-in account. Ask your facility to link them, or ask them to withdraw it for you.',
    ],

    // ── Access that is NOT a consent grant ───────────────────────────────

    'relationship' => [
        'appointment'        => 'You have an appointment with them',
        'care_team'          => 'They are on your care team',
        'treating_clinician' => 'They have treated you',
        'facility_episode'   => 'You are being cared for at this facility',
    ],

    'relationship_detail' => [
        'appointment'        => 'A clinician you are booked to see can open your record to prepare for and carry out that appointment.',
        'care_team'          => 'A clinician recorded as a current member of your care team can open your record for as long as they remain on it.',
        'treating_clinician' => 'A clinician who personally treated you — saw you, prescribed for you, or ordered a test or scan for you — can open the record of the care they gave.',
        'facility_episode'   => 'While you have an open or recent episode of care at a facility, the clinicians working there can open your record. This one names no single person.',
    ],

    'relationship_note' => 'These are not consent grants, and withdrawing consent does not end them. A clinician who is treating you can open your record because of that care itself. To end one of these, the care has to end — cancel the appointment, ask to be taken off the care team, or speak to the facility. Every time anyone opens your record, whatever the reason, it is written to your access log.',

];
