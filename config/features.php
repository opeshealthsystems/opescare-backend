<?php

/*
|--------------------------------------------------------------------------
| Module Feature Flags — V1 Launch Scope Freeze
|--------------------------------------------------------------------------
|
| See docs/plans/V1_LAUNCH_SCOPE.md. These flags freeze whole modules out of
| the V1 launch surface. Freezing is NOT deleting: no route file is removed,
| no migration is rolled back, no seeded institutional data is touched (the
| 15 real Cameroonian insurance_providers rows and the 897 facility rows stay
| exactly where they are). Flip a flag back on and the module returns intact.
|
| These flags are a KILL SWITCH and they FAIL CLOSED. They are deliberately
| NOT the same thing as `module:<key>` (App\Http\Middleware\EnforceModuleEntitlement),
| which is a subscription-entitlement gate and fails OPEN — it calls $next()
| when no organization or no active subscription is resolvable. Never use that
| middleware to freeze a module.
|
| Consumed by:
|   - App\Support\Features::enabled()
|   - App\Http\Middleware\EnforceFeatureFlag  ('feature:<key>' + the URI-pattern
|     freeze map applied in bootstrap/app.php)
|   - the @feature('<key>') Blade directive (App\Providers\AppServiceProvider)
|
*/

/*
 * Frozen modules default OFF in every environment EXCEPT `testing`.
 *
 * The existing suite exercises the frozen code paths directly (ClaimSubmissionTest,
 * billing, CDSS, inventory and analytics feature tests). Defaulting ON under
 * APP_ENV=testing keeps that suite green, so the freeze stays a deployment
 * decision rather than a reason to delete coverage for code we intend to
 * un-freeze later. Production and staging must opt in explicitly, per module.
 *
 * A test that needs to assert frozen behaviour overrides the flag directly:
 *   config(['features.flags.insurance' => false]);
 *
 * NOT every flag in this file freezes. A flag whose default is a literal `true`
 * rather than $frozenDefault describes a surface that SHIPS and merely keeps a
 * kill switch — 'insurance_coverage' is the one such flag today. The mechanism
 * is identical (fail closed, 404); only the default differs.
 */
$frozenDefault = env('APP_ENV', 'production') === 'testing';

return [

    /*
    |----------------------------------------------------------------------
    | Flags
    |----------------------------------------------------------------------
    |
    | One explicit env-driven boolean per gated module. A key that is absent
    | from this list is treated as frozen by Features::enabled() — unknown
    | keys never grant access.
    |
    | Most keys here default OFF ($frozenDefault) because the module is frozen
    | out of V1. 'insurance_coverage' defaults ON because that surface ships;
    | the flag exists so it can still be killed without a deploy.
    |
    */
    'flags' => [

        /*
         * INSURANCE IS SPLIT IN TWO. Read this before flipping either flag.
         *
         *   COVERAGE  = identity data.  "Is this patient covered, by whom,
         *               until when." It is an attribute of the Health ID, it
         *               is read-only, and it travels with the patient across
         *               facilities. That is the interoperability product, so
         *               it ships: 'insurance_coverage' below defaults ON.
         *
         *   CLAIMS    = a money workflow.  policy -> preauth -> claim ->
         *               adjudication -> payment. It is a manual ledger with a
         *               human in every loop, and the Cameroonian payers it
         *               would have to settle against (CNPS, Activa, Chanas,
         *               Saham) expose no APIs to settle with. Shipping it means
         *               shipping data entry, not exchange. So it stays frozen:
         *               'insurance' below defaults OFF.
         *
         * The asymmetry is deliberate, not an oversight. A patient being able
         * to read their own coverage does not imply OpesCare can process a
         * claim against it, and the flags must be able to say so separately.
         */

        // CLAIMS — frozen. Manual claims ledger: claims, preauths, policies,
        // provider admin. 15 real insurers seeded; every other insurance table
        // is empty and the claim lifecycle has never run once in prod.
        // Cameroonian payers have no APIs — this is a sales problem, not code.
        // Freezes: api/v1/insurance/*, portals/insurance/*.
        'insurance' => (bool) env('FEATURE_INSURANCE', $frozenDefault),

        // COVERAGE — LIVE. The patient's read-only view of their own policies
        // (GET api/mobile/insurance -> MobileInsuranceController::index):
        // policy number, status, effective/expiry dates, plan and provider
        // name. No write path, no claim, no money. This is the same fact the
        // FHIR R4 Coverage resource already exposes to partner systems on
        // api/fhir/R4/Coverage (never frozen) — leaving it off meant a partner
        // could read a patient's coverage while the patient could not.
        //
        // NOT $frozenDefault: this defaults ON in every environment, including
        // production. Set FEATURE_INSURANCE_COVERAGE=false to kill it; that
        // does NOT unfreeze claims, and FEATURE_INSURANCE=true does not gate
        // this — the two flags are independent on purpose.
        'insurance_coverage' => (bool) env('FEATURE_INSURANCE_COVERAGE', true),

        // Facility-internal patient billing (invoices, cashier sessions,
        // wallets, payment plans). Internal ops, not cross-system exchange.
        // NOTE: this does NOT cover OpesCare's own platform revenue —
        // portals/admin/financial/*, portals/admin/subscription/* and the
        // MoMo/Orange gateway callbacks stay live. That is how we get paid.
        'billing' => (bool) env('FEATURE_BILLING', $frozenDefault),

        // Facility-internal stock, supply chain and batch tracking. All tables
        // already empty; schema preserved.
        // NOTE: this does NOT cover the pharmacy/blood FINDERS, which are the
        // interoperability product itself and ship in V1 — nor the partner
        // stock-sync ingest that feeds them.
        //
        // That note was true of the intent and false of the code. The URI map
        // in bootstrap/app.php froze `portals/staff/inventory/*`, which swept
        // up the blood-bank stock screen — the ONLY reachable writer of
        // `blood_availability`, the table the public Blood Finder reads. The
        // finder shipped with no way to be given data and answered [] for ever.
        //
        // The blood entry paths are therefore CARVED OUT of this flag:
        //   LIVE   portals/staff/inventory/blood        (+ /blood/{id}/adjust,
        //                                                  /blood/{id}/flag)
        //   FROZEN portals/staff/inventory/pharmacy*
        //   FROZEN portals/staff/supply*
        //   FROZEN portals/pharmacy/inventory
        //   FROZEN api/v1/inventory*   (incl. api/v1/inventory/blood — partners
        //                               use the live, unfrozen ingest at
        //                               POST api/v1/connect/inventory/blood-stock/sync)
        //
        // The carve-out lives in the pattern list in bootstrap/app.php, not in
        // this flag: flipping FEATURE_INVENTORY_OPS still turns facility
        // inventory operations on and off as a whole, and turning it OFF must
        // never take the Blood Finder's only data source with it. Read the
        // 'inventory_ops' block in bootstrap/app.php before editing either.
        'inventory_ops' => (bool) env('FEATURE_INVENTORY_OPS', $frozenDefault),

        // Drug-interaction / allergy / lab-rule alerting. Clinical-safety
        // liability we would own; out of scope until we can carry it.
        'clinical_decision_support' => (bool) env('FEATURE_CLINICAL_DECISION_SUPPORT', $frozenDefault),

        // Analytics and public-health dashboards. Build the data foundation
        // first — dashboards over 2.4% pharmacy coverage mislead more than
        // they inform.
        // NOTE: MINSANTE statutory report generation/submission stays live;
        // only the dashboard/intelligence read surface is frozen.
        'analytics_dashboards' => (bool) env('FEATURE_ANALYTICS_DASHBOARDS', $frozenDefault),

        // MARKETPLACE — frozen. Patient-facing plan shopping and purchase
        // (api/mobile/insurance/marketplace/*, portals/patient/insurance/*).
        // Commercial ambiguity and zero insurance_plans rows in the database.
        // Selling a policy is a money workflow like claims; reading one you
        // already hold is not. See the coverage/claims note above.
        'insurance_marketplace' => (bool) env('FEATURE_INSURANCE_MARKETPLACE', $frozenDefault),

        // Full telehealth platform — waiting-room queue management and video
        // session orchestration. The thin book -> consult path (create, view,
        // consent, cancel, start, end) stays IN and is NOT gated by this flag.
        'telemedicine_full' => (bool) env('FEATURE_TELEMEDICINE_FULL', $frozenDefault),

    ],

];
