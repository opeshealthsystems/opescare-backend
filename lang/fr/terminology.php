<?php

/**
 * Bureau de revue des terminologies cliniques (/admin/terminology).
 *
 * Espace de noms dédié afin d'ajouter ce bureau sans modifier un fichier de
 * traduction existant. EN et FR doivent rester strictement 1:1 —
 * scripts/i18n-audit.php est une barrière CI.
 *
 * VOLONTAIREMENT ABSENTS : les noms d'analytes, les libellés d'examens, les
 * libellés officiels LOINC, les intitulés CIM-10, les noms de systèmes de
 * codes et les unités. Ce sont des termes cliniques, pas du texte d'interface.
 * « Hemoglobin [Mass/volume] in Blood » est le libellé officiel du code 718-7
 * dans toutes les langues ; le traduire produirait une chaîne qui n'est plus
 * le libellé de ce code et serait fausse dans un message FHIR.
 */
return [

    // Communs
    'nav' => 'Terminologies cliniques',
    'nav_log' => 'Journal des applications',
    'breadcrumb_home' => 'Administration',
    'btn_filter' => 'Filtrer',
    'btn_clear' => 'Réinitialiser',
    'btn_open' => 'Examiner',
    'btn_back' => 'Retour',

    // File d'attente
    'index_title' => 'Terminologies cliniques',
    'index_subtitle' => 'Correspondances LOINC et CIM-10 proposées pour le vocabulaire de ce déploiement. Une correspondance reste inerte tant qu’une personne ne l’a pas approuvée, et l’approuver n’écrit encore rien dans un dossier : l’application est un acte distinct.',
    'kpi_pending' => 'En attente de revue',
    'kpi_approved' => 'Approuvées',
    'kpi_rejected' => 'Rejetées',
    'kpi_deprecated' => 'Obsolètes',
    'filter_status' => 'Statut',
    'filter_all_statuses' => 'Tous les statuts',
    'filter_system' => 'Système de codes',
    'filter_all_systems' => 'Tous les systèmes de codes',
    'empty_heading' => 'Aucune correspondance ne correspond à ce filtre',
    'empty_body' => 'Réinitialisez le filtre, ou exécutez le seeder de terminologie pour proposer des correspondances au vocabulaire utilisé.',

    // Colonnes
    'col_local_term' => 'Terme local',
    'col_resource_type' => 'S’applique à',
    'col_standard' => 'Code proposé',
    'col_confidence' => 'Confiance',
    'col_radius' => 'Dossiers concernés',
    'col_status' => 'Statut',
    'col_target' => 'Dossier cible',
    'col_row_unit' => 'Unité du dossier',
    'col_current_code' => 'Code déjà présent',
    'col_decision' => 'Deviendrait',
    'col_when' => 'Quand',
    'col_action' => 'Action',
    'col_actor' => 'Par',
    'col_detail' => 'Détail',
    'col_mapping' => 'Correspondance',
    'col_before' => 'Avant',
    'col_after' => 'Après',

    // Statuts
    'status_pending' => 'En attente de revue',
    'status_approved' => 'Approuvée',
    'status_rejected' => 'Rejetée',
    'status_deprecated' => 'Obsolète',

    // Types de ressource
    'resource_lab_result' => 'Analytes de résultats',
    'resource_lab_order' => 'Examens prescriptibles',
    'resource_condition' => 'Diagnostics',

    // Confiance
    'confidence_review' => 'Exige un jugement',
    'confidence_high' => 'Sans ambiguïté',

    // Portée
    'radius_write' => ':n seraient renseignés',
    'radius_conflict' => ':n portent déjà un code différent',
    'radius_unit_mismatch' => ':n ignorés pour cause d’unité',
    'radius_summary' => ':total dossier(s) correspondent à ce terme local. :write recevraient le code, :already le portent déjà, :conflict portent un code DIFFÉRENT et sont ignorés, :mismatch correspondent au nom mais pas à l’unité et ne sont jamais touchés.',
    'radius_none' => 'Aucun dossier de cette plateforme ne porte ce terme local : appliquer la correspondance n’écrirait rien.',

    // Décisions
    'decision_write' => 'Renseigné',
    'decision_recode' => 'Recodé',
    'decision_already' => 'Déjà codé',
    'decision_conflict' => 'Conflit — ignoré',
    'decision_unit_mismatch' => 'Unité différente — ignoré',

    // Panneaux de détail
    'show_title' => 'Revue de correspondance',
    'panel_mapping' => 'La correspondance proposée',
    'panel_radius' => 'Ce que l’application ferait',
    'panel_review' => 'Décision de revue',
    'panel_apply' => 'Appliquer aux dossiers',
    'panel_history' => 'Historique',
    'field_unit' => 'Unité',
    'field_unit_hint' => 'L’unité fait partie de la correspondance — le même analyte dans une autre unité est un autre code.',
    'field_unit_none' => 'Aucune unité — cette correspondance n’est pas appariée sur une unité.',
    'field_display' => 'Libellé standard',
    'field_reviewer' => 'Examinée par',
    'field_reviewer_none' => 'Personne pour l’instant',
    'field_reject_reason' => 'Motif du rejet',
    'field_reject_placeholder' => 'Pourquoi ce code n’est pas le bon pour ce terme.',
    'review_banner_heading' => 'Celle-ci exige un jugement, pas un simple aval.',
    'review_banner_body' => 'Un autre code défendable existe pour ce terme. Vérifiez le choix dans le navigateur LOINC avant d’approuver.',
    'malformed_banner' => 'Ce code échoue à la validation de son propre système de codes : il ne peut être ni approuvé ni appliqué. Un code faux est pire que pas de code.',
    'review_hint' => 'Approuver atteste que ce code est correct. Cela n’écrit rien dans le dossier d’un patient.',
    'apply_hint' => 'Ceci inscrit le code sur les dossiers listés ci-dessus. Cela ajoute un code et rien d’autre — aucune valeur, unité, plage de référence ni marqueur n’est modifié.',
    'confirm_recode' => 'Oui, remplacer le code différent déjà présent sur :n dossier(s). J’ai vérifié ce qui est remplacé.',
    'btn_approve' => 'Approuver cette correspondance',
    'btn_reject' => 'Rejeter cette correspondance',
    'btn_apply' => 'Appliquer le code à :n dossier(s)',
    'history_none' => 'Rien n’a encore été fait sur cette correspondance.',

    // Journal des applications
    'log_title' => 'Journal des applications de terminologie',
    'log_subtitle' => 'Chaque approbation, rejet et code inscrit par ce bureau, avec l’état de chaque dossier au préalable.',
    'log_empty_heading' => 'Rien d’appliqué pour l’instant',
    'log_empty_body' => 'Aucun code de terminologie n’a été approuvé, rejeté ou inscrit depuis ce bureau.',

    // Refus et messages
    'refuse_not_approved' => 'Cette correspondance n’a pas été approuvée : elle ne peut pas être appliquée. Approuvez-la d’abord.',
    'refuse_no_target' => 'Aucune colonne de cette plateforme ne reçoit ce type de correspondance : il n’y a rien à écrire. Les diagnostics portent déjà directement leur code CIM-10.',
    'refuse_wrong_system' => 'Les colonnes cibles ne contiennent que des codes LOINC. Un code d’un autre système n’y sera pas inscrit.',
    'refuse_malformed_code' => 'Ce code échoue à la validation de son propre système de codes et a été refusé au moment de l’écriture, et pas seulement lors de sa proposition.',
    'flash_not_pending' => 'Cette correspondance a déjà été tranchée ; elle n’est plus en attente de revue.',
    'flash_malformed' => 'Ce code échoue à la validation de son propre système de codes et ne peut pas être approuvé.',
    'flash_approved' => 'Correspondance approuvée. Rien n’a été écrit dans un dossier — utilisez « Appliquer » quand vous serez prêt.',
    'flash_rejected' => 'Correspondance rejetée, et le motif figure au dossier.',
    'flash_applied' => 'Appliqué : :applied renseignés, :recoded recodés. Ignorés : :conflict en conflit, :mismatch pour cause d’unité, :already portant déjà ce code.',

    // The code dictionary browser — the vocabularies themselves.
    'dict_title' => 'Dictionnaire des codes',
    'dict_subtitle' => 'Les nomenclatures publiées elles-mêmes — la CIM-10 de l\'OMS et LOINC — telles qu\'importées de leurs éditeurs. Ce sont des données de référence : elles ne sont pas modifiées ici, seulement remplacées en important une version plus récente.',
    'dict_nav_queue' => 'File de revue des correspondances',
    'dict_common_count' => ':count signalés comme couramment utilisés',
    'dict_never_imported' => 'Le dictionnaire est vide. Les importateurs n\'ont jamais été exécutés — ce n\'est pas la même chose qu\'une recherche sans résultat. Exécutez opescare:import-icd10 et opescare:import-loinc.',
    'dict_provenance' => 'Chargé depuis :',
    'dict_search_placeholder' => 'Code ou libellé — par ex. E11, choléra, hémoglobine',
    'dict_search' => 'Rechercher',
    'dict_all_systems' => 'Toutes les nomenclatures',
    'dict_only_common' => 'Couramment utilisés uniquement',
    'dict_show_headings' => 'Inclure les titres de chapitres et de blocs',
    'dict_col_code' => 'Code',
    'dict_col_system' => 'Nomenclature',
    'dict_col_display' => 'Libellé',
    'dict_col_basis' => 'Motif',
    'dict_heading' => 'Titre — non attribuable',
    'dict_chapter' => 'Chapitre :chapter',
    'dict_no_results' => 'Aucun code ne correspond à cette recherche.',
    // Le rapport de réconciliation — les données de la plateforme relues au
    // regard des nomenclatures. En lecture seule, et le texte doit le redire.
    'gaps_nav' => 'Écarts de terminologie',
    'gaps_title' => 'Écarts de terminologie',
    'gaps_subtitle' => 'Les codes, unités et voies d’administration déjà inscrits sur les dossiers de ce déploiement, relus au regard des nomenclatures publiées. Le rapport signale ce qui ne correspond plus à rien, ce qui est écrit de deux façons, et ce qui n’a jamais été adossé à une nomenclature.',
    'gaps_readonly_notice' => 'Ce rapport lit. Il ne modifie rien, et aucune commande de cette page ne le pourrait. Un dossier clinique est corrigé par une personne responsable qui l’amende — jamais par une correction en masse lancée depuis un rapport.',
    'gaps_vocabularies' => 'Comparé à :',
    'gaps_no_vocabularies' => 'Aucune nomenclature n’est chargée — rien de ce qui suit n’a pu être comparé à quoi que ce soit.',
    'gaps_expected' => 'Nomenclature attendue : :systems',
    'gaps_state_absent' => 'Absent de ce déploiement',
    'gaps_state_empty' => 'Vide',
    'gaps_state_unbacked' => 'Sans nomenclature',
    'gaps_state_clean' => 'Tout correspond',
    'gaps_state_review' => 'À trancher',
    'gaps_absent_body' => 'Cette table ou cette colonne n’existe pas ici ; il n’y a donc rien à vérifier. Ce n’est pas un constat d’absence de problème.',
    'gaps_empty_body' => ':rows lignes, dont aucune ne porte de valeur. Vide n’est pas synonyme de conforme : rien n’est codé ici.',
    'gaps_unbacked_body' => ':distinct valeurs distinctes sur :rows lignes, et aucune des nomenclatures attendues n’est chargée ici. Ce sont des libellés locaux qui tiennent lieu de codes.',
    'gaps_checked_body' => 'Comparé à :system — :resolved des :distinct valeurs distinctes correspondent, soit :resolved_rows lignes sur :rows.',
    'gaps_all_resolve' => 'Toutes les valeurs correspondent.',
    'gaps_col_value' => 'Valeur inscrite au dossier',
    'gaps_col_rows' => 'Lignes',
    'gaps_col_suggestions' => 'Correspondances possibles — simples suggestions',
    'gaps_heading_only' => 'Présent dans la nomenclature, mais seulement comme titre ou groupe — ce n’est pas un code attribuable.',
    'gaps_basis_display' => 'rapprochement par le libellé',
    'gaps_basis_code_shape' => 'code voisin',
    'gaps_padded' => '« :value » comporte une espace au début ou à la fin sur :rows lignes ; la valeur ne correspondra jamais exactement à un code.',
    'gaps_duplicates_heading' => 'Probablement la même chose, écrite de deux façons',
    'gaps_duplicates_caveat' => 'Probablement — et non certainement. Vérifiez chaque cas sur les dossiers d’origine avant de trancher : deux valeurs qui se ressemblent sont parfois réellement différentes.',
    'gaps_rule_case_punctuation' => 'identiques à la casse et à la ponctuation près',
    'gaps_rule_near_string' => 'un seul caractère d’écart',
    'gaps_rule_declared_synonym' => 'abréviation connue de la même voie',
    'gaps_footer' => 'Rien sur cette page n’a été modifié. Chaque ligne relève d’une décision humaine.',
];
