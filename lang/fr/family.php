<?php

/*
|--------------------------------------------------------------------------
| Famille / personnes à charge — expérience tuteur sur le portail web
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/family.php.
*/

return [

    // ── Bandeau « Vous consultez … » ──────────────────────────────────
    'banner_viewing'          => 'Vous consultez le dossier de :name',
    'banner_health_id'        => 'Health ID :id',
    'banner_read_only'        => 'Accès en lecture seule',
    'banner_full'             => 'Accès complet',
    'banner_switch_back'      => 'Revenir à mon dossier',
    'banner_own_inbox'        => 'Les messages et les notifications appartiennent toujours à votre propre compte — ce ne sont pas ceux de :name.',
    'switch_no_longer_active' => 'L’accès tuteur n’est plus actif pour cette personne à charge.',
    'read_only_refused'       => 'Votre accès au dossier de :name est en lecture seule ; cette action n’est donc pas disponible. Le titulaire du compte peut accorder un accès complet.',
    'read_only_qr_note'       => 'Un code QR identifie :name auprès d’un établissement. Seul un tuteur disposant d’un accès complet peut en générer un.',

    // ── Tableau de bord tuteur ─────────────────────────────────────────
    'dash_title'              => 'Mes personnes à charge',
    'dash_subtitle'           => 'Les personnes dont vous gérez le dossier de santé. Ouvrez un dossier pour voir la carte Health ID, les rendez-vous, les vaccinations et plus encore.',
    'dash_breadcrumb'         => 'Personnes à charge',
    'dash_empty_title'        => 'Aucune personne à charge pour l’instant',
    'dash_empty_body'         => 'Enregistrez un enfant qui n’a pas encore de Health ID, ou invitez un adulte qui en possède déjà un — il accepte depuis son propre compte.',
    'dash_view_as'            => 'Ouvrir le dossier',
    'dash_add_child'          => 'Enregistrer un enfant',
    'dash_invite_adult'       => 'Inviter un adulte',
    'dash_incoming'           => 'Invitations en attente',
    'dash_incoming_count'     => ':count invitation(s) en attente de votre réponse',
    'dash_next_appointment'   => 'Prochain rendez-vous',
    'dash_none_scheduled'     => 'Aucun rendez-vous prévu',
    'dash_immunisations'      => 'Vaccinations',
    'dash_identity'           => 'Identité',
    'dash_age'                => ':age ans',
    'dash_pending_invite'     => 'En attente de son acceptation',
    'dash_currently_viewing'  => 'Actuellement ouvert',
    'dash_nav_title'          => 'Aller à',
    'dash_hidden_dependent'   => 'Ce dossier n’est pas visible dans l’environnement actuel.',
    'dash_quick_links'        => 'Ouvrir directement',

    // ── Libellés de navigation ────────────────────────────────────────
    'nav_dashboard'           => 'Tableau de bord',
    'nav_dependents'          => 'Personnes à charge',
    'nav_family'              => 'Famille',
    'nav_appointments'        => 'Rendez-vous',
    'nav_prescriptions'       => 'Ordonnances',
    'nav_immunizations'       => 'Vaccinations',
    'nav_timeline'            => 'Chronologie de santé',
    'nav_documents'           => 'Documents',
    'nav_allergies'           => 'Allergies',
    'nav_clinical'            => 'Pathologies',
    'nav_care_plans'          => 'Plans de soins',
    'nav_access_logs'         => 'Journal des accès',
    'nav_print_card'          => 'Imprimer la carte Health ID',
    'nav_messages'            => 'Messages',
    'nav_settings'            => 'Paramètres',
    'nav_care_map'            => 'Carte des soins',
    'nav_invitations'         => 'Invitations pour moi',
    'nav_profile'             => 'Profil',
    'nav_records'             => 'Dossier',

    // ── Passage à la majorité ─────────────────────────────────────────
    'age_turns_majority'      => ':name aura :age ans le :date. À partir de ce jour, le lien nécessite son propre accord et prend fin :grace jours plus tard s’il n’est pas maintenu.',
    'age_grace'               => 'Période de grâce — :name est désormais majeur(e). L’accès prend fin le :date sauf s’il est maintenu depuis son propre compte.',
    'age_adult_must_invite'   => 'Une personne de :age ans ou plus est invitée, et non enregistrée — elle seule peut accepter de partager son dossier. Les informations saisies ont été reportées.',
    'age_child_note'          => 'Enregistrez uniquement un enfant de moins de :age ans qui n’a pas encore de Health ID OpesCare. Un adulte, ou toute personne qui possède déjà un Health ID, est invité et accepte depuis son propre compte.',

    // ── Lien de parenté (rôle du tuteur envers la personne) ───────────
    'relationship' => [
        'parent'         => 'Parent',
        'child'          => 'Enfant (je suis son fils ou sa fille)',
        'grandparent'    => 'Grand-parent',
        'spouse'         => 'Conjoint(e)',
        'sibling'        => 'Frère / sœur',
        'caregiver'      => 'Aidant',
        'legal_guardian' => 'Tuteur légal',
        'other'          => 'Autre',
    ],
    'relationship_hint'       => 'Votre rôle envers cette personne — « Parent » s’il s’agit de votre enfant, « Enfant » s’il s’agit de votre mère ou de votre père.',

    // ── Niveaux d’accès (canoniques : full | read_only) ───────────────
    'access' => [
        'full'      => 'Accès complet',
        'read_only' => 'Lecture seule',
    ],
    'access_full_explained'      => 'Accès complet : tout voir, prendre et annuler des rendez-vous, demander des renouvellements, mettre à jour les coordonnées et présenter le code QR du Health ID.',
    'access_read_only_explained' => 'Lecture seule : voir le dossier, les rendez-vous, les ordonnances et les vaccinations, sans rien modifier ni générer de code QR.',
    'access_legacy_note'         => 'Ce lien a été créé avec un ancien réglage d’accès (:value) et fonctionne actuellement en lecture seule. Enregistrez pour confirmer un niveau.',

    // ── Inviter un adulte ─────────────────────────────────────────────
    'invite_contact_label'       => 'Health ID, e-mail ou numéro de téléphone',
    'invite_contact_hint'        => 'Correspondance exacte uniquement — le Health ID tel qu’imprimé sur la carte, ou l’e-mail / le numéro de téléphone du compte OpesCare. Aucune recherche par nom.',
    'invite_contact_placeholder' => 'CM-HID-XXXX-XXXX-XXXX',
    'invite_how_it_works'        => 'La personne reçoit l’invitation sur son propre compte OpesCare et décide de l’accepter. Rien n’est partagé avant son accord.',
    'invite_no_match'            => 'Aucun compte OpesCare ne correspond exactement à ce Health ID, cet e-mail ou ce numéro de téléphone.',
    'invite_needs_account'       => 'La personne doit déjà posséder un Health ID OpesCare et se connecter pour accepter. Un enfant qui n’en a pas est enregistré à la place.',

    // ── Invitations qui me sont adressées ─────────────────────────────
    'inv_title'          => 'Invitations pour moi',
    'inv_subtitle'       => 'Des personnes demandent à suivre votre dossier de santé. Vous décidez — rien n’est partagé avant votre accord.',
    'inv_breadcrumb'     => 'Invitations',
    'inv_empty'          => 'Personne n’attend votre réponse.',
    'inv_from'           => ':name demande à être lié(e) à votre dossier en tant que :relationship, avec :access.',
    'inv_expires'        => 'Expire le :date',
    'inv_accept'         => 'Accepter',
    'inv_decline'        => 'Refuser',
    'inv_accepted'       => 'Invitation acceptée. :name peut désormais consulter votre dossier.',
    'inv_declined'       => 'Invitation refusée. Rien n’a été partagé.',
    'inv_not_found'      => 'Cette invitation n’est plus ouverte.',
    'inv_no_patient'     => 'Les invitations sont adressées à un Health ID. Complétez votre profil patient pour en recevoir.',
    'inv_section_title'  => 'Invitations en attente de votre réponse',
    'inv_view_all'       => 'Voir toutes les invitations',
    'inv_not_for_you'    => 'Cette invitation n’était pas adressée à votre Health ID.',

    // ── Pages du dossier ──────────────────────────────────────────────
    'record_of'          => 'Dossier de :name',
    'back_to_dashboard'  => 'Retour au tableau de bord',

    'imm_title'          => 'Vaccinations',
    'imm_subtitle'       => 'Vaccins enregistrés, du plus récent au plus ancien.',
    'imm_empty'          => 'Aucune vaccination enregistrée pour l’instant.',
    'col_vaccine'        => 'Vaccin',
    'col_date'           => 'Date',
    'col_dose'           => 'Dose',
    'col_lot'            => 'Lot',
    'col_facility'       => 'Établissement',
    'col_status'         => 'Statut',

    'tl_title'           => 'Chronologie de santé',
    'tl_subtitle'        => 'Visites, résultats de laboratoire et ordonnances dans un seul fil chronologique.',
    'tl_empty'           => 'Rien dans la chronologie pour l’instant.',
    'tl_visit'           => 'Visite',
    'tl_lab_result'      => 'Résultat de laboratoire',
    'tl_prescription'    => 'Ordonnance',
    'tl_items'           => ':count médicament(s)',

    'doc_title'          => 'Documents',
    'doc_subtitle'       => 'Documents officiels délivrés pour ce dossier.',
    'doc_empty'          => 'Aucun document pour l’instant.',
    'doc_download'       => 'Télécharger',
    'doc_not_released'   => 'Non délivré',
    'col_document'       => 'Document',
    'col_number'         => 'Numéro',
    'col_issued'         => 'Délivré le',
    'col_expires'        => 'Expire le',

    'alg_title'          => 'Allergies',
    'alg_subtitle'       => 'Allergies enregistrées et leur gravité.',
    'alg_empty'          => 'Aucune allergie enregistrée.',
    'col_substance'      => 'Substance',
    'col_severity'       => 'Gravité',
    'col_recorded'       => 'Enregistrée le',

    'cl_title'           => 'Pathologies',
    'cl_subtitle'        => 'Diagnostics et pathologies enregistrés par l’équipe soignante.',
    'cl_empty'           => 'Aucune pathologie enregistrée.',
    'col_condition'      => 'Pathologie',
    'col_code'           => 'Code',
    'col_primary'        => 'Principale',
    'yes'                => 'Oui',

    'cp_title'           => 'Plans de soins',
    'cp_subtitle'        => 'Plans de soins actifs, avec leurs objectifs et interventions.',
    'cp_empty'           => 'Aucun plan de soins actif.',
    'cp_goals'           => 'Objectifs',
    'cp_interventions'   => 'Interventions',
    'cp_period'          => 'du :from au :to',
    'cp_ongoing'         => 'en cours',

    'log_title'          => 'Journal des accès',
    'log_subtitle'       => 'Chaque consultation ou scan de ce Health ID.',
    'log_empty'          => 'Aucun accès enregistré pour l’instant.',
    'col_when'           => 'Quand',
    'col_who'            => 'Qui',
    'col_type'           => 'Type',
    'col_purpose'        => 'Motif',
    'col_result'         => 'Résultat',

    // ── Carte Health ID imprimable (PDF) ─────────────────────────────
    'card_title'              => 'Health ID',
    'card_print'              => 'Imprimer la carte Health ID',
    'card_print_hint'         => 'Une carte format portefeuille (PDF) avec le Health ID et un code QR de vérification.',
    'card_dob'                => 'Date de naissance',
    'card_sex'                => 'Sexe',
    'card_blood'              => 'Groupe sanguin',
    'card_issued'             => 'Délivrée le',
    'card_verify'             => 'Scanner pour vérifier',
    'card_footer'             => 'Identifie le titulaire auprès des établissements connectés à OpesCare. Le Health ID seul n’ouvre aucun dossier — le consentement est demandé dans chaque établissement.',
    'card_status_verified'    => 'Identité vérifiée',
    'card_status_provisional' => 'Identité provisoire',
    'card_sex_male'           => 'Masculin',
    'card_sex_female'         => 'Féminin',

    // ── Sortie de l’attente tuteur ────────────────────────────────────
    'pending_next_steps'      => 'Vous pouvez déjà indiquer de qui vous vous occupez :',
    'child_may_exist' => 'Une personne avec ces informations possède peut-être déjà un Identifiant Santé OpesCare. Invitez-la plutôt, afin de relier son dossier existant avec son consentement.',
];
