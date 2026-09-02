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

    // Remise du lien d'invitation (aucun SMTP en production — le lien s'affiche ici)
    'invite_link_title' => "Lien d'invitation",
    'invite_link_help'  => "Copiez ce lien et envoyez-le vous-même à :email — les invitations ne sont pas envoyées par e-mail.",

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
