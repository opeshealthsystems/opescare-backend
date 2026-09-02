<?php

return [

    // Immunizations — record form
    'breadcrumb_record'  => 'Enregistrer',
    'ph_vaccine_code'    => 'ex. BCG, OPV, DPT',
    'hint_vaccine_code'  => 'Code WHO-EPI ou code local',
    'ph_vaccine_name'    => 'ex. Bacillus Calmette-Guérin',
    'opt_select'         => '— Sélectionner —',
    'route_im'           => 'IM (Intramusculaire)',
    'route_sc'           => 'SC (Sous-cutanée)',
    'route_oral'         => 'Orale',
    'route_intradermal'  => 'Intradermique',
    'route_intranasal'   => 'Intranasale',
    'ph_site'            => 'ex. Deltoïde gauche',
    'opt_completed'      => 'Effectuée',
    'opt_not_done'       => 'Non effectuée',

    // Immunizations — list
    'lbl_dose_n'         => 'Dose :n',

    // Wards — bed map + create form
    'lbl_floor_n'        => 'Étage :n',
    'ph_ward_name'       => 'ex. Salle générale A',
    'ph_floor'           => 'ex. 2',
    'ph_building'        => 'ex. Bloc A',
    'ward_unknown'       => 'Inconnu',

    // Wards — admissions
    'lbl_optional'       => '(facultatif)',

    // Visits — triage
    'opt_na'             => 'N/A',

    // Visits — triage JS vital-range hints (rendered to staff via textContent)
    'vital_spo2'         => 'SpO₂',
    'vital_pulse'        => 'Pouls',
    'vital_bp_sys'       => 'TA systolique',
    'vital_temp'         => 'Temp.',
    'vital_rr'           => 'Fréq. resp.',
    'note_spo2_crit'     => 'Hypoxie sévère — suggérer Réanimation',
    'note_spo2_warn'     => 'O₂ faible — suggérer Critique',
    'note_pulse_crit'    => 'FC extrême — suggérer Critique',
    'note_pulse_warn'    => 'FC anormale',
    'note_bp_crit'       => 'Hypotension — suggérer Critique',
    'note_bp_warn'       => 'Tension artérielle basse',
    'note_temp_crit'     => 'Température extrême',
    'note_temp_warn'     => 'Température anormale',
    'note_rr_crit'       => 'Risque d\'insuffisance respiratoire',
    'note_rr_warn'       => 'Fréquence respiratoire anormale',

    // Prescription — formulaire de prescription du clinicien
    'rx_btn_new'            => 'Nouvelle ordonnance',
    'rx_new_title'          => 'Nouvelle ordonnance',
    'rx_new_subtitle'       => 'Prescrivez à partir du catalogue national des médicaments afin que la pharmacie délivre exactement ce que vous avez prescrit.',
    'rx_breadcrumb_new'     => 'Nouvelle ordonnance',
    'rx_patient'            => 'Patient',
    'rx_select_patient'     => '— Sélectionner un patient —',
    'rx_patient_hint'       => 'Seuls les patients enregistrés, suivis ou ayant donné leur consentement à cet établissement sont listés.',
    'rx_no_patients'        => 'Aucun patient dans cet établissement pour l\'instant. Enregistrez ou admettez un patient avant de prescrire.',
    'rx_validity_days'      => 'Valable (jours)',
    'rx_validity_hint'      => 'Passé ce délai, l\'ordonnance expire et ne peut plus être délivrée.',
    'rx_notes'              => 'Notes pour le pharmacien',
    'rx_notes_ph'           => 'ex. À prendre après les repas. Contrôle dans deux semaines.',
    'rx_items'              => 'Médicaments',
    'rx_item_n'             => 'Médicament :n',
    'rx_medicine'           => 'Médicament',
    'rx_select_medicine'    => '— Sélectionner un médicament —',
    'rx_dose'               => 'Posologie',
    'rx_dose_ph'            => 'ex. 500 mg',
    'rx_dose_hint'          => 'Laissez vide pour utiliser le dosage du catalogue.',
    'rx_frequency'          => 'Fréquence',
    'rx_frequency_ph'       => 'ex. 3 fois par jour',
    'rx_route'              => 'Voie d\'administration',
    'rx_duration_days'      => 'Durée (jours)',
    'rx_quantity'           => 'Quantité',
    'rx_add_item'           => 'Ajouter un autre médicament',
    'rx_remove_item'        => 'Retirer',
    'rx_submit'             => 'Émettre l\'ordonnance',
    'rx_immutable_note'     => 'Une ordonnance ne peut être ni modifiée ni supprimée une fois émise. Pour la corriger, annulez-la et émettez-en une nouvelle.',
    'rx_controlled'         => 'Stupéfiant',
    'rx_prescription_only'  => 'Sur ordonnance',
    'rx_col_actions'        => 'Actions',
    'rx_void'               => 'Annuler',
    'rx_void_title'         => 'Annuler cette ordonnance',
    'rx_void_reason'        => 'Motif',
    'rx_void_reason_ph'     => 'ex. Posologie erronée — remplacée par une ordonnance corrigée.',
    'rx_void_entered_error' => 'Cette ordonnance n\'aurait jamais dû exister (saisie erronée)',
    'rx_void_submit'        => 'Annuler l\'ordonnance',
    'rx_void_cancel'        => 'La conserver',
    'rx_voided_reason'      => 'Motif : :reason',

];
