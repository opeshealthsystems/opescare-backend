<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile App Version Gate
    |--------------------------------------------------------------------------
    |
    | Drives GET /api/mobile/app-config, which the patient app calls at startup
    | (before login) to decide whether a forced update is required. Values are
    | env-driven so ops can bump them without a code deploy.
    |
    |  - min_supported_build: the lowest build number still allowed to run.
    |    The app compares its own build number; anything below is hard-blocked.
    |  - latest_version: the newest published version (shown in the update prompt).
    |  - store_url: where the update prompt sends the user.
    |
    */

    'min_supported_build' => (int) env('MOBILE_MIN_SUPPORTED_BUILD', 1),

    'latest_version' => env('MOBILE_LATEST_VERSION', '1.0.0'),

    // The package this app is actually published under. It used to name
    // `com.opescare.patient`, which is a different app: AppUpdateGate opens
    // this URL, so a user told their build was too old was sent to somebody
    // else's listing and could never get the update.
    //
    // Renamed to `com.opescare.patientid` on 2026-09-07, before the first Play
    // upload — which is the only moment a package name can be changed at all.
    // On this deployment MOBILE_STORE_URL overrides it with the sideload link,
    // because there is no listing yet; this default is what takes over the day
    // the app is published.
    'store_url' => env(
        'MOBILE_STORE_URL',
        'https://play.google.com/store/apps/details?id=com.opescare.patientid'
    ),

];
