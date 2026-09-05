<?php

/*
|--------------------------------------------------------------------------
| Report de rendez-vous mobile — messages destines au patient
|
| Traduction 1:1 de lang/en/appointments_reschedule.php. Les cles, l'encodage
| UTF-8 et les :parametres doivent correspondre exactement a la version
| anglaise — `php scripts/i18n-audit.php` verifie les trois.
|--------------------------------------------------------------------------
*/

return [

    // ── Resultat ────────────────────────────────────────────────────────────
    'success' => 'Votre rendez-vous a été déplacé au :when.',

    // La phrase que porte chaque echec : un patient dont le report a echoue
    // doit apprendre, dans la meme phrase, qu'il n'a pas perdu son creneau.
    'unchanged' => 'Votre rendez-vous initial du :when reste inchangé.',

    // ── Le rendez-vous ne peut pas être déplacé ─────────────────────────────
    'not_found'           => 'Rendez-vous introuvable.',
    'cancelled'           => 'Ce rendez-vous a été annulé, il ne peut donc plus être déplacé. Veuillez prendre un nouveau rendez-vous.',
    'completed'           => 'Ce rendez-vous a déjà eu lieu, il ne peut donc plus être déplacé.',
    'no_show'             => 'Ce rendez-vous a été enregistré comme visite manquée, il ne peut donc plus être déplacé. Veuillez prendre un nouveau rendez-vous.',
    'checked_in'          => 'Vous êtes déjà enregistré pour ce rendez-vous, il ne peut donc plus être déplacé. Veuillez vous adresser à l\'accueil.',
    'already_rescheduled' => 'Ce rendez-vous a déjà été déplacé. Ouvrez le rendez-vous de remplacement pour le modifier à nouveau.',
    'not_reschedulable'   => 'Un rendez-vous ayant le statut « :status » ne peut pas être déplacé.',
    'in_past'             => 'Ce rendez-vous était prévu le :when et appartient désormais au passé, il ne peut donc plus être déplacé.',

    // ── Le créneau choisi ne peut pas être pris ─────────────────────────────
    'slot_not_found'          => 'Ce créneau horaire n\'existe plus.',
    'same_slot'               => 'C\'est l\'horaire pour lequel vous êtes déjà inscrit. Veuillez choisir un autre horaire.',
    'facility_slot_mismatch'  => 'Ce créneau appartient à un autre établissement. Pour changer d\'établissement, annulez ce rendez-vous et prenez-en un nouveau.',
    'slot_not_bookable'       => 'Ce créneau horaire n\'est plus disponible.',
    'facility_not_bookable'   => 'Cet établissement n\'accepte pas de rendez-vous pour le moment.',
    'slot_taken'              => 'Ce créneau horaire a été pris pendant que vous le choisissiez.',

];
