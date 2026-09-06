<?php

/*
|--------------------------------------------------------------------------
| Patient push — channels, sounds and the type → channel map
|--------------------------------------------------------------------------
|
| One Expo push carries three things the handset acts on:
|
|   channelId  the Android notification channel. The patient app creates
|              every channel listed here at boot, each with the sound named
|              beside it (Android plays the CHANNEL's sound, not the
|              message's — so the two must agree, which is why the channel
|              id IS the sound's basename).
|   sound      the bundled sound file, by filename. iOS plays exactly this
|              file; Android ignores it in favour of the channel. 'default'
|              is the OS default tone.
|   data.type  what the app does when the notification is tapped or arrives
|              in the foreground (open the consent prompt, the thread, the
|              result…). Every push the platform sends carries one.
|
| Add a type here and in the app's mirror (opescare-finder-mobile,
| lib/push/channels.ts) in the same change — an unknown type falls back to
| the 'default' channel, so nothing breaks, but the distinct sound is lost.
|
*/

return [

    'expo' => [
        /*
         * Expo's push API needs no secret on this host — the FCM/APNs
         * credentials live once, in EAS. So "configured" is a switch, not
         * a credential check: it is on by default and exists so an
         * operator (or a test) can turn every patient push off in one
         * place. EXPO_PUSH_ENABLED=false in .env silences the platform.
         */
        'enabled' => (bool) env('EXPO_PUSH_ENABLED', true),

        /*
         * Only needed when the EAS project has "enhanced push security"
         * turned on. Read through config(), never env(), because this app
         * runs config:cache on deploy.
         */
        'access_token' => env('EXPO_ACCESS_TOKEN'),
    ],

    /*
     * Android channel id → bundled sound filename.
     */
    'channels' => [
        'consent'      => 'opescare_consent.wav',      // hospital chime — a decision is waiting
        'messages'     => 'opescare_message.wav',
        'results'      => 'opescare_result.wav',
        'appointments' => 'opescare_appointment.wav',
        'emergency'    => 'opescare_alert.wav',
        'default'      => 'default',
    ],

    /*
     * data.type → channel id. Types not listed here use 'default'.
     */
    'types' => [
        'consent_request'   => 'consent',
        'family_invitation' => 'consent',   // also an "open my record to this person?" decision
        'lab_result'        => 'results',
        'appointment'       => 'appointments',
        'message'           => 'messages',
        'emergency_access'  => 'emergency',
    ],

];
