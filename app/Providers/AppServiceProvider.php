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
            // A facility subscribes to the platform under its own audience in
            // the plan catalogue, so `subscriber()` has to resolve for it too.
            // Legacy rows carry the token 'organization' and are untouched.
            'facility' => \App\Models\Facility::class,
        ]);

        /*
         * The wire between a settled invoice and the subscription it paid for.
         *
         * Registered here rather than inside the payment services because
         * PaymentChargeService is the single writer of payment status and has
         * no business knowing that subscriptions exist. The observer reacts
         * only to a status CHANGE on an invoice stamped
         * `subject_type = 'subscription'`; every other invoice passes through
         * without a query. See App\Observers\MobileInvoiceObserver.
         */
        \App\Models\MobileInvoice::observe(\App\Observers\MobileInvoiceObserver::class);

        /*
         * The appointment reminder ladder, kept in step with the appointment.
         *
         * An observer rather than a call at each booking site: an appointment
         * is created or moved from self-booking, the front desk, the staff
         * portal, the mobile reschedule endpoint, the waitlist and the demo
         * seeders, and the bug being fixed here is exactly that NONE of them
         * scheduled a reminder. See App\Observers\AppointmentReminderObserver.
         */
        \App\Models\Appointment::observe(\App\Observers\AppointmentReminderObserver::class);

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

        // @patientfeature - REMOVED with the Subscription module (Finder has no
        // patient subscriptions). The directive stays registered as a constant
        // false rather than being dropped: an unregistered @patientfeature in a
        // Blade file compiles as literal text instead of failing, which would
        // silently print the directive to patients if a view ever reintroduced it.
        \Illuminate\Support\Facades\Blade::if('patientfeature', fn (string $key) => false);

        // @enum($model->status) / @enum($x->severity, 'severity') — render a DB
        // enum value through lang/{locale}/enums.php with a graceful title-case
        // fallback. Keeps status/severity badges bilingual instead of raw English.
        // @capability('prescribe') … @endcapability — show a sidebar link or a
        // dashboard tile only to roles that hold the capability (any-of).
        \Illuminate\Support\Facades\Blade::if('capability', fn (string ...$capabilities) => \App\Support\StaffCapabilities::can(\Illuminate\Support\Facades\Auth::user(), ...$capabilities));

        \Illuminate\Support\Facades\Blade::directive('enum', function (string $expression) {
            return "<?php echo e(\App\Support\Enums::label({$expression})); ?>";
        });

        // `<script @cspNonce>` — stamps the per-request nonce that
        // AddSecurityHeaders put in the Content-Security-Policy, so the block
        // runs without 'unsafe-inline'. Empty outside an HTTP request (CLI
        // rendering, mail) so the attribute is simply omitted there.
        \Illuminate\Support\Facades\Blade::directive('cspNonce', function () {
            return "<?php echo (\$__n = request()->attributes->get('csp_nonce')) ? 'nonce=\"' . e(\$__n) . '\"' : ''; ?>";
        });

        // Clinical module routes (Group 3 + wired modules) — loaded here to
        // avoid touching the sealed routes/api.php file.
        Route::middleware('api')
            ->group(base_path('routes/clinical.php'));

        // Marketing / growth public routes (pricing, etc.) — loaded here to keep
        // them out of the large shared routes/web.php file.
        Route::middleware('web')
            ->group(base_path('routes/marketing.php'));

        // Family / dependents web routes (guardian dashboard, incoming
        // invitations, dependent-aware record pages) — same rationale.
        Route::middleware('web')
            ->group(base_path('routes/web_family.php'));

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

        // Pre-consult questionnaire — the short clinical intake a patient
        // completes before an appointment, plus the clinician's read of it.
        // Same rationale as above: its own file so parallel work cannot collide.
        Route::middleware('api')
            ->group(base_path('routes/mobile_preconsult.php'));

        // Account deletion — the in-app erasure flow (Apple 5.1.1(v) and Google
        // Play both require one when an app creates accounts) plus the public
        // page that documents it.
        //
        // Registered with an EMPTY attribute array, not ->middleware('api'):
        // this file carries its own `api/mobile` group AND a separate `web`
        // group for the public page. Wrapping it in `api` would put a browser
        // page behind the API middleware stack.
        Route::group([], base_path('routes/mobile_account_deletion.php'));

        // Mobile consent ledger routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_consent_ledger.php'));

        // Mobile consent requests routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_consent_requests.php'));

        // Mobile emergency access routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_emergency_access.php'));

        // Mobile facility registry routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_facility_registry.php'));

        // Mobile provider facility routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_provider_facility.php'));

        // Mobile appointments reschedule routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_appointments_reschedule.php'));

        // Patient record import (FHIR bundle / PDF / image, patient-provided and
        // unverified) — its own file so parallel work cannot collide.
        Route::middleware('api')
            ->group(base_path('routes/mobile_record_import.php'));

        // Mobile doctor chart routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_doctor_chart.php'));

        // Mobile doctor messaging routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_doctor_messaging.php'));

        // Mobile doctor results routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_doctor_results.php'));

        // Mobile doctor schedule routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_doctor_schedule.php'));

        // Mobile doctor referral routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_doctor_referrals.php'));

        // Mobile doctor immunisation routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_doctor_immunizations.php'));

        // Mobile front-desk routes (/api/mobile/desk) — same reason.
        Route::middleware('api')
            ->group(base_path('routes/mobile_front_desk.php'));

        // Mobile labs routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_labs.php'));

        // The patient's Health Passport: their record as thirty pages.
        Route::middleware('api')
            ->group(base_path('routes/mobile_health_passport.php'));

        Route::middleware('api')
            ->group(base_path('routes/mobile_documents.php'));

        // Mobile LAB DESK routes (the laboratory's side of the orders above) —
        // loaded here rather than in routes/api.php so parallel work on
        // separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_lab_desk.php'));

        // Mobile patient writes routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_patient_writes.php'));

        // Mobile payments routes — loaded here rather than in routes/api.php so
        // parallel work on separate features cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_payments.php'));

        // Mobile subscription routes (the plan catalogue and taking one out) —
        // same rationale, and deliberately a separate file from the payments
        // one: they share the payment rail, not the surface.
        Route::middleware('api')
            ->group(base_path('routes/mobile_subscriptions.php'));

        // Mobile messaging recipients route — loaded here rather than in
        // routes/api.php so parallel work on separate features cannot collide.
        Route::middleware('api')
            ->group(base_path('routes/mobile_messaging_recipients.php'));

        // Mobile family / dependents routes (member detail, incoming
        // invitations) — loaded here rather than in routes/api.php so parallel
        // work on separate features cannot collide.
        Route::middleware('api')
            ->group(base_path('routes/mobile_family.php'));

        // Mobile pharmacy desk routes (the pharmacist's counter) — loaded here
        // rather than in routes/api.php so parallel work on separate features
        // cannot collide in one file.
        Route::middleware('api')
            ->group(base_path('routes/mobile_pharmacy_desk.php'));

        // Mobile blood-bank staff desk (the receiving end of a patient's blood
        // request) — loaded here rather than in routes/api.php so parallel work
        // on separate features cannot collide in one file. Mounted on
        // `api/mobile/blood-desk`, a SIBLING of the patient prefix in
        // routes/mobile_blood.php, because Laravel keeps one handler per
        // method+URI and both surfaces must stay whole.
        Route::middleware('api')
            ->group(base_path('routes/mobile_blood_desk.php'));

        // Triage desk (web portal) — the first surface over the triage schema,
        // which shipped with the clinical MVP and had no route and no screen.
        // Its own file rather than routes/web.php, same rationale as above.
        // The middleware stack, including `capability:triage`, is declared
        // inside the file with the routes it gates.
        Route::group([], base_path('routes/triage.php'));

        // Patient queue desk (web portal) — the first surface over the queue
        // schema (queue_tickets, queue_status_histories, appointment_check_ins),
        // which shipped with the operational MVP and had no route and no
        // screen. Its own file, same rationale; the middleware stack, including
        // `capability:queue`, is declared inside the file with the routes it gates.
        Route::group([], base_path('routes/queue.php'));

        // Billing / cashier desk (web portal) — the first surface over
        // invoices / payments / receipts / refunds / cashier_sessions, whose
        // `billing` capability was granted to seven roles and gated nothing.
        // Its own file, same rationale; the stack, including
        // `capability:billing` and `capability:refund_approve`, is declared
        // inside the file with the routes it gates. Care is never gated on
        // payment: nothing in it is consulted by any clinical desk.
        Route::group([], base_path('routes/billing.php'));

        // Pharmacy stock operations (web portal) — batches & expiry, the
        // append-only movement log, reorder rules and goods receipts over the
        // supply-chain schema (stock_batches, stock_movements, goods_receipts,
        // reorder_rules), which shipped with no screen. Its own file, same
        // rationale; the stack, including `capability:pharmacy_stock`, is
        // declared inside the file with the routes it gates.
        Route::group([], base_path('routes/pharmacy_ops.php'));

        // Pharmacy desk slices from the dashboard audit (docs/PHARMACY_DASHBOARD_AUDIT.md):
        // patient orders, the price list and the facility settings page. Each file
        // declares its own middleware stack next to the routes it gates.
        Route::group([], base_path('routes/pharmacy_orders.php'));
        Route::group([], base_path('routes/pharmacy_prices.php'));
        Route::group([], base_path('routes/pharmacy_settings.php'));

        // Facility-type dashboards (docs/FACILITY_DASHBOARDS_AUDIT.md): the shared
        // facility settings page, the laboratory manager desk, the hospital/clinic
        // administration desk.
        Route::group([], base_path('routes/facility_settings.php'));
        Route::group([], base_path('routes/lab_manager.php'));
        Route::group([], base_path('routes/facility_admin.php'));
        // Developer dashboard completion (docs/DEVELOPER_DASHBOARD_AUDIT.md).
        Route::group([], base_path('routes/developer_ext.php'));
        // Web referral and immunisation desks — both existed only in the clinician
        // mobile API (docs/audits/{CLINIC,HOSPITAL,HEALTH_CENTRE}_DASHBOARD_AUDIT_2.md).
        Route::group([], base_path('routes/referral_desk.php'));
        Route::group([], base_path('routes/immunisation_desk.php'));
        // Missing documents pass (docs/specs/documents/20-33): rendered on demand, never stored.
        Route::group([], base_path('routes/documents_new.php'));

        // Public imaging-exam finder (docs/audits/IMAGING_DASHBOARD_AUDIT_2.md, I-1).
        Route::middleware('web')->group(base_path('routes/imaging_finder.php'));

        // Code imported from the platform (docs/PLATFORM_IMPORTS.md), one route file per group.
        Route::group([], base_path('routes/imports_files.php'));
        Route::group([], base_path('routes/imports_tasks.php'));
        Route::group([], base_path('routes/imports_clinical.php'));
        Route::group([], base_path('routes/imports_ops.php'));
        Route::group([], base_path('routes/imports_dhis2.php'));

        // Laboratory desk (web portal) — the first web surface over lab_orders /
        // lab_results, whose capabilities were granted and gated nothing. Its
        // own file, same rationale; the middleware stack, including
        // `capability:lab_results`, is declared inside the file with the routes
        // it gates.
        Route::group([], base_path('routes/lab.php'));

        // Imaging desk (web portal) — the imaging half of a diagnostic centre,
        // behind `capability:imaging`. Its own file, same rationale as lab.php.
        Route::group([], base_path('routes/imaging_desk.php'));

        // Diagnostic centre landing (/facility/diagnostic) — lab + imaging on
        // one page, behind `capability:lab_results,imaging` (any-of). Its own
        // file, same rationale.
        Route::group([], base_path('routes/diagnostic.php'));

        // Patient chart (web portal) — the read-only record behind
        // `capability:clinical_chart`, which every clinical role held and
        // which gated no web screen until this file. Its own file, same
        // rationale; the middleware stack is declared inside it. Record-level
        // access is ClinicalRelationshipGate's, applied by the controller.
        Route::group([], base_path('routes/chart.php'));

        // FHIR R4 ingest — the door partner systems push clinical data through
        // (Observation, DiagnosticReport, Immunization, transaction Bundle).
        // Its own file rather than routes/api.php, same rationale as above.
        //
        // There is deliberately NO Patient write route in it: a partner may
        // never mint a Health ID. See routes/fhir.php for the full rationale.
        // The middleware stack is declared inside the file with the routes it
        // gates, because each resource carries its own SMART-on-FHIR scope.
        Route::middleware('api')
            ->group(base_path('routes/fhir.php'));

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

        // FHIR subscription observer wiring removed along with the Fhir module:
        // the Finder extraction does not ship FHIR interop.
    }
}
