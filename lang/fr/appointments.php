<?php

return [
    'book' => [
        'title'       => 'Prendre Rendez-vous',
        'subtitle'    => 'Planifier une consultation avec votre prestataire',
        'date'        => 'Date',
        'time'        => 'Heure',
        'provider'    => 'Prestataire',
        'type'        => 'Type de Rendez-vous',
        'reason'      => 'Motif de la visite',
        'notes'       => 'Notes supplémentaires',
        'submit'      => 'Confirmer la réservation',
        'success'     => 'Rendez-vous pris avec succès.',
        'conflict'    => 'Ce créneau horaire n\'est plus disponible. Veuillez en choisir un autre.',
    ],
    'status' => [
        'scheduled'   => 'Planifié',
        'confirmed'   => 'Confirmé',
        'checked_in'  => 'Enregistré',
        'completed'   => 'Terminé',
        'cancelled'   => 'Annulé',
        'no_show'     => 'Absent',
        'rescheduled' => 'Reprogrammé',
    ],
    'actions' => [
        'reschedule'  => 'Reprogrammer',
        'cancel'      => 'Annuler le rendez-vous',
        'check_in'    => 'Enregistrement',
        'no_show'     => 'Marquer comme absent',
        'confirm'     => 'Confirmer le rendez-vous',
    ],
    'cancel_modal' => [
        'title'   => 'Annuler le rendez-vous',
        'reason'  => 'Motif de l\'annulation',
        'confirm' => 'Oui, Annuler',
        'back'    => 'Retour',
    ],
    'reminders' => [
        'sent'    => 'Rappel de rendez-vous envoyé.',
        'pending' => 'Rappel prévu pour :time.',
    ],
    'no_show' => [
        'recorded' => 'Absence enregistrée pour :patient.',
        'fee_note' => 'Des frais d\'absence peuvent s\'appliquer selon la politique de l\'établissement.',
    ],
];
