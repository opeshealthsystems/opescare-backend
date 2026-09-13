<?php

/**
 * Bureau des droits des patients — demandes d'export et de rectification.
 *
 * La formulation juridique compte ici. Ces textes sont ceux qu'un patient voit
 * lorsqu'une demande statutaire au titre de la loi n° 2010/012 est accordée ou
 * refusée : la traduction est réelle, pas approximative.
 */
return [

    // Navigation et cadre de page
    'nav'                => 'Droits des patients',
    'breadcrumb_home'    => 'Administration',
    'index_title'        => 'Demandes relatives aux droits des patients',
    'index_subtitle'     => 'Demandes d\'export de données et de rectification de dossier déposées par les patients au titre de la loi n° 2010/012. Chaque décision exige un motif et est enregistrée.',
    'nav_log'            => 'Journal des décisions',
    'log_title'          => 'Journal des décisions de gouvernance',
    'log_subtitle'       => 'Chaque acceptation et chaque refus prononcés à ce bureau, avec le motif donné et la personne qui l\'a donné.',
    'back_to_queue'      => 'Retour à la file d\'attente',

    // Compteurs
    'kpi_export_pending'     => 'Exports en attente de décision',
    'kpi_export_approved'    => 'Exports acceptés',
    'kpi_correction_pending' => 'Rectifications en attente de décision',
    'kpi_correction_refused' => 'Rectifications refusées',
    'oldest_pending'         => 'Demande la plus ancienne encore en attente : :days jour(s)',

    // Filtres
    'filter_status'      => 'Statut',
    'filter_all'         => 'Tous les statuts',
    'btn_filter'         => 'Filtrer',
    'btn_clear'          => 'Effacer',

    // Sections
    'section_exports'     => 'Demandes d\'export de données',
    'section_corrections' => 'Demandes de rectification de dossier',

    // Colonnes du tableau
    'col_filed'          => 'Déposée le',
    'col_type'           => 'Type',
    'col_status'         => 'Statut',
    'col_patient'        => 'Patient (identifiant santé)',
    'col_resource'       => 'Enregistrement contesté',
    'col_reason'         => 'Motif invoqué par le patient',
    'col_decision'       => 'Décision',
    'col_decided_by'     => 'Décidée par',
    'col_action'         => 'Action',
    'col_actor'          => 'Auteur',
    'col_when'           => 'Date',

    // Statuts
    'status_pending'     => 'En attente de décision',
    'status_approved'    => 'Acceptée',
    'status_rejected'    => 'Refusée',
    'status_downloaded'  => 'Téléchargé par le patient',
    'status_expired'     => 'Expirée',

    // Détail d'un export
    'export_title'       => 'Demande d\'export de données',
    'export_subtitle'    => 'Une copie intégrale du dossier de ce patient. L\'acceptation ouvre un téléchargement pendant 24 heures, accessible au patient qui a déposé la demande et à personne d\'autre.',
    'export_type'        => 'Export demandé',
    'export_scope'       => 'Périmètre demandé',
    'export_expires'     => 'Fermeture de la fenêtre de téléchargement',
    'export_no_expiry'   => 'Aucune fenêtre de téléchargement ouverte à ce jour',
    'export_decision_reason' => 'Motif enregistré avec la décision',

    // Détail d'une rectification
    'correction_title'      => 'Demande de rectification de dossier',
    'correction_subtitle'   => 'Une rectification est un amendement, jamais un écrasement. Ce que le clinicien a consigné reste au dossier et reste consultable ; l\'acceptation le marque comme saisi par erreur et ajoute un amendement à côté.',
    'correction_target'     => 'L\'enregistrement contesté',
    'correction_target_gone' => 'L\'enregistrement contesté est introuvable.',
    'correction_current'    => 'Actuellement consigné',
    'correction_decisions'  => 'Décisions prises',
    'correction_amendment_note' => 'L\'acceptation ne supprime ni ne modifie aucune valeur consignée. La ligne d\'origine conserve tous ses champs ainsi que le clinicien qui l\'a saisie.',

    // Formulaires de décision
    'form_reason'            => 'Motif de la décision',
    'form_reason_help'       => 'Obligatoire, et conservé de façon permanente. C\'est ce qui est communiqué au patient.',
    'form_reason_placeholder' => 'Expliquez la décision en termes clairs…',
    'btn_approve'            => 'Accepter',
    'btn_refuse'             => 'Refuser',
    'btn_open'               => 'Ouvrir',
    'btn_view_history'       => 'Historique',

    // Refus appliqués par ce bureau
    'refusal_not_pending'      => 'Cette demande a déjà fait l\'objet d\'une décision.',
    'refusal_type_unsupported' => 'Ce bureau ne peut pas amender un enregistrement de ce type en toute sécurité, et il ne fera pas semblant de le pouvoir. La demande doit être traitée par l\'établissement qui détient l\'enregistrement, et refusée ici avec cette explication.',
    'refusal_record_missing'   => 'L\'enregistrement visé par cette demande n\'existe plus : il n\'y a rien à amender.',
    'refusal_heading'          => 'Cette demande ne peut pas être acceptée ici',

    // Historique
    'history_title'      => 'Historique',
    'history_empty'      => 'Aucune décision n\'a encore été enregistrée pour cette demande.',

    // Messages flash
    'flash_export_approved'     => 'Export accepté. Le patient peut désormais télécharger son dossier et en a été informé.',
    'flash_export_refused'      => 'Export refusé. Le motif a été enregistré et le patient en a été informé.',
    'flash_correction_approved' => 'Rectification acceptée. L\'enregistrement a été amendé, l\'original est préservé et le patient en a été informé.',
    'flash_correction_refused'  => 'Rectification refusée. Le motif a été enregistré et le patient en a été informé.',
    'flash_not_pending'         => 'Cette demande a déjà fait l\'objet d\'une décision.',
    'flash_actor_unresolved'    => 'Votre compte n\'a pas pu être identifié : aucune décision n\'a été enregistrée.',

    // États vides
    'empty_exports_heading'     => 'Aucune demande d\'export',
    'empty_exports_body'        => 'Aucun patient n\'a encore demandé une copie de son dossier.',
    'empty_corrections_heading' => 'Aucune demande de rectification',
    'empty_corrections_body'    => 'Aucun patient n\'a encore contesté un enregistrement.',
    'empty_log_heading'         => 'Aucune décision',
    'empty_log_body'            => 'Rien n\'a été accepté ni refusé à ce bureau.',

    // Ce qui est communiqué au patient
    'notify_export_approved_title'     => 'Votre export de dossier est prêt',
    'notify_export_approved_body'      => 'Votre demande de copie de votre dossier de santé a été acceptée. Vous pouvez la télécharger depuis la section confidentialité de l\'application dans les 24 heures.',
    'notify_export_refused_title'      => 'Votre export de dossier a été refusé',
    'notify_export_refused_body'       => 'Votre demande de copie de votre dossier de santé a été refusée. Motif : :reason',
    'notify_correction_approved_title' => 'Votre demande de rectification a été acceptée',
    'notify_correction_approved_body'  => 'L\'enregistrement que vous avez contesté a été marqué comme saisi par erreur et un amendement a été ajouté. Ce qui avait été consigné à l\'origine est conservé, comme la loi l\'exige.',
    'notify_correction_refused_title'  => 'Votre demande de rectification a été refusée',
    'notify_correction_refused_body'   => 'L\'enregistrement que vous avez contesté n\'a pas été modifié. Motif : :reason',

    // Texte inscrit dans l'amendement lui-même
    'amendment_stamp' => "Amendement enregistré le :date à la suite d'une demande de rectification du patient.\nContesté par le patient : :disputed\nDécision du réviseur : :decision\nLa note d'origine est conservée et marquée comme saisie par erreur.",

];
