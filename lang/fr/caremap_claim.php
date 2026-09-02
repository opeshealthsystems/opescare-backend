<?php

/*
|--------------------------------------------------------------------------
| Care Map — revendication d'établissement, fiche en libre-service, révision
|--------------------------------------------------------------------------
|
| Fichier distinct plutôt qu'un ajout aux 5 775 clés de public.php, afin que
| la parité EN/FR reste lisible et évidente pour
| `php scripts/i18n-audit.php`.
|
*/

return [

    // ── Navigation ─────────────────────────────────────────────────────────
    'nav_my_listing'        => 'Ma fiche d\'établissement',
    'nav_directory_review'  => 'Révision de l\'annuaire',

    // ── Parcours public de revendication ───────────────────────────────────
    'page_title'            => 'Revendiquer la fiche de votre établissement',
    'claim_heading'         => 'Revendiquer cette fiche',
    'claim_subheading'      => 'Demandez à gérer :facility sur la carte des soins OpesCare.',
    'claim_intro'           => 'Revendiquer une fiche vous permet de tenir à jour son numéro de téléphone, son adresse e-mail, ses horaires et ses services. Chaque modification est enregistrée à votre nom.',
    'claim_review_notice'   => 'Les revendications sont examinées par un administrateur OpesCare. L\'envoi de ce formulaire n\'accorde aucun accès et ne rend pas l\'établissement vérifié.',

    'field_name'            => 'Votre nom complet',
    'field_role'            => 'Votre fonction dans cet établissement',
    'field_email'           => 'Adresse e-mail professionnelle',
    'field_phone'           => 'Numéro de téléphone direct',
    'field_reason'          => 'Tout élément nous aidant à vérifier votre demande',
    'field_reason_hint'     => 'Par exemple : votre numéro d\'enregistrement, votre poste, ou une page du site de l\'établissement où figure votre nom.',
    'field_optional'        => 'facultatif',

    'role_select'           => 'Choisir…',
    'role_owner'            => 'Je suis le propriétaire ou l\'exploitant',
    'role_manager'          => 'Je gère cet établissement pour le propriétaire',
    'role_authorized_rep'   => 'Je suis un représentant autorisé',
    'role_admin_staff'      => 'Je fais partie du personnel administratif',

    'hint_false_claim'      => 'Soumettre une fausse revendication constitue une violation des conditions d\'utilisation d\'OpesCare.',
    'btn_submit_claim'      => 'Envoyer la revendication',
    'btn_cancel'            => 'Annuler',
    'back_to_listing'       => 'Retour à la fiche',
    'btn_open_care_map'     => 'Ouvrir la carte des soins',
    'btn_find_listing'      => 'Trouver votre établissement',
    'btn_manage_listing'    => 'Gérer ma fiche',

    'already_pending'       => 'Vous avez déjà une revendication en attente d\'examen pour cette fiche.',
    'already_approved'      => 'Vous gérez déjà cette fiche.',

    // ── Page de suivi des revendications ───────────────────────────────────
    'my_claims_title'       => 'Mes revendications d\'établissement',
    'my_claims_subtitle'    => 'Demandes que vous avez faites pour gérer une fiche de la carte des soins.',
    'col_facility'          => 'Établissement',
    'col_status'            => 'Statut',
    'col_submitted'         => 'Envoyée le',
    'col_reviewed'          => 'Examinée le',
    'col_notes'             => 'Notes de l\'examinateur',
    'empty_claims'          => 'Vous n\'avez encore revendiqué aucune fiche d\'établissement.',
    'empty_claims_hint'     => 'Trouvez votre établissement sur la carte des soins et utilisez « Revendiquer cette fiche ».',

    'status_submitted'      => 'En attente d\'examen',
    'status_under_review'   => 'En cours d\'examen',
    'status_approved'       => 'Approuvée',
    'status_rejected'       => 'Refusée',
    'status_revoked'        => 'Révoquée',

    // ── Éditeur de fiche en libre-service ──────────────────────────────────
    'edit_title'            => 'Ma fiche d\'établissement',
    'edit_subtitle'         => 'Ce que voient les patients lorsqu\'ils trouvent :facility sur la carte des soins.',
    'badge_claimed'         => 'Fiche revendiquée',
    'badge_not_verified'    => 'Non vérifiée par OpesCare',
    'not_verified_note'     => 'Une fiche revendiquée signifie qu\'un représentant de cet établissement la tient à jour. Ce n\'est pas la même chose qu\'une vérification de l\'établissement par OpesCare, et cela est présenté différemment aux patients.',
    'audit_note'            => 'Chaque modification est enregistrée avec votre nom, l\'ancienne valeur et la nouvelle.',

    'section_contact'       => 'Coordonnées',
    'section_hours'         => 'Horaires d\'ouverture',
    'section_services'      => 'Services et spécialités',

    'label_phone_primary'   => 'Numéro de téléphone principal',
    'label_phone_secondary' => 'Second numéro de téléphone',
    'label_email'           => 'Adresse e-mail',
    'label_website'         => 'Site web',
    'label_description'     => 'À propos de cet établissement',
    'hint_phone_placeholder'=> 'Aucun numéro de téléphone n\'est enregistré pour cette fiche. En ajouter un est la modification la plus utile que vous puissiez faire.',
    'hint_description'      => 'Une courte description que les patients liront avant de se déplacer.',
    'btn_save'              => 'Enregistrer les modifications',

    'hours_day'             => 'Jour',
    'hours_open'            => 'Ouverture',
    'hours_close'           => 'Fermeture',
    'hours_closed'          => 'Fermé',
    'hours_24'              => 'Ouvert 24 h/24',
    'hours_intro'           => 'Laissez un jour vide si vous préférez ne pas en indiquer les horaires.',
    'btn_save_hours'        => 'Enregistrer les horaires',

    'day_sunday'            => 'Dimanche',
    'day_monday'            => 'Lundi',
    'day_tuesday'           => 'Mardi',
    'day_wednesday'         => 'Mercredi',
    'day_thursday'          => 'Jeudi',
    'day_friday'            => 'Vendredi',
    'day_saturday'          => 'Samedi',

    'svc_name'              => 'Service',
    'svc_category'          => 'Catégorie',
    'svc_specialty'         => 'Spécialité',
    'svc_availability'      => 'Disponibilité',
    'svc_appointment'       => 'Rendez-vous obligatoire',
    'svc_walkin'            => 'Sans rendez-vous accepté',
    'svc_telemedicine'      => 'Disponible en téléconsultation',
    'svc_intro'             => 'Indiquez aux patients ce que vous proposez réellement. Rien d\'autre dans la plateforme ne peut le savoir.',
    'btn_add_service'       => 'Ajouter un service',
    'btn_remove'            => 'Retirer',
    'empty_services'        => 'Aucun service n\'est encore répertorié.',

    'cat_consultation'      => 'Consultation',
    'cat_emergency'         => 'Urgences',
    'cat_diagnostic'        => 'Diagnostic',
    'cat_laboratory'        => 'Laboratoire',
    'cat_imaging'           => 'Imagerie médicale',
    'cat_surgery'           => 'Chirurgie',
    'cat_maternity'         => 'Maternité',
    'cat_pharmacy'          => 'Pharmacie',
    'cat_dental'            => 'Soins dentaires',
    'cat_rehabilitation'    => 'Rééducation',
    'cat_preventive'        => 'Soins préventifs',

    'avail_available'       => 'Disponible',
    'avail_limited'         => 'Limité',
    'avail_unavailable'     => 'Indisponible',
    'avail_by_referral'     => 'Sur orientation uniquement',

    'none_title'            => 'Aucune fiche à gérer pour l\'instant',
    'none_body'             => 'Dès qu\'un administrateur aura approuvé votre revendication, cette page deviendra l\'éditeur de la fiche de votre établissement.',

    // ── Révision de l'annuaire (administration) ────────────────────────────
    'review_title'          => 'Révision de l\'annuaire',
    'review_subtitle'       => 'Décisions sur des établissements qu\'une machine ne doit pas prendre seule.',
    'tab_claims'            => 'Revendications de propriété',
    'tab_imports'           => 'Candidats à l\'import',
    'stat_pending_claims'   => 'Revendications à examiner',
    'stat_pending_imports'  => 'Candidats à l\'import en attente',
    'recent_decisions'      => 'Décisions récentes',

    'claim_col_claimant'    => 'Demandeur',
    'claim_col_contact'     => 'Contact',
    'claim_col_role'        => 'Fonction déclarée',
    'btn_approve'           => 'Approuver',
    'btn_reject'            => 'Refuser',
    'btn_revoke'            => 'Révoquer',
    'notes_placeholder'     => 'Motif (facultatif)',
    'empty_claims_queue'    => 'Aucune revendication d\'établissement n\'attend de décision.',
    'approve_warning'       => 'L\'approbation autorise cette personne à modifier la fiche. Elle ne rend pas l\'établissement vérifié.',

    'import_col_candidate'  => 'Candidat',
    'import_col_reason'     => 'Pourquoi une personne est nécessaire',
    'import_col_match'      => 'Ressemblait à',
    'btn_accept'            => 'Ajouter à l\'annuaire',
    'btn_merge'             => 'Identique au résultat',
    'empty_imports_queue'   => 'Aucun candidat à l\'import n\'attend de décision.',
    'match_score'           => 'Similarité :score',
    'match_distance'        => ':metres m d\'écart',
    'no_name_warning'       => 'Ce candidat n\'a aucune balise de nom. Consultez les balises brutes avant de décider.',
    'raw_tags'              => 'Balises brutes de la source',
    'view_listing'          => 'Voir la fiche',
    'filter_all_reasons'    => 'Tous les motifs',
    'source_label'          => 'Source',

    'reason_generic_name'                   => 'Nom trop générique',
    'reason_unnamed_element'                => 'L\'enregistrement source n\'a pas de nom',
    'reason_uncertain_match'                => 'Correspondance faible avec une fiche existante',
    'reason_multiple_matches'               => 'Correspond à plusieurs fiches',
    'reason_type_conflict'                  => 'Le type d\'établissement diverge',
    'reason_unresolved_city'                => 'Ville non identifiée',
    'reason_already_linked_to_other_element'=> 'La correspondance est déjà liée à un autre enregistrement',

    // ── Messages flash ─────────────────────────────────────────────────────
    'flash_submitted'       => 'Revendication envoyée. Un administrateur l\'examinera et vous contactera.',
    'flash_no_changes'      => 'Aucune modification.',
    'flash_profile_updated' => '{1} 1 champ mis à jour.|[2,*] :count champs mis à jour.',
    'flash_service_added'   => 'Service ajouté à votre fiche.',
    'flash_service_removed' => 'Service retiré de votre fiche.',
    'flash_hours_updated'   => '{0} Horaires d\'ouverture effacés.|{1} Horaires enregistrés pour 1 jour.|[2,*] Horaires enregistrés pour :count jours.',
    'flash_claim_approved'  => 'Revendication approuvée. Le demandeur peut désormais modifier la fiche.',
    'flash_claim_rejected'  => 'Revendication refusée.',
    'flash_claim_revoked'   => 'Revendication révoquée. La fiche conserve son contenu actuel.',
    'flash_import_accepted' => 'Candidat ajouté à l\'annuaire.',
    'flash_import_merged'   => 'Candidat fusionné avec la fiche existante.',
    'flash_import_rejected' => 'Candidat refusé.',

    // ── Erreurs ────────────────────────────────────────────────────────────
    'error_already_submitted' => 'Vous avez déjà une revendication sur cette fiche.',
    'error_already_claimed'   => 'Un autre représentant gère déjà cette fiche. Veuillez contacter le support.',
    'error_service_not_found' => 'Ce service ne figure pas sur votre fiche.',
    'error_import_decided'    => 'Ce candidat a déjà fait l\'objet d\'une décision.',
    'error_import_unnamed'    => 'Donnez un nom au candidat avant de l\'ajouter à l\'annuaire.',
    'error_import_no_match'   => 'Il n\'y a aucune fiche correspondante avec laquelle fusionner.',
    'error_generic'           => 'Une erreur est survenue. Veuillez réessayer.',

];
