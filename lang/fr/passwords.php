<?php

return [
    /*
     * Les cinq clés que le « password broker » de Laravel recherche par nom de
     * statut (Illuminate\Auth\Passwords\PasswordBroker::RESET_LINK_SENT, etc.).
     * Ne pas renommer : ces clés sont construites par le framework.
     */
    'reset'     => 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter avec.',
    'sent'      => 'Nous vous avons envoyé par e-mail le lien de réinitialisation du mot de passe.',
    'throttled' => 'Veuillez patienter avant de réessayer.',
    'token'     => 'Ce jeton de réinitialisation du mot de passe est invalide.',
    'user'      => 'Nous ne trouvons pas d’utilisateur avec cette adresse e-mail.',

    /*
     * Le formulaire de demande. Ce message est renvoyé pour TOUTE adresse —
     * enregistrée, inconnue, ou demandée deux fois en une minute — il ne doit
     * donc jamais se lire comme la confirmation qu’un compte existe.
     */
    'request' => [
        'desc' => 'Saisissez l’adresse e-mail de votre compte OpesCare. Si elle correspond à un compte, nous vous enverrons un lien permettant de définir un nouveau mot de passe.',
        'sent' => 'Si cette adresse correspond à un compte OpesCare, un lien de réinitialisation est en route. Le lien est valable :minutes minutes et ne peut servir qu’une seule fois.',
    ],

    /* Le formulaire derrière un lien valide. */
    'form' => [
        'intro'          => 'Choisissez un nouveau mot de passe pour :email. Vous serez déconnecté partout ailleurs dès son enregistrement.',
        'policy_title'   => 'Votre nouveau mot de passe doit :',
        'policy_length'  => 'comporter au moins 8 caractères',
        'policy_unique'  => 'être différent des mots de passe utilisés sur d’autres services',
        'policy_private' => 'ne jamais être communiqué à qui que ce soit, y compris au personnel OpesCare',
    ],

    /* L’unique message d’échec visible pour tout lien périmé. */
    'invalid_link' => [
        'title' => 'Ce lien de réinitialisation n’est plus valable.',
        'body'  => 'Un lien de réinitialisation ne fonctionne qu’une fois et expire au bout de :minutes minutes. Celui-ci a déjà été utilisé, a expiré, ou n’a pas été émis par OpesCare.',
        'next'  => 'Votre mot de passe n’a pas été modifié. Demandez un nouveau lien : il vous parviendra en quelques minutes.',
        'cta'   => 'Demander un nouveau lien',
    ],

    /* L’e-mail qui transporte le lien. */
    'mail' => [
        'subject'    => 'Réinitialisez votre mot de passe OpesCare',
        'greeting'   => 'Bonjour,',
        'intro'      => 'Nous avons reçu une demande de réinitialisation du mot de passe de votre compte OpesCare.',
        'action'     => 'Définir un nouveau mot de passe',
        'expiry'     => 'Ce lien expire dans :minutes minutes et ne peut être utilisé qu’une seule fois.',
        'ignore'     => 'Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail : votre mot de passe ne sera pas modifié et personne n’a obtenu l’accès à votre compte.',
        'salutation' => 'L’équipe OpesCare',
    ],

    /* L’e-mail envoyé une fois le mot de passe réellement modifié. */
    'changed_mail' => [
        'subject'  => 'Le mot de passe de votre compte OpesCare a été modifié',
        'greeting' => 'Bonjour,',
        'intro'    => 'Le mot de passe de votre compte OpesCare vient d’être modifié au moyen d’un lien de réinitialisation.',
        'sessions' => 'Toutes les sessions connectées à ce compte ont été fermées, sur tous les appareils.',
        'origin'   => 'La modification a été effectuée depuis l’adresse IP :ip.',
        'warn'     => 'Si vous n’êtes pas à l’origine de cette modification, contactez immédiatement le support OpesCare : quelqu’un d’autre a accès à l’adresse e-mail de votre compte.',
    ],
];
