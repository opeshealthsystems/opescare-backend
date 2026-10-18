<?php

/*
|--------------------------------------------------------------------------
| Module Feature Flags — OpesCare
|--------------------------------------------------------------------------
|
| This is the FINDER product: drug finder, blood finder, appointment
| booking and the facility directory. It was extracted from the OpesCare
| platform, where this file froze whole modules out of the V1 launch
| surface (insurance claims/coverage/marketplace, patient billing, facility
| inventory ops, clinical decision support, analytics dashboards, full
| telemedicine).
|
| Those modules are not part of this product. They are DELETED — routes,
| controllers, views and nav are gone — not frozen behind a flag. A freeze
| exists so a module can come back with a config flip; nothing here is
| coming back, so there is nothing to freeze and the flag list below is
| empty on purpose. Do not re-add a platform key to "be safe": an absent
| key already FAILS CLOSED (Features::enabled() treats unknown keys as
| frozen), and a flag that gates no surface is only a place for drift.
|
| The MECHANISM stays, because it costs nothing and the next launch-scope
| decision will want it:
|
|   - App\Support\Features::enabled('<key>')  — flag lookup; fails closed on
|     unknown keys, missing config, non-boolean values.
|   - App\Http\Middleware\EnforceFeatureFlag — the 'feature:<key>' route
|     middleware alias, plus the global URI-pattern freeze map declared via
|     Features::freeze() in bootstrap/app.php (currently not populated).
|     Fails CLOSED with a 404, never 403 — a switched-off feature must not
|     advertise that it exists.
|   - @feature('<key>') Blade directive       — registered in
|     App\Providers\AppServiceProvider; renders nothing for unknown keys.
|   - the feature_states table                — admin override layer read by
|     Features::state(); it can pilot/disable a KNOWN key but can never
|     resurrect an absent one.
|
| To gate a future feature: add an env-driven boolean under 'flags', wrap
| its nav links in @feature('<key>'), and either tag its routes with the
| 'feature:<key>' middleware or give it URI patterns in the
| bootstrap/app.php freeze map. Keep the platform convention: default OFF
| in every environment except `testing`, so shipping is an explicit
| per-environment opt-in and a kill needs no deploy.
|
| This is deliberately NOT `module:<key>`
| (App\Http\Middleware\EnforceModuleEntitlement) — that is a
| subscription-entitlement gate and fails OPEN. Never use it as a kill
| switch. Nor is it 'patient.feature' (per-patient subscription plan).
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Flags
    |----------------------------------------------------------------------
    |
    | One explicit env-driven boolean per gated feature. A key absent from
    | this list is treated as frozen by Features::enabled() — unknown keys
    | never grant access.
    |
    | Empty today: every surface this product ships — the finders, booking,
    | the facility directory, and the data plane that feeds them (partner
    | stock-sync ingest + the staff blood-stock screen) — ships
    | unconditionally.
    |
    */
    'flags' => [],

];
