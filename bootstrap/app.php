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
            // Locale for the API. Without this every __() in an API response
            // rendered in the default locale no matter what the caller asked
            // for, so the whole French half of the mobile app fell back to
            // English the moment the server spoke. SetLocale reads
            // Accept-Language only for stateless requests, so the web group's
            // ?lang= / session behaviour is unchanged.
            \App\Http\Middleware\SetLocale::class,
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
        | URI-pattern feature freeze — mechanism kept, map empty
        |--------------------------------------------------------------------
        |
        | On the OpesCare platform this block declared, via Features::freeze(),
        | the URI surface of every module frozen out of the V1 launch scope
        | (insurance, billing, inventory ops, CDSS, analytics dashboards,
        | telemedicine). In the Finder product those modules are DELETED --
        | their routes no longer exist, so there is nothing to freeze and no
        | map is declared (Features::$frozenPaths defaults to empty, and the
        | globally-appended EnforceFeatureFlag below passes every request
        | straight through).
        |
        | To freeze a future feature by path, declare its patterns here:
        |
        |     \App\Support\Features::freeze([
        |         '<feature-key>' => ['api/v1/thing', 'api/v1/thing/*'],
        |     ]);
        |
        | and add the matching flag to config/features.php -- an unmapped flag
        | gates nothing by URI, and an unflagged map key fails closed with 404.
        */

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
            'auth.provider'    => \App\Http\Middleware\AuthenticateMobileProvider::class,
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

        // (The platform scheduled `opescare:push-dhis2` here — the monthly
        // DHIS2 / MINSANTE aggregate push. Institutional public-health
        // reporting is not part of the Finder product; the command and its
        // schedule entry are deleted.)

        // Data retention purge — daily at 03:00 (Item 53)
        $schedule->command('opescare:purge-expired-data')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Notify expiring provider credentials — every Monday at 08:00 (Item 45)
        $schedule->command('opescare:notify-expiring-credentials --days=30')
                 ->weeklyOn(1, '08:00')
                 ->withoutOverlapping();

        // (The platform scheduled `opescare:bill-api-usage` here — monthly API
        // plan invoicing from metered usage. The subscription/billing domain is
        // deleted from the Finder product along with the BillApiUsage command,
        // so its schedule entry is gone too.)

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
