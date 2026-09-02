<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Maintenance mode — checked on every request before routing.
        // Health endpoints (/up, /api/health) and bypass-token holders are exempt.
        // Must come AFTER ForceHttps so bypass tokens are sent over HTTPS.
        $middleware->prepend(\App\Http\Middleware\CheckMaintenanceMode::class);

        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\DemoSessionMiddleware::class,
            \App\Http\Middleware\DemoDataScope::class,
            // Platform god-mode isolation — runs on every web request but is a
            // no-op except on platform-only paths (/portals/admin/*, /admin/*
            // god mode). Global registration guarantees no admin route can leak
            // to a facility-tier user regardless of which route group defines it.
            \App\Http\Middleware\RequirePlatformAdmin::class,
            \App\Http\Middleware\AddSecurityHeaders::class,
        ]);
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\DemoSessionMiddleware::class,
            \App\Http\Middleware\DemoDataScope::class,
            \App\Http\Middleware\AddSecurityHeaders::class,
            \App\Http\Middleware\LogApiUsage::class,
            \App\Http\Middleware\ApiVersionHeaders::class,
        ]);
        $middleware->append(\App\Http\Middleware\DatabaseHealthMiddleware::class);

        // Subdomain scope enforcement (enabled via SUBDOMAIN_ROUTING=true in .env)
        $middleware->prepend(\App\Http\Middleware\EnforceSubdomainScope::class);

        /*
        |--------------------------------------------------------------------
        | V1 launch-scope module freeze — applied BY URI PATTERN
        |--------------------------------------------------------------------
        |
        | See docs/plans/V1_LAUNCH_SCOPE.md and config/features.php.
        |
        | Every pattern below is gated by its module's flag in config/features.php
        | and FAILS CLOSED with a 404. Why patterns instead of route middleware:
        | routes/api.php is SEALED (apps/api-laravel/CLAUDE.md) and holds the
        | insurance v1 group. Gating by path freezes it without touching the file.
        | routes/web.php is not sealed, but the freeze list is kept here in one
        | auditable place rather than scattered across route files — one diff
        | shows exactly what V1 does and does not ship, and one flag reverses it.
        |
        | Nothing is deleted. Every route, model, migration and seeded row stays.
        |
        | Read the NOT-frozen notes carefully: the neighbouring paths they name
        | are V1 launch features and must keep working.
        |
        */
        \App\Support\Features::freeze([

            // Manual claims ledger: claims, preauths, policies, providers.
            // NOT frozen: nothing else under api/v1 or portals.
            'insurance' => [
                'api/v1/insurance',
                'api/v1/insurance/*',
                'portals/insurance',
                'portals/insurance/*',
            ],

            // The patient's read-only view of their OWN coverage. Defaults ON in
            // config/features.php, unlike everything else in this map: coverage is
            // Health-ID identity data ("who covers this person, until when"), not the
            // claims money workflow above. It is the same fact FHIR R4 Coverage already
            // exposes to partners, so freezing it left the patient unable to see their
            // own cover while a partner system could read it — backwards.
            //
            // This MUST be its own key. featureForRequest() returns the FIRST matching
            // pattern, so while 'insurance' owned 'api/mobile/insurance' the endpoint
            // 404'd on the claims flag and the coverage flag was never consulted.
            'insurance_coverage' => [
                'api/mobile/insurance',          // exact — marketplace is a separate key below
            ],

            // Patient-facing plan shopping / purchase.
            'insurance_marketplace' => [
                'api/mobile/insurance/marketplace',
                'api/mobile/insurance/marketplace/*',
                'portals/patient/insurance',
                'portals/patient/insurance/*',
            ],

            // Facility-internal patient billing.
            // Every pattern below still matches a live route — the api/v1 ones
            // in the SEALED routes/api.php, the portal ones in routes/web.php —
            // so none of them may be removed from this list.
            // NOT frozen: portals/admin/subscription/* and
            // api/payments/mobile-money/*/callback — that is OpesCare's own
            // platform revenue plus live gateway webhooks. 404-ing a payment
            // provider's callback loses money. (portals/admin/financial/* was
            // deleted outright from routes/web.php.)
            'billing' => [
                'api/v1/billing',
                'api/v1/billing/*',
                'api/v1/payment-plans',
                'api/v1/payment-plans/*',
                'api/v1/patients/*/payment-plans',
                'portals/staff/billing',
                'portals/staff/billing/*',
                'portals/patient/billing',
                'portals/patient/billing/*',
                'portals/lite/billing',
            ],

            // Facility-internal stock, supply chain, batch tracking.
            // NOT frozen — these are the V1 finders and the data plane feeding
            // them, and they must keep working:
            //   api/mobile/pharmacy/*        (pharmacy finder)
            //   api/mobile/blood/*           (blood finder)
            //   api/v1/care-map/*            (medicine + blood search)
            //   api/v1/connect/inventory/*   (partner stock-sync ingest)
            //   api/v1/sdk/facilities/*/stock
            //   api/v1/pharmacy/formulary/*  (medicine catalogue)
            //   portals/staff/inventory/blood + /blood/*  <- SEE BELOW
            //
            // ── The blood carve-out. Do not "tidy" this back. ───────────────
            //
            // This list used to read `portals/staff/inventory/*`, which swept
            // up the blood-bank stock screen along with pharmacy stock. That
            // 404'd the ONLY reachable writer of `blood_availability`, and
            // `blood_availability` is what the public Blood Finder
            // (GET api/v1/care-map/blood/search, GET api/mobile/blood/search)
            // reads. The finder shipped in V1, had no way to be given data,
            // and returned [] for ever.
            //
            // So the pharmacy paths are enumerated one by one instead of
            // wildcarding the whole prefix. The freeze is unchanged in every
            // other respect — `portals/staff/inventory/pharmacy*`,
            // `portals/staff/supply*`, `api/v1/inventory*` and
            // `portals/pharmacy/inventory` are all still frozen, because
            // facility inventory and supply-chain OPERATIONS are genuinely out
            // of V1. Only the blood-bank stock entry that feeds the finder is
            // let through. This matches what config/features.php has always
            // said about this flag: it "does NOT cover the pharmacy/blood
            // FINDERS ... nor the partner stock-sync ingest that feeds them".
            //
            // `api/v1/inventory/blood*` stays FROZEN on purpose even though it
            // writes the same table: it is the facility inventory-ops API, and
            // partners already have a live, unfrozen ingest for blood at
            // POST api/v1/connect/inventory/blood-stock/sync (which does not
            // match `api/v1/inventory*`). Blood therefore has two live writers
            // — the staff screen and the partner ingest — without unfreezing
            // the inventory-ops API surface.
            //
            // If a new route is added under portals/staff/inventory/, decide
            // deliberately which list it belongs to. There is no catch-all any
            // more, so an unlisted path is LIVE, not frozen.
            'inventory_ops' => [
                'api/v1/inventory',
                'api/v1/inventory/*',
                'portals/staff/inventory',            // bare prefix; no route today
                'portals/staff/inventory/pharmacy',
                'portals/staff/inventory/pharmacy/*',
                'portals/staff/supply',
                'portals/staff/supply/*',
                'portals/pharmacy/inventory',
            ],

            // Drug-interaction / allergy / lab-rule alerting.
            // The portal surfaces (portals/staff/cdss/*, portals/admin/cdss/*)
            // were deleted from routes/web.php, so their patterns are gone from
            // this list. The api/v1/cdss group still exists in the SEALED
            // routes/api.php and this freeze is the only thing 404-ing it — do
            // not remove these two patterns.
            'clinical_decision_support' => [
                'api/v1/cdss',
                'api/v1/cdss/*',
            ],

            // Analytics + public-health dashboards.
            // portals/staff/analytics/* was deleted from routes/web.php, so its
            // patterns are gone from this list. The api/v1 patterns below still
            // match live routes in the SEALED routes/api.php and this freeze is
            // the only thing 404-ing them — do not remove them.
            // NOT frozen: the rest of api/v1/public-health/* — statutory
            // MINSANTE report generation, review and submission is a legal
            // obligation, not a dashboard. portals/developer/analytics is the
            // partner API-usage surface and also stays.
            'analytics_dashboards' => [
                'api/v1/analytics',
                'api/v1/analytics/*',
                'api/v1/public-health/dashboard',
                'api/v1/public-health/facility-dashboard/*',
                'api/v1/public-health/intelligence/*',
            ],

            // Full telehealth platform: waiting-room queue + video session
            // orchestration.
            // The whole portals/staff/telemedicine/* surface was deleted from
            // routes/web.php, so its patterns are gone from this list. The three
            // api/v1 patterns below still match live routes in the SEALED
            // routes/api.php and this freeze is the only thing 404-ing them —
            // do not remove them.
            // api/mobile/telemedicine/* is gone too — its controller was deleted,
            // so those routes were removed from routes/mobile_telehealth.php.
            // STILL LIVE and NOT gated by this flag (sealed routes/api.php):
            //   POST   api/v1/telemedicine/consultations
            //   GET    api/v1/telemedicine/consultations/{id}
            //   POST   api/v1/telemedicine/consultations/{id}/consent|cancel
            // Their controller (Api\V1\TelemedicineController) has been deleted,
            // so those four 500 rather than 404. Broaden this list to
            // 'api/v1/telemedicine' + 'api/v1/telemedicine/*' to close that gap.
            'telemedicine_full' => [
                // The whole v1 telemedicine surface. This used to cover only the
                // call/waiting-room/session paths, which left four routes —
                // consultations create/show, consent, cancel — matched but
                // unfrozen. Their controller was deleted with the rest of the
                // module, so an AUTHENTICATED partner hitting them got a fatal
                // "class not found" rather than a clean 404. Unauthenticated
                // callers were always fine: auth middleware runs first, which is
                // what kept this quiet.
                'api/v1/telemedicine',
                'api/v1/telemedicine/*',
            ],

        ]);

        // Runs globally so a frozen path 404s before routing — there is no route
        // file, route group or controller a frozen module can be reached through.
        $middleware->append(\App\Http\Middleware\EnforceFeatureFlag::class);

        $middleware->alias([
            'auth.bearer'      => \App\Http\Middleware\VerifyBearerToken::class,
            'sdk.token'        => \App\Http\Middleware\VerifySdkToken::class,
            'throttle.client'  => \App\Http\Middleware\ThrottleByClient::class,
            'bridge.agent'     => \App\Http\Middleware\VerifyBridgeAgent::class,
            'portal.access'    => \App\Http\Middleware\EnsurePortalAccess::class,
            'platform.admin'   => \App\Http\Middleware\RequirePlatformAdmin::class,
            'facility.context' => \App\Http\Middleware\RequireFacilityContext::class,
            'consent.grant'    => \App\Http\Middleware\RequireConsentGrant::class,
            'auth.mobile'      => \App\Http\Middleware\AuthenticateMobilePatient::class,
            'guardian.context' => \App\Http\Middleware\GuardianAccessMiddleware::class,
            'mfa.verified'     => \App\Http\Middleware\EnsureTwoFactorVerified::class,
            'api.admin'        => \App\Http\Middleware\RequireApiAdminRole::class,
            'verify.integration.client' => \App\Http\Middleware\VerifyIntegrationClient::class,
            'module'                    => \App\Http\Middleware\EnforceModuleEntitlement::class,
            // 'feature:<key>' — V1 launch-scope kill switch, fails CLOSED with 404.
            // Not to be confused with 'module:<key>' above (subscription
            // entitlement, fails OPEN) or 'patient.feature' below (per-patient
            // subscription plan).
            'feature'                   => \App\Http\Middleware\EnforceFeatureFlag::class,
            'patient.feature'           => \App\Http\Middleware\EnsurePatientFeature::class,
            'patient.profile'           => \App\Http\Middleware\RequireCompletePatientProfile::class,
            'guardian.profile'          => \App\Http\Middleware\RequireCompleteGuardianProfile::class,
            'api.deprecated'            => \App\Http\Middleware\MarkDeprecated::class,
            'api.quota'                 => \App\Http\Middleware\EnforceApiQuota::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ── Error tracking (Sentry) ─────────────────────────────────────────
        // Report unhandled exceptions to Sentry in deployed environments only.
        // No-op until SENTRY_LARAVEL_DSN is set; PHI is scrubbed in config/sentry.php.
        $exceptions->reportable(function (\Throwable $e) {
            if (app()->bound('sentry') && app()->environment('production', 'staging')) {
                app('sentry')->captureException($e);
            }
        });

        // ── Domain exceptions ───────────────────────────────────────────────
        $exceptions->renderable(function (\App\Exceptions\SlotFullException $e, $request) {
            return response()->json([
                'error_code' => 'SLOT_FULL',
                'message'    => $e->getMessage(),
            ], 409);
        });

        // ── Global API JSON error handler ───────────────────────────────────
        // All requests to /api/* or /fhir/* or Accept: application/json
        // return structured JSON — no HTML stack traces ever leak.
        $exceptions->renderable(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $isApi = $request->is('api/*') || $request->is('fhir/*') || $request->expectsJson();
            if (! $isApi) {
                return null; // let Laravel handle web routes normally
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'status'     => 'error',
                    'error_code' => 'VALIDATION_FAILED',
                    'message'    => 'The request data failed validation.',
                    'errors'     => $e->errors(),
                ], 422);
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'status'     => 'error',
                    'error_code' => 'NOT_FOUND',
                    'message'    => 'The requested resource was not found.',
                ], 404);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'status'     => 'error',
                    'error_code' => 'ENDPOINT_NOT_FOUND',
                    'message'    => 'The API endpoint does not exist.',
                ], 404);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'status'     => 'error',
                    'error_code' => 'UNAUTHENTICATED',
                    'message'    => 'Authentication is required.',
                ], 401);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return response()->json([
                    'status'     => 'error',
                    'error_code' => 'HTTP_ERROR',
                    'message'    => $e->getMessage() ?: 'An HTTP error occurred.',
                ], $e->getStatusCode());
            }

            // Unexpected server error — never leak stack traces
            $message = config('app.debug')
                ? $e->getMessage()
                : 'An unexpected server error occurred. Reference your request ID for support.';

            return response()->json([
                'status'     => 'error',
                'error_code' => 'INTERNAL_SERVER_ERROR',
                'message'    => $message,
                'request_id' => $request->header('X-Request-Id', bin2hex(random_bytes(8))),
            ], 500);
        });
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('family:check-age-transitions')->daily();

        // DHIS2 monthly push — 1st of each month at 04:00 (Item 16)
        $schedule->command('opescare:push-dhis2 --month=' . now()->subMonth()->format('Y-m'))
                 ->monthlyOn(1, '04:00')
                 ->withoutOverlapping()
                 ->onSuccess(function () { \Log::info('DHIS2 monthly push completed'); })
                 ->onFailure(function () { \Log::error('DHIS2 monthly push failed'); });

        // Data retention purge — daily at 03:00 (Item 53)
        $schedule->command('opescare:purge-expired-data')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Notify expiring provider credentials — every Monday at 08:00 (Item 45)
        $schedule->command('opescare:notify-expiring-credentials --days=30')
                 ->weeklyOn(1, '08:00')
                 ->withoutOverlapping();

        // Generate API plan invoices from metered usage — 1st of each month at 02:00
        $schedule->command('opescare:bill-api-usage')
                 ->monthlyOn(1, '02:00')
                 ->withoutOverlapping();

        // Blood Finder: lapse unanswered blood-unit requests — hourly.
        //
        // A request is a 24h hold and BloodRequestStatus documents
        // `pending|confirmed|ready -> expired (scheduler)`, but that scheduler
        // was never written: expires_at was set on every row and never read, so
        // nothing left the open set and five unanswered holds locked a patient
        // out of the feature permanently. Registered here rather than in
        // routes/console.php because that file is SEALED (apps/api-laravel/CLAUDE.md).
        $schedule->command('blood:expire-requests')
                 ->hourly()
                 ->withoutOverlapping();
    })
    ->create();
