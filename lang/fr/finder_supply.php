<?php

/*
|--------------------------------------------------------------------------
| Finder — écrans côté offre (FR)
|--------------------------------------------------------------------------
| Textes des écrans que les établissements et pharmacies utilisent pour
| ALIMENTER les recherches publiques : déclaration de stock en pharmacie,
| saisie de la banque de sang, guichet des rendez-vous et parcours de
| revendication / modification de fiche. Chaînes de présentation
| uniquement. Garder la parité 1:1 avec lang/en/finder_supply.php.
*/

return [

    /* Pharmacie — disponibilité publique (déclaration de stock) */
    'cov_title'            => 'Visible par les patients',
    'cov_hint'             => 'Seuls les médicaments que vous déclarez ici apparaissent dans la Recherche de médicaments publique.',
    'row_reported'         => 'Déclaré',
    'quick_status_label'   => 'Définir la disponibilité',

    /* Saisie banque de sang */
    'blood_group_pick'     => 'Touchez le groupe sanguin',
    'blood_publish_note'   => 'Publié dans la Recherche de sang publique dès l\'enregistrement.',
    'blood_screening_clear'=> 'Dépistage conforme',
    'blood_units_unit'     => 'unités',
    'stepper_minus'        => 'Moins d\'unités',
    'stepper_plus'         => 'Plus d\'unités',

    /* Guichet des rendez-vous */
    'appt_today'           => 'Aujourd\'hui',
    'appt_today_empty'     => 'Rien de prévu aujourd\'hui.',
    'appt_other'           => 'Autres jours',
    'btn_confirm'          => 'Confirmer',
    'btn_no_show'          => 'Absent',
    'btn_cancel'           => 'Annuler',
    'call_patient'         => 'Appeler le patient',
    'cancel_title'         => 'Annuler ce rendez-vous ?',
    'cancel_reason_label'  => 'Motif de l\'annulation',
    'cancel_reason_ph'     => 'ex. le soignant est indisponible ce jour-là',
    'btn_keep'             => 'Garder le rendez-vous',
    'btn_cancel_confirm'   => 'Annuler le rendez-vous',

    /* Parcours de revendication d'établissement */
    'claim_how_title'      => 'Comment fonctionne la revendication',
    'claim_step_submit'    => 'Dites-nous qui vous êtes',
    'claim_step_review'    => 'OpesCare examine votre demande',
    'claim_step_edit'      => 'Vous gérez votre fiche',

    /* Modification de fiche */
    'phone_placeholder'    => 'Ajoutez votre numéro de téléphone — les patients l\'appellent',
    'phone_missing_title'  => 'Les patients ne peuvent pas encore vous appeler',
    'phone_missing_body'   => 'Cette fiche n\'a aucun numéro de téléphone joignable. Un numéro composable est la première chose que cherchent les patients — ajoutez-en un ci-dessous et enregistrez.',

];
