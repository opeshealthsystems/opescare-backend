<?php

/*
|--------------------------------------------------------------------------
| Bureau d'administration de l'établissement
| (/facility/team/overview, /facility/team/members)
|--------------------------------------------------------------------------
| DOIT rester 1:1 avec lang/en/facility_admin.php (scripts/i18n-audit.php) :
| mêmes clés, mêmes :placeholders.
|
| Chaque chiffre de la vue d'ensemble est un décompte sur les lignes de cet
| établissement. Le texte ne promet jamais une valeur que la table ne peut
| pas fournir — une journée vide dit « aucun », pas « 0 minute ».
*/

return [

    'nav_overview'  => "Vue d'ensemble",
    'nav_members'   => "Membre de l'équipe",

    // ── Vue d'ensemble ────────────────────────────────────────────────────
    'overview_title'    => "Vue d'ensemble de l'établissement",
    'overview_subtitle' => "Aujourd'hui à :facility — chaque chiffre est mesuré à partir des dossiers de cet établissement.",
    'overview_day'      => 'Chiffres du :date',
    'breadcrumb_home'   => 'Mon établissement',

    'section_appointments' => 'Rendez-vous',
    'section_queue'        => "Salle d'attente",
    'section_lab'          => 'Laboratoire',
    'section_beds'         => 'Lits',
    'section_billing'      => 'Facturation',
    'section_team'         => 'Équipe',

    'kpi_appointments_today'  => "Réservés aujourd'hui",
    'kpi_checked_in'          => 'Arrivés',
    'kpi_no_show'             => 'Absences',
    'kpi_cancelled'           => 'Annulés',

    'kpi_queue_checked_in'    => 'Enregistrements en file',
    'kpi_queue_waiting'       => 'En attente maintenant',
    'kpi_queue_seen'          => 'Vus',
    'kpi_median_wait'         => 'Attente médiane',
    'kpi_longest_wait'        => 'Attente la plus longue',
    'kpi_no_measure'          => 'Pas encore mesuré',

    'kpi_lab_open'            => 'Demandes ouvertes',
    'kpi_lab_resulted_today'  => "Rendus aujourd'hui",
    'kpi_lab_turnaround'      => 'Délai médian',

    'kpi_wards'               => 'Services actifs',
    'kpi_beds'                => 'Lits',
    'kpi_beds_occupied'       => 'Occupés',
    'kpi_beds_available'      => 'Disponibles',
    'kpi_occupancy'           => 'Occupation',
    'kpi_admissions'          => 'Admissions en cours',
    'beds_none'               => "Cet établissement n'a aucun service enregistré ; aucune occupation n'est donc affichée.",

    'kpi_collected_today'     => "Encaissé aujourd'hui",
    'kpi_outstanding'         => 'Solde impayé',
    'kpi_issued_today'        => "Factures émises aujourd'hui",
    'billing_note'            => "Totaux issus des factures de cet établissement. Rien sur cette page ne conditionne les soins au paiement.",

    'kpi_team_active'         => 'Personnel actif',
    'kpi_open_invites'        => 'Invitations ouvertes',
    'kpi_pending_joins'       => "Demandes d'adhésion en attente",

    'action_open_queue'     => 'Ouvrir la file',
    'action_open_team'      => "Gérer l'équipe",
    'action_open_catalogue' => 'Catalogue des services',

    'duration_minutes' => ':n min',
    'duration_hours'   => ':h h :m min',
    'amount_xaf'       => ':amount FCFA',

    // ── Colonne de la page équipe ────────────────────────────────────────
    'col_manage'   => 'Gérer',
    'action_open'  => 'Ouvrir',

    // ── Page membre ───────────────────────────────────────────────────────
    'member_title'        => "Membre de l'équipe",
    'member_subtitle'     => 'Ce que :name détient dans cet établissement, et ce que ce bureau peut modifier.',
    'back_to_team'        => "Retour à l'équipe",

    'section_identity'    => 'Compte',
    'section_posting'     => 'Affectation dans cet établissement',
    'section_postings'    => 'Toutes les affectations',
    'section_credentials' => 'Titres et habilitations',
    'section_profile'     => 'Dossier du personnel',
    'section_actions'     => 'Actions',

    'lbl_name'            => 'Nom',
    'lbl_email'           => 'Courriel',
    'lbl_account_status'  => 'État du compte',
    'lbl_role_here'       => 'Rôle dans cet établissement',
    'lbl_posting_status'  => 'Affectation',
    'lbl_since'           => 'Depuis',
    'lbl_home_facility'   => "Établissement d'origine",
    'home_facility_here'  => 'Cet établissement',
    'home_facility_other' => 'Un autre établissement',

    'posting_active'      => 'Active',
    'posting_ended'       => 'Terminée',
    'posting_none'        => "Aucune affectation enregistrée ici — cette personne figure uniquement via son établissement d'origine.",

    'col_facility'        => 'Établissement',
    'col_role'            => 'Rôle',
    'col_status'          => 'État',
    'col_assigned'        => 'Affecté le',
    'postings_empty'      => 'Aucune affectation enregistrée.',

    'col_credential'      => 'Titre',
    'col_number'          => 'Numéro',
    'col_issuer'          => 'Organisme émetteur',
    'col_expiry'          => 'Expire le',
    'col_verified'        => 'Vérifié',
    'credential_verified'   => 'Vérifié',
    'credential_unverified' => 'Non vérifié',
    'credential_expired'    => 'Expiré',
    'credentials_empty'     => 'Aucun titre enregistré. Le professionnel ajoute les siens depuis sa page de profil.',

    'lbl_job_title'       => 'Poste',
    'lbl_department'      => 'Service',
    'lbl_employee_number' => 'Matricule',
    'lbl_employment_type' => "Type d'emploi",
    'lbl_phone'           => 'Téléphone',
    'profile_empty'       => "Pas encore de dossier du personnel. Il est créé la première fois que le professionnel ouvre son profil.",

    // ── Actions ───────────────────────────────────────────────────────────
    'change_role_title'   => 'Changer le rôle dans cet établissement',
    'change_role_help'    => "Seuls les rôles cliniques et opérationnels peuvent être attribués ici — la même liste que le formulaire d'invitation. Le changement prend effet à la requête suivante.",
    'change_role_submit'  => 'Changer le rôle',
    'deactivate_title'    => 'Mettre fin à cette affectation',
    'deactivate_help'     => "La personne perd l'accès à cet établissement. S'il s'agit de son établissement d'origine et qu'elle ne travaille nulle part ailleurs sur OpesCare, son compte est également suspendu. Réversible depuis cette page.",
    'deactivate_submit'   => 'Désactiver dans cet établissement',
    'reactivate_title'    => 'Rouvrir cette affectation',
    'reactivate_help'     => "Rétablit le dernier rôle détenu ici et lève une suspension appliquée par ce bureau.",
    'reactivate_submit'   => 'Réactiver',
    'not_manageable_self' => 'Vous ne pouvez pas modifier votre propre rôle ou affectation.',
    'not_manageable_role' => "Ce rôle dépasse ce qu'un bureau d'établissement peut attribuer ; il ne peut donc être ni modifié ni terminé ici.",

    // ── Messages flash ────────────────────────────────────────────────────
    'role_changed'               => 'Rôle dans cet établissement changé en :role.',
    'deactivated'                => "Affectation terminée. La personne n'a plus accès à cet établissement.",
    'deactivated_and_suspended'  => "Affectation terminée et compte suspendu — c'était son seul établissement.",
    'reactivated'                => 'Affectation rouverte en tant que :role.',
    'already_inactive'           => 'Cette affectation est déjà terminée.',
    'already_active'             => 'Cette affectation est déjà active.',
    'member_not_manageable'      => "Le rôle de cette personne ne peut pas être modifié depuis le bureau de l'établissement.",

];
