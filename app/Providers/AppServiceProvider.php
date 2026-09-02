<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Polymorphic subscriber morph map — keeps subscription rows portable
        // and decoupled from model FQCNs (B2B organizations + B2C patients/households).
        // 'household' is added in Phase 3 alongside the Household model.
        Relation::morphMap([
            'patient' => \App\Models\Patient::class,
        ]);

        // @feature('insurance') … @endfeature — hide UI belonging to a module
        // frozen out of the V1 launch scope (config/features.php). FAILS CLOSED:
        // an unknown key renders nothing.
        //
        // A frozen module must be absent from BOTH the routes and the nav — a
        // gated route with a live nav link is a dead link, and a nav link with
        // no gate is a feature reachable by URL only. Wrap every nav entry that
        // points into a frozen module.
        \Illuminate\Support\Facades\Blade::if('feature', function (string $key) {
            return \App\Support\Features::enabled($key);
        });

        // @platformadmin … @endplatformadmin — the role-tier twin of @feature.
        //
        // Same principle: a page gated by RequirePlatformAdmin must be absent
        // from the nav of anyone who cannot open it, or the sidebar is just a
        // list of 403s. This delegates to the middleware's own predicate rather
        // than repeating the role list, so the two cannot drift apart — that
        // drift is exactly what left facility admins staring at Subscription,
        // Staff, Patients and KPI links they had been locked out of.
        \Illuminate\Support\Facades\Blade::if('platformadmin', function () {
            return \App\Http\Middleware\RequirePlatformAdmin::isPlatformTier(auth()->user());
        });

        // @patientfeature('teleconsult') … @endpatientfeature — gate UI by patient
        // subscription feature (honours family-sharing coverage). False when not
        // authenticated as a patient.
        //
        // Previously registered as @feature; renamed when @feature became the V1
        // module-freeze gate above. The two answer different questions: this one
        // is "has this patient paid for it", @feature is "does this product ship".
        \Illuminate\Support\Facades\Blade::if('patientfeature', function (string $key) {
            $patient = auth()->user()?->patient;
            return $patient
                ? app(\App\Modules\Subscription\Services\PatientSubscriptionService::class)->hasFeature($patient, $key)
                : false;
        });

        // @enum($model->status) / @enum($x->severity, 'severity') — render a DB
        // enum value through lang/{locale}/enums.php with a graceful title-case
        // fallback. Keeps status/severity badges bilingual instead of raw English.
        \Illuminate\Support\Facades\Blade::directive('enum', function (string $expression) {
            return "<?php echo e(\App\Support\Enums::label({$expression})); ?>";
        });

        // Clinical module routes (Group 3 + wired modules) — loaded here to
        // avoid touching the sealed routes/api.php file.
        Route::middleware('api')
            ->group(base_path('routes/clinical.php'));

        // Marketing / growth public routes (pricing, etc.) — loaded here to keep
        // them out of the large shared routes/web.php file.
        Route::middleware('web')
            ->group(base_path('routes/marketing.php'));

        // Mobile patient Messaging + Telemedicine routes — loaded here to avoid
        // touching the sealed routes/api.php file (same pattern as clinical.php).
        Route::middleware('api')
            ->group(base_path('routes/mobile_telehealth.php'));

        // Mobile patient self-registration route — same rationale as above.
        Route::middleware('api')
            ->group(base_path('routes/mobile_registration.php'));

        // Mobile patient Forgot/Reset Password routes — loaded here to avoid
        // touching the sealed routes/api.php file (same pattern as above).
        Route::middleware('api')
            ->group(base_path('routes/mobile_password_reset.php'));

        // Mobile patient Help & Support routes — loaded here to avoid touching
        // the sealed routes/api.php file (same pattern as mobile_telehealth.php).
        Route::middleware('api')
            ->group(base_path('routes/mobile_support.php'));

        // Mobile patient Blood Finder routes — loaded here to avoid touching
        // the sealed routes/api.php file (same pattern as mobile_support.php).
        Route::middleware('api')
            ->group(base_path('routes/mobile_blood.php'));

        // Blood bank queue routes — the facility side of the Blood Finder, so a
        // patient's request has a receiver. Same rationale as mobile_blood.php.
        Route::middleware('api')
            ->group(base_path('routes/blood_bank.php'));

        // Mobile patient Clinician/Specialist directory routes — same rationale.
        Route::middleware('api')
            ->group(base_path('routes/mobile_providers.php'));

        // Mobile patient Health Vitals routes — same rationale. Read-only over
        // the existing vital_signs / triage_vital_signs / lab_results tables.
        Route::middleware('api')
            ->group(base_path('routes/mobile_vitals.php'));

        /*
         * Second-factor and one-time codes.
         *
         * The MFA challenge verifies a 6-digit TOTP and, on success, logs the
         * user in. It had no limit at all, so anyone holding a stolen password
         * could sit on /mfa/challenge and walk the whole 000000-999999 space
         * against a code that stays valid for a minute or two. Keyed by the
         * account under challenge as well as the IP, so rotating source
         * addresses does not reset the budget.
         */
        RateLimiter::for('auth-code', function (Request $request) {
            $subject = $request->session()->get('mfa.user_id') ?: $request->ip();

            return [
                Limit::perMinute(5)->by('authcode|' . $subject),
                Limit::perHour(20)->by('authcode|' . $subject),
                Limit::perMinute(20)->by('authcode-ip|' . $request->ip()),
            ];
        });

        // Password reset. Unauthenticated and it sends mail to an address the
        // caller chooses, so an open endpoint is both an email cannon and a
        // way to probe which addresses exist by timing.
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(3)->by('pwreset|' . $request->ip()),
                Limit::perDay(15)->by('pwreset|' . $request->ip()),
            ];
        });

        /*
         * The reset LINK — /reset-password/{token}, both verbs.
         *
         * Both were unthrottled, and both are oracles. The POST sets a password
         * from a token in the URL, so a successful guess is an account takeover
         * with no credential involved; the GET answers just as well, because it
         * renders a form for a live token and a "link expired" card for a dead
         * one, letting a caller find the live token for free and spend the POST
         * only once.
         *
         * A sibling of 'password-reset' rather than a reuse of it: 3/minute is
         * the right budget for an endpoint that sends mail, and the wrong one
         * for a page a genuine user hits three or four times in a row — land,
         * submit, fail the eight-character rule, submit again — from behind a
         * shared clinic or office NAT. Locking a user out of the recovery flow
         * they came to for being locked out is the failure mode to avoid here.
         *
         * Keyed by IP for the same reason 'invite' is: an enumerating caller
         * supplies a DIFFERENT token on every request, so a per-token counter
         * would never fill. The per-token limit is there as well, to cap
         * hammering on a link that has actually leaked.
         */
        RateLimiter::for('password-reset-token', function (Request $request) {
            $token = (string) $request->route('token');

            return [
                Limit::perMinute(10)->by('pwresetlink|' . $request->ip()),
                Limit::perHour(40)->by('pwresetlink|' . $request->ip()),
                Limit::perMinute(5)->by('pwresetlink-token|' . hash('sha256', $token)),
            ];
        });

        // Public unauthenticated writes: sign-up, contact, demo requests. Each
        // creates a row on request from anyone at all, so they need a limit of
        // their own. The burst limit stops scripted floods; the daily cap stops
        // the slow drip that would otherwise walk under it.
        RateLimiter::for('signup', function (Request $request) {
            return [
                Limit::perMinute(5)->by('signup|' . $request->ip()),
                Limit::perDay(20)->by('signup|' . $request->ip()),
            ];
        });

        /*
         * Staff invitation links.
         *
         * The token in the URL is the ONLY thing between a stranger and a real
         * staff account at a real facility — the POST creates that account, with
         * the facility and the role taken from the invite row. Both verbs were
         * unthrottled, so the token space could be walked at full line rate, and
         * the GET enumerates just as well as the POST: it distinguishes a live
         * token from a dead one by what it renders.
         *
         * Keyed by IP rather than by token, because an enumerating caller
         * supplies a DIFFERENT token on every request — a per-token counter
         * would never reach its limit. The per-token limit is there as well, to
         * cap hammering on a link that has actually leaked.
         *
         * Deliberately looser than 'auth-code' (5/min): a shared office NAT can
         * legitimately have several new staff accepting invites at once, and a
         * single person costs 2-3 requests (land, fail validation, resubmit).
         */
        RateLimiter::for('invite', function (Request $request) {
            $token = (string) $request->route('token');

            return [
                Limit::perMinute(10)->by('invite|' . $request->ip()),
                Limit::perHour(60)->by('invite|' . $request->ip()),
                Limit::perMinute(5)->by('invite-token|' . hash('sha256', $token)),
            ];
        });

        RateLimiter::for('verify', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Patient portal rate limit — prevents QR spam, profile-scrape, and enumeration.
        // 120 requests per minute per authenticated user is generous for normal use
        // while blocking automated abuse (a QR-flood attack would hit this instantly).
        RateLimiter::for('portal', function (Request $request) {
            $user = $request->user();
            return $user
                ? Limit::perMinute(120)->by('portal|' . $user->id)
                : Limit::perMinute(20)->by('portal|' . $request->ip());
        });

        // Tighter limit specifically for QR generation — 10 per minute per user.
        // A patient generating more than 10 QRs per minute is an abuse signal.
        RateLimiter::for('portal.qr', function (Request $request) {
            $user = $request->user();
            return $user
                ? Limit::perMinute(10)->by('qr|' . $user->id)
                : Limit::perMinute(3)->by('qr|' . $request->ip());
        });

        // Per-tenant / per-API-key rate limiting:
        // - Unauthenticated: 60 req/min by IP
        // - Authenticated users: 600 req/min by user ID
        // - Integration partners (X-Integration-Client-Id header): 1200 req/min by user ID
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perMinute(60)->by($request->ip());
            }

            $isPartner = $request->header('X-Integration-Client-Id');
            $rate      = $isPartner ? 1200 : 600;

            return Limit::perMinute($rate)->by($user->id);
        });

        // Family: notify guardians when patient events occur
        $familyListener = new \App\Listeners\NotifyGuardiansOfPatientEvent();
        \App\Models\LabResult::created(fn($m) => $familyListener->handleLabResult($m));
        \App\Models\Appointment::created(fn($m) => $familyListener->handleAppointment($m));
        \App\Models\Appointment::updated(fn($m) => $familyListener->handleAppointmentUpdated($m));
        \App\Models\ConsentRequest::created(fn($m) => $familyListener->handleConsentRequest($m));

        // FHIR Subscriptions: dispatch async notifications when clinical resources change.
        // Uses Laravel's Eloquent closure-based observers to avoid a proliferation of
        // observer class registrations. The job is queued (fhir-notifications queue)
        // so delivery never blocks the originating request.
        $fhirObserver = new \App\Observers\FhirSubscriptionObserver(
            app(\App\Modules\Fhir\Services\FhirService::class)
        );
        \App\Models\Patient::created([$fhirObserver, 'created']);
        \App\Models\Patient::updated([$fhirObserver, 'updated']);
        \App\Models\Visit::created([$fhirObserver, 'created']);
        \App\Models\Visit::updated([$fhirObserver, 'updated']);
        \App\Models\LabOrder::created([$fhirObserver, 'created']);
        \App\Models\LabOrder::updated([$fhirObserver, 'updated']);
        \App\Models\Prescription::created([$fhirObserver, 'created']);
        \App\Models\Prescription::updated([$fhirObserver, 'updated']);
        \App\Models\ImmunizationRecord::created([$fhirObserver, 'created']);
        \App\Models\ImmunizationRecord::updated([$fhirObserver, 'updated']);
        \App\Models\AllergyRecord::created([$fhirObserver, 'created']);
        \App\Models\AllergyRecord::updated([$fhirObserver, 'updated']);
        \App\Models\Diagnosis::created([$fhirObserver, 'created']);
        \App\Models\Diagnosis::updated([$fhirObserver, 'updated']);
    }
}
