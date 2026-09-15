<?php

// Platform KPI dashboard (imported from the platform, docs/PLATFORM_IMPORTS.md group C).
return [
    'nav'          => 'KPI dashboard',
    'trend_title'  => 'KPI trend',
    'daily_values' => 'Daily values — :from to :to',
    'export_csv'   => 'Download CSV',
    'export_hint'  => 'Platform-wide daily aggregates only (counts, rates, averages). The file contains no patient names or Health IDs.',
    'recomputed'   => 'KPI snapshots recomputed for :date.',

    'category_volume'     => 'Volume',
    'category_quality'    => 'Quality',
    'category_efficiency' => 'Efficiency',
    'category_financial'  => 'Financial',

    'metric_daily_active_patients'           => 'Daily active patients',
    'metric_daily_active_patients_desc'      => 'Unique patients with a visit on the day',
    'metric_daily_visits'                    => 'Daily visits',
    'metric_daily_visits_desc'               => 'Visits opened on the day',
    'metric_visit_completion_rate'           => 'Visit completion rate',
    'metric_visit_completion_rate_desc'      => 'Share of visits completed (not cancelled or abandoned)',
    'metric_avg_visit_duration_min'          => 'Average visit duration',
    'metric_avg_visit_duration_min_desc'     => 'Average minutes from visit start to completion',
    'metric_lab_turnaround_avg_min'          => 'Lab turnaround time',
    'metric_lab_turnaround_avg_min_desc'     => 'Average minutes from lab order to result',
    'metric_prescription_dispense_rate'      => 'Prescription dispense rate',
    'metric_prescription_dispense_rate_desc' => 'Share of prescriptions fully dispensed',
    'metric_appointment_no_show_rate'        => 'Appointment no-show rate',
    'metric_appointment_no_show_rate_desc'   => 'Share of booked appointments that ended as a no-show',
    'metric_daily_invoices'                  => 'Daily invoices',
    'metric_daily_invoices_desc'             => 'Invoices issued on the day',
    'metric_revenue_daily'                   => 'Daily revenue',
    'metric_revenue_daily_desc'              => 'Total payments received on the day',
    'metric_patient_registrations'           => 'Patient registrations',
    'metric_patient_registrations_desc'      => 'Patients verified by a facility on the day',
];
