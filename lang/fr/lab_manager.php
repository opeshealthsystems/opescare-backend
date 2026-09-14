<?php

/*
|--------------------------------------------------------------------------
| Bureau du responsable de laboratoire (portail web, /facility/lab/dashboard, /requests)
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/lab_manager.php (scripts/i18n-audit.php).
|
| `tat_none` est la chaîne honnête : quand aucune commande libérée dans la
| fenêtre ne porte à la fois une heure de prélèvement et une heure de
| libération, il n’y a pas de délai à afficher, et la page doit le dire
| plutôt que d’afficher 0.
*/

return [

    'nav_dashboard' => 'Tableau de bord du laboratoire',
    'nav_requests'  => 'Demandes des patients',

    // ── Tableau de bord ───────────────────────────────────────────────────
    'dashboard_title'    => 'Tableau de bord du laboratoire',
    'dashboard_subtitle' => 'Le laboratoire de cet établissement, en ce moment. Chaque chiffre est lu dans la liste de travail en direct.',
    'open_worklist'      => 'Ouvrir la liste de travail',
    'open_collection'    => 'Prélèvements',
    'open_requests'      => 'Demandes des patients',

    'kpi_awaiting_sample'      => 'En attente de prélèvement',
    'kpi_sample_in'            => 'Échantillon reçu, rien saisi',
    'kpi_in_progress'          => 'En cours',
    'kpi_awaiting_validation'  => 'Libérés, en attente de validation',
    'kpi_released_today'       => 'Libérés aujourd’hui',
    'kpi_validated_today'      => 'Validés aujourd’hui',
    'kpi_collected_today'      => 'Échantillons prélevés aujourd’hui',
    'kpi_rejected_today'       => 'Échantillons rejetés aujourd’hui',
    'kpi_critical_open'        => 'Valeurs critiques non encore acquittées',
    'kpi_requests_pending'     => 'Demandes de patients en attente',
    'kpi_hint_column'          => 'Colonne de la liste de travail',
    'kpi_hint_today'           => 'Depuis minuit',
    'kpi_hint_validated'       => 'Commandes dont la dernière modification aujourd’hui est la signature de validation',
    'kpi_hint_critical'        => 'Levées par la paillasse ; le clinicien prescripteur acquitte depuis sa boîte de résultats',
    'kpi_hint_requests'        => 'Envoyées par les patients depuis l’application, en attente de décision',

    'tat_title'        => 'Délai de rendu',
    'tat_subtitle'     => 'Échantillon prélevé → résultats libérés, sur les :days derniers jours.',
    'tat_median'       => 'Médiane',
    'tat_mean'         => 'Moyenne',
    'tat_orders'       => 'Commandes libérées mesurées',
    'tat_none'         => 'Données insuffisantes : aucune commande libérée dans les :days derniers jours ne porte à la fois une heure de prélèvement et une heure de libération.',
    'tat_minutes'      => ':minutes min',
    'tat_hours'        => ':hours h :minutes min',
    'tat_days'         => ':days j :hours h',

    'critical_title'    => 'Valeurs critiques en attente d’acquittement',
    'critical_subtitle' => 'Les plus anciennes d’abord. Le clinicien qui a prescrit l’examen acquitte depuis sa boîte de résultats ; d’ici là, la valeur apparaît ici.',
    'critical_empty'    => 'Aucune valeur critique n’attend d’acquittement.',
    'col_raised'        => 'Levée le',
    'col_patient'       => 'Patient',
    'col_test'          => 'Examen',
    'col_value'         => 'Valeur',
    'col_threshold'     => 'Seuil franchi',
    'col_open'          => 'Ouvrir',
    'open_order'        => 'Ouvrir la commande',

    // ── Demandes des patients ─────────────────────────────────────────────
    'requests_title'    => 'Demandes d’examens des patients',
    'requests_subtitle' => 'Examens que les patients de cet établissement lui ont demandés. Approuver crée une vraie commande sur la liste de travail ; refuser explique au patient pourquoi.',
    'requests_empty'    => 'Aucune demande dans cette colonne.',
    'request_status'    => [
        'submitted' => 'En attente',
        'approved'  => 'Approuvée',
        'declined'  => 'Refusée',
        'cancelled' => 'Retirée',
    ],
    'priority'          => [
        'stat'    => 'STAT',
        'urgent'  => 'Urgent',
        'routine' => 'Routine',
    ],
    'col_requested'     => 'Demandé le',
    'col_priority'      => 'Priorité',
    'col_notes'         => 'Note du patient',
    'col_decision'      => 'Décision',
    'col_actions'       => 'Actions',
    'approve'           => 'Approuver',
    'approve_hint'      => 'Crée une commande dans cet établissement, prescrite par vous, à l’urgence demandée par le patient.',
    'decline'           => 'Refuser',
    'decline_reason'    => 'Motif que le patient lira',
    'submit_decline'    => 'Refuser la demande',
    'decided_by'        => 'Par :name le :at',
    'order_raised'      => 'Commande créée',
    'request_approved'  => 'Demande approuvée. La commande est sur la liste de travail et le patient a été informé.',
    'request_declined'  => 'Demande refusée. Le patient a été informé du motif.',

    // ── Rejet d’échantillon ───────────────────────────────────────────────
    'reject'            => 'Rejeter l’échantillon',
    'reject_hint'       => 'Définitif. La commande est clôturée comme rejetée, le motif y est inscrit, et le patient ainsi que le clinicien prescripteur sont informés qu’un nouvel échantillon est nécessaire. Possible uniquement avant toute saisie de résultat.',
    'reject_reason'     => 'Motif du rejet',
    'submit_reject'     => 'Rejeter cet échantillon',
    'rejected'          => 'Échantillon rejeté. La commande est clôturée et le patient et le clinicien ont été informés.',
    'rejected_note'     => 'Cet échantillon a été rejeté : :reason',

    // ── Rapport imprimé ───────────────────────────────────────────────────
    'print_report'      => 'Imprimer le rapport',
    'print_report_hint' => 'La même feuille OpesCare Secure que le patient télécharge, générée maintenant avec un code de vérification.',

    // ── Notifications ─────────────────────────────────────────────────────
    'notify' => [
        'rejected_patient_title'   => 'Un nouvel échantillon est nécessaire pour :test',
        'rejected_patient_body'    => ':facility n’a pas pu utiliser l’échantillon prélevé. Motif : :reason. Merci de revenir pour un nouveau prélèvement.',
        'rejected_clinician_title' => 'Échantillon rejeté : :test',
        'rejected_clinician_body'  => ':facility a rejeté l’échantillon pour :test. Motif : :reason. Un nouvel échantillon est nécessaire.',
    ],

    // ── Refus ─────────────────────────────────────────────────────────────
    'err' => [
        'request_not_found'  => 'Cette demande n’a pas été adressée à ce laboratoire.',
        'not_released'       => 'Seule une commande libérée a un rapport à imprimer.',
        'reason_required'    => 'Indiquez le motif.',
        'reject_released'    => 'Cette commande a été libérée et ne peut pas être rejetée. Rétractez-la via le dossier clinique.',
        'reject_has_results' => 'Un résultat a déjà été saisi sur cette commande. Il peut être corrigé, pas rejeté.',
    ],
];
