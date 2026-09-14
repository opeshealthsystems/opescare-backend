<?php

/*
|--------------------------------------------------------------------------
| E-mails transactionnels — l'habillage commun et le corps de chaque message
|--------------------------------------------------------------------------
|
| Rendu par resources/views/emails/layout.blade.php (HTML) et
| layout-text.blade.php (version texte). Composé par les Mailables de
| app/Mail et les méthodes toMail() de app/Notifications.
|
| Règles portées par ces chaînes :
|   - un OBJET ne contient jamais d'identifiant de santé, de diagnostic, de
|     résultat ni de nom de médicament ;
|   - un e-mail de code dit clairement que personne chez OpesCare ne
|     demandera jamais ce code ;
|   - une alerte d'accès d'urgence est pressante sans être criarde.
|
| À garder strictement 1:1 avec lang/en/mail.php (scripts/i18n-audit.php).
*/

return [

    /* ── Habillage : en-tête, pied de page, replis ─────────────────────── */
    'chrome' => [
        'tagline'       => 'Votre identifiant de santé, où que vous soyez soigné(e)',
        'kind'          => 'Message de compte',
        'link_fallback' => 'Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :',
        'no_reply'      => 'Ce message a été envoyé automatiquement — merci de ne pas y répondre. Pour toute aide, écrivez à :email.',
        'no_reply_plain' => 'Ce message a été envoyé automatiquement — merci de ne pas y répondre.',
        'privacy'       => 'Pour protéger votre vie privée, les e-mails OpesCare ne contiennent jamais de diagnostic, de résultat ni de détail de traitement. Connectez-vous à votre portail sécurisé pour consulter votre dossier.',
        'preferences'   => 'Choisir les e-mails que vous recevez',
        'legal'         => 'OpesCare — plateforme d’identité numérique de santé exploitée dans le cadre de la loi camerounaise n° 2010/012 relative à la protection des données personnelles.',
        'copyright'     => '© :year OpesCare. Tous droits réservés.',
        'signature'     => 'L’équipe OpesCare',
    ],

    'common' => [
        'greeting'       => 'Bonjour,',
        'greeting_named' => 'Bonjour :name,',
        'never_share'    => 'Ne communiquez jamais ce code. Le personnel OpesCare ne vous le demandera jamais — ni par téléphone, ni par message, ni en personne.',
        'what_to_do'     => 'Que faire',
        'what_this_means' => 'Ce que cela signifie',
    ],

    /* ── Code de connexion à usage unique (OtpMail) ────────────────────── */
    'otp' => [
        'subject'    => 'Votre code de connexion OpesCare',
        'preheader'  => 'Votre code à usage unique est ci-dessous. Il expire dans :minutes minutes.',
        'eyebrow'    => 'Vérification de connexion',
        'title'      => 'Votre code de connexion à usage unique',
        'intro'      => 'Saisissez le code ci-dessous pour terminer votre connexion à OpesCare.',
        'code_label' => 'Code de vérification',
        'expiry'     => 'Valable :minutes minutes',
        'ignore'     => 'Si vous n’essayiez pas de vous connecter, ignorez cet e-mail : personne ne peut accéder à votre compte sans ce code.',
    ],

    /* ── Code de réinitialisation du mot de passe (PasswordResetCodeMail — application mobile) */
    'reset_code' => [
        'subject'    => 'Votre code de réinitialisation OpesCare',
        'preheader'  => 'Utilisez ce code dans l’application pour choisir un nouveau mot de passe. Il expire dans :minutes minutes.',
        'eyebrow'    => 'Réinitialisation du mot de passe',
        'title'      => 'Réinitialisez votre mot de passe',
        'intro'      => 'Nous avons reçu une demande de réinitialisation du mot de passe de votre compte OpesCare. Saisissez le code ci-dessous dans l’application pour en choisir un nouveau.',
        'code_label' => 'Code de réinitialisation',
        'expiry'     => 'Valable :minutes minutes',
        'ignore'     => 'Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail : votre mot de passe ne sera pas modifié.',
    ],

    /* ── Lien de réinitialisation (PasswordResetLinkNotification — web) ── */
    'reset_link' => [
        'preheader' => 'Définissez un nouveau mot de passe pour votre compte OpesCare. Le lien expire dans :minutes minutes.',
        'eyebrow'   => 'Réinitialisation du mot de passe',
    ],

    /* ── Mot de passe modifié (PasswordChangedNotification) ────────────── */
    'password_changed' => [
        'preheader'   => 'Le mot de passe de votre compte vient d’être modifié. Si ce n’était pas vous, agissez maintenant.',
        'eyebrow'     => 'Avis de sécurité',
        'panel_title' => 'Si ce n’était pas vous',
        'panel_1'     => 'Demandez immédiatement une nouvelle réinitialisation depuis la page de connexion.',
        'panel_2'     => 'Vérifiez que vous contrôlez toujours l’adresse e-mail de votre compte.',
        'panel_3'     => 'Contactez ensuite le support OpesCare afin que nous examinions le compte avec vous.',
        'action'      => 'Aller à la connexion',
    ],

    /* ── Invitation du personnel d'un établissement (FacilityStaffInviteNotification) */
    'staff_invite' => [
        'preheader' => 'Vous êtes invité(e) à rejoindre :facility sur OpesCare. Activez votre compte avant l’expiration du lien.',
        'eyebrow'   => 'Invitation d’équipe',
        'facility'  => 'Établissement',
        'role'      => 'Rôle',
    ],

    /* ── Expiration de l'identifiant de santé (HealthIdExpiryNotification) */
    'health_id_expiry' => [
        'subject'        => 'Votre identifiant de santé expire dans :days jours',
        'subject_urgent' => 'Action requise : votre identifiant de santé expire dans :days jours',
        'preheader'      => 'Renouvelez-le avant le :date pour continuer à utiliser les services de santé sans interruption.',
        'eyebrow'        => 'Renouvellement de l’identifiant',
        'eyebrow_urgent' => 'Renouvellement bientôt nécessaire',
        'title'          => 'Votre identifiant de santé approche de sa date d’expiration',
        'intro'          => 'Votre identifiant de santé OpesCare expirera le :date. Passé cette date, il ne pourra plus servir à vous identifier dans un établissement.',
        'days_left'      => 'Il reste :days jours avant que votre identifiant ne devienne invalide.',
        'fact_health_id' => 'Identifiant de santé',
        'fact_expires'   => 'Expire le',
        'fact_days_left' => 'Jours restants',
        'action'         => 'Renouveler mon identifiant',
        'panel_title'    => 'Comment renouveler',
        'panel_1'        => 'Connectez-vous à votre portail patient et demandez un renouvellement depuis la page de votre identifiant de santé.',
        'panel_2'        => 'Votre dossier, votre historique et vos consentements restent inchangés — seule la date de validité change.',
        'error_note'     => 'Si vous pensez avoir reçu ce message par erreur, contactez le support OpesCare.',
        'legal'          => 'Les identifiants de santé sont valables dix ans conformément à la Stratégie de santé numérique 2026–2030 du MINSANTE.',
    ],

    /* ── Alerte d'accès d'urgence (EmergencyAccessAlertNotification) ───── */
    'emergency' => [
        'subject'       => 'Votre dossier de santé a été ouvert en accès d’urgence',
        'preheader'     => 'Un soignant a utilisé l’accès d’urgence pour ouvrir votre dossier. Chaque accès est journalisé — vérifiez-le maintenant.',
        'eyebrow'       => 'Avis d’accès d’urgence',
        'title'         => 'Votre dossier de santé a été ouvert en accès d’urgence',
        'intro'         => 'Un soignant a ouvert votre dossier de santé au moyen de la procédure d’urgence, qui permet de vous soigner lorsque vous ne pouvez pas donner votre consentement vous-même.',
        'fact_health_id' => 'Identifiant de santé',
        'fact_when'     => 'Accès le',
        'fact_reason'   => 'Motif indiqué',
        'logged'        => 'Cet accès a été inscrit dans votre journal d’accès, avec l’établissement et le compte qui l’a ouvert. Vous en êtes informé(e) parce que la loi l’exige, et parce que vous avez le droit de le savoir.',
        'action'        => 'Consulter mon journal d’accès',
        'panel_title'   => 'Si vous ne vous y attendiez pas',
        'panel_1'       => 'Ouvrez votre journal d’accès et comparez l’établissement et l’heure avec vos propres souvenirs.',
        'panel_2'       => 'Si l’accès vous paraît anormal, contactez immédiatement le support OpesCare — chaque accès d’urgence fait l’objet d’un examen.',
        'legal'         => 'OpesCare vous informe de chaque accès à votre dossier, comme l’exige la loi camerounaise n° 2010/012.',
    ],

    /* ── Événement famille / tuteur (FamilyEventNotification) ─────────── */
    'family_event' => [
        'subject'   => 'Une mise à jour concernant un proche dont vous avez la charge',
        'preheader' => 'Quelque chose a changé sur un dossier dont vous êtes tuteur. Connectez-vous pour voir les détails.',
        'eyebrow'   => 'Mise à jour famille',
        'title'     => 'Une mise à jour concernant :name',
        'intro'     => 'En tant que tuteur sur OpesCare, vous recevez une note lorsque quelque chose change sur un dossier dont vous avez la charge. Voici la dernière :',
        'action'    => 'Ouvrir la page famille',
        'note'      => 'Les détails ne sont visibles que dans votre portail sécurisé, jamais dans cet e-mail.',
        'dependent_fallback' => 'un proche dont vous avez la charge',
    ],

    /* ── Demande d'accès tuteur (FamilyInviteNotification) ─────────────── */
    'family_invite' => [
        'subject'          => 'Quelqu’un demande un accès tuteur à votre dossier',
        'preheader'        => ':guardian demande à agir comme votre tuteur sur OpesCare. Rien n’est partagé tant que vous n’acceptez pas.',
        'eyebrow'          => 'Demande d’accès tuteur',
        'title'            => 'Une demande d’accès tuteur à votre dossier',
        'intro'            => ':guardian demande un accès tuteur au dossier de santé OpesCare de :patient. Rien n’est partagé sans votre accord.',
        'intro_self'       => ':guardian demande un accès tuteur à votre dossier de santé OpesCare. Rien n’est partagé sans votre accord.',
        'someone'          => 'Quelqu’un',
        'fact_access'      => 'Accès demandé',
        'fact_relationship' => 'Lien',
        'access_full'      => 'Accès complet',
        'access_read_only' => 'Accès en lecture seule',
        'action'           => 'Examiner et décider',
        'expiry'           => 'Cette invitation expire dans :hours heures.',
        'ignore'           => 'Si vous n’attendiez pas cette demande, ignorez cet e-mail : aucun accès n’est accordé tant que vous n’acceptez pas.',
    ],

    /* ── Avis générique de la plateforme (OpesCareNotificationMail) ────── */
    'notification' => [
        'preheader' => 'Un message d’OpesCare concernant votre compte.',
        'eyebrow'   => 'Avis OpesCare',
    ],

];
