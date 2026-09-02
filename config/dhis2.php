<?php

return [
    'base_url'    => env('DHIS2_BASE_URL', ''),
    'username'    => env('DHIS2_USERNAME', ''),
    'password'    => env('DHIS2_PASSWORD', ''),
    'org_unit'    => env('DHIS2_ORG_UNIT', ''),      // DHIS2 orgUnit UID for this facility
    'dataset_id'  => env('DHIS2_DATASET_ID', ''),    // DHIS2 dataSet UID
    'enabled'     => env('DHIS2_ENABLED', false),
    'timeout'     => env('DHIS2_TIMEOUT', 30),

    // -------------------------------------------------------------------------
    // MINSANTE DHIS2 Data Element UIDs
    // Obtain from your DHIS2 instance: Maintenance → Data Elements
    // Each key maps to the DHIS2 dataElement UID for the corresponding metric.
    // Leave blank to skip that element from the push payload.
    // -------------------------------------------------------------------------
    'data_element_map' => [
        // Outpatient Department
        'opd_total'            => env('DHIS2_DE_OPD_TOTAL', ''),
        'opd_male'             => env('DHIS2_DE_OPD_MALE', ''),
        'opd_female'           => env('DHIS2_DE_OPD_FEMALE', ''),
        'opd_under5'           => env('DHIS2_DE_OPD_UNDER5', ''),
        'opd_referrals_in'     => env('DHIS2_DE_OPD_REFERRALS_IN', ''),
        'opd_referrals_out'    => env('DHIS2_DE_OPD_REFERRALS_OUT', ''),

        // Communicable Disease
        'malaria_confirmed'    => env('DHIS2_DE_MALARIA_CONFIRMED', ''),
        'malaria_presumed'     => env('DHIS2_DE_MALARIA_PRESUMED', ''),
        'malaria_treated'      => env('DHIS2_DE_MALARIA_TREATED', ''),
        'hiv_tested'           => env('DHIS2_DE_HIV_TESTED', ''),
        'hiv_positive'         => env('DHIS2_DE_HIV_POSITIVE', ''),
        'tb_presumptive'       => env('DHIS2_DE_TB_PRESUMPTIVE', ''),
        'tb_confirmed'         => env('DHIS2_DE_TB_CONFIRMED', ''),
        'tb_started_treatment' => env('DHIS2_DE_TB_STARTED_TREATMENT', ''),

        // Non-Communicable Disease
        'hypertension_new'     => env('DHIS2_DE_HYPERTENSION_NEW', ''),
        'hypertension_followup'=> env('DHIS2_DE_HYPERTENSION_FOLLOWUP', ''),
        'diabetes_new'         => env('DHIS2_DE_DIABETES_NEW', ''),
        'diabetes_followup'    => env('DHIS2_DE_DIABETES_FOLLOWUP', ''),

        // Maternal & Child Health
        'maternal_visits'      => env('DHIS2_DE_MATERNAL_VISITS', ''),
        'anc_first_visits'     => env('DHIS2_DE_ANC_FIRST_VISITS', ''),
        'deliveries_total'     => env('DHIS2_DE_DELIVERIES_TOTAL', ''),
        'deliveries_skilled'   => env('DHIS2_DE_DELIVERIES_SKILLED', ''),
        'postnatal_visits'     => env('DHIS2_DE_POSTNATAL_VISITS', ''),

        // Immunisation (EPI)
        'immunizations'        => env('DHIS2_DE_IMMUNIZATIONS', ''),
        'bcg_doses'            => env('DHIS2_DE_BCG_DOSES', ''),
        'penta3_doses'         => env('DHIS2_DE_PENTA3_DOSES', ''),
        'measles_doses'        => env('DHIS2_DE_MEASLES_DOSES', ''),

        // Laboratory
        'lab_tests_total'      => env('DHIS2_DE_LAB_TESTS_TOTAL', ''),
        'lab_malaria_rdt'      => env('DHIS2_DE_LAB_MALARIA_RDT', ''),

        // Pharmacy
        'prescriptions_total'  => env('DHIS2_DE_PRESCRIPTIONS_TOTAL', ''),
    ],
];
