<?php

/*
|--------------------------------------------------------------------------
| Bureau de triage
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/triage.php (scripts/i18n-audit.php).
|
| Les chaines `no_bands.*` et `advisory_*` sont les plus critiques : c'est ce
| que l'infirmiere lit lorsque la plateforme ne dispose d'aucune norme valable
| pour le patient devant elle. Elles doivent le dire clairement.
*/

return [

    'nav'   => 'Triage',
    'title' => 'Triage',

    // ── File d'attente ────────────────────────────────────────────────────
    'queue_title'      => 'File de triage',
    'queue_subtitle'   => 'Patients évalués dans cet établissement, du plus récent au plus ancien.',
    'empty_queue'      => 'Aucune évaluation de triage n’a encore été enregistrée dans cet établissement.',
    'new_triage'       => 'Nouveau triage',
    'back_to_queue'    => 'Retour à la file',
    'col_patient'      => 'Patient',
    'col_complaint'    => 'Motif de consultation',
    'col_priority'     => 'Priorité',
    'col_assessed_by'  => 'Évalué par',
    'col_assessed_at'  => 'Évalué le',
    'col_open'         => 'Ouvrir',
    'open_record'      => 'Ouvrir',

    // ── Identification du patient ─────────────────────────────────────────
    'find_patient'    => 'Rechercher le patient',
    'health_id'       => 'Identifiant santé',
    'health_id_hint'  => 'Saisissez l’identifiant santé complet, exactement. La recherche par nom n’est pas possible.',
    'lookup'          => 'Rechercher',
    'not_found'       => 'Aucun patient ne porte cet identifiant santé.',
    'cancel'          => 'Annuler',
    'patient'         => 'Patient',
    'change_patient'  => 'Changer de patient',
    'age_years'       => ':years ans',
    'age_unknown'     => 'Date de naissance non enregistrée',

    // ── L’evaluation ──────────────────────────────────────────────────────
    'create_title'          => 'Enregistrer une évaluation de triage',
    'assessment'            => 'Évaluation',
    'presenting_complaint'  => 'Motif de consultation',
    'complaint_hint'        => 'Dans les mots du patient lorsque c’est possible.',
    'pain_score'            => 'Score de douleur (0–10)',
    'pain_hint'             => 'Tel que le patient le rapporte. Laisser vide si la question n’a pas été posée.',
    'pregnancy_status'      => 'Statut de grossesse',
    'pregnancy'             => [
        'not_pregnant'   => 'Non enceinte',
        'pregnant'       => 'Enceinte',
        'unknown'        => 'Inconnu',
        'not_applicable' => 'Sans objet',
    ],
    'pregnancy_hint'        => 'Enregistré car les normes de signes vitaux de cette plateforme ne sont pas valables pendant la grossesse.',
    'submit'                => 'Enregistrer le triage',
    'recorded'              => 'Évaluation de triage enregistrée.',

    // ── Observations ──────────────────────────────────────────────────────
    'vitals'              => 'Observations',
    'vitals_hint'         => 'Uniquement ce qui a été mesuré ici, maintenant. Laissez un champ vide s’il n’a pas été pris — vide signifie « non mesuré », jamais « inchangé ».',
    'temperature'         => 'Température (°C)',
    'pulse_rate'          => 'Pouls (bpm)',
    'respiratory_rate'    => 'Fréquence respiratoire (/min)',
    'systolic_bp'         => 'TA systolique (mmHg)',
    'diastolic_bp'        => 'TA diastolique (mmHg)',
    'oxygen_saturation'   => 'Saturation en oxygène (%)',
    'weight_kg'           => 'Poids (kg)',
    'height_cm'           => 'Taille (cm)',
    'gcs_score'           => 'Glasgow (3–15)',
    'consciousness_level' => 'Conscience (AVPU)',
    'consciousness'       => [
        'alert'        => 'Alerte',
        'voice'        => 'Réagit à la voix',
        'pain'         => 'Réagit à la douleur',
        'unresponsive' => 'Ne réagit pas',
    ],
    'no_vitals'      => 'Aucune observation n’a été enregistrée avec cette évaluation.',
    'recorded_by'    => 'Enregistré par',
    'recorded_at'    => 'Enregistré le',
    'not_measured'   => 'Non mesuré',

    // ── Acuite ────────────────────────────────────────────────────────────
    'acuity'         => 'Acuité',
    'acuity_title'   => 'Définir l’acuité',
    'acuity_hint'    => 'C’est vous qui la définissez. Cette plateforme ne calcule aucune priorité de triage et ne propose aucun niveau — les observations ci-dessous sont indicatives uniquement.',
    'acuity_choose'  => 'Choisir une priorité',
    'acuity_levels'  => [
        'P1_immediate'   => 'P1 — Immédiat',
        'P2_urgent'      => 'P2 — Urgent',
        'P3_less_urgent' => 'P3 — Moins urgent',
        'P4_standard'    => 'P4 — Standard',
        'P5_non_urgent'  => 'P5 — Non urgent',
    ],
    // La cle de vocabulaire visee par App\Support\TriageAcuity::labelKey().
    'acuity_legacy'  => 'Enregistré avant l’existence de ce bureau (:value)',
    'acuity_none'    => 'Aucune priorité enregistrée',
    'current_priority' => 'Priorité actuelle',
    'assigned_by_nurse' => 'Attribuée par un soignant',
    'score_history'  => 'Historique des priorités',
    'scoring_manual' => 'Définie manuellement',

    // ── Le panneau des indicateurs ────────────────────────────────────────
    'advisory_title'   => 'Indicateurs consultatifs',
    'advisory_note'    => 'Dérivés des normes adultes publiées (NEWS2, 16 ans et plus). Consultatifs uniquement : cela signifie « une personne doit regarder ceci », jamais « ce patient va bien ». Ce n’est pas une priorité de triage et rien ici n’en a attribue une.',
    'advisory_source'  => 'Normes adultes · consultatif',
    'advisory_none'    => 'Aucune observation ne sort de sa norme adulte. Cela n’affirme pas que le patient va bien.',

    'no_bands_title' => 'Aucune interprétation des signes vitaux n’est disponible pour ce patient',
    'no_bands'       => [
        'paediatric'  => 'Ce patient a moins de 16 ans. Les seules normes de signes vitaux de cette plateforme sont des normes adultes (NEWS2, 16 ans et plus), et les valeurs pédiatriques varient selon l’âge — une fréquence respiratoire normale chez l’enfant est une urgence chez l’adulte. Appliquer des normes adultes à un enfant sous-trie systématiquement les enfants malades : aucune interprétation et aucune acuité suggérée ne sont affichees. Évaluez ce patient cliniquement.',
        'age_unknown' => 'Ce patient n’a pas de date de naissance enregistrée ; son âge ne peut donc pas être etabli. Les normes de signes vitaux de cette plateforme ne sont valables qu’à partir de 16 ans, et elle ne supposera pas qu’un patient inconnu est adulte. Aucune interprétation et aucune acuité suggérée ne sont affichees. Évaluez ce patient cliniquement.',
        'pregnancy'   => 'Ce patient est enregistré comme enceinte. Les normes de signes vitaux de cette plateforme (NEWS2) ne sont explicitement pas validees pendant la grossesse : aucune interprétation et aucune acuité suggérée ne sont affichees. Évaluez cette patiente cliniquement.',
    ],

    // ── Relevés du patient ────────────────────────────────────────────────
    'self_reported_title' => 'Les relevés du patient',
    'self_reported_note'  => 'Enregistrés par le patient à domicile, avec son propre matériel. NON vérifiés, NON cliniques, et volontairement ni copiés dans les observations ci-dessus ni utilisés pour un quelconque indicateur. Affichés à titre de contexte seulement.',
    'self_reported_badge' => 'Déclaré par le patient · non vérifié',
    'self_reported_empty' => 'Ce patient n’a enregistré aucun relevé personnel.',
    'measured_at'         => 'Mesuré le',

    // ── Reevaluation ──────────────────────────────────────────────────────
    'show_title'        => 'Évaluation de triage',
    'reassess_title'    => 'Réévaluer',
    'reassess_hint'     => 'Un patient en attente dont l’état a change est retrié, jamais modifié. L’évaluation initiale et ses observations restent exactement telles qu’elles ont été prises.',
    'new_priority'      => 'Nouvelle priorité',
    'reason_for_change' => 'Motif du changement',
    'reason_hint'       => 'Obligatoire. Ce qui a changé, et ce que vous avez observé.',
    'retake_vitals'     => 'Observations reprises maintenant (facultatif)',
    'reassess_submit'   => 'Enregistrer la réévaluation',
    'reassessed'        => 'Réévaluation enregistrée.',
    'history_title'     => 'Historique des réévaluations',
    'no_reassessments'  => 'Ce patient n’a pas été réévalué.',
    'from_to'           => ':from → :to',
    'escalated'         => 'Escalade',
    'de_escalated'      => 'Désescalade',
    'unchanged'         => 'Priorité inchangée',
    'reassessed_by'     => 'Réévalué par',
    'reassessed_at'     => 'Réévalué le',
    'vitals_retaken'    => 'Observations reprises',

    // ── Refus ─────────────────────────────────────────────────────────────
    'no_facility_context' => 'Sélectionnez un établissement avant d’ouvrir le bureau de triage.',
];
