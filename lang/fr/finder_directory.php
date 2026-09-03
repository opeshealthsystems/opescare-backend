<?php

/**
 * Annuaire public Care Map + profil d'établissement — miroir 1:1 de
 * lang/en/finder_directory.php (contrôlé par scripts/i18n-audit.php).
 *
 * Même contrat de véracité que la version anglaise : rien ne décrit l'annuaire
 * comme vérifié, et la disponibilité est toujours « signalée », jamais un fait.
 */
return [

    // ── Habillage de page ───────────────────────────────────────────────
    'meta_title'       => 'Care Map — Trouvez des services de santé près de chez vous | OpesCare',
    'meta_description' => 'Trouvez des hôpitaux, cliniques, pharmacies et laboratoires près de chez vous, répertoriés à partir du registre national des établissements de santé du Cameroun et de données cartographiques ouvertes.',
    'brand_sub'        => 'Care Map',
    'emergency'        => 'Urgences',
    'back_to_directory' => "Retour à l'annuaire",

    // ── Recherche et filtres ────────────────────────────────────────────
    'search_label'       => 'Rechercher des établissements de santé',
    'search_placeholder' => 'Rechercher par nom, ville ou type…',
    'search_button'      => 'Rechercher',
    'near_me'            => 'Près de moi',
    'filter_by_type'     => "Filtrer par type d'établissement",
    'filter_by_region'   => 'Filtrer par région',
    'all_types'          => 'Tous les établissements',
    'all_regions'        => 'Toutes les régions',
    'clear_filters'      => 'Effacer les filtres',

    // ── Résultats et pagination ─────────────────────────────────────────
    'results_count' => '{0}Aucun établissement trouvé|{1}:count établissement trouvé|[2,*]:count établissements trouvés',
    'page_of'       => 'Page :current sur :last',
    'previous'      => 'Précédent',
    'next'          => 'Suivant',
    'distance_km'   => ':km km',
    'no_results_title' => 'Aucun établissement trouvé',
    'no_results_sub'   => "Essayez un autre terme de recherche, ou effacez les filtres pour parcourir tout l'annuaire.",

    // ── Carte d'établissement / badges ──────────────────────────────────
    'open_24h'            => 'Ouvert 24h/24',
    'open_now'            => 'Ouvert actuellement',
    'closed_now'          => 'Fermé actuellement',
    'license_verified'    => 'Licence vérifiée',
    'government_verified' => 'Vérifié par le gouvernement',
    'call'                => 'Appeler',
    'directions'          => 'Itinéraire',
    'view_profile'        => 'Voir le profil',
    'book_appointment'    => 'Prendre rendez-vous',

    // ── Carte ───────────────────────────────────────────────────────────
    'show_map'      => 'Afficher la carte',
    'hide_map'      => 'Masquer la carte',
    'map_label'     => 'Carte des établissements de cette page',
    'map_page_note' => 'La carte affiche les :count établissements de cette page.',
    'locate_me'     => 'Utiliser ma position',
    'you_are_here'  => 'Vous êtes ici',
    'geo_unsupported' => 'Votre navigateur ne prend pas en charge la géolocalisation. Vous pouvez tout de même rechercher par ville ou par région.',
    'geo_denied'      => "Impossible de lire votre position. Vérifiez que l'autorisation de localisation est activée, ou recherchez plutôt par ville.",

    // ── Véracité / provenance ───────────────────────────────────────────
    'disclaimer'          => "Les informations peuvent changer. Confirmez toujours par téléphone auprès de l'établissement avant de vous déplacer, surtout en cas d'urgence.",
    'provenance_heading'  => 'À propos de cette fiche',
    'provenance_registry' => 'Cette fiche provient du registre national des établissements de santé du Cameroun.',
    'provenance_source'   => 'Source de la fiche : :source',
    'no_availability'     => "Cet établissement n'a signalé aucune disponibilité en temps réel. Une section vide signifie « non signalé » — pas « non disponible ».",
    'reported_time'       => 'Signalé :time',
    'not_dated'           => 'Aucune date de signalement — à considérer comme obsolète',

    // ── Sections du profil ──────────────────────────────────────────────
    'about_heading'        => 'À propos',
    'contact_heading'      => 'Contact et localisation',
    'hours_heading'        => "Heures d'ouverture",
    'services_heading'     => 'Services',
    'availability_heading' => 'Disponibilité signalée',
    'pharmacy_stock_heading' => 'Stock de médicaments',
    'blood_heading'        => 'Disponibilité de sang',
    'lab_tests_heading'    => 'Analyses de laboratoire',
    'insurance_heading'    => 'Assurances acceptées',
    'address_label'        => 'Adresse',
    'phone_label'          => 'Téléphone',
    'email_label'          => 'E-mail',
    'website_label'        => 'Site web',
    'region_label'         => 'Région',
    'appointment_required' => 'Rendez-vous requis',
    'requires_doctor_order' => "Nécessite une prescription d'un clinicien",
    'turnaround_label'     => 'Délai : :time',
    'closed_day'           => 'Fermé',
    'booking_hint'         => 'Cet établissement accepte les demandes de rendez-vous via OpesCare.',
    'claim_question'       => 'Vous travaillez dans cet établissement ?',
    'claim_cta'            => 'Revendiquer cette fiche',
    'services_more'        => '+:count autres',

    // ── Statuts de disponibilité (affichés uniquement pour les lignes signalées) ──
    'statuses' => [
        'in_stock'     => 'Signalé en stock',
        'low_stock'    => 'Signalé stock faible',
        'out_of_stock' => 'Signalé en rupture',
        'available'    => 'Signalé disponible',
        'limited'      => 'Signalé limité',
        'unavailable'  => 'Signalé indisponible',
        'unknown'      => 'Non signalé',
    ],

    // ── Types d'établissement ───────────────────────────────────────────
    'types' => [
        'hospital'          => 'Hôpital',
        'clinic'            => 'Clinique',
        'pharmacy'          => 'Pharmacie',
        'health_center'     => 'Centre de santé',
        'laboratory'        => 'Laboratoire',
        'dental'            => 'Cabinet dentaire',
        'imaging_center'    => "Centre d'imagerie",
        'eye_clinic'        => 'Clinique ophtalmologique',
        'specialist'        => 'Spécialiste',
        'diagnostic_center' => 'Centre de diagnostic',
        'maternity'         => 'Maternité',
        'dispensary'        => 'Dispensaire',
        'blood_bank'        => 'Banque de sang',
        'other'             => 'Établissement',
    ],

    // ── Jours de la semaine (0 = dimanche, comme care_facility_hours) ───
    'days' => [
        0 => 'Dimanche',
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ],
];
