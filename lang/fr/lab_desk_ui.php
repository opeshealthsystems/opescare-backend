<?php

/*
|--------------------------------------------------------------------------
| Bureau laboratoire / diagnostic — textes de la refonte du 2026-10-18
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/lab_desk_ui.php (scripts/i18n-audit.php).
*/

return [
    'welcome_back' => 'Bon retour,',
    'today'        => 'Aujourd’hui',
    'active'       => 'En cours',
    'view_all'     => 'Tout voir',

    'kpi_received'       => 'Échantillons reçus',
    'kpi_in_progress'    => 'Analyses en cours',
    'kpi_validated'      => 'Résultats validés',
    'kpi_past_tat'       => 'En attente hors délai',
    'past_tat_sub'       => 'Au-delà de :median',
    'past_tat_no_median' => 'Pas de médiane',

    'quick_actions' => 'Actions rapides',
    'qa_register'   => 'Enregistrer un échantillon',
    'qa_worklist'   => 'Liste de travail',
    'qa_enter'      => 'Saisir les résultats',
    'qa_validate'   => 'Valider les résultats',
    'qa_print'      => 'Imprimer le compte rendu',
    'qa_requests'   => 'Demandes des patients',
    'qa_catalogue'  => 'Catalogue des analyses',
    'qa_search'     => 'Rechercher un patient',

    'queue_title'      => 'File des échantillons',
    'tab_all'          => 'Tous',
    'queue_empty'      => 'Aucun échantillon en attente',
    'queue_empty_body' => 'Les demandes ouvertes apparaissent ici, les plus urgentes d’abord.',
    'queue_band_empty' => 'Aucun échantillon ouvert à cette priorité.',
    'col_time'         => 'Heure',
    'col_tests'        => 'Analyse(s)',
    'col_priority'     => 'Priorité',
    'col_status'       => 'Statut',

    'workload_title' => 'Charge du jour',
    'workload_total' => 'Analyses ouvertes',
    'workload_aria'  => ':total analyses ouvertes à la paillasse',
    'tat_median'     => 'Délai médian, :days derniers jours',
    'tat_none_short' => 'Données insuffisantes',
    'tat_measured'   => ':n demandes mesurées',

    'alerts_title'         => 'Alertes et notifications',
    'alert_critical'       => '{1} :n valeur critique en attente de rappel|[2,*] :n valeurs critiques en attente de rappel',
    'alert_critical_sub'   => 'Attention immédiate requise',
    'alert_past_tat'       => '{1} :n échantillon au-delà du délai habituel|[2,*] :n échantillons au-delà du délai habituel',
    'alert_past_tat_sub'   => 'Voir la liste de travail',
    'alert_requests'       => '{1} :n demande de patient en attente|[2,*] :n demandes de patients en attente',
    'alert_validation'     => '{1} :n résultat en attente de validation|[2,*] :n résultats en attente de validation',
    'alert_validation_sub' => 'Diffusé, en attente de la seconde signature',
    'alert_rejected'       => '{1} :n échantillon refusé aujourd’hui|[2,*] :n échantillons refusés aujourd’hui',
    'alerts_empty'         => 'Rien ne requiert votre attention',
    'alerts_empty_body'    => 'Valeurs critiques, retards et demandes s’afficheront ici.',

    'recent_title'      => 'Résultats récents',
    'recent_empty'      => 'Aucun résultat diffusé',
    'recent_empty_body' => 'Les bilans diffusés apparaissent ici, les plus récents d’abord.',

    'collection_queue' => 'En attente d’échantillon',
    'sign_off'         => 'Signature',
    'worklist_empty_body' => 'Les demandes apparaissent ici dès qu’un clinicien prescrit une analyse.',
    'open_collection'     => 'Ouvrir le prélèvement',
    'how_work_arrives'    => 'Comment le travail arrive',
];
