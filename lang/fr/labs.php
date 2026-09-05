<?php

/**
 * Chaînes serveur pour les points d'accès mobiles du laboratoire.
 *
 * Seuls les messages émis par l'API elle-même figurent ici — erreurs de
 * validation et vocabulaire d'état du payload. Tout ce qu'affichent les ÉCRANS
 * provient du catalogue de l'application (lib/i18n), pas de ce fichier.
 *
 * Doit rester 1:1 avec lang/en/labs.php — vérifié par `php scripts/i18n-audit.php`.
 */
return [

    'request' => [
        // POST /api/mobile/labs/requests, lorsque test_code ne correspond à
        // aucun examen publié par un laboratoire.
        'unknown_test'  => 'Cet examen ne figure sur la liste publiée d’aucun laboratoire. Choisissez un examen dans le catalogue.',
        'submitted'     => 'Votre demande a été transmise à votre équipe soignante.',
        'not_an_order'  => 'Ceci est une demande, pas une prescription d’analyse. Un clinicien doit l’approuver avant que l’examen soit réalisé.',
    ],

    'status' => [
        'submitted' => 'Envoyée',
        'approved'  => 'Approuvée',
        'declined'  => 'Refusée',
        'cancelled' => 'Annulée',
    ],

    'priority' => [
        'routine' => 'Courant',
        'urgent'  => 'Urgent',
        'stat'    => 'Immédiat',
    ],

    'order_status' => [
        'ordered'     => 'Prescrit',
        'in_progress' => 'En cours',
        'completed'   => 'Terminé',
        'cancelled'   => 'Annulé',
    ],

    'flag' => [
        'normal'    => 'Normal',
        'low'       => 'Bas',
        'high'      => 'Élevé',
        'abnormal'  => 'Anormal',
        'critical'  => 'Critique',
        // Une valeur publiée par le laboratoire sans indicateur, et pour
        // laquelle aucune valeur de référence n'existe. Ce n'est PAS un
        // résultat normal — personne ne l'a comparée à quoi que ce soit.
        'unflagged' => 'Non renseigné',
        // Une prescription dont aucun résultat n'a encore été publié.
        'pending'   => 'Résultats en attente',
    ],

    'report' => [
        'no_range'    => 'Non communiquée',
        'no_document' => 'Le laboratoire n’a joint aucun compte rendu signé à cette prescription.',
        'link_expired' => 'Ce lien de téléchargement a expiré. Rouvrez le compte rendu pour en obtenir un nouveau.',
    ],

];
