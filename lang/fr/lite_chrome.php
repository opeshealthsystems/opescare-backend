<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpesCare Lite — Habillage partagé (mise en page, navigation, statut) — Français
    |--------------------------------------------------------------------------
    */

    // Étiquettes de la navigation latérale
    'nav_dashboard'    => 'Tableau de bord',
    'nav_lookup'       => 'Recherche d\'identifiant santé',
    'nav_checkin'      => 'Enregistrement',
    'nav_consultation' => 'Consultation',
    'nav_billing'      => 'Facturation',
    'nav_admin'        => 'Administration',
    'nav_devices'      => 'Appareils',
    'nav_conflicts'    => 'Conflits',
    'nav_full_portal'  => 'Portail complet',

    // Indicateur de statut en ligne/hors ligne
    'status_online'  => 'En ligne — Synchronisé',
    'status_offline' => 'Hors ligne — Les modifications seront synchronisées à la reconnexion',

    // Étiquettes de la navigation inférieure
    'bottom_home'    => 'Accueil',
    'bottom_lookup'  => 'Recherche',
    'bottom_checkin' => 'Enregistrement',
    'bottom_consult' => 'Consultation',
    'bottom_admin'   => 'Administration',

    // Vue Appareils
    'devices_empty'        => 'Aucun appareil Lite enregistré pour l\'instant. Les appareils s\'enregistrent via le point de terminaison API',
    'devices_modules'      => 'modules',
    'devices_never'        => 'Jamais',
    'devices_confirm_activate' => 'Activer cet appareil ?',
    'devices_confirm_revoke'   => 'Révoquer cet appareil ? Cette action est irréversible.',
    'devices_revoke_reason'    => 'Révoqué via le portail Lite.',

    // Vue Conflits
    'conflicts_resolve_note' => 'Résolu via le portail Lite.',

    // Messages flash du contrôleur
    'flash_patient_registered' => 'Patient :name enregistré. Identifiant santé : :health_id',
    'flash_device_activated'   => "Appareil ':name' activé.",
    'flash_device_revoked'     => "Appareil ':name' révoqué.",
];
