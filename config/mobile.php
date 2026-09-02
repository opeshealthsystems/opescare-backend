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

    'store_url' => env(
        'MOBILE_STORE_URL',
        'https://play.google.com/store/apps/details?id=com.opescare.patient'
    ),

];
