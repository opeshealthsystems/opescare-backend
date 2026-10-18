<?php

/*
|--------------------------------------------------------------------------
| Lab / diagnostic desk — 2026-10-18 redesign strings
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/lab_desk_ui.php (scripts/i18n-audit.php).
| "Pending past TAT" is measured against the laboratory's OWN median
| turnaround: there is no per-test TAT target in the system.
*/

return [
    'welcome_back' => 'Welcome back,',
    'today'        => 'Today',
    'active'       => 'Active',
    'view_all'     => 'View all',

    'kpi_received'       => 'Samples received',
    'kpi_in_progress'    => 'Tests in progress',
    'kpi_validated'      => 'Results validated',
    'kpi_past_tat'       => 'Pending past TAT',
    'past_tat_sub'       => 'Over :median',
    'past_tat_no_median' => 'No median yet',

    'quick_actions' => 'Quick actions',
    'qa_register'   => 'Register sample',
    'qa_worklist'   => 'View worklist',
    'qa_enter'      => 'Enter results',
    'qa_validate'   => 'Validate results',
    'qa_print'      => 'Print report',
    'qa_requests'   => 'Patient requests',
    'qa_catalogue'  => 'Test catalogue',
    'qa_search'     => 'Patient search',

    'queue_title'      => 'Sample queue',
    'tab_all'          => 'All',
    'queue_empty'      => 'No sample is waiting',
    'queue_empty_body' => 'Open orders appear here, most urgent first.',
    'queue_band_empty' => 'No open sample at this priority.',
    'col_time'         => 'Time',
    'col_tests'        => 'Test(s)',
    'col_priority'     => 'Priority',
    'col_status'       => 'Status',

    'workload_title' => 'Today’s workload',
    'workload_total' => 'Open tests',
    'workload_aria'  => ':total open tests on the bench',
    'tat_median'     => 'Median turnaround, last :days days',
    'tat_none_short' => 'Not enough data',
    'tat_measured'   => ':n orders measured',

    'alerts_title'         => 'Alerts & notifications',
    'alert_critical'       => '{1} :n critical value awaiting call-back|[2,*] :n critical values awaiting call-back',
    'alert_critical_sub'   => 'Requires immediate attention',
    'alert_past_tat'       => '{1} :n sample past the usual turnaround|[2,*] :n samples past the usual turnaround',
    'alert_past_tat_sub'   => 'Check the worklist for details',
    'alert_requests'       => '{1} :n patient request waiting|[2,*] :n patient requests waiting',
    'alert_validation'     => '{1} :n result awaiting validation|[2,*] :n results awaiting validation',
    'alert_validation_sub' => 'Released, waiting for the second signature',
    'alert_rejected'       => '{1} :n sample rejected today|[2,*] :n samples rejected today',
    'alerts_empty'         => 'Nothing needs attention',
    'alerts_empty_body'    => 'Critical values, delays and requests will show here.',

    'recent_title'      => 'Recent results',
    'recent_empty'      => 'No result released yet',
    'recent_empty_body' => 'Released panels appear here, newest first.',

    'collection_queue' => 'Waiting for a sample',
    'sign_off'         => 'Sign-off',
    'worklist_empty_body' => 'Open orders appear here as soon as a clinician requests a test.',
    'open_collection'     => 'Open sample collection',
    'how_work_arrives'    => 'How work arrives',
];
