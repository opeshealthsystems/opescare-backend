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
    'kpi_hint_critical'        => 'Levées par la paillasse ; clôturées ici quand le laboratoire consigne l’appel',
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
    'critical_subtitle' => 'Les plus anciennes d’abord. Téléphonez la valeur au clinicien prescripteur (ou au patient, pour un examen demandé par le patient) et consignez l’appel ; d’ici là, la valeur apparaît ici.',
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

    // ── Premier usage ─────────────────────────────────────────────────────
    'first_run_title'          => 'Aucune commande ni demande n’est encore parvenue à ce laboratoire',
    'first_run_body'           => 'Les chiffres ci-dessous sont à zéro parce que rien n’a été envoyé ici, et non parce que la journée est calme. Le travail arrive à un laboratoire de trois façons :',
    'first_run_step_catalogue' => 'Les patients vous trouvent sur le moteur public d’examens de laboratoire une fois vos examens et tarifs saisis au catalogue et publiés (voir ci-dessous).',
    'first_run_step_requests'  => 'Les patients envoient une demande d’examen depuis l’application ; elle apparaît dans Demandes des patients pour que vous l’approuviez ou la refusiez.',
    'first_run_step_walkin'    => 'Les cliniciens de cet établissement prescrivent des examens depuis l’application clinicien ; ils arrivent sur la liste de travail.',

    // ── Publication sur le moteur public ──────────────────────────────────
    'pub_title'            => 'Sur le moteur public d’examens de laboratoire',
    'pub_unlinked'         => 'Ce laboratoire n’est lié à aucune fiche publique de l’annuaire : aucun de ses examens ne peut apparaître sur le moteur. Revendiquez votre fiche ou demandez à un administrateur OpesCare de la lier.',
    'pub_listing_inactive' => 'La fiche « :listing » n’est pas active : le moteur ne l’affiche pas, même si des examens sont publiés.',
    'pub_listing'          => 'Fiche publique',
    'pub_published'        => 'Examens proposés sur le moteur',
    'pub_catalogue_tests'  => 'Examens de laboratoire (LOINC) actifs au catalogue',
    'pub_last'             => 'Dernière publication',
    'pub_never'            => 'Jamais',
    'pub_no_tests'         => 'Le catalogue ne contient aucun examen de laboratoire LOINC actif : il n’y a rien à publier.',
    'pub_open_catalogue'   => 'Ouvrir le catalogue',
    'pub_open_finder'      => 'Voir le moteur public',

    // ── Rappel d’une valeur critique ──────────────────────────────────────
    'callback'                      => 'Consigner l’appel',
    'callback_informed'             => 'Personne informée (nom et fonction)',
    'callback_informed_placeholder' => 'ex. Dr Mbarga, médecin prescripteur',
    'callback_note'                 => 'Note (facultative)',
    'callback_hint'                 => 'À consigner uniquement après relecture de la valeur par la personne nommée. L’alerte est clôturée et ne peut pas être rouverte ; le résultat n’est pas modifié.',
    'callback_submit'               => 'Clôturer l’alerte',
    'callback_recorded'             => 'Appel consigné. L’alerte est clôturée.',

    // ── Page publique de recherche d’examens ──────────────────────────────
    'finder' => [
        'title'              => 'Trouver un examen de laboratoire',
        'meta_description'   => 'Recherchez les examens de laboratoire et les tarifs que les laboratoires ont publiés sur le réseau OpesCare.',
        'badge'              => 'Examens de laboratoire',
        'intro'              => 'Recherchez les examens que les laboratoires ont publiés, avec leurs propres tarifs. OpesCare ne fixe pas les prix et ne vérifie pas la paillasse — chaque résultat est la publication du laboratoire lui-même.',
        'search_label'       => 'Quel examen vous faut-il ?',
        'search_placeholder' => 'ex. Hémoglobine, Glycémie, 718-7…',
        'results_count'      => '{1} :count laboratoire a publié cet examen.|[2,*] :count laboratoires ont publié cet examen.',
        'published_line'     => 'Publié par le laboratoire · :time',
        'price_fcfa'         => ':price FCFA',
        'no_price'           => 'Aucun tarif publié',
        'turnaround'         => 'Délai : :time',
        'before_you_travel'  => 'Certains examens exigent une prescription. Appelez le laboratoire pour confirmer les conditions et le tarif avant de vous déplacer.',
        'empty_title'        => 'Aucun laboratoire n’a encore publié cet examen.',
        'empty_body'         => 'Cela ne veut pas dire qu’aucun laboratoire ne le réalise — seulement qu’aucun sur le réseau ne l’a publié. Appelez directement un laboratoire proche.',
        'empty_cta'          => 'Trouver des laboratoires proches',
    ],

    // ── Refus ─────────────────────────────────────────────────────────────
    'err' => [
        'request_not_found'  => 'Cette demande n’a pas été adressée à ce laboratoire.',
        'not_released'       => 'Seule une commande libérée a un rapport à imprimer.',
        'reason_required'    => 'Indiquez le motif.',
        'reject_released'    => 'Cette commande a été libérée et ne peut pas être rejetée. Rétractez-la via le dossier clinique.',
        'reject_has_results' => 'Un résultat a déjà été saisi sur cette commande. Il peut être corrigé, pas rejeté.',
        'alert_not_found'    => 'Cette valeur critique n’a pas été levée dans ce laboratoire.',
        'informed_required'  => 'Indiquez la personne à qui la valeur a été relue.',
        'callback_already'   => 'Cette alerte est déjà clôturée.',
    ],

    // Public lab-test search API disclaimer (CareMapController::searchTests).
    'api_test_disclaimer' => 'Certains examens nécessitent une prescription médicale. Confirmez les conditions auprès du laboratoire avant de vous déplacer.',
];
