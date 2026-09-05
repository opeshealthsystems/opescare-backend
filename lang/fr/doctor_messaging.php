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

    // ── Photo de profil ──────────────────────────────────────────────────
    'photo_not_an_image' => 'Ce fichier n\'est pas une image. Envoyez une photo JPEG, PNG ou WebP.',
    'photo_dimensions'   => 'Cette image est trop grande. Le plus grand côté peut atteindre :max pixels.',
    'photo_not_found'    => 'Cette photo n\'est plus disponible.',

    // ── Session ──────────────────────────────────────────────────────────
    'unauthenticated' => 'Votre session a expiré. Reconnectez-vous.',

];
