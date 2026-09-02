<?php

return [
    // Types d'organisation (public + admin)
    'org_types' => [
        'facility'  => 'Établissement de santé',
        'insurer'   => 'Assureur',
        'lab'       => 'Laboratoire',
        'pharmacy'  => 'Pharmacie',
        'developer' => 'Développeur / Intégrateur',
        'other'     => 'Autre',
    ],

    // Statuts du pipeline
    'statuses' => [
        'new'       => 'Nouveau',
        'contacted' => 'Contacté',
        'qualified' => 'Qualifié',
        'won'       => 'Gagné',
        'lost'      => 'Perdu',
    ],

    // Page publique « Demander une démo »
    'demo' => [
        'page_title' => 'Demander une démo | OpesCare',
        'meta_description'  => 'Réservez une démo personnalisée d\'OpesCare pour votre établissement, assureur, laboratoire, pharmacie ou équipe de développement.',
        'nav_label'         => 'Demander une démo',
        'badge'             => 'Pour les organisations',
        'heading'           => 'Découvrez OpesCare en action',
        'subheading'        => 'Parlez-nous de votre organisation et notre équipe organisera une présentation sur mesure.',
        'form_title'        => 'Demandez votre démo',
        'form_subtitle'     => 'Nous répondons généralement sous un jour ouvrable.',
        'field_org_name'        => 'Nom de l\'organisation',
        'field_org_name_ph'     => 'ex. Centre Hospitalier de Yaoundé',
        'field_org_type'        => 'Type d\'organisation',
        'field_org_type_ph'     => 'Sélectionnez le type d\'organisation',
        'field_name'            => 'Votre nom',
        'field_name_ph'         => 'Nom complet',
        'field_email'           => 'E-mail professionnel',
        'field_email_ph'        => 'vous@organisation.org',
        'field_phone'           => 'Téléphone',
        'field_phone_ph'        => 'Facultatif',
        'field_message'         => 'Que souhaitez-vous voir ?',
        'field_message_ph'      => 'Parlez-nous de vos objectifs, de la taille de votre équipe et de vos besoins spécifiques.',
        'submit'                => 'Demander une démo',
        'success_title'         => 'Merci — notre équipe vous contactera',
        'success_body'          => 'Nous avons bien reçu votre demande et un membre de notre équipe vous contactera sous peu pour planifier votre démo.',
        'success_cta'           => 'Retour aux tarifs',
    ],

    // Boîte de réception des leads (admin)
    'admin' => [
        'page_title'        => 'Leads',
        'breadcrumb'        => 'Leads',
        'heading'           => 'Leads et pipeline de démos',
        'description'       => 'Demandes de démo captées depuis le site marketing. Les plus récentes en premier.',
        'filter_all'        => 'Tous les statuts',
        'filter_label'      => 'Filtrer par statut',
        'filter_apply'      => 'Filtrer',
        'filter_reset'      => 'Réinitialiser',
        'stat_total'        => 'Total des leads',
        'col_organization'  => 'Organisation',
        'col_type'          => 'Type',
        'col_contact'       => 'Contact',
        'col_source'        => 'Source',
        'col_status'        => 'Statut',
        'col_date'          => 'Date',
        'col_actions'       => 'Actions',
        'empty'             => 'Aucun lead pour le moment. Les demandes de démo apparaîtront ici.',
        'action_update'     => 'Mettre à jour',
        'modal_title'       => 'Mettre à jour le statut du lead',
        'modal_status'      => 'Statut',
        'modal_note'        => 'Note (facultatif)',
        'modal_note_ph'     => 'Ajouter une note sur cette mise à jour…',
        'modal_cancel'      => 'Annuler',
        'modal_save'        => 'Enregistrer',
        'flash_updated'     => 'Lead mis à jour.',
    ],
];
