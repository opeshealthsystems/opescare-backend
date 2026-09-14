<?php

/*
|--------------------------------------------------------------------------
| Downloadable patient documents
|--------------------------------------------------------------------------
|
| The lab report, prescription, immunisation record and discharge summary a
| patient can download for themselves. Each is rendered at the moment it is
| asked for and never stored, so these strings describe the record as it is
| now rather than as it was when some file was written.
|
| EN and FR are generated from one source and must stay 1:1
| (scripts/i18n-audit.php enforces it).
|
*/

return [

    'issued'            => 'Délivré le',
    'issued_to'         => 'Délivré à',
    'health_id'         => 'Identifiant santé',
    'date_of_birth'     => 'Date de naissance',
    'sex'               => 'Sexe',
    'not_recorded'      => 'Non renseigné',
    'none_recorded'     => 'Rien enregistré',
    'reference'         => 'Référence',
    'facility'          => 'Établissement',
    'status'            => 'Statut',
    'date'              => 'Date',
    'generated_note'    => 'Généré à partir du dossier en direct au moment du téléchargement. Ce n’est pas une copie archivée.',
    'lab_title'         => 'Compte rendu de laboratoire',
    'lab_subtitle'      => 'Résultats tels que validés par le laboratoire',
    'lab_test'          => 'Analyse',
    'lab_ordered'       => 'Prescrite le',
    'lab_reported'      => 'Rendue le',
    'lab_ordered_by'    => 'Prescrite par',
    'lab_analyte'       => 'Paramètre',
    'lab_result'        => 'Résultat',
    'lab_unit'          => 'Unité',
    'lab_range'         => 'Valeurs de référence',
    'lab_flag'          => 'Signalement',
    'lab_no_results'    => 'Cette demande ne comporte aucun résultat validé.',
    'lab_interpretation'=> 'Interprétation',
    'lab_advice'        => 'Discutez de ces résultats avec le clinicien qui les a prescrits. Une valeur hors des valeurs de référence n’est pas un diagnostic.',
    'rx_title'          => 'Ordonnance',
    'rx_subtitle'       => 'Telle que rédigée par le prescripteur',
    'rx_prescriber'     => 'Prescripteur',
    'rx_prescribed'     => 'Prescrite le',
    'rx_expires'        => 'Valable jusqu’au',
    'rx_drug'           => 'Médicament',
    'rx_dose'           => 'Dose',
    'rx_route'          => 'Voie',
    'rx_frequency'      => 'Fréquence',
    'rx_duration'       => 'Durée',
    'rx_quantity'       => 'Quantité',
    'rx_days'           => ':count jours',
    'rx_no_items'       => 'Cette ordonnance ne comporte aucune ligne de médicament.',
    'rx_dispensed'      => 'Délivrée le',
    'rx_pharmacy'       => 'Pharmacie',
    'rx_not_a_supply'   => 'Cette feuille indique ce qui a été prescrit. Elle ne prouve pas qu’un médicament a été délivré.',
    'imm_title'         => 'Carnet de vaccination',
    'imm_subtitle'      => 'Toutes les doses enregistrées pour cet identifiant santé',
    'imm_vaccine'       => 'Vaccin',
    'imm_dose'          => 'Dose',
    'imm_given'         => 'Administré le',
    'imm_batch'         => 'Lot',
    'imm_no_doses'      => 'Aucune vaccination n’est enregistrée pour cet identifiant santé.',
    'imm_completeness'  => 'Ceci présente ce qui a été enregistré sur cette plateforme. Une dose administrée ailleurs et jamais enregistrée n’y figurera pas.',
    'dis_title'         => 'Compte rendu d’hospitalisation',
    'dis_subtitle'      => 'Admission et sortie telles qu’enregistrées',
    'dis_admitted'      => 'Admis le',
    'dis_discharged'    => 'Sorti le',
    'dis_ward'          => 'Service',
    'dis_reason'        => 'Motif d’admission',
    'dis_outcome'       => 'Motif de sortie',
    'dis_destination'   => 'Destination à la sortie',
    'dis_length'        => 'Durée du séjour',

    'available_title' => 'Documents que vous pouvez télécharger',
    'available_hint' => 'Chacun est généré au moment du téléchargement, à partir de votre dossier tel qu’il est. Rien n’est archivé.',

    'cert_title' => 'Certificat',
    'cert_subtitle' => 'Tel que délivré et enregistré',
    'document_id' => 'Numéro de document',
    'cert_valid_until' => 'Valable jusqu’au',
    'cert_no_expiry' => 'Sans date d’expiration',
    'cert_printed' => 'Imprimé le',
    'cert_particulars' => 'Détails',
    'cert_state_active' => 'Valide',
    'cert_state_expired' => 'Expiré',
    'cert_state_revoked' => 'Révoqué',
    'cert_verify' => 'Code de vérification :code — à vérifier sur la page de vérification OpesCare.',

    /*
    | Fiches de service / documents cliniques à la demande assemblés par
    | App\Services\Documents\DocumentDataAssembler et DocumentOnDemandAssembler.
    | Ce sont les valeurs imprimées quand le dossier ne contient rien.
    */

    // Valeurs de remplacement communes
    'not_assigned'          => 'Non attribué',
    'na'                    => 'N/D',
    'pending'               => 'En attente',
    'pending_documentation' => 'Documentation en attente',
    'pending_assessment'    => 'Évaluation en attente',
    'none'                  => 'Aucun',
    'none_known'            => 'Aucune connue',
    'none_documented'       => 'Aucune documentée',
    'not_documented'        => 'Non documenté',
    'not_specified'         => 'Non précisé',
    'not_set'               => 'Non réglé',
    'no_records'            => 'Aucun enregistrement',
    'unknown'               => 'Inconnu',
    'unknown_patient'       => 'Patient inconnu',
    'per_prescription'      => 'Selon l’ordonnance',
    'per_order'             => 'Selon la prescription',
    'monitoring'            => 'Sous surveillance',
    'staff'                 => 'Personnel',
    'ward'                  => 'Service',
    'room_air'              => 'Air ambiant',
    'skin_intact'           => 'Intacte',
    'diet_normal'           => 'Normal',
    'laboratory'            => 'Laboratoire',
    'urgency_routine'       => 'ROUTINE',
    'gestational_age'       => ':weeks sem :days j',
    'date_range'            => ':from – :to',

    // Rôles imprimés tant qu’aucun clinicien nommé n’a signé
    'role_assigned_nurse'         => 'Infirmier(ère) désigné(e)',
    'role_attending_physician'    => 'Médecin traitant',
    'role_resident'               => 'Interne',
    'role_nursing_team'           => 'Équipe soignante',
    'role_nursing_staff'          => 'Personnel infirmier',
    'role_assigned_midwife'       => 'Sage-femme désignée',
    'role_attending_obstetrician' => 'Obstétricien(ne) traitant(e)',
    'role_neonatologist'          => 'Néonatologiste',
    'role_attending_surgeon'      => 'Chirurgien(ne) traitant(e)',
    'role_anaesthetist'           => 'Anesthésiste',
    'role_scrub_nurse'            => 'Infirmier(ère) instrumentiste',
    'role_admitting_nurse'        => 'Infirmier(ère) d’admission',
    'role_requesting_clinician'   => 'Clinicien demandeur',
    'role_wound_care_nurse'       => 'Infirmier(ère) référent(e) plaies',
    'shift_outgoing'              => 'Équipe sortante',
    'shift_incoming'              => 'Équipe entrante',

    // Valeurs par défaut propres à chaque document
    'icu_bed_default'        => 'Lit de réanimation',
    'nicu_bed_default'       => 'Incubateur de néonatologie',
    'glu_target_range'       => 'À jeun : 4,4–7,2 mmol/L | Postprandial : < 10,0 mmol/L',
    // Terme clinique. Le titre est lu dans clinical_code_dictionary.display_fr
    // (CIM-10 E11) quand la ligne existe. Aucune traduction publiée n’a été
    // vérifiée pour ce repli, il reste donc volontairement en anglais.
    'glu_diabetes_type_fallback' => 'Type 2 Diabetes Mellitus',
    // Titre publié par l’OMS en français.
    'growth_who_standard'    => 'Normes OMS de croissance de l’enfant (2006)',
    'ssc_scheduled_procedure' => 'Intervention programmée',
    'naa_admission_mode_emergency' => 'Urgence',
    'naa_reaction'           => 'Réaction',
    'inv_test_default'       => 'Analyse',
    'inv_tube_as_required'   => 'Selon le protocole',
    'inv_indication_default' => 'Selon l’évaluation clinique',
    'inv_turnaround_default' => 'Selon le protocole du laboratoire',
    'fall_tool'              => 'Échelle de chute de Morse',
    // Items de l’échelle de Morse : aucune version française validée n’a été
    // vérifiée pour cette formulation ; les libellés d’items restent en
    // anglais plutôt que d’être inventés.
    'fall_item_history'      => 'History of falling (immediate or within 3 months)',
    'fall_item_secondary_dx' => 'Secondary diagnosis',
    'fall_item_aid'          => 'Ambulatory aid',
    'fall_item_iv'           => 'IV / Heparin lock',
    'fall_item_gait'         => 'Gait / Transferring',
    'fall_item_mental'       => 'Mental status',
    'fall_pending_intervention' => 'Évaluation du risque de chute en attente — à réaliser à l’admission',
    'braden_tool'            => 'Échelle de Braden',
    // Sous-échelles de Braden : intitulés de la version française publiée (HAS).
    'braden_sensory'         => 'Perception sensorielle',
    'braden_moisture'        => 'Humidité',
    'braden_activity'        => 'Activité',
    'braden_mobility'        => 'Mobilité',
    'braden_nutrition'       => 'Nutrition',
    'braden_friction'        => 'Friction et cisaillement',
    'braden_site_sacrum'     => 'Sacrum',
    'braden_site_heels'      => 'Talons',
    'braden_site_elbows'     => 'Coudes',
    'braden_pending_plan'    => 'Évaluation en attente — à réaliser à l’admission',

];
