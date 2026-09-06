<?php

/*
| API mobile clinicien — messagerie et profil personnel du clinicien.
|
| Messages d'API volontairement simples : ils sont lus sur un téléphone par
| une personne qui se trouve avec un patient. Ils disent ce qui s'est passé
| sans nommer une table, un code HTTP ni une conversation qui pourrait ne
| pas être la sienne.
*/

return [

    // ── Messagerie ───────────────────────────────────────────────────────
    // Un seul message pour « conversation inexistante » et « pas la vôtre ».
    // Une réponse distincte confirmerait l'existence de la conversation.
    'thread_not_found' => 'Cette conversation n\'est pas disponible.',
    'send_failed'      => 'Votre réponse n\'a pas pu être envoyée. La conversation a peut-être été fermée.',

    // Ouvrir une conversation AVEC un patient (store). Le refus est la règle
    // du dossier, dans les mots du dossier : une seule définition de la
    // relation de soins, que le clinicien veuille lire ou écrire.
    'patient_not_found'   => 'Aucun dossier patient ne correspond à cet identifiant.',
    'no_relationship'     => 'Vous n\'avez pas de relation de soins avec ce patient ; aucune conversation ne peut donc être ouverte avec lui. Demandez au patient d\'accorder l\'accès à votre établissement, ou ouvrez le dossier depuis votre propre agenda.',
    // Une conversation que la boîte du clinicien ne pourrait jamais afficher
    // est pire qu'un refus : le patient croirait avoir été contacté.
    'no_delivery_context' => 'Ce patient n\'a aucun rendez-vous dans votre établissement ; une conversation ouverte ici n\'atteindrait pas votre boîte. Prenez ou enregistrez d\'abord un rendez-vous pour lui.',
    'thread_not_created'  => 'La conversation n\'a pas pu être ouverte. Réessayez.',

    // La cloche et la notification push du patient lorsqu'un clinicien ouvre
    // une conversation avec lui. S'affiche sur un écran verrouillé : nomme le
    // clinicien et l'établissement, jamais l'objet (texte libre du clinicien)
    // ni le contenu.
    'patient_notice'      => [
        'new_thread_title'  => 'Nouveau message de :provider',
        'new_thread_body'   => ':provider (:facility) vous a envoyé un message. Ouvrez Messages pour le lire et répondre.',
        'new_thread_action' => 'Ouvrir la conversation',
        'a_clinician'       => 'Votre clinicien',
        'your_facility'     => 'votre établissement',
    ],

    // ── Photo de profil ──────────────────────────────────────────────────
    'photo_not_an_image' => 'Ce fichier n\'est pas une image. Envoyez une photo JPEG, PNG ou WebP.',
    'photo_dimensions'   => 'Cette image est trop grande. Le plus grand côté peut atteindre :max pixels.',
    'photo_not_found'    => 'Cette photo n\'est plus disponible.',

    // ── Session ──────────────────────────────────────────────────────────
    'unauthenticated' => 'Votre session a expiré. Reconnectez-vous.',

];
