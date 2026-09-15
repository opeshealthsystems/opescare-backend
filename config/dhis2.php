<?php

// Imported from the platform (config/dhis2.php) and adapted for the Finder:
//  - credentials may be a username/password pair OR a personal access token;
//  - there is no single DHIS2_ORG_UNIT: every facility has its own org unit,
//    stored in dhis2_org_unit_mappings and set from /admin/public-health/dhis2/mappings;
//  - DHIS2_ENABLED is gone: reporting is "configured" when a base URL and
//    credentials exist, and nothing is ever pushed without a platform admin's approval;
//  - the data element map only lists what the Finder actually writes (see
//    App\Modules\PublicHealth\Services\DraftGenerationService). An env value
//    here is the fallback; a row in dhis2_data_element_mappings wins.

return [
    'base_url'    => env('DHIS2_BASE_URL', env('DHIS2_URL', '')),
    'username'    => env('DHIS2_USERNAME', ''),
    'password'    => env('DHIS2_PASSWORD', ''),
    'token'       => env('DHIS2_TOKEN', ''),         // DHIS2 personal access token (d2pat_...)
    'dataset_id'  => env('DHIS2_DATASET_ID', ''),    // DHIS2 dataSet UID (optional)
    'timeout'     => env('DHIS2_TIMEOUT', 30),

    // -------------------------------------------------------------------------
    // MINSANTE DHIS2 Data Element UIDs: obtain from the DHIS2 instance
    // (Maintenance -> Data Elements). Blank = not mapped, never sent.
    // Disaggregated indicators (per vaccine, per lab test) are mapped per
    // indicator code from the admin mapping page.
    // -------------------------------------------------------------------------
    'data_element_map' => [
        'appointments_completed'  => env('DHIS2_DE_APPOINTMENTS_COMPLETED', ''),
        'prescriptions_dispensed' => env('DHIS2_DE_PRESCRIPTIONS_DISPENSED', ''),
        'referrals_sent'          => env('DHIS2_DE_REFERRALS_SENT', ''),
        'referrals_received'      => env('DHIS2_DE_REFERRALS_RECEIVED', ''),
    ],
];
