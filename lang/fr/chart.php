<?php

/*
|--------------------------------------------------------------------------
| Dossier patient (portail web)
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/chart.php (scripts/i18n-audit.php).
*/

return [

    'nav'   => 'Dossier patient',
    'title' => 'Dossier patient',

    // ── Recherche ─────────────────────────────────────────────────────────
    'lookup_title'   => 'Ouvrir un dossier patient',
    'lookup_subtitle' => 'Saisissez l’identifiant santé complet du patient devant vous.',
    'health_id'      => 'Identifiant santé',
    'health_id_hint' => 'Identifiant santé exact uniquement. Les patients ne peuvent être ni recherchés par nom ni listés.',
    'lookup'         => 'Ouvrir le dossier',
    'not_found'      => 'Aucun dossier n’est disponible pour cet identifiant santé. Soit aucun patient ne le détient, soit vous n’avez aucune relation de soins avec ce patient : un rendez-vous, l’appartenance à son équipe de soins, un acte clinique de votre part, un épisode en cours dans cet établissement ou un consentement accordé à cet établissement.',
    'landing_hint'   => 'Pour confirmer uniquement l’identité, utilisez la recherche par identifiant santé du tableau de bord de l’établissement.',
    'back_to_lookup' => 'Chercher un autre patient',
    'no_facility_context' => 'Aucun établissement actif. Sélectionnez l’établissement où vous travaillez avant d’ouvrir un dossier.',
    'audit_unavailable'   => 'Le journal d’accès n’a pas pu être écrit ; le dossier n’a donc pas été affiché. Réessayez dans un instant.',

    // ── Onglets ───────────────────────────────────────────────────────────
    'tabs' => [
        'summary'       => 'Résumé',
        'encounters'    => 'Consultations',
        'vitals'        => 'Constantes',
        'diagnoses'     => 'Diagnostics',
        'labs'          => 'Résultats de laboratoire',
        'immunizations' => 'Vaccinations',
        'preconsult'    => 'Pré-consultation',
        'triage'        => 'Historique de triage',
        'documents'     => 'Documents',
        'partner_reported' => 'Transmis par des partenaires',
    ],

    // ── Fondement et périmètre ────────────────────────────────────────────
    'opened_on_basis' => 'Ouvert sur la base de',
    'basis' => [
        'appointment'        => 'votre rendez-vous avec ce patient',
        'care_team'          => 'votre appartenance à l’équipe de soins de ce patient',
        'treating_clinician' => 'un acte clinique que vous avez réalisé pour ce patient',
        'facility_episode'   => 'un épisode de soins en cours dans cet établissement',
        'consent_grant'      => 'un consentement accordé par le patient à cet établissement',
    ],
    'scope_preview_title' => 'Aperçu uniquement',
    'scope_preview_note'  => 'Ce patient garde son dossier complet derrière son consentement et cet établissement ne détient aucun consentement actif. L’identité, les allergies et les médicaments en cours sont affichés ; chaque autre onglet nécessite le consentement du patient.',
    'consent_required_title' => 'Le consentement du patient est requis',
    'consent_required_note'  => 'Cette section est masquée parce que le patient a demandé que son dossier complet reste derrière son consentement et que cet établissement ne détient aucun consentement actif. Demandez l’accès au patient. Ce refus a été journalisé.',

    // ── Identité ──────────────────────────────────────────────────────────
    'patient'      => 'Patient',
    'sex'          => 'Sexe',
    'sexes' => [
        'male'    => 'Masculin',
        'female'  => 'Féminin',
        'other'   => 'Autre',
        'unknown' => 'Non renseigné',
    ],
    'blood_group'  => 'Groupe sanguin',
    'age'          => 'Âge',
    'age_years'    => ':years ans',
    'age_unknown'  => 'Date de naissance non enregistrée',
    'age_band'     => 'Tranche d’âge',
    'age_bands' => [
        'neonate'    => 'Nouveau-né',
        'infant'     => 'Nourrisson',
        'child'      => 'Enfant',
        'adolescent' => 'Adolescent',
        'adult'      => 'Adulte',
        'geriatric'  => 'Personne âgée',
    ],
    'not_recorded' => 'Non renseigné',
    'privacy_title' => 'Paramètres de confidentialité du patient',
    'privacy_consent_on'   => 'Dossier complet derrière consentement : ACTIVÉ',
    'privacy_consent_off'  => 'Dossier complet derrière consentement : DÉSACTIVÉ',
    'privacy_emergency_on' => 'Accès d’urgence : autorisé',
    'privacy_emergency_off' => 'Accès d’urgence : refusé par le patient',

    // ── Sections du résumé ────────────────────────────────────────────────
    'allergies_title' => 'Allergies',
    'allergies_none'  => 'Aucune allergie n’a été enregistrée. Cela ne signifie pas que le patient n’en a aucune.',
    'col_substance'   => 'Substance',
    'col_severity'    => 'Sévérité',
    'col_status'      => 'Statut',
    'col_recorded'    => 'Enregistré',

    'problems_title'  => 'Problèmes actifs',
    'problems_none'   => 'Aucun problème n’a été enregistré sur la liste des problèmes.',
    'col_code'        => 'Code',
    'col_description' => 'Description',
    'col_onset'       => 'Début',

    'medicines_title' => 'Médicaments en cours',
    'medicines_none'  => 'Aucune ordonnance active n’est enregistrée.',
    'col_drug'        => 'Médicament',
    'col_dose'        => 'Dose',
    'col_route'       => 'Voie',
    'col_frequency'   => 'Fréquence',
    'col_duration'    => 'Durée',
    'duration_days'   => ':days jours',
    'col_prescribed'  => 'Prescrit le',
    'col_prescriber'  => 'Prescripteur',

    // ── Consultations ─────────────────────────────────────────────────────
    'encounters_none' => 'Aucune consultation n’a été enregistrée pour ce patient.',
    'col_started'     => 'Début',
    'col_ended'       => 'Fin',
    'col_type'        => 'Type',
    'col_facility'    => 'Établissement',
    'col_doctor'      => 'Médecin',
    'reason'          => 'Motif de consultation',
    'history'         => 'Histoire de la maladie',
    'examination'     => 'Examen clinique',
    'plan'            => 'Plan de traitement',
    'discharge'       => 'Orientation à la sortie',
    'note_none'       => 'Aucune note clinique n’est rattachée à cette consultation.',

    // ── Constantes ────────────────────────────────────────────────────────
    'facility_vitals_title' => 'Observations mesurées en établissement',
    'facility_vitals_none'  => 'Aucune observation n’a été enregistrée en établissement.',
    'self_reported_title'   => 'Les relevés du patient lui-même',
    'self_reported_note'    => 'Relevés faits par le patient chez lui, avec son propre matériel. NON vérifiés, NON cliniques, jamais interprétés ni mélangés aux observations ci-dessus. Affichés à titre de contexte uniquement.',
    'self_reported_empty'   => 'Ce patient n’a enregistré aucun relevé personnel.',
    'col_measure'      => 'Mesure',
    'col_value'        => 'Valeur',
    'col_flag'         => 'Indicateur',
    'col_provenance'   => 'Source',
    'col_recorded_by'  => 'Relevé par',
    'col_recorded_at'  => 'Relevé le',
    'entered_at_note'  => 'heure de saisie — cette ligne n’enregistre pas d’heure de mesure',
    'measured_at'      => 'Mesuré',
    'measure' => [
        'temperature'       => 'Température (°C)',
        'pulse_rate'        => 'Pouls (bpm)',
        'respiratory_rate'  => 'Fréquence respiratoire (/min)',
        'blood_pressure'    => 'Tension artérielle (mmHg)',
        'oxygen_saturation' => 'SpO₂ (%)',
        'weight_kg'         => 'Poids (kg)',
        'height_cm'         => 'Taille (cm)',
        'gcs_score'         => 'GCS',
    ],
    'advisory_source' => 'Normes adultes · indicatif',
    'advisory_note'   => 'Les indicateurs sont dérivés de normes adultes publiées (NEWS2, 16 ans et plus). Indicatifs uniquement — un indicateur signifie « quelqu’un doit regarder ceci », jamais « ce patient est en sécurité », et l’absence d’indicateur n’est pas rassurante.',
    'no_bands_title'  => 'Aucune interprétation des constantes n’est disponible pour ce patient',
    'no_bands' => [
        'paediatric'  => 'Ce patient a moins de 16 ans. Les seules normes de constantes de cette plateforme sont des normes adultes (NEWS2, 16 ans et plus), et les valeurs pédiatriques varient selon l’âge — une fréquence respiratoire normale chez l’enfant est une urgence chez l’adulte. Aucun indicateur n’est affiché. Évaluez ce patient cliniquement.',
        'age_unknown' => 'Ce patient n’a pas de date de naissance enregistrée ; son âge ne peut donc pas être établi. Les normes de cette plateforme ne sont valables qu’à partir de 16 ans et elle ne supposera pas qu’un patient inconnu est adulte. Aucun indicateur n’est affiché.',
        'pregnancy'   => 'Ce relevé a été pris pendant une grossesse enregistrée. Les normes de cette plateforme (NEWS2) ne sont explicitement pas validées pendant la grossesse. Aucun indicateur n’est affiché pour ce relevé.',
    ],
    'row_no_bands_pregnancy' => 'Grossesse enregistrée — pas de normes',

    // ── Diagnostics ───────────────────────────────────────────────────────
    'diagnoses_none'    => 'Aucun diagnostic n’a été codé pour ce patient.',
    'col_title'         => 'Libellé',
    'col_rank'          => 'Rang',
    'primary'           => 'Principal',
    'secondary'         => 'Secondaire',
    'col_provider'      => 'Enregistré par',
    'col_visit'         => 'Visite',
    'title_from_record' => 'Libellé tel qu’enregistré — code absent du dictionnaire',

    // ── Laboratoire ───────────────────────────────────────────────────────
    'labs_none'      => 'Aucun résultat de laboratoire n’a été publié pour ce patient.',
    'col_test'       => 'Analyte',
    'col_loinc'      => 'LOINC',
    'col_range'      => 'Intervalle de référence',
    'col_resulted'   => 'Publié le',
    'flag_source' => [
        'lab'       => 'signalé par le laboratoire',
        'range'     => 'selon l’intervalle du laboratoire',
        'published' => 'selon un intervalle publié',
    ],
    'labs_note'      => 'Un indicateur dit qui a comparé la valeur et à quoi. « Non renseigné » signifie que personne ne l’a comparée — ce n’est pas « normal ».',

    // ── Vaccinations ──────────────────────────────────────────────────────
    'immunizations_none' => 'Aucune vaccination n’a été enregistrée pour ce patient.',
    'col_vaccine'        => 'Vaccin',
    'col_dose_number'    => 'Dose',
    'col_administered'   => 'Administré le',
    'col_verification'   => 'Vérification',
    'historical'         => 'Historique (d’après un carnet)',

    // ── Pré-consultation ──────────────────────────────────────────────────
    'preconsult_badge'  => 'Déclaré par le patient · non vérifié',
    'preconsult_note'   => 'Réponses données par le patient avant un rendez-vous dans cet établissement, avec ses propres mots. Contexte déclaré par le patient, pas une constatation clinique : rien ici n’est un diagnostic, une allergie enregistrée ni une observation, et c’est pour cela que cet onglet est séparé des onglets cliniques.',
    'preconsult_none'   => 'Aucun questionnaire de pré-consultation n’a été soumis pour un rendez-vous dans cet établissement.',
    'col_submitted'     => 'Soumis le',
    'answers_count'     => ':count réponses',

    // ── Historique de triage ──────────────────────────────────────────────
    'triage_none'      => 'Aucune évaluation de triage n’a été enregistrée pour ce patient.',
    'col_assessed'     => 'Évalué le',
    'col_complaint'    => 'Motif de consultation',
    'col_priority'     => 'Priorité actuelle',
    'col_nurse'        => 'Évalué par',
    'priority_none'    => 'Aucune priorité',
    'open_triage'      => 'Ouvrir au poste de triage',
    'other_facility'   => 'Autre établissement',

    // ── Documents ─────────────────────────────────────────────────────────
    'documents_none'  => 'Aucun document délivré n’est enregistré pour ce patient.',
    'col_document'    => 'Document',
    'col_number'      => 'Numéro',
    'col_issued'      => 'Délivré le',

    // ── Liens et avis ─────────────────────────────────────────────────────
    'links_title'     => 'Postes',
    'link_triage'     => 'Poste de triage',
    'link_queue'      => 'File des patients',
    'link_blood'      => 'Demandes de sang',
    'read_only'       => 'Lecture seule. Rien sur cette page n’écrit dans le dossier.',
    'partner_notice'  => 'Les mesures envoyées par des systèmes partenaires sont dans l’onglet Transmis par des partenaires, avec le système et l’établissement d’envoi, et ne sont jamais mêlées aux mesures prises ici.',
    'consent_request_title' => "Demander l'accès au patient",
    'consent_request_hint' => "Le patient reçoit la demande sur son téléphone et décide. S'il accepte, cet établissement peut ouvrir le dossier pendant 24 heures.",
    'consent_purpose' => 'Motif',
    'consent_justification' => 'Note pour le patient (facultatif)',
    'consent_request_submit' => 'Envoyer la demande',
    'consent_requested' => "Si cet identifiant santé est enregistré, la demande a été envoyée au patient. Recherchez à nouveau l'identifiant une fois qu'il a accepté.",
];
