<?php

/**
 * Facility switching — one catalogue for both surfaces.
 *
 * A clinician posted at more than one facility can choose which one they are
 * acting as: on the web through /select-facility (reachable mid-session from
 * the sidebar) and on the phone through GET /api/mobile/provider/facilities +
 * POST /api/mobile/provider/facility.
 *
 * `api.*` messages are returned by the mobile endpoints. SetLocale runs on the
 * `web` middleware group only, so /api/* answers in the application default
 * locale regardless of Accept-Language — that is why every API refusal here
 * also carries an `error_code` the phone can translate itself.
 *
 * MUST stay 1:1 with lang/fr/facility_switch.php, same keys and the same
 * :placeholders (php scripts/i18n-audit.php is a CI gate).
 */

return [

    // ── Web: the selector and the sidebar link ───────────────────────────
    'nav'                 => 'Switch Facility',
    'acting_as'           => 'Acting as',
    'switched'            => 'You are now acting as :facility.',
    'already_active'      => 'You are already acting as :facility.',
    'sign_in_required'    => 'Please sign in to choose a facility.',

    // ── Mobile API ───────────────────────────────────────────────────────
    'api' => [
        'not_a_member'        => 'You do not hold an active posting at that facility.',
        'facility_suspended'  => 'That facility is suspended and cannot be used.',
    ],

];
