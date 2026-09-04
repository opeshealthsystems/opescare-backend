<?php

/**
 * Gestion de l'équipe d'un établissement — inviter du personnel.
 *
 * EN et FR doivent rester strictement 1:1 (vérifié par scripts/i18n-audit.php).
 */
return [
    // Chrome de la page
    'page_title'      => 'Mon équipe',
    'page_subtitle'   => 'Invitez des cliniciens et du personnel à travailler dans cet établissement.',
    'breadcrumb_home' => 'Mon établissement',

    // Formulaire d'invitation
    'invite_form_title'      => 'Inviter un membre du personnel',
    'invite_form_help'       => "L'invitation n'est valable que pour cet établissement, et la liste des rôles se limite aux rôles cliniques et opérationnels.",
    'field_email'            => 'Adresse e-mail professionnelle',
    'field_name'             => 'Nom complet (facultatif)',
    'field_role'             => 'Rôle',
    'field_role_placeholder' => 'Sélectionnez un rôle',
    'invite_submit'          => "Créer l'invitation",

    // Remise du lien d'invitation. Le lien fait foi : la remise des e-mails
    // depuis cet hôte n'est pas garantie, le lien copiable est donc toujours affiché.
    'invite_link_title' => "Lien d'invitation",
    'invite_link_help'  => "Copiez ce lien et envoyez-le à :email — ce lien est le moyen fiable de transmettre l'invitation.",

    // Liste des invitations
    'invites_title'   => 'Invitations en attente',
    'invites_empty'   => "Aucune invitation n'a encore été émise pour cet établissement.",
    'col_email'       => 'E-mail',
    'col_name'        => 'Nom',
    'col_role'        => 'Rôle',
    'col_status'      => 'Statut',
    'col_expires'     => 'Expire le',
    'col_invited_by'  => 'Invité par',
    'col_actions'     => 'Actions',
    'action_reissue'  => 'Nouveau lien',
    'action_revoke'   => 'Révoquer',

    'status_pending'  => 'En attente d’acceptation',
    'status_used'     => 'Acceptée',
    'status_revoked'  => 'Révoquée',
    'status_expired'  => 'Expirée',

    // Liste du personnel
    'staff_title' => 'Personnel de cet établissement',
    'staff_empty' => "Aucun compte du personnel n'est encore rattaché à cet établissement.",

    'user_status_active'    => 'Actif',
    'user_status_suspended' => 'Suspendu',
    'user_status_pending'   => 'En attente',

    // Messages flash
    'invite_created'       => "Invitation créée. Copiez le lien ci-dessous et envoyez-le à la personne invitée.",
    'invite_reissued'      => "Un nouveau lien d'invitation a été généré. L'ancien lien ne fonctionne plus.",
    'invite_revoked'       => 'Invitation révoquée.',
    'invite_already_used'  => "Cette invitation a déjà été acceptée et ne peut plus être modifiée.",
    'invite_already_open'  => "Une invitation est déjà en cours pour cette adresse e-mail dans cet établissement.",
    'email_taken'          => 'Un compte existe déjà avec cette adresse e-mail.',
    'role_unknown'         => "Ce rôle n'est pas disponible dans cet établissement.",
    'accept_failed'        => "Le compte n'a pas pu être créé. Demandez un nouveau lien d'invitation à votre administrateur.",

    // Page publique d'invitation
    'invite_email_lbl'   => 'Adresse e-mail invitée',
    'invite_error_help'  => "Si vous pensez qu'il s'agit d'une erreur, demandez à l'administrateur de votre établissement d'émettre une nouvelle invitation OpesCare.",
    'password_hint'      => 'Au moins 8 caractères',

    /*
     * ── E-mail d'invitation ─────────────────────────────────────────────
     *
     * Une commodité qui s'ajoute au lien, jamais un remplacement. Les deux
     * messages ci-dessous sont formulés pour ne jamais affirmer que le message
     * a été remis : tout ce que l'on sait, c'est que le serveur de messagerie
     * l'a accepté.
     */
    'invite_mail_accepted' => "L'e-mail d'invitation a été accepté par le serveur de messagerie pour :email. La remise n'est pas garantie — s'il n'arrive pas, envoyez vous-même le lien ci-dessous.",
    'invite_mail_failed'   => "L'e-mail d'invitation n'a pas pu être envoyé à :email. Copiez le lien ci-dessous et transmettez-le vous-même.",

    'mail' => [
        'subject'       => 'Vous êtes invité(e) à rejoindre :facility sur OpesCare',
        'greeting'      => 'Bonjour,',
        'intro'         => 'Vous êtes invité(e) à rejoindre :facility sur OpesCare en tant que :role.',
        'invited_by'    => 'Invitation émise par : :name',
        'action'        => 'Activer mon compte',
        'expiry'        => "Ce lien d'invitation expire le :date.",
        'link_fallback' => "Si le bouton ne fonctionne pas, collez cette adresse dans votre navigateur : :url",
        'ignore'        => "Si vous n'attendiez pas cette invitation, vous pouvez ignorer ce message — aucun compte n'est créé tant que le lien n'est pas utilisé.",
        'salutation'    => "L'équipe OpesCare",
    ],

    /*
     * ── Demandes de rattachement ────────────────────────────────────────
     *
     * L'image inversée de l'invitation : le clinicien demande, l'établissement
     * décide. Le demandeur peut décrire le rôle souhaité ; c'est
     * l'établissement qui attribue le rôle. Ce sont deux choses distinctes, et
     * la formulation ci-dessous les garde distinctes à l'écran comme en base.
     */
    'join_nav'            => 'Rejoindre un établissement',
    'join_page_title'     => 'Rejoindre un établissement',
    'join_page_subtitle'  => "Demandez à un établissement de vous ajouter à son équipe. L'établissement examine votre demande et décide du rôle qui vous est attribué.",
    'join_role_notice'    => "Vous pouvez décrire le rôle souhaité, mais c'est l'établissement qui choisit le rôle qui vous est attribué.",

    'join_teams_title'    => 'Établissements où vous travaillez déjà',
    'join_teams_empty'    => "Vous ne faites encore partie d'aucune équipe d'établissement.",

    'join_search_label'       => 'Rechercher un établissement',
    'join_search_placeholder' => "Nom de l'établissement",
    'join_search_submit'      => 'Rechercher',
    'join_search_prompt'      => "Recherchez l'établissement où vous travaillez pour envoyer une demande.",
    'join_search_empty'       => 'Aucun établissement ne correspond à ce nom.',
    'join_search_hint'        => "Au plus :limit résultats sont affichés — précisez le nom si l'établissement recherché n'apparaît pas.",

    'join_form_title'              => 'Demande de rattachement à :facility',
    'join_field_requested_role'    => 'Rôle souhaité (facultatif)',
    'join_field_requested_role_ph' => 'ex. sage-femme de nuit',
    'join_field_requested_role_help' => "C'est une note pour la personne qui examinera la demande. L'établissement attribue le rôle : ce que vous saisissez ici ne détermine pas vos accès.",
    'join_field_message'           => "Message à l'établissement (facultatif)",
    'join_field_message_ph'        => "Tout ce qui aide à vous identifier — service, date de prise de poste, personne qui vous recommande.",
    'join_submit'                  => 'Envoyer la demande',
    'join_ask_button'              => 'Demander à rejoindre',

    'join_my_requests_title' => 'Mes demandes',
    'join_my_requests_empty' => "Vous n'avez encore demandé à rejoindre aucun établissement.",
    'join_col_facility'      => 'Établissement',
    'join_col_professional'  => 'Professionnel',
    'join_col_requested'     => 'Rôle demandé',
    'join_col_granted'       => 'Rôle attribué',
    'join_col_message'       => 'Message',
    'join_col_submitted'     => 'Envoyée le',
    'join_col_decision'      => 'Décision',
    'join_action_withdraw'   => 'Retirer',
    'join_action_approve'    => 'Approuver',
    'join_action_decline'    => 'Refuser',
    'join_grant_role_label'  => 'Attribuer ce rôle',
    'join_note_label'        => 'Note (facultatif)',
    'join_no_message'        => 'Aucun message',
    'join_decided_by'        => 'Décidée par :name',

    'join_queue_title' => 'Demandes de rattachement à cet établissement',
    'join_queue_help'  => "Cliniciens ayant demandé à rejoindre cette équipe. Choisissez le rôle que vous attribuez — le rôle qu'ils demandent n'est qu'une note.",
    'join_queue_empty' => "Personne n'a demandé à rejoindre cet établissement.",
    'join_queue_badge' => ':count en attente d’examen',

    'join_status_pending'   => "En attente d’examen",
    'join_status_approved'  => 'Approuvée',
    'join_status_declined'  => 'Refusée',
    'join_status_withdrawn' => 'Retirée',

    // Messages flash
    'join_submitted'        => 'Votre demande a été envoyée à :facility. La décision s’affichera sur cette page.',
    'join_withdrawn'        => 'Demande retirée.',
    'join_approved'         => "Demande approuvée. Le compte fait désormais partie de cette équipe en tant que :role.",
    'join_declined'         => 'Demande refusée.',
    'join_already_pending'  => "Vous avez déjà une demande en attente d’examen dans cet établissement.",
    'join_already_member'   => "Vous faites déjà partie de l’équipe de cet établissement.",
    'join_already_decided'  => "Cette demande a déjà été tranchée et ne peut plus être modifiée.",
    'join_facility_unknown' => "Cet établissement est introuvable.",
    'join_cooldown'         => "Cet établissement a refusé votre dernière demande. Vous pourrez en soumettre une nouvelle à partir du :date, ou leur demander un lien d'invitation.",
    'join_action_failed'    => "La demande n'a pas pu être traitée. Veuillez réessayer.",

    /*
     * Les rôles qu'un administrateur d'établissement peut attribuer, tels que
     * la personne invitée les verra. Cette liste reflète exactement
     * FacilityStaffInvite::INVITABLE_ROLES.
     */
    'roles' => [
        'doctor'              => 'Médecin',
        'specialist'          => 'Médecin spécialiste',
        'consultant'          => 'Médecin consultant',
        'resident'            => 'Médecin résident',
        'visiting_doctor'     => 'Médecin vacataire',
        'nurse'               => 'Infirmier / Infirmière',
        'triage_nurse'        => 'Infirmier(ère) de triage',
        'ward_nurse'          => 'Infirmier(ère) de service',
        'midwife'             => 'Sage-femme',
        'nurse_supervisor'    => 'Superviseur des soins infirmiers',
        'receptionist'        => 'Réceptionniste',
        'front_desk'          => "Agent d'accueil",
        'records_officer'     => 'Archiviste médical',
        'labtech'             => 'Technicien de laboratoire',
        'lab_scientist'       => 'Biologiste de laboratoire',
        'lab_manager'         => 'Responsable de laboratoire',
        'sample_collection'   => 'Agent de prélèvement',
        'pharmacist'          => 'Pharmacien',
        'pharmacy_technician' => 'Technicien en pharmacie',
        'pharmacy_manager'    => 'Responsable de pharmacie',
        'cashier'             => 'Caissier',
        'billing_officer'     => 'Agent de facturation',
    ],
];
