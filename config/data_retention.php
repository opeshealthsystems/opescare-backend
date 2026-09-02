<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Retention Policy (OpesCare)
    |--------------------------------------------------------------------------
    | Retention periods per Cameroon Law No. 2010/012 and HIPAA equivalents.
    | All durations are in the unit noted in the key name.
    */

    'audit_logs_days'      => env('RETENTION_AUDIT_LOGS_DAYS', 2555),   // 7 years
    'access_logs_days'     => env('RETENTION_ACCESS_LOGS_DAYS', 365),
    'api_usage_logs_days'  => env('RETENTION_API_USAGE_LOGS_DAYS', 180),
    'ussd_sessions_days'   => env('RETENTION_USSD_SESSIONS_DAYS', 30),
    'export_files_hours'   => env('RETENTION_EXPORT_FILES_HOURS', 24),
    'clinical_data_years'  => env('RETENTION_CLINICAL_DATA_YEARS', 10),  // per Cameroon Law 2010/012
];
