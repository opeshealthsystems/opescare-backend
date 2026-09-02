<?php

return [
    'display' => [
        'title'          => 'Affichage de la file d\'attente',
        'now_serving'    => 'En cours de service',
        'ticket'         => 'Ticket :number',
        'station'        => 'Poste :name',
        'waiting'        => ':count en attente',
        'your_turn_soon' => 'Votre tour approche.',
        'called'         => 'Veuillez vous rendre à :station.',
    ],
    'status' => [
        'waiting'    => 'En attente',
        'called'     => 'Appelé',
        'in_service' => 'En service',
        'completed'  => 'Terminé',
        'cancelled'  => 'Annulé',
        'no_show'    => 'Absent',
        'transferred' => 'Transféré',
    ],
    'actions' => [
        'check_in'    => 'Enregistrement',
        'call_next'   => 'Appeler le suivant',
        'start'       => 'Démarrer le service',
        'transfer'    => 'Transférer le patient',
        'complete'    => 'Terminer',
        'prioritize'  => 'Prioriser',
    ],
    'priority' => [
        'emergency'    => 'Urgence',
        'urgent'       => 'Urgent',
        'normal'       => 'Normal',
        'scheduled'    => 'Planifié',
    ],
    'privacy' => [
        'masked_display' => 'Pour la confidentialité, seuls les numéros de ticket sont affichés sur l\'écran public.',
    ],
];
