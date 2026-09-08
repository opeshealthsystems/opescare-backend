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

];
