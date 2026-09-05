<?php

/*
|--------------------------------------------------------------------------
| Suppression de compte — la divulgation détaillée
|--------------------------------------------------------------------------
|
| Traduction 1:1 de lang/en/account_deletion.php. Chaque phrase est une
| promesse effectivement tenue par le code de
| App\Services\Compliance\AccountDeletionService.
|
| Les mêmes :placeholders doivent figurer des deux côtés — c'est vérifié par
| php scripts/i18n-audit.php avant chaque déploiement, au même titre que la
| parité des clés et la validité de l'UTF-8.
|
| Note de traduction : le mot de confirmation est SUPPRIMER en français et
| DELETE en anglais. Le service accepte les DEUX quelle que soit la langue
| affichée (AccountDeletionService::confirmationMatches), pour qu'un patient
| qui change de langue en cours de route ne soit pas piégé par un simple
| écart de locale.
|
| Ce qui est délibérément absent, ici comme en anglais : toute affirmation
| selon laquelle le dossier fermé serait masqué aux cliniciens. Le service
| écrit IdentityStatus::ErasurePending, mais rien dans ce dépôt n'applique ce
| statut. « Marqué comme fermé » est ce qui est vrai ; c'est donc ce qui est
| écrit.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Écran
    |----------------------------------------------------------------------
    */

    'title'     => 'Supprimer votre compte',
    'subtitle'  => 'Fermez votre compte OpesCare et effacez vos données personnelles.',
    'headline'  => 'Fermer votre compte n\'est pas la même chose que supprimer votre dossier médical.',
    'summary'   => 'Nous effaçons tout ce qui vous identifie et tout ce qui permet de se connecter à votre place. Votre dossier médical, lui, est conservé, car la loi l\'exige. Rien ne se passe pendant :days jours, et vous pouvez tout arrêter durant ce délai.',

    /*
    |----------------------------------------------------------------------
    | 1. Effacé immédiatement
    |----------------------------------------------------------------------
    */

    'erased' => [
        'title' => 'Effacé immédiatement, définitivement',
        'note'  => 'Ces données sont écrasées ou supprimées dans la base. Elles ne sont pas archivées, et nous ne pourrons pas vous les restituer ensuite.',
        'items' => [
            'sign_in'           => 'Votre adresse e-mail de connexion',
            'password'          => 'Votre mot de passe',
            'pin'               => 'Votre code PIN',
            'sessions'          => 'Toutes vos sessions connectées, sur ce téléphone et sur tout autre',
            'devices'           => 'Vos appareils enregistrés et vos jetons de notification push',
            'phone'             => 'Votre numéro de téléphone, ainsi que la clé de recherche qui en dérive',
            'email'             => 'Votre adresse e-mail de contact',
            'address'           => 'Votre adresse postale',
            'emergency_contact' => 'Votre contact d\'urgence et son numéro de téléphone',
            'national_id'       => 'Votre numéro de pièce d\'identité nationale et votre numéro CNAMGS',
            'referral_code'     => 'Votre code de parrainage',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | 2. Désidentifié
    |----------------------------------------------------------------------
    */

    'deidentified' => [
        'title' => 'Conservé, mais ce n\'est plus vous',
        'note'  => 'Votre fiche patient ne peut pas être supprimée sans supprimer le dossier clinique qui y est rattaché : elle reste donc, vidée des éléments qui vous identifient.',
        'items' => [
            'name'          => 'Votre nom est remplacé par « Closed Account »',
            'record_marked' => 'Votre fiche est marquée comme fermée et effacée, afin que le personnel voie que le compte a été clos à votre demande',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | 3. Conservé en vertu d'une obligation légale
    |----------------------------------------------------------------------
    */

    'retained' => [
        'title'       => 'Conservé, parce que la loi l\'exige',
        'note'        => 'Votre identifiant santé, votre date de naissance, votre sexe et votre groupe sanguin restent attachés à votre dossier médical, car une ordonnance ou un résultat de laboratoire ne peut pas être lu sans risque sans eux.',
        'legal_basis' => 'La réglementation sanitaire camerounaise (MINSANTE) impose la conservation du dossier médical pendant :years ans. Un établissement doit pouvoir prouver ce qu\'il a prescrit, délivré et analysé. Nous n\'avons pas le droit de le supprimer avant, et nous ne vous dirons pas le contraire.',
        'purge_note'  => 'Conservé jusqu\'au :date. À cette date, le dossier conservé — y compris votre fiche patient elle-même — est détruit.',
    ],

    /*
    |----------------------------------------------------------------------
    | 4. Ce que nous ne pouvons pas supprimer
    |----------------------------------------------------------------------
    */

    'cannot' => [
        'title' => 'Ce que nous ne pouvons pas supprimer',
        'items' => [
            'clinical_events' => 'Les ordonnances, résultats de laboratoire, diagnostics et consultations sont des traces permanentes de faits qui ont eu lieu. Ils peuvent être corrigés ou marqués comme saisis par erreur, jamais retirés.',
            'access_logs'     => 'Le journal indiquant qui a ouvert votre dossier est conservé. C\'est votre preuve, pas la nôtre : l\'effacer détruirait le seul élément prouvant qui a consulté votre dossier.',
            'facility_copies' => 'Toute copie détenue par un hôpital, une pharmacie ou un laboratoire dans son propre système ou sur papier. Adressez-vous directement à eux ; nous n\'y avons pas accès.',
            'fiscal'          => 'Les factures et les traces de paiement, exigées séparément par les règles fiscales et comptables.',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Décompte de ce qui subsiste
    |----------------------------------------------------------------------
    */

    'kept_items' => [
        'prescriptions'     => 'Ordonnances',
        'lab_results'       => 'Résultats de laboratoire',
        'lab_orders'        => 'Demandes d\'analyses',
        'encounters'        => 'Rencontres cliniques',
        'visits'            => 'Visites en établissement',
        'appointments'      => 'Rendez-vous',
        'imaging_orders'    => 'Demandes d\'imagerie',
        'radiology_reports' => 'Comptes rendus de radiologie',
        'immunizations'     => 'Carnet de vaccination',
        'problems'          => 'Entrées de la liste de problèmes',
        'diagnoses'         => 'Diagnostics',
        'invoices'          => 'Factures',
        'payments'          => 'Paiements',
        'access_events'     => 'Accès à l\'identifiant santé',
        'access_logs'       => 'Entrées du journal d\'accès au dossier',
    ],

    /*
    |----------------------------------------------------------------------
    | Confirmation
    |----------------------------------------------------------------------
    */

    'confirm_phrase'      => 'SUPPRIMER',
    'confirm_instruction' => 'Saisissez :phrase pour confirmer que vous avez lu ce qui est effacé et ce qui est conservé.',
    'reauth_note'         => 'Saisissez votre mot de passe ou votre code PIN. Nous le demandons parce que fermer un compte ne doit pas être à la portée d\'un inconnu tenant votre téléphone déverrouillé.',
    'reason_label'        => 'Pourquoi fermez-vous votre compte ? (facultatif)',
    'reason_placeholder'  => 'Cela nous aide à corriger ce qui vous a fait partir. Ce n\'est pas obligatoire.',

    /*
    |----------------------------------------------------------------------
    | Délai de grâce / statut
    |----------------------------------------------------------------------
    */

    'grace' => [
        'title'      => 'Votre compte sera fermé le :date',
        'countdown'  => 'Il vous reste :days jours pour changer d\'avis.',
        'today'      => 'Votre compte sera fermé aujourd\'hui.',
        'body'       => 'Rien n\'a encore été effacé. Jusqu\'au :date, vous pouvez annuler et conserver votre compte tel quel. Passé cette date, l\'effacement s\'exécute et devient irréversible.',
        'cancel_cta' => 'Annuler — garder mon compte',
        'requested'  => 'Demandé le :date',
    ],

    'completed' => [
        'title' => 'Ce compte est fermé',
        'body'  => 'Votre compte a été fermé le :date. Vos données personnelles ont été effacées. Votre dossier médical est conservé jusqu\'au :until.',
    ],

    /*
    |----------------------------------------------------------------------
    | Actions et résultats
    |----------------------------------------------------------------------
    */

    'cta'                 => 'Supprimer mon compte',
    'cta_confirm'         => 'Oui, supprimer mon compte',
    'back'                => 'Garder mon compte',
    'request_created'     => 'Votre compte sera fermé le :date. Vous pouvez annuler à tout moment d\'ici là.',
    'request_cancelled'   => 'Votre compte ne sera pas fermé. Rien n\'a été effacé.',
    'already_pending'     => 'Vous avez déjà demandé la fermeture de ce compte. Il sera fermé le :date.',
    'nothing_pending'     => 'Vous n\'avez aucune demande de fermeture en cours.',
    'executed'            => 'Votre compte a été fermé et vos données personnelles ont été effacées.',

    /*
    |----------------------------------------------------------------------
    | Erreurs
    |----------------------------------------------------------------------
    */

    'errors' => [
        'bad_credential'   => 'Ce mot de passe ou ce code PIN est incorrect. Votre compte n\'a pas été modifié.',
        'bad_confirmation' => 'Le mot de confirmation ne correspond pas. Rien n\'a été modifié.',
        'no_credential'    => 'Ce compte n\'a ni mot de passe ni code PIN : nous ne pouvons donc pas vérifier votre identité. Définissez-en un d\'abord, ou demandez à l\'établissement qui vous a enregistré de vous aider à fermer le compte.',
        'not_found'        => 'Nous n\'avons pas trouvé votre fiche patient. Rien n\'a été modifié.',
        'already_closed'   => 'Ce compte est déjà fermé.',
    ],

    /*
    |----------------------------------------------------------------------
    | Page web publique — Google Play exige une adresse accessible sans
    | installer l'application ni se connecter.
    |----------------------------------------------------------------------
    */

    'web' => [
        'page_title'       => 'Supprimer votre compte OpesCare',
        'meta_description' => 'Comment supprimer votre compte OpesCare, ce qui est effacé, ce qui est conservé au titre des règles camerounaises de conservation du dossier médical, et sous quel délai.',
        'badge'            => 'Vos droits sur vos données',
        'hero_title'       => 'Supprimer votre compte OpesCare',
        'hero_subtitle'    => 'Vous pouvez fermer votre compte et effacer vos données personnelles depuis l\'application. Cette page explique précisément ce que cela fait — et ce que cela ne fait pas.',
        'how_title'        => 'Comment supprimer votre compte',
        'how_intro'        => 'La voie la plus rapide est dans l\'application, et elle prend effet sans qu\'aucune validation humaine ne soit nécessaire.',
        'step_app'         => 'Ouvrez l\'application OpesCare, allez dans Paramètres, puis choisissez « Supprimer le compte ».',
        'step_confirm'     => 'Lisez la liste détaillée de ce qui est effacé et de ce qui est conservé, saisissez votre mot de passe ou votre code PIN, puis tapez le mot de confirmation.',
        'step_grace'       => 'Votre compte sera fermé :days jours plus tard. D\'ici là, vous pouvez annuler dans l\'application et rien n\'est effacé.',
        'no_app_title'     => 'Si vous ne pouvez pas utiliser l\'application',
        'no_app_body'      => 'Écrivez à l\'adresse ci-dessous depuis l\'e-mail associé à votre compte, ou demandez à tout établissement OpesCare de faire la démarche pour vous. Nous vérifierons votre identité avant d\'agir, et le même délai de grâce de :days jours s\'applique.',
        'contact_label'    => 'Contact protection des données',
        'timing_title'     => 'Sous quel délai',
        'timing_body'      => 'L\'effacement s\'exécute :days jours après votre demande. Vos données personnelles sont effacées ce jour-là. Votre dossier médical est conservé :years ans à compter de cette date, puis détruit.',
        'last_updated'     => 'Dernière mise à jour',
    ],

];
