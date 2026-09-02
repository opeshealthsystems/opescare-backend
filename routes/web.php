<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\MedicalId\AdminUserManagementController;
use App\Http\Controllers\MedicalId\AdminFacilityManagementController;
use App\Http\Controllers\MedicalId\AdminPatientManagementController;
use App\Http\Controllers\MedicalId\AdminStaffManagementController;
use App\Http\Controllers\MedicalId\AdminSupportController;
use App\Http\Controllers\MedicalId\AdminAppointmentsController;
use App\Http\Controllers\MedicalId\AdminRolesController;
use App\Http\Controllers\MedicalId\AdminOrganizationsController;

// ── Developer Documentation (public, no auth required) ──────────────────────
Route::prefix('docs')->name('docs.')->group(function () {
    Route::get('/',              [DocsController::class, 'index'])->name('index');
    Route::get('/authentication',[DocsController::class, 'authentication'])->name('authentication');
    Route::get('/api',           [DocsController::class, 'api'])->name('api');
    Route::get('/sdk',           [DocsController::class, 'sdk'])->name('sdk');
    Route::get('/bridge',        [DocsController::class, 'bridge'])->name('bridge');
    Route::get('/widget',        [DocsController::class, 'widget'])->name('widget');
    Route::get('/webhooks',      [DocsController::class, 'webhooks'])->name('webhooks');
    Route::get('/errors',        [DocsController::class, 'errors'])->name('errors');
    Route::get('/playground',    [DocsController::class, 'playground'])->name('playground');
    Route::get('/changelog',     [DocsController::class, 'changelog'])->name('changelog');
});

// ── OAuth 2.0 / OIDC discovery (public, no auth, cacheable) ─────────────────
// RFC 7517 JWKS + RFC 8414 server metadata at the domain root, so third parties
// can verify Connect RS256 tokens and discover endpoints. See docs/API-OAUTH.md.
Route::get('/.well-known/jwks.json', [\App\Http\Controllers\WellKnownController::class, 'jwks'])
    ->name('well-known.jwks');
Route::get('/.well-known/oauth-authorization-server', [\App\Http\Controllers\WellKnownController::class, 'authorizationServerMetadata'])
    ->name('well-known.oauth-metadata');

// ── Machine-facing discovery: robots, sitemap, llms.txt ─────────────────────
// Generated from the live route table, so they cannot drift out of date the
// way a hand-written sitemap does. See App\Http\Controllers\SeoController.
Route::get('/robots.txt',  [\App\Http\Controllers\SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/llms.txt',    [\App\Http\Controllers\SeoController::class, 'llms'])->name('seo.llms');

// Root / Landing
Route::get('/', [PublicPageController::class, 'index'])->name('public.landing');

// Network services — the patient-facing discovery layer. These pages carry the
// medicine and blood availability detail that used to be homepage sections.
Route::prefix('network')->group(function () {
    Route::get('/medicine-finder', [PublicPageController::class, 'networkMedicineFinder'])->name('public.network.medicine-finder');
    Route::get('/blood-finder', [PublicPageController::class, 'networkBloodFinder'])->name('public.network.blood-finder');
});

// Core Institutional Pages
Route::get('/about', [PublicPageController::class, 'about'])->name('public.about');
Route::get('/how-it-works', [PublicPageController::class, 'howItWorks'])->name('public.how-it-works');

// Solutions
Route::prefix('solutions')->group(function () {
    Route::get('/patients', [PublicPageController::class, 'solutionsPatients'])->name('public.solutions.patients');
    Route::get('/hospitals', [PublicPageController::class, 'solutionsHospitals'])->name('public.solutions.hospitals');
    Route::get('/pharmacies', [PublicPageController::class, 'solutionsPharmacies'])->name('public.solutions.pharmacies');
    Route::get('/laboratories', [PublicPageController::class, 'solutionsLaboratories'])->name('public.solutions.laboratories');
    Route::get('/insurers', [PublicPageController::class, 'solutionsInsurers'])->name('public.solutions.insurers');
    Route::get('/public-health', [PublicPageController::class, 'solutionsPublicHealth'])->name('public.solutions.public-health');
});

// Tech & Dev
Route::get('/interoperability', [PublicPageController::class, 'interoperability'])->name('public.interoperability');
Route::get('/developers', [PublicPageController::class, 'developers'])->name('public.developers');
Route::get('/developers/pricing', [PublicPageController::class, 'pricing'])->name('public.developers.pricing');
Route::get('/security', [PublicPageController::class, 'security'])->name('public.security');

// Legal & Privacy
Route::get('/privacy', [PublicPageController::class, 'privacy'])->name('public.privacy');
Route::get('/terms', [PublicPageController::class, 'terms'])->name('public.terms');
Route::get('/consent', [PublicPageController::class, 'consent'])->name('public.consent');

// Support
Route::get('/faq', [PublicPageController::class, 'faq'])->name('public.faq');
Route::get('/help', [PublicPageController::class, 'help'])->name('public.help');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('public.contact');
Route::post('/contact', [PublicPageController::class, 'contactSubmit'])->middleware('throttle:signup')->name('public.contact.submit');
Route::get('/status', [PublicPageController::class, 'status'])->name('public.status');
Route::get('/sla', [PublicPageController::class, 'sla'])->name('public.sla');

// Onboarding / Auth Path Selectors & Tailored Routes
Route::get('/signup', [PublicPageController::class, 'showRegisterSelector'])->name('register');
Route::get('/register', function() { return redirect()->route('register'); });

// Patient Onboarding
Route::get('/signup/patient', [PublicPageController::class, 'showPatientRegister'])->name('register.patient');
Route::post('/signup/patient', [PublicPageController::class, 'submitPatientRegister'])->middleware('throttle:signup')->name('register.patient.submit');
Route::get('/register/patient', function() { return redirect()->route('register.patient'); });

// Guardian Caregiver Requests
Route::get('/signup/guardian', [PublicPageController::class, 'showGuardianRegister'])->name('register.guardian');
Route::post('/signup/guardian', [PublicPageController::class, 'submitGuardianRegister'])->middleware('throttle:signup')->name('register.guardian.submit');

// Organization Onboarding Form
Route::get('/signup/organization', [PublicPageController::class, 'showOrganizationRegister'])->name('register.organization');
Route::post('/signup/organization', [PublicPageController::class, 'submitOrganizationRegister'])->middleware('throttle:signup')->name('register.organization.submit');
Route::get('/register/hospital', function() { return redirect()->route('register.organization'); });

// Developer API Requests
Route::get('/signup/developer', [PublicPageController::class, 'showDeveloperRegister'])->name('register.developer');
Route::post('/signup/developer', [PublicPageController::class, 'submitDeveloperRegister'])->middleware('throttle:signup')->name('register.developer.submit');

// Staff Invitation Activation
// Both verbs are throttled: the POST mints a staff account at a facility, and
// the GET tells an enumerating caller whether a token is live by what it
// renders. Limiting only the POST would leave the oracle wide open.
Route::get('/invite/{token}', [PublicPageController::class, 'showStaffInvite'])->middleware('throttle:invite')->name('invite.accept');
Route::post('/invite/{token}', [PublicPageController::class, 'submitStaffInvite'])->middleware('throttle:invite')->name('invite.accept.submit');

// Password Recovery & Credential Update
Route::get('/forgot-password', [PublicPageController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [PublicPageController::class, 'submitForgotPassword'])->middleware('throttle:password-reset')->name('password.email');
// Both verbs are throttled: the POST sets a password from a token in the URL,
// so a successful guess is an account takeover, and the GET is an oracle too —
// it renders the form for a live token and an expired-link card for a dead one.
Route::get('/reset-password/{token}', [PublicPageController::class, 'showResetPassword'])->middleware('throttle:password-reset-token')->name('password.reset');
Route::post('/reset-password/{token}', [PublicPageController::class, 'submitResetPassword'])->middleware('throttle:password-reset-token')->name('password.update');

// OTP Screen Challenge
Route::get('/verify/otp', [PublicPageController::class, 'showVerifyOtp'])->name('otp.verify');
Route::post('/verify/otp', [PublicPageController::class, 'submitVerifyOtp'])->middleware('throttle:auth-code')->name('otp.verify.submit');
Route::post('/verify/otp/resend', [PublicPageController::class, 'resendOtp'])->middleware('throttle:auth-code')->name('otp.resend');

// Verification & Restriction Status Displays
// Where EnsurePortalAccess sends an authenticated user whose own portal is
// frozen out of the launch scope. Without it they were redirected onto a
// 404 and left with no working page at all.
Route::middleware(['web', 'auth'])->get('/portal-unavailable', [PublicPageController::class, 'showPortalUnavailable'])->name('portal.unavailable');
Route::get('/pending-approval', [PublicPageController::class, 'showPendingApproval'])->name('account.pending');
Route::get('/account-suspended', [PublicPageController::class, 'showAccountSuspended'])->name('account.suspended');

// Multi-Facility Access Plane Selector
Route::get('/select-facility', [PublicPageController::class, 'showSelectFacility'])->name('select-facility');
Route::post('/select-facility', [PublicPageController::class, 'submitSelectFacility'])->name('select-facility.submit');

// Secure Portal Access Override
Route::get('/login', [PublicPageController::class, 'showLogin'])->name('login');
// Rate-limit the web login to blunt credential brute-force / password spraying
// (the API login is throttled; the web form was not).
Route::post('/login', [PublicPageController::class, 'submitLogin'])->middleware('throttle:5,1')->name('login.submit');
Route::get('/mfa/challenge', [PublicPageController::class, 'showMfaChallenge'])->name('mfa.challenge');
Route::post('/mfa/challenge', [PublicPageController::class, 'submitMfaChallenge'])->middleware('throttle:auth-code')->name('mfa.challenge.submit');

// ── Patient mobile app (Expo web build) ─────────────────────────────────────
// The app is exported as a single-page bundle into public/app, so nginx serves
// every real file (/app/_expo/*, /app/assets/*) directly and never reaches
// Laravel. Only paths that do NOT exist on disk fall through to here — which is
// exactly the client-side routes expo-router owns. Without this, deep links and
// refreshes inside the app would 404.
//
// It talks to this same origin's API, so there is no CORS surface and no second
// host to keep in step.
Route::get('/app/{path?}', function (?string $path = null) {
    $index = public_path('app/index.html');

    abort_unless(is_file($index), 404);

    return response()->file($index, [
        // The shell is tiny and changes on every deploy; the fingerprinted
        // bundles it pulls are immutable and cached by nginx on their own.
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
})->where('path', '.*')->name('mobile.app');

// Session / Logout
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login')->with('success', 'You have been signed out securely.');
})->name('logout');
Route::get('/logout', function () {
    return redirect()->route('login');
});

// Localization Switcher
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr'])) {
        Session::put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

// Demo Access Routes
Route::middleware(['web'])->group(function () {
    Route::get('/demo-access',          [\App\Http\Controllers\Demo\DemoAccessController::class, 'index'])->name('demo.index');
    Route::get('/demo-access/public',   [\App\Http\Controllers\Demo\DemoAccessController::class, 'publicDemo'])->name('demo.public');
    Route::get('/demo-access/internal', [\App\Http\Controllers\Demo\DemoAccessController::class, 'internalDemo'])->name('demo.internal');
    Route::post('/demo-access/login-as', [\App\Http\Controllers\Demo\DemoAccessController::class, 'loginAs'])->name('demo.login-as');
});

// Public Medical ID Verification Routes
Route::middleware(['web', 'throttle:verify'])->group(function () {
    Route::get('/verify/health-id', [\App\Http\Controllers\MedicalId\VerifyController::class, 'healthId'])->name('verify.health-id');
    Route::post('/verify/health-id', [\App\Http\Controllers\MedicalId\VerifyController::class, 'healthIdLookup'])->name('verify.health-id.lookup');
    Route::get('/verify/qr/{token}', [\App\Http\Controllers\MedicalId\VerifyController::class, 'qr'])->name('verify.qr');
});

// Portal Routes — require authentication, correct portal for role, and facility context
// ── Patient profile completion ──────────────────────────────────────────────
// Sign-up stops at email + password; this is where identity is collected and
// the Health ID is minted. Deliberately OUTSIDE the patient.profile gate below,
// or the gate would redirect to itself forever.
Route::middleware(['web', 'auth', 'mfa.verified', 'throttle:portal'])->group(function () {
    Route::get('/portals/patient/complete-profile',  [\App\Http\Controllers\MedicalId\PatientProfileCompletionController::class, 'show'])->name('portals.patient.complete-profile');
    Route::post('/portals/patient/complete-profile', [\App\Http\Controllers\MedicalId\PatientProfileCompletionController::class, 'store'])->name('portals.patient.complete-profile.store');
});

// ── Caregiver profile completion ────────────────────────────────────────────
// Same shape as the patient step above, and outside the guardian.profile gate
// for the same reason: the gate redirects here, so it must not guard itself.
Route::middleware(['web', 'auth', 'mfa.verified', 'throttle:portal'])->group(function () {
    Route::get('/portals/guardian/complete-profile',  [\App\Http\Controllers\MedicalId\GuardianProfileCompletionController::class, 'show'])->name('portals.guardian.complete-profile');
    Route::post('/portals/guardian/complete-profile', [\App\Http\Controllers\MedicalId\GuardianProfileCompletionController::class, 'store'])->name('portals.guardian.complete-profile.store');
    Route::get('/portals/guardian/pending',           [\App\Http\Controllers\MedicalId\GuardianProfileCompletionController::class, 'pending'])->name('portals.guardian.pending');
});

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'patient.profile', 'guardian.profile', 'throttle:portal'])->group(function () {
    Route::get('/portals/patient', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'index'])->name('portals.patient');
    // QR generation has its own tighter rate limit (10/min) on top of the portal limit
    Route::post('/portals/patient/generate-qr', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'generateTemporaryQr'])
        ->middleware('throttle:portal.qr')
        ->name('portals.patient.qr');

    Route::get('/portals/staff', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'index'])->name('portals.staff');
    // Action-task inbox (surfaces the Tasks module)
    Route::get('/portals/staff/tasks', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'index'])->name('portals.staff.tasks');
    Route::post('/portals/staff/tasks/{uuid}/acknowledge', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'acknowledge'])->name('portals.staff.tasks.acknowledge');
    Route::post('/portals/staff/tasks/{uuid}/complete', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'complete'])->name('portals.staff.tasks.complete');
    Route::post('/portals/staff/tasks/{uuid}/escalate', [\App\Http\Controllers\MedicalId\TaskInboxController::class, 'escalate'])->name('portals.staff.tasks.escalate');
    Route::get('/portals/staff/appointments', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointments'])->name('portals.staff.appointments');
    Route::get('/portals/staff/queue', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'queue'])->name('portals.staff.queue');
    Route::get('/portals/staff/billing', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'billing'])->name('portals.staff.billing');
    Route::get('/portals/staff/support', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'support'])->name('portals.staff.support');

    Route::get('/portals/staff/referrals', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referrals'])->name('portals.staff.referrals');
    Route::get('/portals/staff/referrals/create', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsCreate'])->name('portals.staff.referrals.create');
    Route::post('/portals/staff/referrals', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsStore'])->name('portals.staff.referrals.store');
    Route::get('/portals/staff/referrals/{id}', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsShow'])->name('portals.staff.referrals.show');
    Route::post('/portals/staff/referrals/{id}/send', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsSend'])->name('portals.staff.referrals.send');
    Route::post('/portals/staff/referrals/{id}/accept', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsAccept'])->name('portals.staff.referrals.accept');
    Route::post('/portals/staff/referrals/{id}/reject', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsReject'])->name('portals.staff.referrals.reject');
    Route::post('/portals/staff/referrals/{id}/complete', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsComplete'])->name('portals.staff.referrals.complete');
    Route::post('/portals/staff/referrals/{id}/cancel', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'referralsCancel'])->name('portals.staff.referrals.cancel');

    Route::get('/portals/staff/queue-display', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'queueDisplay'])->name('portals.staff.queue-display');

    Route::get('/portals/staff/immunizations', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'immunizations'])->name('portals.staff.immunizations');
    Route::get('/portals/staff/immunizations/record', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'immunizationsRecord'])->name('portals.staff.immunizations.record');
    Route::post('/portals/staff/immunizations', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'immunizationsStore'])->name('portals.staff.immunizations.store');

    Route::middleware(['guardian.context'])->group(function () {
        Route::get('/portals/patient/appointments', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'appointments'])->name('portals.patient.appointments');
        // Booking + reschedule (book must precede the {id} routes)
        Route::get('/portals/patient/appointments/book', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'bookAppointmentForm'])->name('portals.patient.appointments.book');
        Route::post('/portals/patient/appointments/book', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'bookAppointment'])->name('portals.patient.appointments.book.store');
        Route::get('/portals/patient/appointments/{id}/reschedule', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'rescheduleAppointmentForm'])->name('portals.patient.appointments.reschedule');
        Route::post('/portals/patient/appointments/{id}/reschedule', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'rescheduleAppointment'])->name('portals.patient.appointments.reschedule.store');
        Route::post('/portals/patient/appointments/{id}/cancel', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'cancelAppointment'])->name('portals.patient.appointments.cancel');
        Route::get('/portals/patient/labs', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'labResults'])->name('portals.patient.labs');
        Route::get('/portals/patient/prescriptions', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'prescriptions'])->name('portals.patient.prescriptions');
        Route::post('/portals/patient/prescriptions/{id}/refill', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'requestRefill'])->name('portals.patient.prescriptions.refill');
        Route::get('/portals/patient/consent', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'consentRequests'])->name('portals.patient.consent');
        Route::post('/portals/patient/consent/{id}/approve', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'approveConsent'])->name('portals.patient.consent.approve');
        Route::post('/portals/patient/consent/{id}/deny', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'denyConsent'])->name('portals.patient.consent.deny');
        Route::post('/portals/patient/consent/{id}/revoke', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'revokeConsent'])->name('portals.patient.consent.revoke');
        Route::get('/portals/patient/documents', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'documents'])->name('portals.patient.documents');
        Route::get('/portals/patient/documents/{id}/download', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'documentDownload'])->name('portals.patient.documents.download');
        // Subscription self-service (view plan, upgrade, cancel)
        Route::get('/portals/patient/subscription', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'subscription'])->name('portals.patient.subscription');
        Route::post('/portals/patient/subscription/subscribe', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'subscribe'])->name('portals.patient.subscription.subscribe');
        Route::post('/portals/patient/subscription/cancel', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'cancelSubscription'])->name('portals.patient.subscription.cancel');
        // Mobile Money checkout for paid plans (collect phone → initiate → poll → activate)
        Route::get('/portals/patient/subscription/checkout', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'subscriptionCheckout'])->name('portals.patient.subscription.checkout');
        Route::post('/portals/patient/subscription/checkout', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'subscriptionCheckoutPay'])->name('portals.patient.subscription.checkout.pay');
        Route::get('/portals/patient/subscription/pending/{subscription}', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'subscriptionCheckoutPending'])->name('portals.patient.subscription.pending');
        Route::get('/portals/patient/subscription/pending/{subscription}/status', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'subscriptionCheckoutStatus'])->name('portals.patient.subscription.status');
        // Health ID card PDF download
        Route::get('/portals/patient/health-id-card/download', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'downloadHealthIdCard'])->name('portals.patient.health-id-card.download');
        // QR token management
        Route::post('/portals/patient/qr/{tokenId}/revoke', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'revokeQrToken'])->name('portals.patient.qr.revoke');
        Route::post('/portals/patient/qr/revoke-all', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'revokeAllQrTokens'])->name('portals.patient.qr.revoke-all');
        Route::post('/portals/patient/report-lost-card', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'reportLostCard'])->name('portals.patient.report-lost-card');
        Route::get('/portals/patient/profile', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'profile'])->name('portals.patient.profile');
        Route::post('/portals/patient/profile', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'updateProfile'])->name('portals.patient.profile.update');
        Route::get('/portals/patient/logs',          [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'accessLogs'])->name('portals.patient.logs');
        // Clinical data pages (blood group visible on dashboard; detail pages here)
        Route::get('/portals/patient/allergies',     [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'allergies'])->name('portals.patient.allergies');
        Route::get('/portals/patient/clinical',      [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'clinicalHistory'])->name('portals.patient.clinical');
        Route::get('/portals/patient/immunizations', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'immunizations'])->name('portals.patient.immunizations');
        // Health timeline + clinical extensions (mirrors mobile endpoints)
        Route::get('/portals/patient/timeline',   [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'timeline'])->name('portals.patient.timeline');
        Route::get('/portals/patient/care-plans', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'carePlans'])->name('portals.patient.care-plans');
        Route::get('/portals/patient/referrals',  [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'referrals'])->name('portals.patient.referrals');
        Route::get('/portals/patient/surveys',    [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'surveys'])->name('portals.patient.surveys');
        Route::get('/portals/patient/settings',   [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'settings'])->name('portals.patient.settings');
        Route::post('/portals/patient/settings',  [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'updateSettings'])->name('portals.patient.settings.update');

        // Secure messaging (patient ↔ care team) — built on the Messaging module
        // Billing history + pay a facility invoice via MTN MoMo
        Route::get('/portals/patient/billing', [\App\Http\Controllers\MedicalId\PatientBillingController::class, 'index'])->name('portals.patient.billing');
        Route::get('/portals/patient/billing/{invoice}/pay', [\App\Http\Controllers\MedicalId\PatientBillingController::class, 'payForm'])->name('portals.patient.billing.pay');
        Route::post('/portals/patient/billing/{invoice}/pay', [\App\Http\Controllers\MedicalId\PatientBillingController::class, 'pay'])->name('portals.patient.billing.pay.store');
        Route::get('/portals/patient/billing/payment/{payment}/pending', [\App\Http\Controllers\MedicalId\PatientBillingController::class, 'pending'])->name('portals.patient.billing.pending');
        Route::get('/portals/patient/billing/payment/{payment}/status', [\App\Http\Controllers\MedicalId\PatientBillingController::class, 'status'])->name('portals.patient.billing.status');
        // Notifications centre + channel preferences
        Route::get('/portals/patient/notifications', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'index'])->name('portals.patient.notifications');
        Route::post('/portals/patient/notifications/read', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'markAllRead'])->name('portals.patient.notifications.read');
        Route::get('/portals/patient/notifications/preferences', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'preferences'])->name('portals.patient.notifications.preferences');
        Route::post('/portals/patient/notifications/preferences', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'updatePreferences'])->name('portals.patient.notifications.preferences.update');
        Route::get('/portals/patient/messages', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'index'])->name('portals.patient.messages');
        // Compose a new thread (must precede the {thread} wildcard)
        Route::get('/portals/patient/messages/compose', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'composeForm'])->name('portals.patient.messages.compose');
        Route::post('/portals/patient/messages', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'store'])->name('portals.patient.messages.store');
        Route::get('/portals/patient/messages/{thread}', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'show'])->name('portals.patient.messages.show');
        Route::post('/portals/patient/messages/{thread}/messages', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'send'])->name('portals.patient.messages.send');

        // Insurance marketplace
        Route::get('/portals/patient/insurance',                 [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'insuranceMarketplace'])->name('portals.patient.insurance');
        Route::get('/portals/patient/insurance/plans/{id}',      [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'insurancePlanDetail'])->name('portals.patient.insurance.plan');
        Route::post('/portals/patient/insurance/plans/{id}/purchase', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'insurancePurchase'])->name('portals.patient.insurance.purchase');

        // ── Data Subject Rights (Cameroon Law No. 2010/012 Art. 21–24) ──────────
        Route::get('/portals/patient/data-rights/export',  [\App\Http\Controllers\MedicalId\DataSubjectRightsController::class, 'export'])->name('portals.patient.data-rights.export');
        Route::post('/portals/patient/data-rights/rectify',[\App\Http\Controllers\MedicalId\DataSubjectRightsController::class, 'rectify'])->name('portals.patient.data-rights.rectify');
        Route::post('/portals/patient/data-rights/erase',  [\App\Http\Controllers\MedicalId\DataSubjectRightsController::class, 'erase'])->name('portals.patient.data-rights.erase');
    });

    // ── Staff: Visit Flow ────────────────────────────────────────
    Route::get('/portals/staff/visits', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'index'])->name('portals.staff.visits');
    Route::post('/portals/staff/visits', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'store'])->name('portals.staff.visits.store');
    Route::post('/portals/staff/visits/{id}/transition', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'transition'])->name('portals.staff.visits.transition');
    Route::post('/portals/staff/visits/{id}/complete', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'complete'])->name('portals.staff.visits.complete');
    Route::post('/portals/staff/visits/{id}/cancel', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'cancel'])->name('portals.staff.visits.cancel');
    Route::get('/portals/staff/visits/{id}/triage', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'triage'])->name('portals.staff.visits.triage');
    Route::post('/portals/staff/visits/{id}/triage', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'triageStore'])->name('portals.staff.visits.triage.store');
    Route::post('/portals/staff/visits/{id}/triage/escalate', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'triageEscalate'])->name('portals.staff.visits.triage.escalate');
    Route::get('/portals/staff/visits/{id}/consult', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'consult'])->name('portals.staff.visits.consult');
    Route::post('/portals/staff/visits/{id}/consult', [\App\Http\Controllers\MedicalId\VisitPortalController::class, 'consultStore'])->name('portals.staff.visits.consult.store');

    // --- Inventory Portal ---
    Route::get('/portals/staff/inventory/pharmacy', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'pharmacy'])->name('portals.staff.inventory.pharmacy');
    Route::post('/portals/staff/inventory/pharmacy', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'pharmacyStore'])->name('portals.staff.inventory.pharmacy.store');
    Route::post('/portals/staff/inventory/pharmacy/{id}/restock', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'pharmacyRestock'])->name('portals.staff.inventory.pharmacy.restock');
    Route::post('/portals/staff/inventory/pharmacy/{id}/dispense', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'pharmacyDispense'])->name('portals.staff.inventory.pharmacy.dispense');
    Route::post('/portals/staff/inventory/pharmacy/{id}/flag', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'pharmacyFlag'])->name('portals.staff.inventory.pharmacy.flag');
    Route::delete('/portals/staff/inventory/pharmacy/{id}', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'pharmacyDelete'])->name('portals.staff.inventory.pharmacy.delete');
    Route::get('/portals/staff/inventory/blood', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'blood'])->name('portals.staff.inventory.blood');
    Route::post('/portals/staff/inventory/blood', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'bloodUpsert'])->name('portals.staff.inventory.blood.upsert');
    Route::post('/portals/staff/inventory/blood/{id}/adjust', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'bloodAdjust'])->name('portals.staff.inventory.blood.adjust');
    Route::post('/portals/staff/inventory/blood/{id}/flag', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'bloodFlag'])->name('portals.staff.inventory.blood.flag');

    // --- Staff HR Portal ---
    Route::get('/portals/staff/hr/directory', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'directory'])->name('portals.staff.hr.directory');
    Route::post('/portals/staff/hr/directory', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'directoryStore'])->name('portals.staff.hr.directory.store');
    Route::post('/portals/staff/hr/directory/{id}/status', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'directoryStatus'])->name('portals.staff.hr.directory.status');
    Route::post('/portals/staff/hr/directory/{id}/license', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'addLicense'])->name('portals.staff.hr.directory.license');
    Route::get('/portals/staff/hr/shifts', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'shifts'])->name('portals.staff.hr.shifts');
    Route::post('/portals/staff/hr/shifts', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'shiftsStore'])->name('portals.staff.hr.shifts.store');
    Route::post('/portals/staff/hr/shifts/{id}/toggle', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'shiftsToggle'])->name('portals.staff.hr.shifts.toggle');
    Route::get('/portals/staff/hr/roster', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'roster'])->name('portals.staff.hr.roster');
    Route::post('/portals/staff/hr/roster', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'rosterStore'])->name('portals.staff.hr.roster.store');
    Route::post('/portals/staff/hr/roster/{id}/publish', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'rosterPublish'])->name('portals.staff.hr.roster.publish');
    Route::post('/portals/staff/hr/roster/{id}/archive', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'rosterArchive'])->name('portals.staff.hr.roster.archive');
    Route::post('/portals/staff/hr/roster/{rosterId}/assign', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'rosterAssign'])->name('portals.staff.hr.roster.assign');
    Route::delete('/portals/staff/hr/roster/assignment/{assignmentId}', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'rosterUnassign'])->name('portals.staff.hr.roster.unassign');
    Route::get('/portals/staff/hr/leave', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'leave'])->name('portals.staff.hr.leave');
    Route::post('/portals/staff/hr/leave', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'leaveStore'])->name('portals.staff.hr.leave.store');
    Route::post('/portals/staff/hr/leave/{id}/approve', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'leaveApprove'])->name('portals.staff.hr.leave.approve');
    Route::post('/portals/staff/hr/leave/{id}/reject', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'leaveReject'])->name('portals.staff.hr.leave.reject');
    Route::post('/portals/staff/hr/leave/{id}/withdraw', [\App\Http\Controllers\MedicalId\StaffHRPortalController::class, 'leaveWithdraw'])->name('portals.staff.hr.leave.withdraw');

    // ── Staff: Appointment actions ──────────────────────────────
    Route::get('/portals/staff/appointments/create', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsCreate'])->name('portals.staff.appointments.create');
    Route::post('/portals/staff/appointments', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsStore'])->name('portals.staff.appointments.store');
    Route::post('/portals/staff/appointments/{id}/confirm', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsConfirm'])->name('portals.staff.appointments.confirm');
    Route::post('/portals/staff/appointments/{id}/cancel', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsCancel'])->name('portals.staff.appointments.cancel');
    Route::post('/portals/staff/appointments/{id}/check-in', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsCheckIn'])->name('portals.staff.appointments.check-in');
    Route::post('/portals/staff/appointments/{id}/no-show', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsNoShow'])->name('portals.staff.appointments.no-show');

    // ── Staff: Queue actions ─────────────────────────────────────
    Route::post('/portals/staff/queue/check-in', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'queueCheckIn'])->name('portals.staff.queue.check-in');
    Route::post('/portals/staff/queue/{id}/call', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'queueCall'])->name('portals.staff.queue.call');
    Route::post('/portals/staff/queue/{id}/start', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'queueStart'])->name('portals.staff.queue.start');
    Route::post('/portals/staff/queue/{id}/complete', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'queueComplete'])->name('portals.staff.queue.complete');

    // ── Staff: Billing actions ───────────────────────────────────
    Route::get('/portals/staff/billing/create', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'billingCreate'])->name('portals.staff.billing.create');
    Route::post('/portals/staff/billing', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'billingStore'])->name('portals.staff.billing.store');
    Route::post('/portals/staff/billing/{id}/pay', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'billingPay'])->name('portals.staff.billing.pay');

    // ── Staff: Support actions ───────────────────────────────────
    Route::post('/portals/staff/support', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'supportStore'])->name('portals.staff.support.store');
    Route::post('/portals/staff/support/{id}/reply', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'supportReply'])->name('portals.staff.support.reply');
    Route::post('/portals/staff/support/{id}/close', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'supportClose'])->name('portals.staff.support.close');
    Route::post('/portals/staff/support/{id}/escalate', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'supportEscalate'])->name('portals.staff.support.escalate');
    Route::post('/portals/staff/support/{id}/assign', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'supportAssign'])->name('portals.staff.support.assign');

    // ══ BEGIN: staff messaging + facility team management ════════
    // Two halves of the same gap: patients could send messages nobody could
    // read, and facilities could not create the staff accounts that would read
    // them. Both sit in this group so they inherit portal.access (role→portal
    // routing), facility.context (session facility) and throttle:portal.

    // ── Staff: patient message inbox ─────────────────────────────
    Route::get('/portals/staff/messages',                 [\App\Http\Controllers\MedicalId\StaffMessagingController::class, 'index'])->name('portals.staff.messages');
    Route::get('/portals/staff/messages/{thread}',        [\App\Http\Controllers\MedicalId\StaffMessagingController::class, 'show'])->name('portals.staff.messages.show');
    Route::post('/portals/staff/messages/{thread}/reply', [\App\Http\Controllers\MedicalId\StaffMessagingController::class, 'send'])->name('portals.staff.messages.send');

    // ── Facility admin: my team (invite / reissue / revoke) ───────
    // Prefix is portals/facility, NOT portals/admin: everything under
    // portals/admin/* is platform-tier only (RequirePlatformAdmin), which is
    // exactly why facility admins had no staff management at all.
    Route::get('/portals/facility/team',                            [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'index'])->name('portals.facility.team');
    Route::post('/portals/facility/team/invites',                   [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'store'])->name('portals.facility.team.invite');
    Route::post('/portals/facility/team/invites/{id}/reissue',      [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'reissue'])->name('portals.facility.team.invite.reissue');
    Route::post('/portals/facility/team/invites/{id}/revoke',       [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'revoke'])->name('portals.facility.team.invite.revoke');
    // ══ END: staff messaging + facility team management ══════════

    // ── Data Import Portal ────────────────────────────────────────
    Route::get('/portals/staff/data-import',                    [\App\Http\Controllers\MedicalId\DataImportController::class, 'index'])->name('portals.staff.data_import.index');
    Route::get('/portals/staff/data-import/upload',             [\App\Http\Controllers\MedicalId\DataImportController::class, 'create'])->name('portals.staff.data_import.create');
    Route::post('/portals/staff/data-import',                   [\App\Http\Controllers\MedicalId\DataImportController::class, 'store'])->name('portals.staff.data_import.store');
    Route::get('/portals/staff/data-import/{id}/mapping',       [\App\Http\Controllers\MedicalId\DataImportController::class, 'mapping'])->name('portals.staff.data_import.mapping');
    Route::post('/portals/staff/data-import/{id}/mapping',      [\App\Http\Controllers\MedicalId\DataImportController::class, 'mappingStore'])->name('portals.staff.data_import.mapping.store');
    Route::post('/portals/staff/data-import/{id}/validate',     [\App\Http\Controllers\MedicalId\DataImportController::class, 'validate'])->name('portals.staff.data_import.validate');
    Route::get('/portals/staff/data-import/{id}/preview',       [\App\Http\Controllers\MedicalId\DataImportController::class, 'preview'])->name('portals.staff.data_import.preview');
    Route::post('/portals/staff/data-import/{id}/approve',      [\App\Http\Controllers\MedicalId\DataImportController::class, 'approve'])->name('portals.staff.data_import.approve');
    Route::post('/portals/staff/data-import/{id}/rollback',     [\App\Http\Controllers\MedicalId\DataImportController::class, 'rollback'])->name('portals.staff.data_import.rollback');
    Route::post('/portals/staff/data-import/{id}/cancel',       [\App\Http\Controllers\MedicalId\DataImportController::class, 'cancel'])->name('portals.staff.data_import.cancel');
    Route::get('/portals/staff/data-import/{id}/audit',         [\App\Http\Controllers\MedicalId\DataImportController::class, 'auditLog'])->name('portals.staff.data_import.audit');

    // --- Global Search ---
    Route::get('/portals/staff/search', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'search'])->name('portals.staff.search');

    // ── Clinical Register (prescription & lab listings for clinical staff) ─────
    Route::get('/portals/staff/prescriptions', [\App\Http\Controllers\MedicalId\StaffClinicalController::class, 'prescriptions'])->name('portals.staff.prescriptions');
    // ── BEGIN prescribing block (clinician issues a prescription) ─────────────
    // `create` must precede any /{id} route added under this prefix later.
    Route::get('/portals/staff/prescriptions/create',     [\App\Http\Controllers\MedicalId\StaffClinicalController::class, 'prescriptionCreate'])->name('portals.staff.prescriptions.create');
    Route::post('/portals/staff/prescriptions',           [\App\Http\Controllers\MedicalId\StaffClinicalController::class, 'prescriptionStore'])->name('portals.staff.prescriptions.store');
    // No update / destroy by design — a prescription is an immutable clinical
    // event, corrected by void or entered-in-error, never overwritten.
    Route::post('/portals/staff/prescriptions/{id}/void', [\App\Http\Controllers\MedicalId\StaffClinicalController::class, 'prescriptionVoid'])->name('portals.staff.prescriptions.void');
    // ── END prescribing block ────────────────────────────────────────────────
    Route::get('/portals/staff/lab-orders',    [\App\Http\Controllers\MedicalId\StaffClinicalController::class, 'labOrders'])->name('portals.staff.lab_orders');

    // --- Ward / Admission / Bed Management ---
    Route::get('/portals/staff/wards',                            [\App\Http\Controllers\MedicalId\WardController::class, 'index'])->name('portals.staff.wards');
    Route::post('/portals/staff/wards',                           [\App\Http\Controllers\MedicalId\WardController::class, 'wardStore'])->name('portals.staff.wards.store');
    Route::get('/portals/staff/wards/admissions',                 [\App\Http\Controllers\MedicalId\WardController::class, 'admissions'])->name('portals.staff.wards.admissions');
    Route::post('/portals/staff/wards/admissions',                [\App\Http\Controllers\MedicalId\WardController::class, 'admitStore'])->name('portals.staff.wards.admit');
    Route::post('/portals/staff/wards/admissions/{id}/discharge', [\App\Http\Controllers\MedicalId\WardController::class, 'dischargeStore'])->name('portals.staff.wards.discharge');
    Route::post('/portals/staff/wards/admissions/{id}/transfer',  [\App\Http\Controllers\MedicalId\WardController::class, 'transferStore'])->name('portals.staff.wards.transfer');

    // --- File Storage & Medical Attachments ---
    Route::get('/portals/staff/files',              [\App\Http\Controllers\MedicalId\FileStorageController::class, 'index'])->name('portals.staff.files.index');
    Route::get('/portals/staff/files/upload',       [\App\Http\Controllers\MedicalId\FileStorageController::class, 'create'])->name('portals.staff.files.create');
    Route::post('/portals/staff/files',             [\App\Http\Controllers\MedicalId\FileStorageController::class, 'store'])->name('portals.staff.files.store');
    Route::get('/portals/staff/files/{id}/download',[\App\Http\Controllers\MedicalId\FileStorageController::class, 'download'])->name('portals.staff.files.download');
    Route::delete('/portals/staff/files/{id}',      [\App\Http\Controllers\MedicalId\FileStorageController::class, 'destroy'])->name('portals.staff.files.destroy');

    // --- Supply Chain Portal ---
    Route::get('/portals/staff/supply',                                     [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'index'])->name('portals.staff.supply');
    Route::get('/portals/staff/supply/items',                               [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'items'])->name('portals.staff.supply.items');
    Route::post('/portals/staff/supply/items',                              [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'itemStore'])->name('portals.staff.supply.items.store');
    Route::get('/portals/staff/supply/suppliers',                           [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'suppliers'])->name('portals.staff.supply.suppliers');
    Route::post('/portals/staff/supply/suppliers',                          [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'supplierStore'])->name('portals.staff.supply.suppliers.store');
    Route::get('/portals/staff/supply/stock',                               [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'stock'])->name('portals.staff.supply.stock');
    Route::post('/portals/staff/supply/stock/receive',                      [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'stockReceive'])->name('portals.staff.supply.stock.receive');
    Route::post('/portals/staff/supply/stock/{id}/adjust',                  [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'stockAdjust'])->name('portals.staff.supply.stock.adjust');
    Route::get('/portals/staff/supply/purchase-orders',                     [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'purchaseOrders'])->name('portals.staff.supply.purchase_orders');
    Route::post('/portals/staff/supply/purchase-orders',                    [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'purchaseOrderStore'])->name('portals.staff.supply.purchase_orders.store');
    Route::post('/portals/staff/supply/purchase-orders/{id}/approve',       [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'purchaseOrderApprove'])->name('portals.staff.supply.purchase_orders.approve');
    Route::get('/portals/staff/supply/goods-receipts',                      [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'goodsReceipts'])->name('portals.staff.supply.goods_receipts');
    Route::post('/portals/staff/supply/goods-receipts',                     [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'goodsReceiptsStore'])->name('portals.staff.supply.goods_receipts.store');
    Route::get('/portals/staff/supply/movements',                           [\App\Http\Controllers\MedicalId\SupplyChainController::class, 'movements'])->name('portals.staff.supply.movements');

    // --- Insurance Portal ---
    Route::get('/portals/insurance', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'dashboard'])->name('portals.insurance.dashboard');
    Route::get('/portals/insurance/providers', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'providers'])->name('portals.insurance.providers');
    Route::post('/portals/insurance/providers', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'providersStore'])->name('portals.insurance.providers.store');
    Route::post('/portals/insurance/providers/{providerId}/plans', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'plansStore'])->name('portals.insurance.plans.store');

    Route::get('/portals/insurance/policies', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'policies'])->name('portals.insurance.policies');
    Route::post('/portals/insurance/policies', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'policiesStore'])->name('portals.insurance.policies.store');
    Route::post('/portals/insurance/policies/{id}/activate', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'policiesActivate'])->name('portals.insurance.policies.activate');
    Route::post('/portals/insurance/policies/{id}/deactivate', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'policiesDeactivate'])->name('portals.insurance.policies.deactivate');
    Route::post('/portals/insurance/policies/{policyId}/eligibility', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'eligibilityStore'])->name('portals.insurance.eligibility.store');

    Route::get('/portals/insurance/preauths', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'preauths'])->name('portals.insurance.preauths');
    Route::post('/portals/insurance/preauths', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'preauthsStore'])->name('portals.insurance.preauths.store');
    Route::post('/portals/insurance/preauths/{id}/submit', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'preauthsSubmit'])->name('portals.insurance.preauths.submit');
    Route::post('/portals/insurance/preauths/{id}/decide', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'preauthsDecide'])->name('portals.insurance.preauths.decide');
    Route::post('/portals/insurance/preauths/{id}/cancel', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'preauthsCancel'])->name('portals.insurance.preauths.cancel');

    Route::get('/portals/insurance/claims', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'claims'])->name('portals.insurance.claims');
    Route::post('/portals/insurance/claims', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'claimsStore'])->name('portals.insurance.claims.store');
    Route::post('/portals/insurance/claims/{id}/submit', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'claimsSubmit'])->name('portals.insurance.claims.submit');
    Route::post('/portals/insurance/claims/{id}/decide', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'claimsDecide'])->name('portals.insurance.claims.decide');
    Route::post('/portals/insurance/claims/{id}/cancel', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'claimsCancel'])->name('portals.insurance.claims.cancel');
    Route::post('/portals/insurance/claims/{id}/pay', [\App\Http\Controllers\MedicalId\InsurancePortalController::class, 'claimsPay'])->name('portals.insurance.claims.pay');

    Route::get('/portals/admin', [\App\Http\Controllers\MedicalId\AdminPortalController::class, 'index'])->name('portals.admin');
    // Facility task management (admin overview of the Tasks module)
    Route::get('/portals/admin/tasks', [\App\Http\Controllers\MedicalId\AdminTaskController::class, 'index'])->name('portals.admin.tasks');
    Route::post('/portals/admin/tasks', [\App\Http\Controllers\MedicalId\AdminTaskController::class, 'store'])->name('portals.admin.tasks.store');
    Route::post('/portals/admin/tasks/{uuid}/reassign', [\App\Http\Controllers\MedicalId\AdminTaskController::class, 'reassign'])->name('portals.admin.tasks.reassign');
    Route::post('/portals/admin/tasks/{uuid}/complete', [\App\Http\Controllers\MedicalId\AdminTaskController::class, 'complete'])->name('portals.admin.tasks.complete');
    Route::post('/portals/admin/tasks/{uuid}/escalate', [\App\Http\Controllers\MedicalId\AdminTaskController::class, 'escalate'])->name('portals.admin.tasks.escalate');
    // Broadcasts / announcements composer
    Route::get('/portals/admin/broadcasts', [\App\Http\Controllers\MedicalId\AdminBroadcastController::class, 'index'])->name('portals.admin.broadcasts');
    Route::post('/portals/admin/broadcasts', [\App\Http\Controllers\MedicalId\AdminBroadcastController::class, 'store'])->name('portals.admin.broadcasts.store');
    Route::post('/portals/admin/broadcasts/{uuid}/publish', [\App\Http\Controllers\MedicalId\AdminBroadcastController::class, 'publish'])->name('portals.admin.broadcasts.publish');
    Route::post('/portals/admin/broadcasts/{uuid}/cancel', [\App\Http\Controllers\MedicalId\AdminBroadcastController::class, 'cancel'])->name('portals.admin.broadcasts.cancel');

    // ── Facility Clinical Register (hospital_admin / clinic_admin) ─────────
    Route::get('/portals/admin/clinical/prescriptions', [\App\Http\Controllers\MedicalId\FacilityClinicalController::class, 'prescriptions'])->name('portals.admin.clinical.prescriptions');
    Route::get('/portals/admin/clinical/lab-orders',    [\App\Http\Controllers\MedicalId\FacilityClinicalController::class, 'labOrders'])->name('portals.admin.clinical.lab_orders');
    Route::get('/portals/admin/go-live', [\App\Http\Controllers\Api\V1\Admin\FacilityGoLiveReadinessController::class, 'index'])->name('portals.admin.go-live');

    // ── Master Admin Control Center ───────────────────────────────
    Route::get('/portals/admin/cc',                      [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'index'])->name('portals.admin.cc');
    Route::get('/portals/admin/cc/settings',             [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'settings'])->name('portals.admin.cc.settings');
    Route::post('/portals/admin/cc/settings',            [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'settingsUpdate'])->name('portals.admin.cc.settings.update');
    Route::get('/portals/admin/cc/feature-flags',        [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'featureFlags'])->name('portals.admin.cc.feature_flags');
    Route::post('/portals/admin/cc/feature-flags/{key}', [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'featureFlagToggle'])->name('portals.admin.cc.feature_flags.toggle');
    Route::get('/portals/admin/cc/modules',              [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'modules'])->name('portals.admin.cc.modules');
    Route::post('/portals/admin/cc/modules/{key}',       [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'moduleToggle'])->name('portals.admin.cc.modules.toggle');
    Route::get('/portals/admin/cc/maintenance',          [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'maintenance'])->name('portals.admin.cc.maintenance');
    Route::post('/portals/admin/cc/maintenance',         [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'maintenanceStore'])->name('portals.admin.cc.maintenance.store');
    Route::post('/portals/admin/cc/maintenance/{id}',    [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'maintenanceToggle'])->name('portals.admin.cc.maintenance.toggle');
    Route::get('/portals/admin/cc/health',               [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'systemHealth'])->name('portals.admin.cc.health');
    Route::get('/portals/admin/cc/audit',                [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'auditLog'])->name('portals.admin.cc.audit');

    // ── MINSANTE Compliance Reports ─────────────────────────────────────────
    Route::get('/portals/admin/reports/minsante-monthly',           [\App\Http\Controllers\MedicalId\ComplianceReportController::class, 'minsanteMonthly'])->name('portals.admin.reports.minsante-monthly');
    Route::get('/portals/admin/reports/minsante-monthly/download',  [\App\Http\Controllers\MedicalId\ComplianceReportController::class, 'minsanteMonthlyDownload'])->name('portals.admin.reports.minsante-monthly.download');

    // --- Connect Suite Admin Portal ---
    Route::get('/portals/admin/connect',                       [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'index'])->name('portals.admin.connect');
    Route::get('/portals/admin/connect/clients',               [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'clients'])->name('portals.admin.connect.clients');
    Route::post('/portals/admin/connect/clients',              [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'clientStore'])->name('portals.admin.connect.clients.store');
    Route::post('/portals/admin/connect/clients/{id}/action',  [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'clientAction'])->name('portals.admin.connect.clients.action');
    Route::get('/portals/admin/connect/tokens',                [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'tokens'])->name('portals.admin.connect.tokens');
    Route::post('/portals/admin/connect/tokens',               [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'tokenStore'])->name('portals.admin.connect.tokens.store');
    Route::post('/portals/admin/connect/tokens/{id}/revoke',   [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'tokenRevoke'])->name('portals.admin.connect.tokens.revoke');
    Route::get('/portals/admin/connect/webhooks',              [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'webhooks'])->name('portals.admin.connect.webhooks');
    Route::post('/portals/admin/connect/webhooks/{id}/toggle', [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'webhookToggle'])->name('portals.admin.connect.webhooks.toggle');
    Route::get('/portals/admin/connect/widget',                [\App\Http\Controllers\MedicalId\ConnectPortalController::class, 'widget'])->name('portals.admin.connect.widget');

    // --- Admin: Developer Portal Management ---
    Route::get('/portals/admin/developer/production-requests',             [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminProductionRequests'])->name('portals.admin.developer.production_requests');
    Route::post('/portals/admin/developer/production-requests/{prodRequest}/approve', [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminApproveProductionRequest'])->name('portals.admin.developer.production_requests.approve');
    Route::post('/portals/admin/developer/production-requests/{prodRequest}/reject',  [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminRejectProductionRequest'])->name('portals.admin.developer.production_requests.reject');
    Route::get('/portals/admin/developer/accounts',                        [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminDeveloperAccounts'])->name('portals.admin.developer.accounts');
    Route::post('/portals/admin/developer/accounts/{account}/suspend',     [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminSuspendDeveloper'])->name('portals.admin.developer.accounts.suspend');

    // --- Bridge Agent Management ---
    Route::get('/portals/admin/bridge',                        [\App\Http\Controllers\MedicalId\BridgeAdminController::class, 'index'])->name('portals.admin.bridge');
    Route::post('/portals/admin/bridge',                       [\App\Http\Controllers\MedicalId\BridgeAdminController::class, 'store'])->name('portals.admin.bridge.store');
    Route::post('/portals/admin/bridge/{id}/toggle',           [\App\Http\Controllers\MedicalId\BridgeAdminController::class, 'toggle'])->name('portals.admin.bridge.toggle');
    Route::get('/portals/admin/bridge/{id}/batches',           [\App\Http\Controllers\MedicalId\BridgeAdminController::class, 'batches'])->name('portals.admin.bridge.batches');

    // --- Subscription & SaaS Billing ---
    Route::get('/portals/admin/subscription/plans',                             [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'plans'])->name('portals.admin.subscription.plans');
    Route::post('/portals/admin/subscription/plans',                            [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'plansStore'])->name('portals.admin.subscription.plans.store');
    Route::post('/portals/admin/subscription/plans/{id}/toggle',                [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'plansToggle'])->name('portals.admin.subscription.plans.toggle');
    Route::get('/portals/admin/subscription',                                   [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptions'])->name('portals.admin.subscription');
    Route::post('/portals/admin/subscription',                                  [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptionsStore'])->name('portals.admin.subscription.store');
    // Static sub-paths MUST come before the {id} wildcard to avoid being swallowed by it
    Route::get('/portals/admin/subscription/invoices',                          [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'invoices'])->name('portals.admin.subscription.invoices');
    Route::post('/portals/admin/subscription/invoices/{id}/mark-paid',          [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'invoiceMarkPaid'])->name('portals.admin.subscription.invoices.mark_paid');
    Route::get('/portals/admin/subscription/{id}',                              [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptionDetail'])->name('portals.admin.subscription.detail');
    Route::post('/portals/admin/subscription/{id}/cancel',                      [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptionsCancel'])->name('portals.admin.subscription.cancel');
    Route::post('/portals/admin/subscription/{id}/renew',                       [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptionsRenew'])->name('portals.admin.subscription.renew');
    Route::post('/portals/admin/subscription/{id}/pause',                       [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptionsPause'])->name('portals.admin.subscription.pause');
    Route::post('/portals/admin/subscription/{id}/reactivate',                  [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptionsReactivate'])->name('portals.admin.subscription.reactivate');
    Route::post('/portals/admin/subscription/{id}/change-plan',                 [\App\Http\Controllers\MedicalId\SubscriptionAdminController::class, 'subscriptionsChangePlan'])->name('portals.admin.subscription.change_plan');

    // --- Security Operations Center ---
    Route::get('/portals/admin/security',                    [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'index'])->name('portals.admin.security');
    Route::get('/portals/admin/security/incidents',          [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'incidents'])->name('portals.admin.security.incidents');
    Route::post('/portals/admin/security/incidents',         [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'incidentStore'])->name('portals.admin.security.incidents.store');
    Route::post('/portals/admin/security/incidents/{id}',    [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'incidentUpdate'])->name('portals.admin.security.incidents.update');
    Route::get('/portals/admin/security/emergency-access',   [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'emergencyAccess'])->name('portals.admin.security.emergency_access');
    Route::get('/portals/admin/security/audit-explorer',     [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'auditExplorer'])->name('portals.admin.security.audit_explorer');

    // ── Family Management ──────────────────────────────────────────────────────
    Route::get('/portals/patient/family',                           [\App\Http\Controllers\MedicalId\FamilyController::class, 'index'])->name('portals.patient.family');
    Route::get('/portals/patient/family/add',                       [\App\Http\Controllers\MedicalId\FamilyController::class, 'addForm'])->name('portals.patient.family.add');
    Route::post('/portals/patient/family/add',                      [\App\Http\Controllers\MedicalId\FamilyController::class, 'store'])->name('portals.patient.family.store');
    Route::get('/portals/patient/family/invite',                    [\App\Http\Controllers\MedicalId\FamilyController::class, 'inviteForm'])->name('portals.patient.family.invite');
    Route::post('/portals/patient/family/invite',                   [\App\Http\Controllers\MedicalId\FamilyController::class, 'sendInvite'])->name('portals.patient.family.invite.send');
    Route::post('/portals/patient/family/switch/{patientId}',       [\App\Http\Controllers\MedicalId\FamilyController::class, 'switchTo'])->name('portals.patient.family.switch');
    Route::post('/portals/patient/family/switch-back',              [\App\Http\Controllers\MedicalId\FamilyController::class, 'switchBack'])->name('portals.patient.family.switch.back');
    Route::get('/portals/patient/family/{id}/edit',                 [\App\Http\Controllers\MedicalId\FamilyController::class, 'editForm'])->name('portals.patient.family.edit');
    Route::post('/portals/patient/family/{id}/edit',                [\App\Http\Controllers\MedicalId\FamilyController::class, 'update'])->name('portals.patient.family.update');
    Route::post('/portals/patient/family/{id}/revoke',              [\App\Http\Controllers\MedicalId\FamilyController::class, 'revoke'])->name('portals.patient.family.revoke');
    Route::post('/portals/patient/family/{id}/guardian-consent/approve', [\App\Http\Controllers\MedicalId\FamilyController::class, 'guardianConsentApprove'])->name('portals.patient.family.guardian_consent.approve');
    Route::post('/portals/patient/family/{id}/guardian-consent/deny',    [\App\Http\Controllers\MedicalId\FamilyController::class, 'guardianConsentDeny'])->name('portals.patient.family.guardian_consent.deny');

    // ── User Management (god mode) ──────────────────────────────────────────
    Route::prefix('portals/admin/users')->name('portals.admin.users.')->group(function () {
        Route::get('/',                  [AdminUserManagementController::class,    'index'])->name('index');
        Route::post('/',                 [AdminUserManagementController::class,    'store'])->name('store');
        Route::get('{id}',               [AdminUserManagementController::class,    'show'])->name('show');
        Route::post('{id}/update',       [AdminUserManagementController::class,    'update'])->name('update');
        Route::post('{id}/suspend',      [AdminUserManagementController::class,    'suspend'])->name('suspend');
        Route::post('{id}/activate',     [AdminUserManagementController::class,    'activate'])->name('activate');
        Route::post('{id}/reset-password',[AdminUserManagementController::class,   'resetPassword'])->name('reset-password');
        Route::post('{id}/delete',       [AdminUserManagementController::class,    'destroy'])->name('destroy');
    });

    // ── Facility Management ─────────────────────────────────────────────────
    Route::prefix('portals/admin/facilities')->name('portals.admin.facilities.')->group(function () {
        Route::get('/',              [AdminFacilityManagementController::class, 'index'])->name('index');
        Route::post('/',             [AdminFacilityManagementController::class, 'store'])->name('store');
        Route::get('{id}',           [AdminFacilityManagementController::class, 'show'])->name('show');
        Route::post('{id}/update',   [AdminFacilityManagementController::class, 'update'])->name('update');
        Route::post('{id}/suspend',  [AdminFacilityManagementController::class, 'suspend'])->name('suspend');
        Route::post('{id}/activate', [AdminFacilityManagementController::class, 'activate'])->name('activate');
        Route::post('{id}/approve',  [AdminFacilityManagementController::class, 'approve'])->name('approve');
        Route::post('{id}/delete',   [AdminFacilityManagementController::class, 'destroy'])->name('destroy');
    });

    // ── Patient Management ──────────────────────────────────────────────────
    Route::prefix('portals/admin/patients')->name('portals.admin.patients.')->group(function () {
        Route::get('/',              [AdminPatientManagementController::class, 'index'])->name('index');
        Route::get('{id}',           [AdminPatientManagementController::class, 'show'])->name('show');
        Route::post('{id}/update',   [AdminPatientManagementController::class, 'update'])->name('update');
        Route::post('{id}/suspend',  [AdminPatientManagementController::class, 'suspend'])->name('suspend');
        Route::post('{id}/activate', [AdminPatientManagementController::class, 'activate'])->name('activate');
        Route::post('{id}/delete',   [AdminPatientManagementController::class, 'destroy'])->name('destroy');
    });

    // ── Staff Overview ──────────────────────────────────────────────────────
    Route::prefix('portals/admin/staff')->name('portals.admin.staff.')->group(function () {
        Route::get('/',              [AdminStaffManagementController::class, 'index'])->name('index');
        Route::get('{id}',           [AdminStaffManagementController::class, 'show'])->name('show');
        Route::post('{id}/suspend',  [AdminStaffManagementController::class, 'suspend'])->name('suspend');
        Route::post('{id}/activate', [AdminStaffManagementController::class, 'activate'])->name('activate');
    });

    // ── Support Admin ───────────────────────────────────────────────────────
    Route::prefix('portals/admin/support')->name('portals.admin.support.')->group(function () {
        Route::get('/',              [AdminSupportController::class, 'index'])->name('index');
        Route::get('{id}',           [AdminSupportController::class, 'show'])->name('show');
        Route::post('{id}/assign',   [AdminSupportController::class, 'assign'])->name('assign');
        Route::post('{id}/close',    [AdminSupportController::class, 'close'])->name('close');
        Route::post('{id}/reopen',   [AdminSupportController::class, 'reopen'])->name('reopen');
        Route::post('{id}/delete',   [AdminSupportController::class, 'destroy'])->name('destroy');
    });

    // ── Appointments Overview ───────────────────────────────────────────────
    Route::prefix('portals/admin/appointments')->name('portals.admin.appointments.')->group(function () {
        Route::get('/',              [AdminAppointmentsController::class, 'index'])->name('index');
        Route::get('{id}',           [AdminAppointmentsController::class, 'show'])->name('show');
        Route::post('{id}/cancel',   [AdminAppointmentsController::class, 'cancel'])->name('cancel');
        Route::post('{id}/delete',   [AdminAppointmentsController::class, 'destroy'])->name('destroy');
    });

    // ── Roles & Permissions ─────────────────────────────────────────────────
    Route::prefix('portals/admin/roles')->name('portals.admin.roles.')->group(function () {
        Route::get('/',              [AdminRolesController::class, 'index'])->name('index');
        Route::post('/',             [AdminRolesController::class, 'store'])->name('store');
        Route::post('{id}/update',   [AdminRolesController::class, 'update'])->name('update');
        Route::post('{id}/delete',   [AdminRolesController::class, 'destroy'])->name('destroy');
        Route::get('{id}/users',     [AdminRolesController::class, 'users'])->name('users');
    });

    // ── Organizations / Facility Applications ──────────────────────────────
    Route::prefix('portals/admin/organizations')->name('portals.admin.organizations.')->group(function () {
        Route::get('/',              [AdminOrganizationsController::class, 'index'])->name('index');
        Route::get('pending',        [AdminOrganizationsController::class, 'pending'])->name('pending');
        Route::post('{id}/approve',  [AdminOrganizationsController::class, 'approve'])->name('approve');
        Route::post('{id}/reject',   [AdminOrganizationsController::class, 'reject'])->name('reject');
        Route::post('{id}/delete',   [AdminOrganizationsController::class, 'destroy'])->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| OpesCare Verifiable Document Web Routes
|--------------------------------------------------------------------------
*/
Route::get('/verify/document/{token}', [\App\Http\Controllers\Api\V1\DocumentController::class, 'verifyPublic'])->name('document.verify');
Route::get('/documents/{id}/view', [\App\Http\Controllers\Api\V1\DocumentController::class, 'renderDocument'])->name('document.render');
Route::get('/share/document/{token}', function ($token) {
    try {
        $document = resolve(\App\Services\Documents\DocumentShareService::class)->resolveShareLink($token);
        return redirect()->route('document.render', ['id' => $document->id]);
    } catch (\Exception $e) {
        abort(404, $e->getMessage());
    }
})->name('document.share.view');


/*
|--------------------------------------------------------------------------
| OpesCare Verified Care Access Map Web Routes
|--------------------------------------------------------------------------
*/
Route::get('/care-map', [\App\Http\Controllers\Api\V1\CareMapController::class, 'publicDirectory'])->name('public.care-map');
Route::get('/care-map/facility/{id}', [\App\Http\Controllers\Api\V1\CareMapController::class, 'publicProfile'])->name('public.care-map.profile');
Route::get('/care-map/emergency', [\App\Http\Controllers\Api\V1\CareMapController::class, 'publicEmergency'])->name('public.care-map.emergency');
Route::middleware(['web', 'auth', 'platform.admin'])->get('/admin/care-map/governance', [\App\Http\Controllers\Api\V1\CareMapController::class, 'adminGovernance'])->name('admin.care-map.governance');

// ┌──────────────────────────────────────────────────────────────────────────┐
// │ BEGIN — Facility claim + self-service listing + directory review         │
// └──────────────────────────────────────────────────────────────────────────┘
//
// Three audiences, three gates:
//
//  1. A facility representative claiming their listing (auth, listing id in
//     the URL — this is the ONLY place a facility id may come from a request).
//  2. That representative editing what they were approved for. Note that no
//     route below carries a facility id: FacilityListingController resolves it
//     from the approved claim on the authenticated user and nowhere else.
//  3. A platform administrator deciding claims and import candidates. The
//     /admin/care-map prefix is already in RequirePlatformAdmin's
//     PLATFORM_ONLY_PREFIXES, so facility-tier admins cannot reach it.

Route::middleware(['web', 'auth', 'throttle:portal'])->group(function () {
    Route::get('/care-map/facility/{id}/claim',  [\App\Http\Controllers\CareMap\FacilityClaimController::class, 'create'])->name('public.care-map.claim');
    Route::post('/care-map/facility/{id}/claim', [\App\Http\Controllers\CareMap\FacilityClaimController::class, 'store'])->name('public.care-map.claim.store');

    Route::get('/portals/listing/claims', [\App\Http\Controllers\CareMap\FacilityClaimController::class, 'myClaims'])->name('portals.listing.claims');

    // Self-service listing editor — deliberately id-less.
    Route::get('/portals/listing',                       [\App\Http\Controllers\CareMap\FacilityListingController::class, 'edit'])->name('portals.listing.edit');
    Route::post('/portals/listing',                      [\App\Http\Controllers\CareMap\FacilityListingController::class, 'update'])->name('portals.listing.update');
    Route::post('/portals/listing/hours',                [\App\Http\Controllers\CareMap\FacilityListingController::class, 'updateHours'])->name('portals.listing.hours.update');
    Route::post('/portals/listing/services',             [\App\Http\Controllers\CareMap\FacilityListingController::class, 'storeService'])->name('portals.listing.services.store');
    Route::delete('/portals/listing/services/{service}', [\App\Http\Controllers\CareMap\FacilityListingController::class, 'destroyService'])->name('portals.listing.services.destroy');
});

Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/care-map/review', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'index'])->name('admin.care-map.review');

    Route::post('/admin/care-map/review/claims/{claim}/approve', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'approveClaim'])->name('admin.care-map.review.claims.approve');
    Route::post('/admin/care-map/review/claims/{claim}/reject',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'rejectClaim'])->name('admin.care-map.review.claims.reject');
    Route::post('/admin/care-map/review/claims/{claim}/revoke',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'revokeClaim'])->name('admin.care-map.review.claims.revoke');

    Route::post('/admin/care-map/review/imports/{review}/accept', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'acceptImport'])->name('admin.care-map.review.imports.accept');
    Route::post('/admin/care-map/review/imports/{review}/merge',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'mergeImport'])->name('admin.care-map.review.imports.merge');
    Route::post('/admin/care-map/review/imports/{review}/reject', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'rejectImport'])->name('admin.care-map.review.imports.reject');
    Route::post('/admin/care-map/review/imports/{review}/defer',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'deferImport'])->name('admin.care-map.review.imports.defer');
});
// ┌──────────────────────────────────────────────────────────────────────────┐
// │ END — Facility claim + self-service listing + directory review           │
// └──────────────────────────────────────────────────────────────────────────┘

/*
|--------------------------------------------------------------------------
| OpesCare Health Organization Portal
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access'])->group(function () {
    Route::get('/portals/healthorg',           [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'dashboard'])->name('portals.healthorg.dashboard');
    Route::get('/portals/healthorg/programs',  [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'programs'])->name('portals.healthorg.programs');
    Route::get('/portals/healthorg/outreach',  [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'outreach'])->name('portals.healthorg.outreach');
    Route::get('/portals/healthorg/reports',   [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'reports'])->name('portals.healthorg.reports');
    Route::get('/portals/healthorg/signals',   [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'signals'])->name('portals.healthorg.signals');
    // Health-Org write flows (programs, outreach, reports, signal review)
    Route::post('/portals/healthorg/programs',  [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'storeProgram'])->name('portals.healthorg.programs.store');
    Route::post('/portals/healthorg/outreach',  [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'storeOutreach'])->name('portals.healthorg.outreach.store');
    Route::post('/portals/healthorg/outreach/{id}/complete', [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'completeOutreach'])->name('portals.healthorg.outreach.complete');
    Route::post('/portals/healthorg/reports',   [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'storeReport'])->name('portals.healthorg.reports.store');
    Route::post('/portals/healthorg/reports/{id}/submit', [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'submitReport'])->name('portals.healthorg.reports.submit');
    Route::post('/portals/healthorg/signals/{id}/review', [\App\Http\Controllers\MedicalId\HealthOrgPortalController::class, 'reviewSignal'])->name('portals.healthorg.signals.review');
});

/*
|--------------------------------------------------------------------------
| OpesCare Lab / Diagnostic Portal
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access'])->group(function () {
    Route::get('/portals/lab',                                   [\App\Http\Controllers\MedicalId\LabPortalController::class, 'dashboard'])->name('portals.lab.dashboard');
    Route::get('/portals/lab/orders',                            [\App\Http\Controllers\MedicalId\LabPortalController::class, 'orders'])->name('portals.lab.orders');
    Route::get('/portals/lab/results',                           [\App\Http\Controllers\MedicalId\LabPortalController::class, 'results'])->name('portals.lab.results');
    Route::get('/portals/lab/samples',                           [\App\Http\Controllers\MedicalId\LabPortalController::class, 'samples'])->name('portals.lab.samples');
    Route::post('/portals/lab/orders/{id}/collect',              [\App\Http\Controllers\MedicalId\LabPortalController::class, 'markCollected'])->name('portals.lab.orders.collect');
    Route::post('/portals/lab/orders/{id}/process',              [\App\Http\Controllers\MedicalId\LabPortalController::class, 'markProcessing'])->name('portals.lab.orders.process');
    Route::get('/portals/lab/orders/{id}/result',                [\App\Http\Controllers\MedicalId\LabPortalController::class, 'enterResultForm'])->name('portals.lab.orders.result');
    Route::post('/portals/lab/orders/{id}/result',               [\App\Http\Controllers\MedicalId\LabPortalController::class, 'storeResult'])->name('portals.lab.orders.result.store');
});

/*
|--------------------------------------------------------------------------
| OpesCare Pharmacy Portal
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access'])->group(function () {
    Route::get('/portals/pharmacy',                              [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'dashboard'])->name('portals.pharmacy.dashboard');
    Route::get('/portals/pharmacy/prescriptions',               [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'prescriptions'])->name('portals.pharmacy.prescriptions');
    Route::post('/portals/pharmacy/prescriptions/{id}/dispense',[\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'dispense'])->name('portals.pharmacy.dispense');
    Route::get('/portals/pharmacy/inventory',                   [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'inventory'])->name('portals.pharmacy.inventory');
    // Public medicine availability — the medicine_pharmacy_stocks rows the
    // patient Medicine Finder reads. Deliberately NOT under the
    // /portals/pharmacy/inventory path, which the inventory_ops V1 freeze
    // 404s (bootstrap/app.php): the finders ship in V1, facility-internal
    // inventory ops do not.
    Route::get('/portals/pharmacy/stock',                       [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'stock'])->name('portals.pharmacy.stock');
    Route::post('/portals/pharmacy/stock',                      [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'reportStock'])->name('portals.pharmacy.stock.report');
    Route::get('/portals/pharmacy/controlled',                  [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'controlled'])->name('portals.pharmacy.controlled');
});

/*
|--------------------------------------------------------------------------
| OpesCare Lite — Simplified Portal for Small / Low-Connectivity Facilities
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'portal.access'])->group(function () {
    Route::get('/portals/lite',                                         [\App\Http\Controllers\MedicalId\LitePortalController::class, 'dashboard'])->name('portals.lite.dashboard');
    Route::get('/portals/lite/lookup',                                  [\App\Http\Controllers\MedicalId\LitePortalController::class, 'lookup'])->name('portals.lite.lookup');
    Route::get('/portals/lite/register-patient',                        [\App\Http\Controllers\MedicalId\LitePortalController::class, 'registerPatientForm'])->name('portals.lite.register_patient');
    Route::post('/portals/lite/register-patient',                       [\App\Http\Controllers\MedicalId\LitePortalController::class, 'registerPatientStore'])->name('portals.lite.register_patient.store');
    Route::get('/portals/lite/checkin',                                 [\App\Http\Controllers\MedicalId\LitePortalController::class, 'checkIn'])->name('portals.lite.checkin');
    Route::post('/portals/lite/checkin',                                [\App\Http\Controllers\MedicalId\LitePortalController::class, 'checkInStore'])->name('portals.lite.checkin.store');
    Route::get('/portals/lite/consultation',                            [\App\Http\Controllers\MedicalId\LitePortalController::class, 'consultation'])->name('portals.lite.consultation');
    Route::get('/portals/lite/billing',                                 [\App\Http\Controllers\MedicalId\LitePortalController::class, 'billing'])->name('portals.lite.billing');
    Route::get('/portals/lite/devices',                                 [\App\Http\Controllers\MedicalId\LitePortalController::class, 'devices'])->name('portals.lite.devices');
    Route::post('/portals/lite/devices/{device}/activate',              [\App\Http\Controllers\MedicalId\LitePortalController::class, 'activateDevice'])->name('portals.lite.devices.activate');
    Route::post('/portals/lite/devices/{device}/revoke',                [\App\Http\Controllers\MedicalId\LitePortalController::class, 'revokeDevice'])->name('portals.lite.devices.revoke');
    Route::get('/portals/lite/conflicts',                               [\App\Http\Controllers\MedicalId\LitePortalController::class, 'conflicts'])->name('portals.lite.conflicts');
    Route::post('/portals/lite/conflicts/{conflict}/resolve',           [\App\Http\Controllers\MedicalId\LitePortalController::class, 'resolveConflict'])->name('portals.lite.conflicts.resolve');
    Route::get('/portals/lite/devices/{device}/offline-events',         [\App\Http\Controllers\MedicalId\LitePortalController::class, 'offlineEvents'])->name('portals.lite.offline_events');
});


/*
|--------------------------------------------------------------------------
| Developer Portal — External Developer Self-Service
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'portal.access'])->group(function () {
    Route::get('/portals/developer',                                          [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'dashboard'])->name('portals.developer.dashboard');
    Route::get('/portals/developer/onboard',                                  [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'onboard'])->name('portals.developer.onboard');
    Route::post('/portals/developer/onboard',                                 [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'onboardStore'])->name('portals.developer.onboard.store');

    // Apps (Integration Clients)
    Route::get('/portals/developer/apps',                                     [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'apps'])->name('portals.developer.apps');
    Route::get('/portals/developer/apps/create',                              [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'createApp'])->name('portals.developer.apps.create');
    Route::post('/portals/developer/apps',                                    [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'storeApp'])->name('portals.developer.apps.store');
    Route::get('/portals/developer/apps/{clientId}',                          [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'showApp'])->name('portals.developer.apps.show');
    Route::post('/portals/developer/apps/{clientId}/rotate-secret',           [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'rotateSecret'])->name('portals.developer.apps.rotate');
    Route::post('/portals/developer/apps/{clientId}/pay-invoice',             [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'payInvoice'])->name('portals.developer.apps.pay');
    Route::post('/portals/developer/apps/{clientId}/toggle',                  [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'toggleApp'])->name('portals.developer.apps.toggle');

    // Production Access Requests
    Route::get('/portals/developer/production-requests',                      [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'productionRequests'])->name('portals.developer.production_requests');
    Route::get('/portals/developer/production-requests/create',               [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'createProductionRequest'])->name('portals.developer.production_requests.create');
    Route::post('/portals/developer/production-requests',                     [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'storeProductionRequest'])->name('portals.developer.production_requests.store');

    // Webhook Delivery Logs
    Route::get('/portals/developer/apps/{clientId}/webhook-deliveries',       [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'webhookDeliveries'])->name('portals.developer.webhook_deliveries');

    // API Usage Analytics
    Route::get('/portals/developer/analytics',                                [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'analytics'])->name('portals.developer.analytics');
});


/*
|--------------------------------------------------------------------------
| OpesCare Legal Centre — Public & Admin Routes
|--------------------------------------------------------------------------
*/
// Public legal centre
Route::get('/legal',                                                    [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'publicIndex'])->name('public.legal');
Route::get('/legal/{slug}',                                             [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'publicShow'])->name('public.legal.show');

// Admin legal document management
Route::middleware(['web', 'auth', 'portal.access'])->group(function () {
    Route::get('/portals/admin/legal',                                      [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'index'])->name('portals.admin.legal');
    Route::post('/portals/admin/legal',                                     [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'store'])->name('portals.admin.legal.store');
    // Patient rights — account closures
    Route::get('/portals/admin/legal/patient-rights/closures',              [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'closureRequests'])->name('portals.admin.legal.closures');
    Route::post('/portals/admin/legal/patient-rights/closures/{closure}/review', [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'reviewClosure'])->name('portals.admin.legal.closures.review');

    // Privacy complaints
    Route::get('/portals/admin/legal/privacy-complaints',                   [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'privacyComplaints'])->name('portals.admin.legal.complaints');
    Route::post('/portals/admin/legal/privacy-complaints/{complaint}/resolve', [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'resolveComplaint'])->name('portals.admin.legal.complaints.resolve');

    // Minor transitions
    Route::get('/portals/admin/legal/minor-transitions',                    [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'minorTransitions'])->name('portals.admin.legal.minor_transitions');

    // Wildcard document routes LAST — otherwise '{document}' shadows the static
    // paths above (e.g. /legal/privacy-complaints would 404 as a missing document).
    Route::get('/portals/admin/legal/{document}',                           [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'show'])->name('portals.admin.legal.show');
    Route::post('/portals/admin/legal/{document}/versions',                 [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'publishVersion'])->name('portals.admin.legal.publish_version');
});

// --------------------------------------------------
// Facility Onboarding & Go-Live Portal
// --------------------------------------------------
Route::middleware(['web', 'auth', 'portal.access'])->group(function () {
    // Integration Certifications
    Route::get('/portals/admin/certifications',                                   [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'index'])->name('portals.admin.certifications.index');
    Route::get('/portals/admin/certifications/create',                            [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'create'])->name('portals.admin.certifications.create');
    Route::post('/portals/admin/certifications',                                  [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'store'])->name('portals.admin.certifications.store');
    Route::get('/portals/admin/certifications/{certification}',                   [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'show'])->name('portals.admin.certifications.show');
    Route::post('/portals/admin/certifications/{certification}/test-run',         [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'recordTestRun'])->name('portals.admin.certifications.test_run');
    Route::post('/portals/admin/certifications/{certification}/badge/issue',      [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'issueBadge'])->name('portals.admin.certifications.badge.issue');
    Route::post('/portals/admin/certifications/badges/{badge}/revoke',            [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'revokeBadge'])->name('portals.admin.certifications.badge.revoke');
    Route::post('/portals/admin/certifications/seed-requirements',                [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'seedRequirements'])->name('portals.admin.certifications.seed');
    // --------------------------------------------------
    // Code System Mappings (LOINC/ICD-10/ATC)
    Route::get('/portals/admin/code-mappings',                               [\App\Http\Controllers\MedicalId\CodeSystemMappingController::class, 'index'])->name('portals.admin.code_mappings.index');
    Route::get('/portals/admin/code-mappings/create',                        [\App\Http\Controllers\MedicalId\CodeSystemMappingController::class, 'create'])->name('portals.admin.code_mappings.create');
    Route::post('/portals/admin/code-mappings',                              [\App\Http\Controllers\MedicalId\CodeSystemMappingController::class, 'store'])->name('portals.admin.code_mappings.store');
    Route::post('/portals/admin/code-mappings/{mapping}/approve',            [\App\Http\Controllers\MedicalId\CodeSystemMappingController::class, 'approve'])->name('portals.admin.code_mappings.approve');
    Route::post('/portals/admin/code-mappings/{mapping}/reject',             [\App\Http\Controllers\MedicalId\CodeSystemMappingController::class, 'reject'])->name('portals.admin.code_mappings.reject');
    Route::delete('/portals/admin/code-mappings/{mapping}',                  [\App\Http\Controllers\MedicalId\CodeSystemMappingController::class, 'destroy'])->name('portals.admin.code_mappings.destroy');
    // --------------------------------------------------
    // KPI Dashboard
    Route::get('/portals/admin/kpi',                                         [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'index'])->name('portals.admin.kpi.index');
    Route::get('/portals/admin/kpi/trend',                                   [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'trend'])->name('portals.admin.kpi.trend');
    Route::post('/portals/admin/kpi/export',                                 [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'requestExport'])->name('portals.admin.kpi.export');
    Route::post('/portals/admin/kpi/recompute',                              [\App\Http\Controllers\MedicalId\KpiDashboardController::class, 'recompute'])->name('portals.admin.kpi.recompute');
    // --------------------------------------------------
    Route::get('/portals/admin/onboarding',                                  [\App\Http\Controllers\MedicalId\OnboardingPortalController::class, 'index'])->name('portals.admin.onboarding');
    Route::get('/portals/admin/onboarding/{facility}',                       [\App\Http\Controllers\MedicalId\OnboardingPortalController::class, 'show'])->name('portals.admin.onboarding.show');
    Route::post('/portals/admin/onboarding/{facility}/mark',                 [\App\Http\Controllers\MedicalId\OnboardingPortalController::class, 'markItem'])->name('portals.admin.onboarding.mark');
    Route::post('/portals/admin/onboarding/{facility}/approve',              [\App\Http\Controllers\MedicalId\OnboardingPortalController::class, 'approve'])->name('portals.admin.onboarding.approve');
});

// ── God-Mode Admin — Users, Facilities, Patients, Staff, Organizations, Roles ─
// platform.admin restricts these cross-facility god-mode routes to the platform
// role tier (a facility admin must NOT manage all users/facilities/patients).
Route::middleware(['web', 'auth', 'mfa.verified', 'platform.admin'])->group(function () {
    // Users
    Route::get('/admin/users',                                  [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{id}',                             [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'show'])->name('admin.users.show');
    Route::post('/admin/users',                                 [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{id}',                             [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'update'])->name('admin.users.update');
    Route::post('/admin/users/{id}/suspend',                    [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'suspend'])->name('admin.users.suspend');
    Route::post('/admin/users/{id}/activate',                   [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'activate'])->name('admin.users.activate');
    Route::post('/admin/users/{id}/reset-password',             [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'resetPassword'])->name('admin.users.reset_password');
    Route::delete('/admin/users/{id}',                          [\App\Http\Controllers\MedicalId\AdminUserManagementController::class, 'destroy'])->name('admin.users.destroy');
    // Facilities
    Route::get('/admin/facilities',                             [\App\Http\Controllers\MedicalId\AdminFacilityManagementController::class, 'index'])->name('admin.facilities.index');
    Route::get('/admin/facilities/{id}',                        [\App\Http\Controllers\MedicalId\AdminFacilityManagementController::class, 'show'])->name('admin.facilities.show');
    Route::post('/admin/facilities',                            [\App\Http\Controllers\MedicalId\AdminFacilityManagementController::class, 'store'])->name('admin.facilities.store');
    Route::put('/admin/facilities/{id}',                        [\App\Http\Controllers\MedicalId\AdminFacilityManagementController::class, 'update'])->name('admin.facilities.update');
    Route::post('/admin/facilities/{id}/suspend',               [\App\Http\Controllers\MedicalId\AdminFacilityManagementController::class, 'suspend'])->name('admin.facilities.suspend');
    Route::post('/admin/facilities/{id}/activate',              [\App\Http\Controllers\MedicalId\AdminFacilityManagementController::class, 'approve'])->name('admin.facilities.approve');
    Route::delete('/admin/facilities/{id}',                     [\App\Http\Controllers\MedicalId\AdminFacilityManagementController::class, 'destroy'])->name('admin.facilities.destroy');
    // Patients
    Route::get('/admin/patients',                               [\App\Http\Controllers\MedicalId\AdminPatientManagementController::class, 'index'])->name('admin.patients.index');
    Route::get('/admin/patients/{id}',                          [\App\Http\Controllers\MedicalId\AdminPatientManagementController::class, 'show'])->name('admin.patients.show');
    Route::put('/admin/patients/{id}',                          [\App\Http\Controllers\MedicalId\AdminPatientManagementController::class, 'update'])->name('admin.patients.update');
    Route::post('/admin/patients/{id}/activate',                [\App\Http\Controllers\MedicalId\AdminPatientManagementController::class, 'activate'])->name('admin.patients.activate');
    Route::post('/admin/patients/{id}/suspend',                 [\App\Http\Controllers\MedicalId\AdminPatientManagementController::class, 'suspend'])->name('admin.patients.suspend');
    Route::delete('/admin/patients/{id}',                       [\App\Http\Controllers\MedicalId\AdminPatientManagementController::class, 'destroy'])->name('admin.patients.destroy');
    // Staff
    Route::get('/admin/staff',                                  [\App\Http\Controllers\MedicalId\AdminStaffManagementController::class, 'index'])->name('admin.staff.index');
    Route::get('/admin/staff/{id}',                             [\App\Http\Controllers\MedicalId\AdminStaffManagementController::class, 'show'])->name('admin.staff.show');
    Route::post('/admin/staff/{id}/suspend',                    [\App\Http\Controllers\MedicalId\AdminStaffManagementController::class, 'suspend'])->name('admin.staff.suspend');
    Route::post('/admin/staff/{id}/activate',                   [\App\Http\Controllers\MedicalId\AdminStaffManagementController::class, 'activate'])->name('admin.staff.activate');
    // Organizations
    Route::get('/admin/organizations',                          [\App\Http\Controllers\MedicalId\AdminOrganizationsController::class, 'index'])->name('admin.organizations.index');
    Route::post('/admin/organizations/{id}/approve',            [\App\Http\Controllers\MedicalId\AdminOrganizationsController::class, 'approve'])->name('admin.organizations.approve');
    Route::post('/admin/organizations/{id}/reject',             [\App\Http\Controllers\MedicalId\AdminOrganizationsController::class, 'reject'])->name('admin.organizations.reject');
    Route::delete('/admin/organizations/{id}',                  [\App\Http\Controllers\MedicalId\AdminOrganizationsController::class, 'destroy'])->name('admin.organizations.destroy');
    // Roles
    Route::get('/admin/roles',                                  [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'index'])->name('admin.roles.index');
    Route::post('/admin/roles',                                 [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'store'])->name('admin.roles.store');
    Route::put('/admin/roles/{id}',                             [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'update'])->name('admin.roles.update');
    Route::delete('/admin/roles/{id}',                          [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'destroy'])->name('admin.roles.destroy');
    Route::get('/admin/roles/{id}/users',                       [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'users'])->name('admin.roles.users');
});

// ── Document Template Preview Gallery (gated) ──────────────────────────────
// Renders document templates filled with sample data. Public only when demo
// mode is explicitly enabled (for sales walkthroughs); otherwise login-gated so
// template mockups are never browsable by anonymous visitors in production.
Route::prefix('document-preview')->name('document.preview.')
    ->middleware(config('demo.enabled') ? ['web'] : ['web', 'auth'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\DocumentPreviewController::class, 'index'])->name('gallery');
        Route::get('/{type}', [\App\Http\Controllers\DocumentPreviewController::class, 'show'])->name('show');
    });

// ── Document Render — Category B (Living Clinical Forms) ─────────────────
Route::get('/patients/{patientId}/clinical-forms/{type}', [\App\Http\Controllers\DocumentRenderController::class, 'clinicalForm'])
    ->name('documents.clinical-form')
    ->where('type', '[a-z-]+');

// ── Document Render — Category C (On-Demand Reports) ─────────────────────
Route::get('/patients/{patientId}/documents/{type}/render', [\App\Http\Controllers\DocumentRenderController::class, 'onDemand'])
    ->name('documents.on-demand')
    ->where('type', '[a-z-]+');

// Public — no auth required (GET lets unauthenticated patients view the invite page)
Route::get('/family/invite/accept/{token}',  [\App\Http\Controllers\MedicalId\FamilyController::class, 'acceptInvite'])->name('portals.patient.family.invite.accept');
// POST requires auth — patient must be logged in to confirm acceptance
Route::post('/family/invite/accept/{token}', [\App\Http\Controllers\MedicalId\FamilyController::class, 'confirmInvite'])->middleware('auth')->name('portals.patient.family.invite.confirm');
