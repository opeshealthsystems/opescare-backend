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

    /*
    |--------------------------------------------------------------------------
    | Cible de scan d'ordonnance — GET /verify/rx/{token}
    |--------------------------------------------------------------------------
    | La page sur laquelle arrive un pharmacien après avoir scanné une ordonnance imprimée.
    */

    'rx_title'             => 'Vérification d\'Ordonnance — OpesCare',
    'rx_heading'           => 'Vérification d\'Ordonnance',
    'rx_subheading'        => 'Voici l\'ordonnance correspondant au code scanné. Vérifiez la référence et le patient avant toute délivrance.',

    // Refus unique — invalide, expiré et retiré donnent la même réponse.
    'rx_invalid_title'     => 'Ordonnance Indisponible',
    'rx_invalid_body'      => 'Ce lien est invalide, a expiré ou a été retiré. Demandez au patient un code d\'ordonnance à jour.',

    // Référence
    'rx_reference_label'   => 'Référence de l\'Ordonnance',
    'rx_no_reference'      => 'Aucune référence attribuée',

    // Bandeaux de verdict
    'rx_do_not_dispense'   => 'Ne Pas Délivrer',
    'rx_retracted_body'    => 'Cette ordonnance a été retirée (:status) et n\'est plus une prescription valide. Ne délivrez aucun médicament sur cette base. Contactez l\'établissement émetteur si le patient a besoin d\'un remplacement.',
    'rx_expired_body'      => 'Cette ordonnance a dépassé sa date de validité et n\'est plus une prescription valide. Ne délivrez aucun médicament sur cette base. Le patient doit obtenir une nouvelle ordonnance auprès de son prescripteur.',
    'rx_dispensed_title'   => 'Déjà Délivrée',
    'rx_dispensed_body'    => 'Cette ordonnance a déjà été délivrée en totalité. Ne la délivrez pas une seconde fois.',
    'rx_valid_title'       => 'Ordonnance Valide',
    'rx_valid_body'        => 'Cette ordonnance est active et peut être délivrée. Confirmez le nom du patient et son ID de Santé ci-dessous avant de remettre tout médicament.',

    // Sections
    'rx_section_patient'   => 'Patient',
    'rx_section_order'     => 'Ordonnance',
    'rx_section_medicines' => 'Médicaments Prescrits',

    // Champs
    'rx_patient'           => 'Nom',
    'rx_health_id'         => 'ID de Santé',
    'rx_facility'          => 'Établissement Émetteur',
    'rx_prescriber'        => 'Prescripteur',
    'rx_prescribed_at'     => 'Prescrite le',
    'rx_expires_at'        => 'Valable Jusqu\'au',
    'rx_no_expiry'         => 'Aucune date d\'expiration enregistrée',
    'rx_status'            => 'Statut',
    'rx_dispensed_at'      => 'Délivrée le',
    'rx_not_dispensed'     => 'Pas encore délivrée',
    'rx_not_recorded'      => 'Non renseigné',

    // Tableau des médicaments
    'rx_col_medicine'      => 'Médicament',
    'rx_col_dose'          => 'Dose',
    'rx_col_frequency'     => 'Fréquence',
    'rx_col_route'         => 'Voie',
    'rx_col_duration'      => 'Durée',
    'rx_col_quantity'      => 'Qté',
    'rx_col_status'        => 'Statut',
    'rx_days'              => ':count jour(s)',
    'rx_no_items'          => 'Aucun médicament n\'est enregistré sur cette ordonnance.',

    // Audit
    'rx_audit_note'        => 'Cet accès a été consigné. Le patient voit chaque ouverture de cette ordonnance et peut retirer le lien à tout moment depuis son portail.',

    // Libellés de statut
    'rx_status_active'              => 'Active',
    'rx_status_pending'             => 'En attente',
    'rx_status_partially_dispensed' => 'Partiellement délivrée',
    'rx_status_dispensed'           => 'Délivrée',
    'rx_status_expired'             => 'Expirée',
    'rx_status_cancelled'           => 'Annulée',
    'rx_status_voided'              => 'Annulée pour vice',
    'rx_status_entered_in_error'    => 'Saisie erronée',
    'rx_status_amended'             => 'Modifiée — remplacée',
    'rx_status_unknown'             => 'Inconnu',
];
