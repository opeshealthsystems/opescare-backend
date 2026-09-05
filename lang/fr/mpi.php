<?php

/*
|--------------------------------------------------------------------------
| Index maître des patients — le bureau de revue des identités
|--------------------------------------------------------------------------
|
| Pendant français de lang/en/mpi.php — mêmes clés, mêmes :placeholders.
|
*/

return [
    'nav'      => 'Identité des patients',
    'title'    => 'Identité des patients',
    'subtitle' => 'Deux dossiers pour une même personne, ce sont deux demi-historiques. :count patients au registre.',

    'btn_detect' => 'Rechercher les doublons maintenant',

    'tab_pending'  => 'À examiner',
    'tab_merged'   => 'Fusionnés',
    'tab_rejected' => 'Personnes distinctes',

    'tier_all'           => 'Tous',
    'tier_deterministic' => 'Déterministe',
    'tier_probable'      => 'Probable',
    'tier_possible'      => 'Possible',

    'merge_warning' => 'Une fusion déplace tous les enregistrements — ordonnances, rendez-vous, visites, comptes de connexion — d’un identifiant santé vers l’autre, et transforme l’identifiant retiré en alias qui ouvre toujours le dossier conservé. Rien n’est supprimé et toute fusion peut être annulée depuis l’onglet Fusionnés. Rien ici ne fusionne de lui-même : une personne choisit le dossier conservé et en écrit la raison.',

    'detected_scan'                 => 'Trouvé par analyse',
    'detected_identifier_collision' => 'Même identifiant rattaché aux deux',
    'detected_manual'               => 'Signalé manuellement',
    'reviewed_by'                   => 'examiné par :name',

    // Indices — positifs
    'reason_name_match'          => 'Même nom',
    'reason_phone_match'         => 'Même téléphone',
    'reason_dob_match'           => 'Même date de naissance',
    'reason_dob_match_estimated' => 'Même date de naissance (estimée)',
    'reason_sex_match'           => 'Même sexe',
    'reason_national_id_match'   => 'Même numéro national',
    'reason_identifier_match'    => 'Identifiant partagé',
    'reason_identifier_collision' => 'Identifiant détenu par les deux',
    // Indices — négatifs
    'reason_phone_differs'       => 'Téléphones différents',
    'reason_dob_differs'         => 'Dates de naissance différentes',
    'reason_sex_differs'         => 'Sexes différents',
    'reason_national_id_differs' => 'Numéros nationaux différents',

    'col_record'     => 'Dossier',
    'col_survivor'   => 'Conservé',
    'col_retired'    => 'Retiré',
    'record_missing' => 'Ce dossier n’existe plus.',

    'f_health_id'      => 'Identifiant santé',
    'f_name'           => 'Nom',
    'f_dob'            => 'Date de naissance',
    'f_estimated'      => 'estimée',
    'f_sex'            => 'Sexe',
    'f_phone'          => 'Téléphone',
    'f_national_id'    => 'Numéro national',
    'f_logins'         => 'Connexions',
    'f_history'        => 'Historique',
    'f_history_counts' => ':rx ordonnances · :appt rendez-vous · :enc visites',
    'f_created'        => 'Inscrit le',
    'f_status'         => 'Statut',
    'f_on_file'        => 'renseigné',
    'f_missing'        => 'non renseigné',
    'f_none'           => 'aucune',

    'sex_male'   => 'Masculin',
    'sex_female' => 'Féminin',

    'survivor_label'     => 'Quel dossier est conservé ?',
    'survivor_hint'      => ':n enregistrements cliniques',
    'reason_label'       => 'Pourquoi s’agit-il de la même personne ?',
    'reason_placeholder' => 'Ce que vous avez vérifié — la carte, le numéro de téléphone, le patient devant vous.',
    'btn_merge'          => 'Fusionner dans le dossier choisi',
    'confirm_merge'      => 'Fusionner ces deux dossiers ? Tous les enregistrements de l’autre identifiant santé seront déplacés vers celui que vous avez choisi. Cette opération peut être annulée.',
    'reject_placeholder' => 'Pourquoi pas (facultatif)',
    'btn_reject'         => 'Personnes distinctes',

    'merge_summary'         => ':moved lignes déplacées, :kept conservées sur le dossier retiré, :filled champs complétés sur le dossier conservé.',
    'btn_unmerge'           => 'Annuler cette fusion',
    'unmerge_reason_label'  => 'En quoi cette fusion était-elle erronée ?',
    'btn_unmerge_confirm'   => 'Rétablir les dossiers',
    'confirm_unmerge'       => 'Annuler cette fusion ? Chaque ligne déplacée retourne à l’identifiant santé retiré, qui redevient un dossier actif.',

    'empty_pending'  => 'Rien en attente. Lancez une analyse, ou revenez après celle de cette nuit.',
    'empty_merged'   => 'Aucune fusion pour l’instant.',
    'empty_rejected' => 'Aucune paire n’a encore été déclarée comme deux personnes distinctes.',

    'flash_detected' => ':scanned patients analysés ; :created paire(s) mise(s) en file d’examen.',
    'flash_merged'   => 'Fusion effectuée. :moved lignes déplacées vers le dossier conservé ; :kept conservées sur le dossier retiré.',
    'flash_rejected' => 'Enregistré comme deux personnes distinctes.',
    'flash_unmerged' => 'Fusion annulée. Les deux dossiers sont de nouveau actifs.',

    'error_not_pending'          => 'Cette paire a déjà été tranchée.',
    'error_survivor_not_in_pair' => 'Le dossier conservé doit être l’un des deux dossiers de la paire.',
    'error_already_merged'       => 'L’un de ces dossiers a déjà été fusionné dans un autre.',
    'error_merge_not_active'     => 'Cette fusion a déjà été annulée.',
    'error_same_patient'         => 'Un dossier ne peut pas être fusionné avec lui-même.',
    'error_generic'              => 'Une erreur est survenue et rien n’a été modifié.',
];
