<?php

// Mesures transmises par des partenaires : données envoyées par un système
// externe via FHIR R4. Toujours affichées à part des mesures de l'établissement.
return [
    'tab'               => 'Transmis par des partenaires',
    'title'             => 'Mesures transmises par des partenaires',
    'provenance_label'  => 'Transmis par un système partenaire',
    'separate_note'     => 'Ces mesures ont été envoyées par un autre système via l\'API d\'interopérabilité. Personne dans cet établissement ne les a mesurées ; elles ne font pas partie des constantes, résultats ou tendances de l\'établissement, et l\'intervalle et l\'interprétation du partenaire sont affichés tels qu\'envoyés.',
    'none'              => 'Aucun système partenaire n\'a transmis de mesure pour ce patient.',
    'summary_count'     => '{0} Aucune mesure transmise par des partenaires|{1} :count mesure transmise par des partenaires|[2,*] :count mesures transmises par des partenaires',
    'all'               => 'Toutes les mesures transmises par des partenaires',
    'badge_system'      => 'Système : :name',
    'badge_facility'    => 'Envoyé par : :name',
    'unknown_facility'  => 'Établissement inconnu',
    'col_reading'       => 'Mesure',
    'col_value'         => 'Valeur',
    'col_range'         => 'Intervalle / interprétation du partenaire',
    'col_taken'         => 'Prise le',
    'col_received'      => 'Reçue le',
    'col_source'        => 'Source',
    'types' => [
        'observation'       => 'Observation',
        'diagnostic_report' => 'Compte rendu',
        'immunization'      => 'Vaccination',
    ],
    'previous'          => 'Plus récentes',
    'next'              => 'Plus anciennes',
];
