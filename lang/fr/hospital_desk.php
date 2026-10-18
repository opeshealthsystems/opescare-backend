<?php

/*
|--------------------------------------------------------------------------
| Bureau hôpital / clinique / centre de santé — chaînes de complétude
|--------------------------------------------------------------------------
| Miroir 1:1 de lang/en/hospital_desk.php (php scripts/i18n-audit.php).
*/

return [
    // ── Confirmations (window.confirm via data-confirm, public/js/op-actions.js) ──
    'confirm_queue_complete'      => 'Marquer cette consultation comme terminée ? Le ticket quitte la file.',
    'confirm_queue_no_show'       => 'Marquer ce patient comme absent ? Le ticket est clôturé en absence.',
    'confirm_queue_cancel'        => 'Annuler ce ticket ? Le patient est retiré de la file.',
    'confirm_appt_check_in'       => 'Enregistrer l\'arrivée de ce patient ? Un ticket de file est ouvert pour aujourd\'hui.',
    'confirm_appt_confirm'        => 'Confirmer ce rendez-vous ? Le patient est notifié.',
    'confirm_appt_no_show'        => 'Marquer ce rendez-vous comme absence ? Cette action est irréversible.',
    'confirm_team_approve'        => 'Approuver cette demande d\'adhésion avec le rôle sélectionné ?',
    'confirm_team_decline'        => 'Refuser cette demande d\'adhésion ?',
    'confirm_invite_reissue'      => 'Renvoyer cette invitation ? L\'ancien lien cesse de fonctionner.',
    'confirm_invite_revoke'       => 'Révoquer cette invitation ? Le lien cesse de fonctionner immédiatement.',
    'confirm_member_role'         => 'Changer le rôle de ce membre dans cet établissement ?',
    'confirm_member_deactivate'   => 'Désactiver ce membre ? Il perd l\'accès aux bureaux de cet établissement.',
    'confirm_member_reactivate'   => 'Réactiver ce membre dans cet établissement ?',
    'confirm_referral_send'       => 'Envoyer cette référence à l\'établissement destinataire ?',
    'confirm_referral_accept'     => 'Accepter cette référence ? L\'établissement référent et le patient sont informés.',
    'confirm_referral_reject'     => 'Rejeter cette référence avec le motif indiqué ?',
    'confirm_referral_complete'   => 'Marquer cette référence comme terminée ?',
    'confirm_referral_cancel'     => 'Annuler cette référence avec le motif indiqué ?',
    'confirm_blood_confirm'       => 'Confirmer cette demande de sang ? Les poches sont réservées au patient.',
    'confirm_blood_reject'        => 'Rejeter cette demande de sang ?',
    'confirm_blood_ready'         => 'Marquer les poches comme prêtes à être retirées ?',
    'confirm_blood_fulfil'        => 'Marquer cette demande comme servie ? Le stock est décrémenté.',
    'confirm_task_complete'       => 'Marquer cette tâche comme terminée ?',
    'confirm_task_escalate'       => 'Escalader cette tâche aux administrateurs de l\'établissement ?',
    'confirm_support_close'       => 'Marquer ce ticket comme résolu ?',
    'confirm_immunisation_void'   => 'Annuler cet enregistrement de vaccination ? Il reste au dossier, marqué comme annulé.',
    'confirm_bed_service'         => 'Changer l\'état de service de ce lit ?',

    // ── Étapes suivantes depuis une ligne, un ticket ou une tuile ──
    'open_chart'                  => 'Ouvrir le dossier',
    'open_triage'                 => 'Enregistrer le triage',
    'open_appointment'            => 'Ouvrir la réservation',
    'open_book'                   => 'Ouvrir l\'agenda des rendez-vous',
    'open_queue'                  => 'Ouvrir la file d\'attente',
    'open_reports'                => 'Ouvrir les rapports',
    'open_invoices'               => 'Ouvrir les factures',
    'open_wards'                  => 'Ouvrir services & lits',
    'open_billing'                => 'Ouvrir la facturation',
    'patient_next_steps'          => 'Étapes suivantes pour ce patient',
    'patient_next_steps_hint'     => 'Le dossier ne s\'ouvre qu\'avec une base de consentement ; le triage enregistre les constantes pour cette visite.',

    // ── États vides : que faire ensuite ──
    'empty_triage_hint'           => 'Personne n\'a été trié aujourd\'hui. Les constantes enregistrées ici ordonnent la file.',
    'empty_triage_action'         => 'Enregistrer une évaluation de triage',
    'empty_referrals_sent_hint'   => 'Cet établissement n\'a encore référé personne vers l\'extérieur.',
    'empty_referrals_received_hint' => 'Aucun autre établissement n\'a encore référé de patient ici.',
    'empty_referrals_action'      => 'Référer un patient',
    'empty_prescriber_action'     => 'Rédiger une ordonnance',
    'empty_messages_hint'         => 'Les patients ouvrent une conversation depuis leur propre portail ; elle apparaît ici dès l\'envoi.',
    'empty_blood_requests_action' => 'Faire une demande de sang',
    'empty_tasks_hint'            => 'Les tâches sont attribuées par les administrateurs de l\'établissement et par la plateforme ; rien ne vous attend.',
    'empty_support_hint'          => 'Décrivez un problème dans le formulaire ci-dessus et l\'équipe OpesCare répond ici.',
    'empty_appointments_action'   => 'Prendre un rendez-vous',
    'empty_waitlist_hint'         => 'Les patients rejoignent la liste d\'attente depuis leur propre portail quand aucun créneau ne leur convient.',
    'empty_queue_action'          => 'Enregistrer l\'arrivée d\'un patient',

    // ── Liste d\'attente ──
    'waitlist_showing'            => 'Affichage de :from–:to sur :total',

    // ── Tableau de bord établissement (/facility) ──
    'dash_welcome' => 'Bon retour',
    'dash_kpi_queue' => 'File d\'attente',
    'dash_kpi_queue_urgent' => ':count urgent(s) en attente',
    'dash_kpi_rx' => 'Ordonnances du jour',
    'dash_kpi_rx_sub' => 'Émises dans cet établissement aujourd\'hui',
    'dash_quick' => 'Actions rapides',
    'dash_qa_book' => 'Prendre rendez-vous',
    'dash_qa_checkin' => 'Enregistrer un patient',
    'dash_qa_triage' => 'Triage',
    'dash_qa_rx' => 'Nouvelle ordonnance',
    'dash_qa_referral' => 'Nouvelle référence',
    'dash_qa_search' => 'Rechercher un patient',
    'dash_queue_title' => 'File d\'attente du jour',
    'dash_queue_empty' => 'Personne en attente',
    'dash_col_arrived' => 'Arrivée',
    'dash_col_priority' => 'Priorité',
    'dash_col_status' => 'Statut',
    'dash_col_action' => 'Action',
    'dash_open' => 'Ouvrir',
    'dash_alerts' => 'Alertes',
    'dash_alert_urgent' => 'Patients urgents en attente',
    'dash_alert_urgent_sub' => 'Priorité urgence ou très urgente, pas encore vus',
    'dash_appts_title' => 'Rendez-vous du jour',
    'dash_appts_empty' => 'Aucun rendez-vous aujourd\'hui',
    'dash_appts_empty_text' => 'Aucun rendez-vous n\'est prévu dans cet établissement aujourd\'hui.',

    // ── Refonte des postes clinique (2026-10-18) ──
    'b_action_edit' => 'Modifier',
    'c_count_paren' => '(:count)',
    'd_ref_sec_patient' => 'Patient et urgence',
    'd_ref_sec_destination' => 'Destination du patient',
    'd_ref_sec_clinical' => 'Transmission clinique',
    'd_imm_doses' => '{0} Aucune dose enregistrée|{1} :count dose enregistrée|[2,*] :count doses enregistrées',
];
