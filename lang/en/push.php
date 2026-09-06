<?php

/*
|--------------------------------------------------------------------------
| Patient push notifications — lock-screen copy
|--------------------------------------------------------------------------
|
| Every string here lands on a lock screen, which is a public surface. Name
| the facility, the person and the KIND of event; never a diagnosis, a result
| value, a medication or a test outcome. Keys are grouped by data.type
| (config/push_channels.php) so the copy for a push and its channel live
| under the same name. Resolved in the PATIENT's locale with the
| three-argument __('push.x.y', [...], $locale) — see App\Support\PatientPush.
|
*/

return [

    'consent_request' => [
        'title' => 'Access request',
        'body'  => ':facility is asking to open your health record. Tap to approve or deny.',
    ],

    'family_invitation' => [
        'title' => 'Family invitation',
        'body'  => ':guardian would like to be linked to your health record. Tap to review.',
    ],

    'lab_result' => [
        'title'    => 'Result reviewed',
        'body'     => 'Your clinician has reviewed your :test result. Open the app to read their note.',
        'ready'    => 'Your :test result is ready in your record.',
    ],

    'appointment' => [
        'title' => 'Appointment reminder',
        'body'  => 'You have an appointment at :facility on :when.',
    ],

    'message' => [
        'title' => 'New message',
        'body'  => 'You have a new message from :sender.',
    ],

    'emergency_access' => [
        'title' => 'Emergency access to your record',
        'body'  => ':facility opened your health record under emergency access. Tap to see the details.',
    ],

];
