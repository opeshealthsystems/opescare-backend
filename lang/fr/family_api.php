<?php

/*
|--------------------------------------------------------------------------
| API mobile Famille / Personnes à charge
|--------------------------------------------------------------------------
|
| Messages renvoyés par MobileFamilyController et le middleware
| ActAsDependent, et notifications in-app écrites par une invitation
| familiale. À garder 1:1 avec lang/en/family_api.php.
|
*/

return [
    // ── Refus ───────────────────────────────────────────────────────────────
    'dependent_forbidden'  => "Vous n'avez pas accès au dossier de ce membre de la famille.",
    'member_not_found'     => 'Membre de la famille introuvable.',
    'dependent_is_adult'   => 'Cette personne a :age ans ou plus. Les adultes gardent leur propre identifiant santé — envoyez-leur plutôt une invitation.',
    'invitation_not_sent'  => "Aucune invitation n'a pu être envoyée à ce contact. Vérifiez les informations ou demandez-lui de partager son identifiant santé.",
    'invitation_not_found' => "Cette invitation n'est plus disponible.",

    // ── Résultats ───────────────────────────────────────────────────────────
    'invitation_accepted'  => 'Invitation acceptée. :name peut maintenant consulter votre dossier.',
    'invitation_declined'  => 'Invitation refusée.',

    // ── Libellés ────────────────────────────────────────────────────────────
    'unknown_contact' => 'Contact inconnu',
    'someone'         => "Quelqu'un",

    'access' => [
        'full'      => 'accès complet',
        'read_only' => 'lecture seule',
    ],

    // Le rôle du tuteur envers la personne à charge (« rattaché à votre dossier en tant que … »).
    'relationship' => [
        'parent'         => 'parent',
        'grandparent'    => 'grand-parent',
        'child'          => 'enfant',
        'spouse'         => 'conjoint(e)',
        'sibling'        => 'frère ou sœur',
        'caregiver'      => 'aidant(e)',
        'legal_guardian' => 'tuteur légal',
        'other'          => 'proche',
    ],

    // ── Notifications in-app ────────────────────────────────────────────────
    'notification_invite_title'    => ':guardian souhaite être rattaché(e) à votre dossier',
    'notification_invite_message'  => ':guardian demande à être rattaché(e) à votre dossier OpesCare en tant que :relationship (:access). Ouvrez Famille pour accepter ou refuser.',
    'notification_invite_action'   => "Voir l'invitation",

    'notification_accepted_title'   => ':name a accepté votre invitation',
    'notification_accepted_message' => 'Vous pouvez maintenant consulter le dossier de :name depuis Famille.',
    'notification_declined_title'   => ':name a refusé votre invitation',
    'notification_declined_message' => "Aucun lien n'a été créé. Vous pourrez envoyer une nouvelle invitation plus tard.",
    'notification_answer_action'    => 'Ouvrir Famille',
    'invitation_queued' => 'Si cette personne possède un Identifiant Santé OpesCare, elle recevra votre invitation dans son application. Elle apparaîtra dans vos invitations en attente une fois envoyée.',
];
