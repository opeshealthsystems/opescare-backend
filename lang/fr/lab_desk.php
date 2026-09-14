<?php

/*
|--------------------------------------------------------------------------
| Bureau du laboratoire (portail web, /facility/lab)
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/lab_desk.php (scripts/i18n-audit.php).
*/

return [

    'nav'            => 'Bureau du laboratoire',
    'nav_collection' => 'Prélèvements',
    'title'          => 'Bureau du laboratoire',

    // ── Liste de travail ─────────────────────────────────────────────────
    'worklist_title'    => 'Liste de travail du laboratoire',
    'worklist_subtitle' => 'Les demandes de cet établissement, les plus urgentes d’abord et les plus anciennes en premier à urgence égale.',
    'empty_worklist'    => 'Aucune demande dans cette colonne.',
    'status'            => [
        'ordered'     => 'En attente de prélèvement',
        'collected'   => 'Échantillon reçu',
        'in_progress' => 'En cours',
        'resulted'    => 'Publié',
        'validated'   => 'Validé',
        'cancelled'   => 'Clos',
    ],
    'urgency'           => [
        'stat'    => 'STAT',
        'urgent'  => 'Urgent',
        'routine' => 'Routine',
    ],
    'col_patient'     => 'Patient',
    'col_test'        => 'Examen',
    'col_urgency'     => 'Urgence',
    'col_ordered_by'  => 'Prescrit par',
    'col_ordered_at'  => 'Prescrit le',
    'col_results'     => 'Résultats',
    'col_open'        => 'Ouvrir',
    'open_order'      => 'Ouvrir',
    'health_id'       => 'Identifiant santé',
    'health_id_hint'  => 'Saisissez l’identifiant santé complet, exactement. Les patients ne peuvent pas être recherchés par nom.',
    'lookup'          => 'Rechercher',
    'clear'           => 'Effacer',
    'filtered_by'     => 'Demandes de :health_id uniquement.',

    // ── Prélèvements ─────────────────────────────────────────────────────
    'collection_title'    => 'Prélèvements',
    'collection_subtitle' => 'Demandes en attente d’un échantillon dans cet établissement, les plus anciennes d’abord.',
    'empty_collection'    => 'Aucun échantillon n’est attendu.',
    'specimen_type'       => 'Échantillon',
    'specimen_hint'       => 'Facultatif. Pré-rempli depuis le dictionnaire LOINC lorsque l’examen prescrit en nomme un ; sinon laissez vide plutôt que de deviner.',
    'specimen_unknown'    => 'Le dictionnaire ne nomme aucun échantillon pour cet examen.',
    'mark_collected'      => 'Marquer prélevé',
    'collected'           => 'Échantillon enregistré. La demande est passée à la paillasse.',
    'back_to_worklist'    => 'Retour à la liste de travail',

    // ── Page de la demande ───────────────────────────────────────────────
    'order_title'       => 'Demande d’analyse',
    'patient'           => 'Patient',
    'test'              => 'Examen',
    'test_code'         => 'Code local',
    'loinc'             => 'LOINC',
    'loinc_none'        => 'Aucune correspondance LOINC pour cet examen. Aucune n’est inscrite sur ses résultats.',
    'dictionary'        => 'Dictionnaire',
    'dictionary_specimen' => 'Échantillon',
    'dictionary_scale'    => 'Échelle',
    'dictionary_units'    => 'Unités usuelles',
    'indication'        => 'Indication clinique',
    'order_notes'       => 'Notes de la demande',
    'ordered_by'        => 'Prescrit par',
    'ordered_at'        => 'Prescrit le',
    'collected_at'      => 'Échantillon prélevé',
    'resulted_at'       => 'Publié le',
    'validated_by'      => 'Validé par',
    'not_yet'           => 'Pas encore',
    'unknown'           => 'Non renseigné',
    'age_years'         => ':years ans',

    'results_title'     => 'Résultats',
    'empty_results'     => 'Aucun résultat n’a été saisi sur cette demande.',
    'col_analyte'       => 'Analyte',
    'col_value'         => 'Valeur',
    'col_unit'          => 'Unité',
    'col_reference'     => 'Intervalle de référence',
    'col_flag'          => 'Indicateur',
    'col_entered_by'    => 'Saisi par',
    'col_resulted_at'   => 'Résultat le',
    'col_actions'       => 'Actions',
    'no_range'          => 'Aucun intervalle de référence — non comparé',
    'no_unit'           => 'Aucune unité enregistrée',
    'flag'              => [
        'critical'  => 'Critique',
        'high'      => 'Élevé',
        'low'       => 'Bas',
        'abnormal'  => 'Anormal',
        'normal'    => 'Normal',
        'unflagged' => 'Non comparé',
    ],
    'flag_source_published' => 'dérivé d’un intervalle de référence publié',
    'flag_source_range'     => 'dérivé de l’intervalle propre au laboratoire',
    'flag_source_lab'       => 'enregistré par le laboratoire',
    'superseded'            => 'Remplacé',
    'corrects'              => 'Correction',
    'correct'               => 'Corriger',
    'cancel'                => 'Annuler',

    // ── Valeurs critiques ────────────────────────────────────────────────
    'alert_title'          => 'Valeur critique',
    'alert_pending'        => 'En attente d’accusé de réception du clinicien',
    'alert_acknowledged'   => 'Accusé de réception par :name le :at',
    'alert_threshold'      => 'Seuil franchi : :threshold',
    'alert_note'           => 'Le clinicien prescripteur accuse réception d’une valeur critique depuis sa boîte de résultats. D’ici là, elle apparaît ici comme en attente.',

    // ── Saisie d’un résultat ─────────────────────────────────────────────
    'entry_title'        => 'Saisir un résultat',
    'entry_hint'         => 'Chaque saisie est une nouvelle ligne. Rien de déjà saisi n’est modifié.',
    'parameter_name'     => 'Analyte',
    'parameter_hint'     => 'Par défaut, l’examen prescrit.',
    'value'              => 'Valeur',
    'value_hint'         => 'Un nombre, ou un texte court tel que « Négatif ». Le texte est conservé tel quel et jamais comparé.',
    'unit'               => 'Unité',
    'unit_hint'          => 'Telle que rapportée par l’automate. Laissez vide s’il n’y en a pas — une unité n’est jamais remplie à votre place.',
    'unit_suggested'     => 'Unités usuelles du dictionnaire : :units',
    'notes'              => 'Notes',
    'range_preview'      => 'Intervalle publié pour cet examen et ce patient',
    'range_preview_none' => 'Aucun intervalle de référence publié ne correspond à cet examen pour ce patient. Les valeurs seront conservées sans indicateur.',
    'range_critical'     => 'Critique en dessous de :low / au-dessus de :high',
    'submit_result'      => 'Saisir le résultat',
    'result_entered'     => 'Résultat saisi.',

    'correct_title'      => 'Corriger ce résultat',
    'correct_hint'       => 'L’original reste au dossier. Une nouvelle ligne porte la valeur corrigée et désigne celle-ci. Sur une demande validée, le bilan repasse en « Publié » et doit être validé de nouveau.',
    'reason'             => 'Motif de la correction',
    'submit_correction'  => 'Enregistrer la correction',
    'result_corrected'   => 'Correction enregistrée. Le résultat d’origine est inchangé.',

    // ── Publication et validation ────────────────────────────────────────
    'release'            => 'Publier les résultats',
    'release_hint'       => 'Publie tous les résultats de cette demande au patient et au clinicien prescripteur. Cette action est irréversible.',
    'released'           => 'Résultats publiés.',
    'validate'           => 'Valider',
    'validate_hint'      => 'Une seconde personne confirme le bilan publié. Quiconque a saisi ou publié un résultat ne peut pas le valider.',
    'validated'          => 'Demande validée.',
    'validated_note'     => 'Cette demande est validée. Ses résultats ne peuvent qu’être corrigés, jamais modifiés.',
    'validation_locked'  => 'La validation revient à un validateur ou à un responsable de laboratoire.',

    // ── Refus ────────────────────────────────────────────────────────────
    'err' => [
        'order_not_found'     => 'Cette demande ne figure pas sur la liste de travail de ce laboratoire.',
        'result_not_found'    => 'Ce résultat n’appartient pas à cette demande.',
        'no_facility_context' => 'Cette session n’est liée à aucun établissement. Reconnectez-vous.',
        'validated_locked'    => 'Cette demande est validée. Corrigez un résultat au lieu d’en saisir un nouveau.',
        'not_released'        => 'Seule une demande publiée et non encore validée peut être validée.',
        'self_validation'     => 'Vous avez saisi ou publié un résultat sur cette demande et ne pouvez pas la valider. Une seconde personne doit le faire.',
        'order_closed'        => 'Cette demande est :status et ne peut plus être traitée.',
        'reason_required'     => 'Indiquez le motif de la correction.',
        'refused'             => 'Le laboratoire a refusé cette action.',
    ],
];
