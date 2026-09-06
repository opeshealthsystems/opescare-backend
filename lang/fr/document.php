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

];
