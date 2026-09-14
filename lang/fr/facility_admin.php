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
    'nav_invoices'    => 'Factures',
    'nav_departments' => 'Services',

    // ── Factures (lecture seule) ──────────────────────────────────────────
    'action_open_invoices'  => 'Voir les factures',
    'invoices_title'        => 'Factures',
    'invoices_subtitle'     => "Toutes les factures émises dans cet établissement, les plus récentes en premier. Cette page est en lecture seule : émettre, encaisser, annuler et rembourser restent au guichet de facturation.",
    'invoices_empty'        => "Aucune facture n'a encore été émise dans cet établissement.",
    'col_invoice_number'    => 'Numéro',
    'col_invoice_patient'   => 'Patient',
    'col_invoice_status'    => 'Statut',
    'col_invoice_total'     => 'Part patient',
    'col_invoice_paid'      => 'Payé',
    'col_invoice_balance'   => 'Solde',
    'col_invoice_date'      => 'Émise le',
    'invoice_patient_unknown' => 'Patient introuvable',

    // ── Services (lecture seule) ──────────────────────────────────────────
    'action_open_departments' => 'Voir les services',
    'departments_title'       => 'Services',
    'departments_subtitle'    => "Le personnel actif de cet établissement, regroupé par le service inscrit sur sa fiche.",
    'departments_free_text'   => "Les services sont du texte libre aujourd'hui : chaque nom est regroupé exactement tel qu'il a été saisi sur la fiche du personnel, deux orthographes font donc deux lignes. Il n'existe pas encore de registre des services.",
    'departments_empty'       => "Aucune fiche de personnel active de cet établissement ne porte encore de service.",
    'department_unassigned'   => 'Aucun service inscrit',
    'department_members'      => ':count membres',
    'col_department'          => 'Service',
    'col_department_count'    => 'Effectif',
    'col_department_roles'    => 'Rôles',
    'col_department_members'  => 'Membres',

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

    // ── Rapports et exports (H-6) ─────────────────────────────────────────
    'nav_reports'             => 'Rapports',
    'action_open_reports'     => 'Rapports et exports',
    'reports_title'           => 'Rapports',
    'reports_subtitle'        => "Chiffres par jour tirés des propres dossiers de cet établissement sur la période choisie. Uniquement des décomptes, des médianes et des totaux en francs entiers — aucun patient n'apparaît dans un tableau ni dans un export.",
    'reports_range_from'      => 'Du',
    'reports_range_to'        => 'Au',
    'reports_range_apply'     => 'Afficher',
    'reports_range_shown'     => 'Période du :from au :to (:days jours).',
    'reports_range_order'     => 'La date de fin ne peut pas précéder la date de début.',
    'reports_range_too_long'  => 'Un rapport couvre au plus :days jours.',
    'reports_export_csv'      => 'Exporter en CSV',
    'reports_empty'           => 'Aucune ligne sur cette période.',
    'reports_table_appointments' => 'Rendez-vous par statut et par jour',
    'reports_table_queue'        => "Salle d'attente par jour",
    'reports_table_lab'          => 'Laboratoire par jour',
    'reports_table_billing'      => 'Facturation par jour',
    'reports_table_staff'        => 'Effectif par rôle',
    'reports_queue_note'         => "Seuls les jours comptant au moins un enregistrement sont listés. L'attente médiane court de l'enregistrement au premier appel.",
    'reports_lab_note'           => 'Le délai court de la prescription à la publication du résultat, médiane sur les analyses publiées ce jour-là.',
    'reports_billing_note'       => "Émis selon le jour où la facture a été établie (part patient) ; encaissé selon le jour du paiement. Francs entiers. Rien ici ne conditionne les soins au paiement.",
    'reports_no_show_rate'       => "Taux d'absence sur la période",
    'reports_no_show_rate_value' => ':pct % (:no_show sur :total réservés)',
    'col_day'                    => 'Jour',
    'col_total'                  => 'Total',
    'col_checked_in'             => 'Enregistrés',
    'col_seen'                   => 'Vus',
    'col_left'                   => 'Partis / absents',
    'col_median_wait'            => 'Attente médiane',
    'col_released'               => 'Publiés',
    'col_median_turnaround'      => 'Délai médian',
    'col_issued'                 => 'Factures émises',
    'col_issued_amount'          => 'Émis (FCFA)',
    'col_collected'              => 'Paiements',
    'col_collected_amount'       => 'Encaissé (FCFA)',
    'col_headcount'              => 'Effectif',

    // ── Services et lits (H-5) ────────────────────────────────────────────
    'nav_wards'               => 'Services et lits',
    'action_open_wards'       => 'Services et lits',
    'wards_title'             => 'Services et lits',
    'wards_subtitle'          => "Les services de cet établissement et leurs lits. L'occupation est lue dans les admissions enregistrées.",
    'wards_scope_note'        => "Ce bureau ne tient que la structure : services, lits, et si un lit est en service. Admettre ou faire sortir un patient est un acte clinique enregistré ailleurs ; l'occupation apparaît ici dès que des admissions sont enregistrées. Les lits ne sont jamais supprimés — ils sont mis hors service puis remis en service.",
    'wards_empty'             => 'Aucun service enregistré pour le moment. Créez le premier ci-dessous.',
    'wards_active_admissions' => 'Admissions en cours',
    'ward_create_title'       => 'Créer un service',
    'ward_name'               => 'Nom du service',
    'ward_type'               => 'Type de service',
    'ward_capacity'           => 'Capacité en lits',
    'ward_capacity_help'      => "Capacité prévue. Elle augmente automatiquement lorsqu'on ajoute des lits.",
    'ward_create_submit'      => 'Créer le service',
    'ward_types'              => [
        'general'   => 'Médecine générale',
        'icu'       => 'Soins intensifs / réanimation',
        'maternity' => 'Maternité',
        'pediatric' => 'Pédiatrie',
        'surgical'  => 'Chirurgie',
        'emergency' => 'Urgences',
        'isolation' => 'Isolement',
    ],
    'col_ward'                => 'Service',
    'col_ward_type'           => 'Type',
    'col_ward_capacity'       => 'Capacité',
    'col_beds'                => 'Lits',
    'col_occupied'            => 'Occupés',
    'col_available'           => 'Disponibles',
    'col_out_of_service'      => 'Hors service',
    'col_occupancy'           => 'Occupation',
    'ward_inactive'           => 'Inactif',
    'beds_add_title'          => 'Ajouter des lits',
    'beds_add_count'          => 'Nombre de lits',
    'beds_add_prefix'         => 'Préfixe des étiquettes',
    'beds_add_prefix_help'    => 'Les lits sont étiquetés PRÉFIXE-01, PRÉFIXE-02… à la suite des étiquettes déjà présentes dans le service.',
    'beds_add_submit'         => 'Ajouter les lits',
    'beds_none_in_ward'       => 'Aucun lit dans ce service pour le moment.',
    'bed_status_available'    => 'En service',
    'bed_status_occupied'     => 'Occupé',
    'bed_status_maintenance'  => 'Hors service',
    'bed_status_reserved'     => 'Réservé',
    'bed_set_out_of_service'  => 'Mettre hors service',
    'bed_set_in_service'      => 'Remettre en service',
    'ward_created'            => 'Service :name créé.',
    'beds_added'              => ':count lit(s) ajouté(s) à :ward.',
    'bed_out_of_service'      => 'Le lit :bed est hors service.',
    'bed_back_in_service'     => 'Le lit :bed est de nouveau en service.',
    'bed_status_unchanged'    => "Le lit :bed n'a pas été modifié : il est occupé ou déjà dans cet état.",

    "no_facility_context"    => "Aucun contexte d'établissement pour ce compte. Sélectionnez d'abord un établissement.",

];
