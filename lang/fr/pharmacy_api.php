<?php

/*
|--------------------------------------------------------------------------
| Comptoir de pharmacie — messages de l'API
|--------------------------------------------------------------------------
|
| Doit rester 1:1 avec lang/en/pharmacy_api.php — vérifié par
| `php scripts/i18n-audit.php` (clés, encodage UTF-8 ET parité des
| :paramètres).
|
| Règles de rédaction :
|   - Un refus ne confirme jamais qu'un dossier existe ailleurs. « Pas à ce
|     comptoir » est la réponse pour une ordonnance absente ET pour une
|     ordonnance adressée à une autre pharmacie.
|   - Une notification au patient s'affiche sur un écran verrouillé. Nommer la
|     pharmacie et le type d'événement ; jamais le médicament, la dose ou le
|     diagnostic.
|
*/

return [

    'errors' => [
        'facility_required'        => 'Votre session n\'est liée à aucun établissement. Reconnectez-vous et choisissez votre pharmacie.',
        'prescription_not_found'   => 'Cette ordonnance n\'est pas à ce comptoir.',
        'reservation_not_found'    => 'Cette réservation n\'est pas dans cette pharmacie.',
        'stock_not_found'          => 'Cette ligne ne figure pas dans le stock de cette pharmacie.',
        'invalid_transition'       => 'Cette étape n\'est pas possible depuis l\'état actuel du dossier.',
        'not_dispensable'          => 'Cette ordonnance ne peut plus être délivrée.',
        'item_not_on_prescription' => 'L\'un des articles ne figure pas sur cette ordonnance.',
        'stock_negative'           => 'Le stock ne peut pas passer sous zéro. La ligne compte :quantity ; l\'ajustement était de :delta.',
        'listing_unlinked'         => 'Cet établissement n\'a aucune fiche de pharmacie pour tenir son stock. Demandez à un administrateur de la lier dans l\'annuaire.',
        'collect_is_for_pickup'    => 'Une livraison se marque comme livrée, pas comme retirée.',
        'dispatch_is_for_delivery' => 'Seule une livraison peut être expédiée.',
    ],

    // Inscrit sur une ligne d'ordonnance quand le pharmacien note la quantité
    // réellement sortie du rayon.
    'dispense_note' => 'Quantité délivrée : :quantity',

    // Écritures du registre de stock (stock_movements.reason porte ce préfixe
    // pour distinguer un ajustement du comptoir d'une réception de marchandise).
    'movement_reason' => 'Ajustement du comptoir de pharmacie : :reason',

    // La cloche et la notification push reçues par le patient. :pharmacy est le
    // nom de la pharmacie, :reference la référence à citer au comptoir.
    'notifications' => [
        'prescription_ready' => [
            'title'   => 'Votre ordonnance est prête',
            'message' => ':pharmacy a préparé l\'ordonnance :reference. Apportez votre identifiant santé pour la retirer.',
            'action'  => 'Voir l\'ordonnance',
        ],
        'prescription_partially_dispensed' => [
            'title'   => 'Une partie de votre ordonnance a été délivrée',
            'message' => ':pharmacy a délivré une partie de l’ordonnance :reference. Le reste reste à retirer.',
            'action'  => 'Voir l’ordonnance',
        ],
        'prescription_dispensed' => [
            'title'   => 'Votre ordonnance a été délivrée',
            'message' => ':pharmacy a délivré l\'ordonnance :reference. Elle est désormais inscrite dans votre dossier de santé.',
            'action'  => 'Voir l\'ordonnance',
        ],
        'reservation_ready' => [
            'title'   => 'Votre commande est prête à retirer',
            'message' => ':pharmacy a préparé votre réservation :reference au comptoir. Présentez la référence à votre arrivée.',
            'action'  => 'Voir la réservation',
        ],
        'reservation_dispatched' => [
            'title'   => 'Votre commande est en route',
            'message' => ':pharmacy a expédié la réservation :reference en livraison. Gardez votre téléphone à portée de main.',
            'action'  => 'Suivre la commande',
        ],
        'reservation_delivered' => [
            'title'   => 'Votre commande a été livrée',
            'message' => ':pharmacy a marqué la réservation :reference comme livrée. Si vous ne l\'avez pas reçue, contactez la pharmacie.',
            'action'  => 'Voir la réservation',
        ],
    ],

];
