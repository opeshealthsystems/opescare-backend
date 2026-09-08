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
    'queue_subtitle'   => 'Patients evalues dans cet etablissement, du plus recent au plus ancien.',
    'empty_queue'      => 'Aucune evaluation de triage n’a encore ete enregistree dans cet etablissement.',
    'new_triage'       => 'Nouveau triage',
    'back_to_queue'    => 'Retour a la file',
    'col_patient'      => 'Patient',
    'col_complaint'    => 'Motif de consultation',
    'col_priority'     => 'Priorite',
    'col_assessed_by'  => 'Evalue par',
    'col_assessed_at'  => 'Evalue le',
    'col_open'         => 'Ouvrir',
    'open_record'      => 'Ouvrir',

    // ── Identification du patient ─────────────────────────────────────────
    'find_patient'    => 'Rechercher le patient',
    'health_id'       => 'Identifiant sante',
    'health_id_hint'  => 'Saisissez l’identifiant sante complet, exactement. La recherche par nom n’est pas possible.',
    'lookup'          => 'Rechercher',
    'not_found'       => 'Aucun patient ne porte cet identifiant sante.',
    'cancel'          => 'Annuler',
    'patient'         => 'Patient',
    'change_patient'  => 'Changer de patient',
    'age_years'       => ':years ans',
    'age_unknown'     => 'Date de naissance non enregistree',

    // ── L’evaluation ──────────────────────────────────────────────────────
    'create_title'          => 'Enregistrer une evaluation de triage',
    'assessment'            => 'Evaluation',
    'presenting_complaint'  => 'Motif de consultation',
    'complaint_hint'        => 'Dans les mots du patient lorsque c’est possible.',
    'pain_score'            => 'Score de douleur (0–10)',
    'pain_hint'             => 'Tel que le patient le rapporte. Laisser vide si la question n’a pas ete posee.',
    'pregnancy_status'      => 'Statut de grossesse',
    'pregnancy'             => [
        'not_pregnant'   => 'Non enceinte',
        'pregnant'       => 'Enceinte',
        'unknown'        => 'Inconnu',
        'not_applicable' => 'Sans objet',
    ],
    'pregnancy_hint'        => 'Enregistre car les normes de signes vitaux de cette plateforme ne sont pas valables pendant la grossesse.',
    'submit'                => 'Enregistrer le triage',
    'recorded'              => 'Evaluation de triage enregistree.',

    // ── Observations ──────────────────────────────────────────────────────
    'vitals'              => 'Observations',
    'vitals_hint'         => 'Uniquement ce qui a ete mesure ici, maintenant. Laissez un champ vide s’il n’a pas ete pris — vide signifie « non mesure », jamais « inchange ».',
    'temperature'         => 'Temperature (°C)',
    'pulse_rate'          => 'Pouls (bpm)',
    'respiratory_rate'    => 'Frequence respiratoire (/min)',
    'systolic_bp'         => 'TA systolique (mmHg)',
    'diastolic_bp'        => 'TA diastolique (mmHg)',
    'oxygen_saturation'   => 'Saturation en oxygene (%)',
    'weight_kg'           => 'Poids (kg)',
    'height_cm'           => 'Taille (cm)',
    'gcs_score'           => 'Glasgow (3–15)',
    'consciousness_level' => 'Conscience (AVPU)',
    'consciousness'       => [
        'alert'        => 'Alerte',
        'voice'        => 'Reagit a la voix',
        'pain'         => 'Reagit a la douleur',
        'unresponsive' => 'Ne reagit pas',
    ],
    'no_vitals'      => 'Aucune observation n’a ete enregistree avec cette evaluation.',
    'recorded_by'    => 'Enregistre par',
    'recorded_at'    => 'Enregistre le',
    'not_measured'   => 'Non mesure',

    // ── Acuite ────────────────────────────────────────────────────────────
    'acuity'         => 'Acuite',
    'acuity_title'   => 'Definir l’acuite',
    'acuity_hint'    => 'C’est vous qui la definissez. Cette plateforme ne calcule aucune priorite de triage et ne propose aucun niveau — les observations ci-dessous sont indicatives uniquement.',
    'acuity_choose'  => 'Choisir une priorite',
    'acuity_levels'  => [
        'P1_immediate'   => 'P1 — Immediat',
        'P2_urgent'      => 'P2 — Urgent',
        'P3_less_urgent' => 'P3 — Moins urgent',
        'P4_standard'    => 'P4 — Standard',
        'P5_non_urgent'  => 'P5 — Non urgent',
    ],
    // La cle de vocabulaire visee par App\Support\TriageAcuity::labelKey().
    'acuity_legacy'  => 'Enregistre avant l’existence de ce bureau (:value)',
    'acuity_none'    => 'Aucune priorite enregistree',
    'current_priority' => 'Priorite actuelle',
    'assigned_by_nurse' => 'Attribuee par un soignant',
    'score_history'  => 'Historique des priorites',
    'scoring_manual' => 'Definie manuellement',

    // ── Le panneau des indicateurs ────────────────────────────────────────
    'advisory_title'   => 'Indicateurs consultatifs',
    'advisory_note'    => 'Derives des normes adultes publiees (NEWS2, 16 ans et plus). Consultatifs uniquement : cela signifie « une personne doit regarder ceci », jamais « ce patient va bien ». Ce n’est pas une priorite de triage et rien ici n’en a attribue une.',
    'advisory_source'  => 'Normes adultes · consultatif',
    'advisory_none'    => 'Aucune observation ne sort de sa norme adulte. Cela n’affirme pas que le patient va bien.',

    'no_bands_title' => 'Aucune interpretation des signes vitaux n’est disponible pour ce patient',
    'no_bands'       => [
        'paediatric'  => 'Ce patient a moins de 16 ans. Les seules normes de signes vitaux de cette plateforme sont des normes adultes (NEWS2, 16 ans et plus), et les valeurs pediatriques varient selon l’age — une frequence respiratoire normale chez l’enfant est une urgence chez l’adulte. Appliquer des normes adultes a un enfant sous-trie systematiquement les enfants malades : aucune interpretation et aucune acuite suggeree ne sont affichees. Evaluez ce patient cliniquement.',
        'age_unknown' => 'Ce patient n’a pas de date de naissance enregistree ; son age ne peut donc pas etre etabli. Les normes de signes vitaux de cette plateforme ne sont valables qu’a partir de 16 ans, et elle ne supposera pas qu’un patient inconnu est adulte. Aucune interpretation et aucune acuite suggeree ne sont affichees. Evaluez ce patient cliniquement.',
        'pregnancy'   => 'Ce patient est enregistre comme enceinte. Les normes de signes vitaux de cette plateforme (NEWS2) ne sont explicitement pas validees pendant la grossesse : aucune interpretation et aucune acuite suggeree ne sont affichees. Evaluez cette patiente cliniquement.',
    ],

    // ── Relevés du patient ────────────────────────────────────────────────
    'self_reported_title' => 'Les relevés du patient',
    'self_reported_note'  => 'Enregistres par le patient a domicile, avec son propre materiel. NON verifies, NON cliniques, et volontairement ni copies dans les observations ci-dessus ni utilises pour un quelconque indicateur. Affiches a titre de contexte seulement.',
    'self_reported_badge' => 'Declare par le patient · non verifie',
    'self_reported_empty' => 'Ce patient n’a enregistre aucun releve personnel.',
    'measured_at'         => 'Mesure le',

    // ── Reevaluation ──────────────────────────────────────────────────────
    'show_title'        => 'Evaluation de triage',
    'reassess_title'    => 'Reevaluer',
    'reassess_hint'     => 'Un patient en attente dont l’etat a change est retrie, jamais modifie. L’evaluation initiale et ses observations restent exactement telles qu’elles ont ete prises.',
    'new_priority'      => 'Nouvelle priorite',
    'reason_for_change' => 'Motif du changement',
    'reason_hint'       => 'Obligatoire. Ce qui a change, et ce que vous avez observe.',
    'retake_vitals'     => 'Observations reprises maintenant (facultatif)',
    'reassess_submit'   => 'Enregistrer la reevaluation',
    'reassessed'        => 'Reevaluation enregistree.',
    'history_title'     => 'Historique des reevaluations',
    'no_reassessments'  => 'Ce patient n’a pas ete reevalue.',
    'from_to'           => ':from → :to',
    'escalated'         => 'Escalade',
    'de_escalated'      => 'Desescalade',
    'unchanged'         => 'Priorite inchangee',
    'reassessed_by'     => 'Reevalue par',
    'reassessed_at'     => 'Reevalue le',
    'vitals_retaken'    => 'Observations reprises',

    // ── Refus ─────────────────────────────────────────────────────────────
    'no_facility_context' => 'Selectionnez un etablissement avant d’ouvrir le bureau de triage.',
];
