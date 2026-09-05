<?php

/**
 * Changement d'établissement — un seul catalogue pour les deux surfaces.
 *
 * Un clinicien affecté à plusieurs établissements peut choisir celui au nom
 * duquel il agit : sur le web via /select-facility (accessible en cours de
 * session depuis la barre latérale) et sur le téléphone via
 * GET /api/mobile/provider/facilities + POST /api/mobile/provider/facility.
 *
 * Les messages `api.*` sont renvoyés par les points de terminaison mobiles.
 * SetLocale ne s'applique qu'au groupe de middleware `web` : /api/* répond
 * donc toujours dans la langue par défaut de l'application, quel que soit
 * Accept-Language — d'où l'`error_code` que le téléphone traduit lui-même.
 *
 * DOIT rester 1:1 avec lang/en/facility_switch.php : mêmes clés et mêmes
 * :placeholders (php scripts/i18n-audit.php est un contrôle CI).
 */

return [

    // ── Web : le sélecteur et le lien de la barre latérale ───────────────
    'nav'                 => "Changer d'établissement",
    'acting_as'           => 'Vous agissez pour',
    'switched'            => 'Vous agissez désormais pour :facility.',
    'already_active'      => 'Vous agissez déjà pour :facility.',
    'sign_in_required'    => 'Veuillez vous connecter pour choisir un établissement.',

    // ── API mobile ───────────────────────────────────────────────────────
    'api' => [
        'not_a_member'        => "Vous n'avez pas d'affectation active dans cet établissement.",
        'facility_suspended'  => 'Cet établissement est suspendu et ne peut pas être utilisé.',
    ],

];
