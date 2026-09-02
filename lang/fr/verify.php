<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpesCare Outil de Vérification Prestataire — Français
    |--------------------------------------------------------------------------
    */

    // Health ID lookup page
    'health_id_title'      => 'Vérification de l\'ID de Santé — OpesCare',
    'badge_provider_tool'  => 'Outil de Vérification Prestataire',
    'health_id_heading'    => 'Vérification de l\'ID de Santé',
    'health_id_subheading' => 'Recherchez l\'identité vérifiée d\'un patient et le statut de ses dossiers actifs. Réservé aux prestataires de soins autorisés.',

    // QR verification page
    'qr_title'             => 'Vérification QR — OpesCare',
    'qr_heading'           => 'Vérification QR de l\'ID de Santé',
    'qr_subheading'        => 'Ce jeton révèle l\'identité vérifiée du patient à des fins cliniques uniquement.',
    'qr_processing'        => 'Validation du jeton…',
    'qr_processing_note'   => 'Cela prend généralement moins d\'une seconde.',
    'qr_expired_title'     => 'Code QR Expiré',
    'qr_expired_body'      => 'Ce code QR a expiré. Demandez au patient d\'en générer un nouveau depuis son portail patient.',
    'qr_invalid_title'     => 'Jeton Invalide',
    'qr_invalid_body'      => 'Ce jeton n\'est pas reconnu. Il a peut-être été altéré ou déjà utilisé. Contactez le support si le problème persiste.',
    'qr_token_expired_ui'  => 'Jeton introuvable ou expiré. Demandez au patient de régénérer son code QR.',

    // Shared disclaimer
    'disclaimer'           => 'Cet outil est réservé aux prestataires de soins autorisés. Chaque vérification est consignée et auditable par le patient. L\'accès non autorisé est un délit pénal.',

    // Form fields
    'field_health_id'          => 'ID de Santé du Patient',
    'field_health_id_hint'     => 'Saisissez l\'ID de Santé alphanumérique tel qu\'il apparaît sur la carte ou le code QR du patient.',
    'field_purpose'            => 'Motif d\'Accès',
    'field_purpose_placeholder'=> '— Sélectionner le motif —',
    'field_purpose_hint'       => 'Ce motif est enregistré avec votre identifiant personnel à des fins d\'audit.',

    // Purpose options
    'purpose_emergency'    => 'Soins d\'Urgence',
    'purpose_scheduled'    => 'Consultation Clinique Planifiée',
    'purpose_lab'          => 'Livraison de Résultats de Laboratoire',
    'purpose_prescription' => 'Délivrance d\'Ordonnance',
    'purpose_insurance'    => 'Traitement de Demande d\'Assurance',
    'purpose_referral'     => 'Référence / Transfert',
    'purpose_other'        => 'Autre',

    // Submit
    'btn_verify'           => 'Vérifier l\'ID de Santé',

    // Result panel
    'result_verified'      => 'Identité Vérifiée',
    'result_name'          => 'Nom',
    'result_health_id'     => 'ID de Santé',
    'result_dob'           => 'Date de Naissance',
    'result_blood_type'    => 'Groupe Sanguin',
    'allergies'            => 'Allergies :',
    'audit_note'           => 'Cet accès a été consigné avec vos identifiants prestataire et est visible par le patient.',

    // Footer
    'footer_note'          => 'Besoin d\'aide ?',
    'footer_help'          => 'Visiter le Centre d\'Aide',
    'footer_contact'       => 'Contacter le Support',
    'footer_manual_verify' => 'Recherche Manuelle d\'ID',

    // Erreurs
    'error_not_found'      => 'Aucun ID de Santé vérifié ne correspond à l\'identifiant fourni. Veuillez vérifier l\'ID et réessayer.',
];
