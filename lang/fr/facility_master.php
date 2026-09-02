<?php

/**
 * Chaînes console pour `facilities:import-master`.
 *
 * Un espace de noms distinct, afin que l'import de la liste nationale de
 * référence n'entre en collision avec aucun autre fichier de traduction. Les
 * motifs de revue (`uncertain_match`, `generic_name`, …) ne sont volontairement
 * PAS traduits ici : ce sont les identifiants déjà stockés dans
 * `facility_import_reviews.reason` et partagés avec l'import OpenStreetMap ; les
 * renommer par locale scinderait la file d'attente.
 */
return [
    'title'              => 'Liste nationale de référence des établissements du Cameroun',
    'dry_run_notice'     => 'SIMULATION — rien ne sera écrit. Relancez avec --apply pour appliquer.',
    'file_missing'       => 'Jeu de données introuvable à l\'emplacement :path.',
    'file_invalid'       => 'Le jeu de données :path n\'est pas un JSON valide, ou ne contient pas de tableau « facilities ».',
    'loaded'             => ':count établissements chargés depuis :path (extraction du :retrieved).',
    'attribution'        => 'Attribution inscrite sur chaque ligne : :attribution',
    'unverified_notice'  => 'Rien ici n\'est vérifié — les colonnes de vérification du classeur sont vides sur toutes les lignes, chaque établissement reste donc non vérifié.',
    'unknown_region'     => 'Aucun enregistrement pour la région « :region ». Régions présentes dans ce jeu de données : :regions',
    'limit_applied'      => '--limit appliqué : traitement des :count premiers enregistrements.',
    'region_filter'      => '--region appliqué : :count enregistrements sur :total se trouvent dans :region.',

    'records_considered' => 'Enregistrements examinés',
    'inserted'           => 'Ajoutés comme nouveaux établissements',
    'updated'            => 'Établissements existants enrichis',
    'unchanged'          => 'Correspondance trouvée, rien à ajouter',
    'protected'          => 'Ignorés — l\'établissement gère ses propres données',
    'review'             => 'En attente de revue humaine',
    'unmapped'           => 'Ignorés — type d\'établissement non reconnu',
    'no_coords'          => 'Ignorés — sans coordonnées',

    'review_heading'     => 'En attente de revue, par motif',
    'fields_heading'     => 'Champs renseignés sur les établissements existants',
    'region_heading'     => 'Par région',
    'region_unknown'     => '(sans région)',

    'column_region'      => 'Région',
    'column_inserted'    => 'Ajout',
    'column_updated'     => 'Enrichi',
    'column_review'      => 'Revue',
    'column_unchanged'   => 'Inchangé',
    'column_protected'   => 'Protégé',

    'dry_run_complete'   => 'Simulation terminée — aucune ligne écrite. Relancez avec --apply pour appliquer.',
    'applied'            => 'Import appliqué.',
    'review_pending'     => ':count candidat(s) figurent dans facility_import_reviews avec le statut « pending ». Rien n\'a été fusionné ni inséré pour eux.',
];
