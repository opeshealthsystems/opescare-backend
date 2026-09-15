<?php

// Tableau de bord des indicateurs de la plateforme (importé, docs/PLATFORM_IMPORTS.md groupe C).
return [
    'nav'          => 'Indicateurs clés',
    'trend_title'  => 'Évolution de l’indicateur',
    'daily_values' => 'Valeurs quotidiennes — du :from au :to',
    'export_csv'   => 'Télécharger le CSV',
    'export_hint'  => 'Agrégats quotidiens pour toute la plateforme uniquement (effectifs, taux, moyennes). Le fichier ne contient ni nom de patient ni Health ID.',
    'recomputed'   => 'Indicateurs recalculés pour le :date.',

    'category_volume'     => 'Volume',
    'category_quality'    => 'Qualité',
    'category_efficiency' => 'Efficacité',
    'category_financial'  => 'Finances',

    'metric_daily_active_patients'           => 'Patients actifs par jour',
    'metric_daily_active_patients_desc'      => 'Patients distincts ayant eu une visite dans la journée',
    'metric_daily_visits'                    => 'Visites par jour',
    'metric_daily_visits_desc'               => 'Visites ouvertes dans la journée',
    'metric_visit_completion_rate'           => 'Taux de visites terminées',
    'metric_visit_completion_rate_desc'      => 'Part des visites terminées (ni annulées ni abandonnées)',
    'metric_avg_visit_duration_min'          => 'Durée moyenne d’une visite',
    'metric_avg_visit_duration_min_desc'     => 'Minutes en moyenne entre le début et la fin d’une visite',
    'metric_lab_turnaround_avg_min'          => 'Délai de rendu des analyses',
    'metric_lab_turnaround_avg_min_desc'     => 'Minutes en moyenne entre la demande d’analyse et le résultat',
    'metric_prescription_dispense_rate'      => 'Taux de délivrance des ordonnances',
    'metric_prescription_dispense_rate_desc' => 'Part des ordonnances entièrement délivrées',
    'metric_appointment_no_show_rate'        => 'Taux de rendez-vous non honorés',
    'metric_appointment_no_show_rate_desc'   => 'Part des rendez-vous réservés où le patient ne s’est pas présenté',
    'metric_daily_invoices'                  => 'Factures par jour',
    'metric_daily_invoices_desc'             => 'Factures émises dans la journée',
    'metric_revenue_daily'                   => 'Recettes par jour',
    'metric_revenue_daily_desc'              => 'Total des paiements reçus dans la journée',
    'metric_patient_registrations'           => 'Enregistrements de patients',
    'metric_patient_registrations_desc'      => 'Patients vérifiés par un établissement dans la journée',
];
