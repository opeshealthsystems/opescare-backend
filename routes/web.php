<?php

// OpesCare Finder extraction — surface reduced from the platform on 2026-09-02;
// the platform's sealed api.php remains authoritative for the platform.
//
// Kept: public pages (landing/marketing/SEO/legal), auth (login/register/
// password/2FA/OTP), care-map + facility claim/listing, patient portal
// (booking + profile/messaging/family/notifications), staff portal
// (appointments, messaging, team, blood inventory, prescribing), pharmacy
// portal, admin core (users, roles, control center, directory review,
// facility mgmt, connect clients), select-facility, invite, status.
// Deleted with their modules: clinical/visits/queue/HR/wards/supply/files/
// data-import portals, insurance/lab/lite/healthorg/developer portals,
// subscription/security/broadcast/task/support admin, documents rendering.

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\MedicalId\AdminUserManagementController;
use App\Http\Controllers\MedicalId\AdminFacilityManagementController;
use App\Http\Controllers\MedicalId\AdminAppointmentsController;
use App\Http\Controllers\MedicalId\AdminRolesController;

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
// RFC 7517 JWKS + RFC 8414 server metadata, so partners can verify Connect
// RS256 tokens used by the inventory stock ingest.
Route::get('/.well-known/jwks.json', [\App\Http\Controllers\WellKnownController::class, 'jwks'])
    ->name('well-known.jwks');
Route::get('/.well-known/oauth-authorization-server', [\App\Http\Controllers\WellKnownController::class, 'authorizationServerMetadata'])
    ->name('well-known.oauth-metadata');

// ── Machine-facing discovery: robots, sitemap, llms.txt ─────────────────────
Route::get('/robots.txt',  [\App\Http\Controllers\SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/llms.txt',    [\App\Http\Controllers\SeoController::class, 'llms'])->name('seo.llms');
// /sitemap.xml is an index; the real urlsets live in these two children.
// Facility profiles are chunked (<=2,000 URLs per file - see config/finder-seo.php).
Route::get('/sitemap-pages.xml', [\App\Http\Controllers\SeoController::class, 'sitemapPages'])->name('seo.sitemap.pages');
Route::get('/sitemap-facilities-{page}.xml', [\App\Http\Controllers\SeoController::class, 'sitemapFacilities'])
    ->whereNumber('page')->name('seo.sitemap.facilities');

// Root / Landing
Route::get('/', [PublicPageController::class, 'index'])->name('public.landing');

// Network services — the patient-facing discovery layer.
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

// Professional Self-Registration (doctor / nurse / pharmacist / …) — creates a
// REAL but unverified account; the qualifications entered are reviewed by an
// admin before the verified badge appears. The complementary facility-invited
// path is the /invite/{token} activation below.
Route::get('/signup/professional', [\App\Http\Controllers\MedicalId\ProfessionalRegistrationController::class, 'create'])->middleware('guest')->name('register.professional');
Route::post('/signup/professional', [\App\Http\Controllers\MedicalId\ProfessionalRegistrationController::class, 'store'])->middleware(['guest', 'throttle:signup'])->name('register.professional.submit');
Route::get('/register/professional', function () { return redirect()->route('register.professional'); });

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
// so a successful guess is an account takeover, and the GET is an oracle too.
Route::get('/reset-password/{token}', [PublicPageController::class, 'showResetPassword'])->middleware('throttle:password-reset-token')->name('password.reset');
Route::post('/reset-password/{token}', [PublicPageController::class, 'submitResetPassword'])->middleware('throttle:password-reset-token')->name('password.update');

// OTP Screen Challenge
Route::get('/verify/otp', [PublicPageController::class, 'showVerifyOtp'])->name('otp.verify');
Route::post('/verify/otp', [PublicPageController::class, 'submitVerifyOtp'])->middleware('throttle:auth-code')->name('otp.verify.submit');
Route::post('/verify/otp/resend', [PublicPageController::class, 'resendOtp'])->middleware('throttle:auth-code')->name('otp.resend');

// Verification & Restriction Status Displays
// Embeddable map page for the mobile app (iframe on web, WebView on native).
// Driven by ?markers=<json> or ?lat=&lng=&name=; same-origin so no CORS.
Route::get('/embed/map', function () { return view('embed.map'); })->name('embed.map');

Route::middleware(['web', 'auth'])->get('/portal-unavailable', [PublicPageController::class, 'showPortalUnavailable'])->name('portal.unavailable');
Route::get('/pending-approval', [PublicPageController::class, 'showPendingApproval'])->name('account.pending');
Route::get('/account-suspended', [PublicPageController::class, 'showAccountSuspended'])->name('account.suspended');

// Multi-Facility Access Plane Selector
Route::get('/select-facility', [PublicPageController::class, 'showSelectFacility'])->name('select-facility');
Route::post('/select-facility', [PublicPageController::class, 'submitSelectFacility'])->name('select-facility.submit');

// Secure Portal Access Override
Route::get('/login', [PublicPageController::class, 'showLogin'])->name('login');
// Rate-limit the web login to blunt credential brute-force / password spraying.
Route::post('/login', [PublicPageController::class, 'submitLogin'])->middleware('throttle:5,1')->name('login.submit');
// OPESCARD: a scanned card QR fills the identifier box only — never a credential.
Route::post('/login/opescard/resolve', [\App\Http\Controllers\Auth\OpescardController::class, 'resolve'])
    ->middleware(['guest', 'throttle:10,1'])->name('login.opescard.resolve');
Route::get('/mfa/challenge', [PublicPageController::class, 'showMfaChallenge'])->name('mfa.challenge');
Route::post('/mfa/challenge', [PublicPageController::class, 'submitMfaChallenge'])->middleware('throttle:auth-code')->name('mfa.challenge.submit');

// ── Patient mobile app (Expo web build) ─────────────────────────────────────
// The app is exported as a single-page bundle into public/app; only paths that
// do NOT exist on disk fall through to here — the client-side routes
// expo-router owns. Without this, deep links inside the app would 404.
Route::get('/app/{path?}', function (?string $path = null) {
    $index = public_path('app/index.html');

    abort_unless(is_file($index), 404);

    return response()->file($index, [
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

// Public Medical ID Verification Routes — the landing for the temporary QR a
// patient generates from the portal or mobile app.
Route::middleware(['web', 'throttle:verify'])->group(function () {
    Route::get('/verify/health-id', [\App\Http\Controllers\MedicalId\VerifyController::class, 'healthId'])->name('verify.health-id');
    Route::post('/verify/health-id', [\App\Http\Controllers\MedicalId\VerifyController::class, 'healthIdLookup'])->name('verify.health-id.lookup');
    Route::get('/verify/qr/{token}', [\App\Http\Controllers\MedicalId\VerifyController::class, 'qr'])->name('verify.qr');
    Route::get('/verify/rx/{token}', [\App\Http\Controllers\MedicalId\PrescriptionVerifyController::class, 'rx'])->name('verify.rx');
    Route::get('/verify/doc/{token}', [\App\Http\Controllers\MedicalId\DocumentVerifyController::class, 'show'])->name('verify.document');
});

// ── Patient profile completion ──────────────────────────────────────────────
// Sign-up stops at email + password; this is where identity is collected.
// Deliberately OUTSIDE the patient.profile gate below, or the gate would
// redirect to itself forever.
Route::middleware(['web', 'auth', 'mfa.verified', 'throttle:portal'])->group(function () {
    Route::get('/patient/complete-profile',  [\App\Http\Controllers\MedicalId\PatientProfileCompletionController::class, 'show'])->name('portals.patient.complete-profile');
    Route::post('/patient/complete-profile', [\App\Http\Controllers\MedicalId\PatientProfileCompletionController::class, 'store'])->name('portals.patient.complete-profile.store');
});

// ── Caregiver profile completion ────────────────────────────────────────────
Route::middleware(['web', 'auth', 'mfa.verified', 'throttle:portal'])->group(function () {
    Route::get('/portals/guardian/complete-profile',  [\App\Http\Controllers\MedicalId\GuardianProfileCompletionController::class, 'show'])->name('portals.guardian.complete-profile');
    Route::post('/portals/guardian/complete-profile', [\App\Http\Controllers\MedicalId\GuardianProfileCompletionController::class, 'store'])->name('portals.guardian.complete-profile.store');
    Route::get('/portals/guardian/pending',           [\App\Http\Controllers\MedicalId\GuardianProfileCompletionController::class, 'pending'])->name('portals.guardian.pending');
});

// Portal Routes — require authentication, correct portal for role, and facility context
// Professional self-service profile + qualifications. Intentionally NOT behind
// facility.context: a self-registered professional is not attached to a facility
// until an admin verifies them, but must still complete their profile here.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/professional', function () { return redirect()->route('professional.profile'); });
    Route::get('/professional/profile', [\App\Http\Controllers\MedicalId\ProfessionalProfileController::class, 'profile'])->name('professional.profile');
    Route::post('/professional/profile', [\App\Http\Controllers\MedicalId\ProfessionalProfileController::class, 'updateProfile'])->name('professional.profile.update');
    Route::post('/professional/qualifications', [\App\Http\Controllers\MedicalId\ProfessionalProfileController::class, 'storeQualification'])->name('professional.qualifications.store');
    Route::delete('/professional/qualifications/{id}', [\App\Http\Controllers\MedicalId\ProfessionalProfileController::class, 'destroyQualification'])->name('professional.qualifications.destroy');

    // Ask to join a facility's team. Outside facility.context for the same
    // reason as the routes above: the professionals who need this page are
    // exactly the ones with no facility yet. The facility's half of the
    // conversation — approve / decline — lives on /facility/team.
    Route::get('/professional/facilities',                [\App\Http\Controllers\MedicalId\ProfessionalJoinRequestController::class, 'index'])->name('professional.join');
    Route::post('/professional/facilities',               [\App\Http\Controllers\MedicalId\ProfessionalJoinRequestController::class, 'store'])->middleware('throttle:portal')->name('professional.join.store');
    Route::post('/professional/facilities/{id}/withdraw', [\App\Http\Controllers\MedicalId\ProfessionalJoinRequestController::class, 'withdraw'])->middleware('throttle:portal')->name('professional.join.withdraw');
});

// Platform-wide notification centre — every authenticated user reads their own
// notification_events. /feed is polled by the topbar bell (unread + latest);
// /read stamps notifications_seen_at.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\MedicalId\NotificationCenterController::class, 'index'])->name('notifications.center');
    Route::get('/notifications/feed', [\App\Http\Controllers\MedicalId\NotificationCenterController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/read', [\App\Http\Controllers\MedicalId\NotificationCenterController::class, 'markRead'])->name('notifications.read');
});

Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'patient.profile', 'guardian.profile', 'throttle:portal'])->group(function () {
    Route::get('/patient', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'index'])->name('portals.patient');
    // QR generation has its own tighter rate limit (10/min) on top of the portal limit
    Route::post('/patient/generate-qr', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'generateTemporaryQr'])
        ->middleware('throttle:portal.qr')
        ->name('portals.patient.qr');

    // /facility itself is deliberately NOT behind a capability: it is the
    // landing page for every facility role, including billing and data-quality
    // roles that hold no mapped web surface yet. The Health-ID lookup on it is
    // gated in the view (@capability('identify_patient')).
    Route::get('/facility', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'index'])->name('portals.staff');
    // Global search — facilities, documents, medicines, lab tests, partners and
    // the caller's own messages. NEVER patients: the patient branch of
    // GlobalSearchService was deleted (it was a name-prefix LIKE across the
    // register, one flag away from live), and a query-log test holds that no
    // search touches `patients`. The screen and its 133-line view existed,
    // finished, with no route at all; the dashboard audit found it that way.
    // No capability beyond the portal: nothing it returns is PHI, and the
    // facility scope comes from the session context like every other desk.
    Route::get('/facility/search', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'search'])->name('portals.staff.search');
    // The appointment book: front desk runs it, clinicians see their schedule.
    // `capability:` runs AFTER portal.access + facility.context in this stack,
    // so it judges the role at the facility the session has just resolved.
    Route::get('/facility/appointments', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointments'])
        ->middleware('capability:front_desk,schedule')
        ->name('portals.staff.appointments');

    Route::middleware(['guardian.context'])->group(function () {
        Route::get('/patient/appointments', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'appointments'])->name('portals.patient.appointments');
        // Booking + reschedule (book must precede the {id} routes)
        Route::get('/patient/appointments/book', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'bookAppointmentForm'])->name('portals.patient.appointments.book');
        Route::post('/patient/appointments/book', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'bookAppointment'])->name('portals.patient.appointments.book.store');
        Route::get('/patient/appointments/{id}/reschedule', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'rescheduleAppointmentForm'])->name('portals.patient.appointments.reschedule');
        Route::post('/patient/appointments/{id}/reschedule', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'rescheduleAppointment'])->name('portals.patient.appointments.reschedule.store');
        Route::post('/patient/appointments/{id}/cancel', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'cancelAppointment'])->name('portals.patient.appointments.cancel');
        // Waitlist: a patient asks to be told when a slot frees up at a facility.
        Route::get('/patient/waitlist', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'waitlist'])->name('portals.patient.waitlist');
        Route::post('/patient/waitlist', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'joinWaitlist'])->name('portals.patient.waitlist.join');
        Route::post('/patient/waitlist/{id}/cancel', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'cancelWaitlist'])->name('portals.patient.waitlist.cancel');
        Route::get('/patient/prescriptions', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'prescriptions'])->name('portals.patient.prescriptions');
        Route::post('/patient/prescriptions/{id}/refill', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'requestRefill'])->name('portals.patient.prescriptions.refill');
        // QR token management
        Route::post('/patient/qr/{tokenId}/revoke', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'revokeQrToken'])->name('portals.patient.qr.revoke');
        Route::post('/patient/qr/revoke-all', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'revokeAllQrTokens'])->name('portals.patient.qr.revoke-all');
        Route::get('/patient/profile', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'profile'])->name('portals.patient.profile');
        Route::post('/patient/profile', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'updateProfile'])->name('portals.patient.profile.update');
        Route::get('/patient/settings',   [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'settings'])->name('portals.patient.settings');
        Route::post('/patient/settings',  [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'updateSettings'])->name('portals.patient.settings.update');
        // Both controller methods existed for months with no route (D-B3).
        // Referrals are read-only for the patient; the lost-card report is a
        // POST from Settings that revokes every active QR token.
        Route::get('/patient/referrals',  [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'referrals'])->name('portals.patient.referrals');
        Route::post('/patient/lost-card', [\App\Http\Controllers\MedicalId\PatientPortalController::class, 'reportLostCard'])->name('portals.patient.lost-card');

        // Notifications centre + channel preferences
        Route::get('/patient/notifications', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'index'])->name('portals.patient.notifications');
        Route::post('/patient/notifications/read', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'markAllRead'])->name('portals.patient.notifications.read');
        Route::get('/patient/notifications/preferences', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'preferences'])->name('portals.patient.notifications.preferences');
        Route::post('/patient/notifications/preferences', [\App\Http\Controllers\MedicalId\PatientNotificationController::class, 'updatePreferences'])->name('portals.patient.notifications.preferences.update');
        // Secure messaging (patient <-> care team) — built on the Messaging module
        Route::get('/patient/messages', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'index'])->name('portals.patient.messages');
        // Compose a new thread (must precede the {thread} wildcard)
        Route::get('/patient/messages/compose', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'composeForm'])->name('portals.patient.messages.compose');
        Route::post('/patient/messages', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'store'])->name('portals.patient.messages.store');
        Route::get('/patient/messages/{thread}', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'show'])->name('portals.patient.messages.show');
        Route::post('/patient/messages/{thread}/messages', [\App\Http\Controllers\MedicalId\PatientMessagingController::class, 'send'])->name('portals.patient.messages.send');
    });

    // --- Inventory Portal (blood only — the facility side of the Blood Finder) ---
    // blood_inventory: clinicians, front desk and the lab — not the pharmacy.
    Route::middleware('capability:blood_inventory')->group(function () {
        Route::get('/facility/inventory/blood', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'blood'])->name('portals.staff.inventory.blood');
        Route::post('/facility/inventory/blood', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'bloodUpsert'])->name('portals.staff.inventory.blood.upsert');
        Route::post('/facility/inventory/blood/{id}/adjust', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'bloodAdjust'])->name('portals.staff.inventory.blood.adjust');
        Route::post('/facility/inventory/blood/{id}/flag', [\App\Http\Controllers\MedicalId\InventoryPortalController::class, 'bloodFlag'])->name('portals.staff.inventory.blood.flag');
    });

    // ── Staff: Appointment actions ──────────────────────────────
    // Same gate as the book itself (front_desk OR schedule): the desk confirms,
    // checks in and books; a clinician confirms the bookings on their own list
    // (CareJourneyTest pins a doctor doing exactly that). Pharmacy and lab roles
    // hold neither and are refused with 403 before the controller runs.
    Route::middleware('capability:front_desk,schedule')->group(function () {
        Route::get('/facility/appointments/create', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsCreate'])->name('portals.staff.appointments.create');
        // The facility's waitlist (waiting + notified), urgent first.
        Route::get('/facility/appointments/waitlist', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsWaitlist'])->name('portals.staff.appointments.waitlist');
        Route::post('/facility/appointments', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsStore'])->name('portals.staff.appointments.store');
        Route::post('/facility/appointments/{id}/confirm', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsConfirm'])->name('portals.staff.appointments.confirm');
        Route::post('/facility/appointments/{id}/cancel', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsCancel'])->name('portals.staff.appointments.cancel');
        Route::post('/facility/appointments/{id}/check-in', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsCheckIn'])->name('portals.staff.appointments.check-in');
        Route::post('/facility/appointments/{id}/no-show', [\App\Http\Controllers\MedicalId\StaffPortalController::class, 'appointmentsNoShow'])->name('portals.staff.appointments.no-show');
    });

    // ── Staff: patient message inbox ─────────────────────────────
    Route::middleware('capability:messaging')->group(function () {
        Route::get('/facility/messages',                 [\App\Http\Controllers\MedicalId\StaffMessagingController::class, 'index'])->name('portals.staff.messages');
        Route::get('/facility/messages/{thread}',        [\App\Http\Controllers\MedicalId\StaffMessagingController::class, 'show'])->name('portals.staff.messages.show');
        Route::post('/facility/messages/{thread}/reply', [\App\Http\Controllers\MedicalId\StaffMessagingController::class, 'send'])->name('portals.staff.messages.send');
    });

    // ── Facility admin: my team (invite / reissue / revoke) ───────
    // Prefix is facility, NOT admin: everything under
    // admin/* is platform-tier only (RequirePlatformAdmin).
    // portal.access already redirects non-admin roles off /facility/team;
    // capability:facility_team is defence in depth from the one map.
    Route::middleware('capability:facility_team')->group(function () {
        Route::get('/facility/team',                            [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'index'])->name('portals.facility.team');
        Route::post('/facility/team/invites',                   [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'store'])->name('portals.facility.team.invite');
        Route::post('/facility/team/invites/{id}/reissue',      [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'reissue'])->name('portals.facility.team.invite.reissue');
        Route::post('/facility/team/invites/{id}/revoke',       [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'revoke'])->name('portals.facility.team.invite.revoke');

        // Join requests: the other direction — a clinician asked to be added, the
        // facility decides. The role is chosen HERE, by the approver, never by the
        // requester; the facility comes from the session, never from the body.
        Route::post('/facility/team/join-requests/{id}/approve', [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'approveJoinRequest'])->name('portals.facility.team.join.approve');
        Route::post('/facility/team/join-requests/{id}/decline', [\App\Http\Controllers\MedicalId\FacilityStaffController::class, 'declineJoinRequest'])->name('portals.facility.team.join.decline');
    });

    // ── Facility service catalogue: "what we offer" ──────────────────────
    // The facility picks, from the institutional code dictionary, the tests,
    // procedures and services it actually provides, and states its own local
    // name, price (whole francs) and turnaround for each.
    //
    // ROUTE ORDER: /facility/catalogue/browse is a LITERAL segment and must be
    // declared before the {id} routes below it, or `browse` is swallowed as an
    // item id. (Those routes are ->whereUuid as well, which makes it safe
    // twice over; the declaration order is still the rule.)
    //
    // The facility comes from the session context this middleware stack has
    // already resolved — never from the request. There is no facility_id field
    // on any of these forms to tamper with.
    //
    //   php artisan route:list --path=facility/catalogue
    Route::middleware('capability:service_catalogue')->group(function () {
        Route::get('/facility/catalogue',               [\App\Http\Controllers\MedicalId\FacilityCatalogueController::class, 'index'])->name('portals.facility.catalogue');
        Route::get('/facility/catalogue/browse',        [\App\Http\Controllers\MedicalId\FacilityCatalogueController::class, 'browse'])->name('portals.facility.catalogue.browse');
        Route::post('/facility/catalogue',              [\App\Http\Controllers\MedicalId\FacilityCatalogueController::class, 'store'])->name('portals.facility.catalogue.store');
        Route::post('/facility/catalogue/{id}',         [\App\Http\Controllers\MedicalId\FacilityCatalogueController::class, 'update'])->whereUuid('id')->name('portals.facility.catalogue.update');
        Route::post('/facility/catalogue/{id}/retire',  [\App\Http\Controllers\MedicalId\FacilityCatalogueController::class, 'deactivate'])->whereUuid('id')->name('portals.facility.catalogue.retire');
        Route::post('/facility/catalogue/{id}/restore', [\App\Http\Controllers\MedicalId\FacilityCatalogueController::class, 'reactivate'])->whereUuid('id')->name('portals.facility.catalogue.restore');
    });


    // ── Family Management ──────────────────────────────────────────────────────
    Route::get('/patient/family',                           [\App\Http\Controllers\MedicalId\FamilyController::class, 'index'])->name('portals.patient.family');
    Route::get('/patient/family/add',                       [\App\Http\Controllers\MedicalId\FamilyController::class, 'addForm'])->name('portals.patient.family.add');
    Route::post('/patient/family/add',                      [\App\Http\Controllers\MedicalId\FamilyController::class, 'store'])->name('portals.patient.family.store');
    Route::get('/patient/family/invite',                    [\App\Http\Controllers\MedicalId\FamilyController::class, 'inviteForm'])->name('portals.patient.family.invite');
    Route::post('/patient/family/invite',                   [\App\Http\Controllers\MedicalId\FamilyController::class, 'sendInvite'])->name('portals.patient.family.invite.send');
    Route::post('/patient/family/switch/{patientId}',       [\App\Http\Controllers\MedicalId\FamilyController::class, 'switchTo'])->name('portals.patient.family.switch');
    Route::post('/patient/family/switch-back',              [\App\Http\Controllers\MedicalId\FamilyController::class, 'switchBack'])->name('portals.patient.family.switch.back');
    Route::get('/patient/family/{id}/edit',                 [\App\Http\Controllers\MedicalId\FamilyController::class, 'editForm'])->name('portals.patient.family.edit');
    Route::post('/patient/family/{id}/edit',                [\App\Http\Controllers\MedicalId\FamilyController::class, 'update'])->name('portals.patient.family.update');
    Route::post('/patient/family/{id}/revoke',              [\App\Http\Controllers\MedicalId\FamilyController::class, 'revoke'])->name('portals.patient.family.revoke');
    Route::post('/patient/family/{id}/guardian-consent/approve', [\App\Http\Controllers\MedicalId\FamilyController::class, 'guardianConsentApprove'])->name('portals.patient.family.guardian_consent.approve');
    Route::post('/patient/family/{id}/guardian-consent/deny',    [\App\Http\Controllers\MedicalId\FamilyController::class, 'guardianConsentDeny'])->name('portals.patient.family.guardian_consent.deny');

    Route::get('/admin', [\App\Http\Controllers\MedicalId\AdminPortalController::class, 'index'])->name('portals.admin');

    // ── Master Admin Control Center ───────────────────────────────
    Route::get('/admin/cc',                      [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'index'])->name('portals.admin.cc');
    Route::get('/admin/cc/settings',             [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'settings'])->name('portals.admin.cc.settings');
    Route::post('/admin/cc/settings',            [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'settingsUpdate'])->name('portals.admin.cc.settings.update');
    Route::get('/admin/cc/feature-flags',        [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'featureFlags'])->name('portals.admin.cc.feature_flags');
    Route::post('/admin/cc/feature-flags/{key}', [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'featureFlagToggle'])->name('portals.admin.cc.feature_flags.toggle');
    Route::get('/admin/cc/modules',              [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'modules'])->name('portals.admin.cc.modules');
    Route::post('/admin/cc/modules/{key}',       [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'moduleToggle'])->name('portals.admin.cc.modules.toggle');
    Route::get('/admin/cc/maintenance',          [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'maintenance'])->name('portals.admin.cc.maintenance');
    Route::post('/admin/cc/maintenance',         [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'maintenanceStore'])->name('portals.admin.cc.maintenance.store');
    Route::post('/admin/cc/maintenance/{id}',    [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'maintenanceToggle'])->name('portals.admin.cc.maintenance.toggle');
    Route::get('/admin/cc/health',               [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'systemHealth'])->name('portals.admin.cc.health');
    // Read-only: backup status plus the steps to add off-host storage keys on
    // the server. Platform-only by RequirePlatformAdmin's /admin/* rule.
    Route::get('/admin/cc/backups',              [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'backups'])->name('portals.admin.cc.backups');
    Route::get('/admin/cc/audit',                [\App\Http\Controllers\MedicalId\AdminControlCenterController::class, 'auditLog'])->name('portals.admin.cc.audit');

    // Professional qualification verification queue (platform-admin).
    Route::get('/admin/verifications', [\App\Http\Controllers\MedicalId\CredentialVerificationController::class, 'index'])->name('portals.admin.verifications.index');
    Route::get('/admin/verifications/{id}', [\App\Http\Controllers\MedicalId\CredentialVerificationController::class, 'show'])->name('portals.admin.verifications.show');
    Route::post('/admin/verifications/{id}/verify', [\App\Http\Controllers\MedicalId\CredentialVerificationController::class, 'verify'])->name('portals.admin.verifications.verify');
    Route::post('/admin/verifications/{id}/reject', [\App\Http\Controllers\MedicalId\CredentialVerificationController::class, 'reject'])->name('portals.admin.verifications.reject');


    // ── User Management (god mode) ──────────────────────────────────────────
    // NOTE: users / facilities / roles keep the /portals/admin/* URI (they are
    // NOT shortened to /admin/*). A parallel god-mode set already lives at the
    // bare /admin/{users,facilities,roles} URIs (names admin.*), wired to the
    // same controllers and used by the CRUD views. Moving these here onto those
    // exact URIs would collide, and Laravel keeps only ONE name per method+URI —
    // the later registration would silently evict portals.admin.*.index, which
    // the admin nav links by. Leaving these three at /portals/admin/* lets BOTH
    // name families resolve, exactly as before the prefix rename.
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



    // ── Appointments Overview ───────────────────────────────────────────────
    Route::prefix('admin/appointments')->name('portals.admin.appointments.')->group(function () {
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

});

/*
|--------------------------------------------------------------------------
| OpesCare Verified Care Access Map Web Routes
|--------------------------------------------------------------------------
*/
Route::get('/care-map', [\App\Http\Controllers\Api\V1\CareMapController::class, 'publicDirectory'])->name('public.care-map');
Route::get('/care-map/facility/{id}', [\App\Http\Controllers\Api\V1\CareMapController::class, 'publicProfile'])->name('public.care-map.profile');
Route::get('/care-map/emergency', [\App\Http\Controllers\Api\V1\CareMapController::class, 'publicEmergency'])->name('public.care-map.emergency');
Route::middleware(['web', 'auth', 'platform.admin'])->get('/admin/care-map/governance', [\App\Http\Controllers\Api\V1\CareMapController::class, 'adminGovernance'])->name('admin.care-map.governance');

// ── Facility claim + self-service listing + directory review ────────────────
//
// Three audiences, three gates:
//  1. A facility representative claiming their listing (auth, listing id in
//     the URL — this is the ONLY place a facility id may come from a request).
//  2. That representative editing what they were approved for: no route below
//     carries a facility id — FacilityListingController resolves it from the
//     approved claim on the authenticated user and nowhere else.
//  3. A platform administrator deciding claims and import candidates. The
//     /admin/care-map prefix is in RequirePlatformAdmin's PLATFORM_ONLY_PREFIXES.

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

/*
 * Patient identity — the Master Patient Index review desk.
 *
 * The MPI had a service layer and no entry point: no route, no command, no
 * screen. Every merge is a person here choosing a survivor and writing why;
 * nothing merges on its own.
 */
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/mpi',                                   [\App\Http\Controllers\MedicalId\AdminIdentityReviewController::class, 'index'])->name('portals.admin.mpi.index');
    Route::post('/admin/mpi/detect',                           [\App\Http\Controllers\MedicalId\AdminIdentityReviewController::class, 'detect'])->name('portals.admin.mpi.detect');
    Route::post('/admin/mpi/candidates',                       [\App\Http\Controllers\MedicalId\AdminIdentityReviewController::class, 'raise'])->name('portals.admin.mpi.candidates.raise');
    Route::get('/admin/mpi/candidates/{candidate}',            [\App\Http\Controllers\MedicalId\AdminIdentityReviewController::class, 'show'])->name('portals.admin.mpi.candidates.show');
    Route::post('/admin/mpi/candidates/{candidate}/merge',     [\App\Http\Controllers\MedicalId\AdminIdentityReviewController::class, 'merge'])->name('portals.admin.mpi.candidates.merge');
    Route::post('/admin/mpi/candidates/{candidate}/reject',    [\App\Http\Controllers\MedicalId\AdminIdentityReviewController::class, 'reject'])->name('portals.admin.mpi.candidates.reject');
    Route::post('/admin/mpi/merges/{merge}/unmerge',           [\App\Http\Controllers\MedicalId\AdminIdentityReviewController::class, 'unmerge'])->name('portals.admin.mpi.merges.unmerge');
});

/*
 * Plans & pricing — what the platform charges.
 *
 * PlatformFeeSeeder establishes the plans and says in its own docblock that it
 * "does not own the prices"; this is the screen that does. Platform-tier only
 * (RequirePlatformAdmin treats everything under /admin/ as platform-only), and
 * every change here writes an admin_action_logs row, so the Control Center's
 * audit page answers "who changed this price, when, and from what" without a
 * second trail being invented for money.
 */
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/plans',                [\App\Http\Controllers\MedicalId\AdminPlanPricingController::class, 'index'])->name('portals.admin.plans.index');
    Route::post('/admin/plans/{plan}/prices', [\App\Http\Controllers\MedicalId\AdminPlanPricingController::class, 'updatePrices'])->name('portals.admin.plans.update');
    Route::post('/admin/plans/{plan}/toggle', [\App\Http\Controllers\MedicalId\AdminPlanPricingController::class, 'toggle'])->name('portals.admin.plans.toggle');
});

/*
 * Legal & compliance desk.
 *
 * LegalAdminController shipped with nine admin methods, five Blade views and
 * all 94 of its EN/FR strings — and no routes. `route:list --path=legal`
 * returned only the two PUBLIC pages, so every admin screen here
 * (closure requests, privacy complaints, minor transitions, the document
 * publisher) was unreachable: not URL-only, but with no URL at all. The
 * closures table even carried an empty `row-actions` cell where its
 * approve/reject buttons belonged.
 *
 * That is the wiring the fork dropped, not a feature being re-added — the
 * controller, the views and the translations were all already here.
 */
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/legal',                          [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'index'])->name('portals.admin.legal.index');
    Route::post('/admin/legal',                         [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'store'])->name('portals.admin.legal.store');
    Route::get('/admin/legal/documents/{document}',     [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'show'])->name('portals.admin.legal.show');
    Route::post('/admin/legal/documents/{document}/versions', [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'publishVersion'])->name('portals.admin.legal.versions.publish');

    Route::get('/admin/legal/closures',                 [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'closureRequests'])->name('portals.admin.legal.closures');
    Route::post('/admin/legal/closures/{closure}/review', [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'reviewClosure'])->name('portals.admin.legal.closures.review');

    Route::get('/admin/legal/complaints',               [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'privacyComplaints'])->name('portals.admin.legal.complaints');
    Route::post('/admin/legal/complaints/{complaint}/resolve', [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'resolveComplaint'])->name('portals.admin.legal.complaints.resolve');

    Route::get('/admin/legal/minor-transitions',        [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'minorTransitions'])->name('portals.admin.legal.minor_transitions');
});

Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/care-map/review', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'index'])->name('admin.care-map.review');

    Route::post('/admin/care-map/review/claims/{claim}/approve', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'approveClaim'])->name('admin.care-map.review.claims.approve');
    Route::post('/admin/care-map/review/claims/{claim}/reject',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'rejectClaim'])->name('admin.care-map.review.claims.reject');
    Route::post('/admin/care-map/review/claims/{claim}/revoke',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'revokeClaim'])->name('admin.care-map.review.claims.revoke');

    Route::post('/admin/care-map/review/listings/{listing}/link',   [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'linkListing'])->name('admin.care-map.review.listings.link');

    Route::post('/admin/care-map/review/imports/{review}/accept', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'acceptImport'])->name('admin.care-map.review.imports.accept');
    Route::post('/admin/care-map/review/imports/{review}/merge',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'mergeImport'])->name('admin.care-map.review.imports.merge');
    Route::post('/admin/care-map/review/imports/{review}/reject', [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'rejectImport'])->name('admin.care-map.review.imports.reject');
    Route::post('/admin/care-map/review/imports/{review}/defer',  [\App\Http\Controllers\MedicalId\AdminDirectoryReviewController::class, 'deferImport'])->name('admin.care-map.review.imports.defer');
});

/*
|--------------------------------------------------------------------------
| OpesCare Pharmacy Portal
|--------------------------------------------------------------------------
| /pharmacy/inventory (facility-internal inventory ops) was removed
| with the inventory-ops module; the public stock reporting page below is
| what the patient Medicine Finder reads.
*/
// Every route below carries a `capability:` gate from config/staff_capabilities.php
// on top of portal.access. portal.access alone put /facility/prescriptions in the
// `facility` bucket — 39 roles, so a receptionist or a lab tech could open the
// prescriber desk and WRITE a script. The capability is the role-level answer.
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access'])->group(function () {
    // The pharmacy dashboard shows the dispense queue AND stock alerts; a role
    // holding either half may open it (the view hides the half it lacks).
    Route::get('/pharmacy',                              [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'dashboard'])
        ->middleware('capability:dispense,pharmacy_stock')
        ->name('portals.pharmacy.dashboard');

    // ── Prescriber desk — writing prescriptions (the clinical half of the
    // module; dispensing is the pharmacy routes below). Facility-scoped.
    // prescribe: doctors and medical supervisors only — not nurses, not the
    // front desk, not the pharmacy, not the lab.
    Route::middleware('capability:prescribe')->group(function () {
        Route::get('/facility/prescriptions',            [\App\Http\Controllers\MedicalId\PrescriberController::class, 'index'])->name('portals.prescriber.index');
        Route::get('/facility/prescriptions/new',        [\App\Http\Controllers\MedicalId\PrescriberController::class, 'create'])->name('portals.prescriber.create');
        Route::post('/facility/prescriptions',           [\App\Http\Controllers\MedicalId\PrescriberController::class, 'store'])->name('portals.prescriber.store');
        Route::get('/facility/prescriptions/{id}',       [\App\Http\Controllers\MedicalId\PrescriberController::class, 'show'])->name('portals.prescriber.show');
        Route::get('/facility/prescriptions/{id}/print', [\App\Http\Controllers\MedicalId\PrescriptionPrintController::class, 'show'])->name('portals.prescriber.print');
        Route::post('/facility/prescriptions/{id}/void', [\App\Http\Controllers\MedicalId\PrescriberController::class, 'void'])->name('portals.prescriber.void');

        // Certificate desk — medical certificate and sick note (arrêt de
        // travail), issued through DocumentIssuanceService and printed on
        // demand on the secure sheet. Same gate as prescribing: a medical act.
        Route::get('/facility/certificates',              [\App\Http\Controllers\MedicalId\CertificateIssuanceController::class, 'index'])->name('portals.certificates.index');
        Route::get('/facility/certificates/new',          [\App\Http\Controllers\MedicalId\CertificateIssuanceController::class, 'create'])->name('portals.certificates.create');
        Route::post('/facility/certificates',             [\App\Http\Controllers\MedicalId\CertificateIssuanceController::class, 'store'])->name('portals.certificates.store');
        Route::get('/facility/certificates/{id}/pdf',     [\App\Http\Controllers\MedicalId\CertificateIssuanceController::class, 'pdf'])->whereUuid('id')->name('portals.certificates.pdf');
    });

    // ── Pharmacy counter — dispensing against prescriptions (dispense).
    Route::middleware('capability:dispense')->group(function () {
        Route::get('/pharmacy/prescriptions',               [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'prescriptions'])->name('portals.pharmacy.prescriptions');
        Route::get('/pharmacy/prescriptions/lookup', [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'prescriptionLookup'])->name('portals.pharmacy.prescriptions.lookup');
        // Detail + the dispensing register (reads; the write stays on `dispense` below).
        // `lookup` is registered first so the literal path wins over {id}.
        Route::get('/pharmacy/prescriptions/{id}',          [\App\Http\Controllers\MedicalId\PharmacyPrescriptionController::class, 'show'])->whereUuid('id')->name('portals.pharmacy.prescriptions.show');
        Route::get('/pharmacy/dispensing',                  [\App\Http\Controllers\MedicalId\PharmacyPrescriptionController::class, 'dispensing'])->name('portals.pharmacy.dispensing');
        Route::get('/pharmacy/dispensing/export',           [\App\Http\Controllers\MedicalId\PharmacyPrescriptionController::class, 'dispensingExport'])->name('portals.pharmacy.dispensing.export');
        Route::post('/pharmacy/prescriptions/{id}/dispense',[\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'dispense'])->name('portals.pharmacy.dispense');
    });

    // ── Stock — public medicine availability (the medicine_pharmacy_stocks
    // rows the patient Medicine Finder reads) and the controlled register.
    // pharmacy_stock: the pharmacy roles plus medicine_stock, who may report
    // stock but never dispense.
    Route::middleware('capability:pharmacy_stock')->group(function () {
        Route::get('/pharmacy/stock',                       [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'stock'])->name('portals.pharmacy.stock');
        Route::post('/pharmacy/stock',                      [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'reportStock'])->name('portals.pharmacy.stock.report');
        Route::get('/pharmacy/controlled',                  [\App\Http\Controllers\MedicalId\PharmacyPortalController::class, 'controlled'])->name('portals.pharmacy.controlled');
    });
});

// ── Pharmacy reports + controlled register export (capability:pharmacy_stock).
// Same gate as routes/pharmacy_ops.php, in the same order: the capability is
// judged BEFORE portal.access so a wrong-bucket role (a receptionist under
// /pharmacy) gets the 403 `role_forbidden`, not a redirect that hides it.
Route::middleware(['web', 'auth', 'mfa.verified', 'capability:pharmacy_stock', 'portal.access'])->group(function () {
    // The month's controlled register as the secure-sheet PDF (?format=csv for the file).
    Route::get('/pharmacy/controlled/register',             [\App\Http\Controllers\MedicalId\PharmacyReportsController::class, 'controlledRegister'])->name('portals.pharmacy.controlled.register');

    // Reports: expiring stock, valuation, dispensing volume (counts only — no
    // patient), low stock — one page, each exportable as CSV or secure-sheet PDF.
    Route::get('/pharmacy/reports',                         [\App\Http\Controllers\MedicalId\PharmacyReportsController::class, 'index'])->name('portals.pharmacy.reports');
    Route::get('/pharmacy/reports/{report}/csv',            [\App\Http\Controllers\MedicalId\PharmacyReportsController::class, 'csv'])->whereIn('report', \App\Services\Pharmacy\PharmacyReportService::REPORTS)->name('portals.pharmacy.reports.csv');
    Route::get('/pharmacy/reports/{report}/pdf',            [\App\Http\Controllers\MedicalId\PharmacyReportsController::class, 'pdf'])->whereIn('report', \App\Services\Pharmacy\PharmacyReportService::REPORTS)->name('portals.pharmacy.reports.pdf');
});

/*
|--------------------------------------------------------------------------
| OpesCare Legal Centre — Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/legal',                                                    [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'publicIndex'])->name('public.legal');
Route::get('/legal/{slug}',                                             [\App\Http\Controllers\MedicalId\LegalAdminController::class, 'publicShow'])->name('public.legal.show');

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
    // Roles
    Route::get('/admin/roles',                                  [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'index'])->name('admin.roles.index');
    Route::post('/admin/roles',                                 [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'store'])->name('admin.roles.store');
    Route::put('/admin/roles/{id}',                             [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'update'])->name('admin.roles.update');
    Route::delete('/admin/roles/{id}',                          [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'destroy'])->name('admin.roles.destroy');
    Route::get('/admin/roles/{id}/users',                       [\App\Http\Controllers\MedicalId\AdminRolesController::class, 'users'])->name('admin.roles.users');
});

// Public — no auth required (GET lets unauthenticated patients view the invite page)
Route::get('/family/invite/accept/{token}',  [\App\Http\Controllers\MedicalId\FamilyController::class, 'acceptInvite'])->name('portals.patient.family.invite.accept');
// POST requires auth — patient must be logged in to confirm acceptance
Route::post('/family/invite/accept/{token}', [\App\Http\Controllers\MedicalId\FamilyController::class, 'confirmInvite'])->middleware('auth')->name('portals.patient.family.invite.confirm');

/*
|--------------------------------------------------------------------------
| Developer Portal — External Developer Self-Service
|--------------------------------------------------------------------------
| With the platform's Connect admin portal removed, this is how a partner
| obtains and manages the integration-client credentials used by the
| Connect inventory stock ingest (api/v1/connect/inventory/*).
*/
Route::middleware(['web', 'auth', 'portal.access'])->group(function () {
    Route::get('/developer',                                          [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'dashboard'])->name('portals.developer.dashboard');
    Route::get('/developer/onboard',                                  [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'onboard'])->name('portals.developer.onboard');
    Route::post('/developer/onboard',                                 [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'onboardStore'])->name('portals.developer.onboard.store');

    // Apps (Integration Clients)
    Route::get('/developer/apps',                                     [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'apps'])->name('portals.developer.apps');
    Route::get('/developer/apps/create',                              [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'createApp'])->name('portals.developer.apps.create');
    Route::post('/developer/apps',                                    [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'storeApp'])->name('portals.developer.apps.store');
    Route::get('/developer/apps/{clientId}',                          [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'showApp'])->name('portals.developer.apps.show');
    Route::post('/developer/apps/{clientId}/rotate-secret',           [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'rotateSecret'])->name('portals.developer.apps.rotate');
    Route::post('/developer/apps/{clientId}/pay-invoice',             [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'payInvoice'])->name('portals.developer.apps.pay');
    Route::post('/developer/apps/{clientId}/toggle',                  [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'toggleApp'])->name('portals.developer.apps.toggle');

    // Production Access Requests
    Route::get('/developer/production-requests',                      [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'productionRequests'])->name('portals.developer.production_requests');
    Route::get('/developer/production-requests/create',               [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'createProductionRequest'])->name('portals.developer.production_requests.create');
    Route::post('/developer/production-requests',                     [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'storeProductionRequest'])->name('portals.developer.production_requests.store');

    // Webhook Delivery Logs
    Route::get('/developer/apps/{clientId}/webhook-deliveries',       [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'webhookDeliveries'])->name('portals.developer.webhook_deliveries');

    // API Usage Analytics
    Route::get('/developer/analytics',                                [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'analytics'])->name('portals.developer.analytics');
});

// --- Admin: Developer Portal Management (approvals for the self-service flow) ---
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'throttle:portal'])->group(function () {
    Route::get('/admin/developer/production-requests',             [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminProductionRequests'])->name('portals.admin.developer.production_requests');
    Route::post('/admin/developer/production-requests/{prodRequest}/approve', [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminApproveProductionRequest'])->name('portals.admin.developer.production_requests.approve');
    Route::post('/admin/developer/production-requests/{prodRequest}/reject',  [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminRejectProductionRequest'])->name('portals.admin.developer.production_requests.reject');
    Route::get('/admin/developer/accounts',                        [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminDeveloperAccounts'])->name('portals.admin.developer.accounts');
    Route::post('/admin/developer/accounts/{account}/suspend',     [\App\Http\Controllers\MedicalId\DeveloperPortalController::class, 'adminSuspendDeveloper'])->name('portals.admin.developer.accounts.suspend');
});

// --- Integration Certifications (partner integrations for the stock ingest) ---
// Platform-tier only: badges certify partners for the whole country. Without
// platform.admin any facility admin could issue or revoke them.
Route::middleware(['web', 'auth', 'portal.access', 'platform.admin', 'mfa.verified'])->group(function () {
    Route::get('/admin/certifications',                                   [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'index'])->name('portals.admin.certifications.index');
    Route::get('/admin/certifications/create',                            [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'create'])->name('portals.admin.certifications.create');
    Route::post('/admin/certifications',                                  [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'store'])->name('portals.admin.certifications.store');
    Route::get('/admin/certifications/{certification}',                   [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'show'])->name('portals.admin.certifications.show');
    Route::post('/admin/certifications/{certification}/test-run',         [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'recordTestRun'])->name('portals.admin.certifications.test_run');
    Route::post('/admin/certifications/{certification}/badge/issue',      [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'issueBadge'])->name('portals.admin.certifications.badge.issue');
    Route::post('/admin/certifications/badges/{badge}/revoke',            [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'revokeBadge'])->name('portals.admin.certifications.badge.revoke');
    Route::post('/admin/certifications/seed-requirements',                [\App\Http\Controllers\MedicalId\IntegrationCertificationController::class, 'seedRequirements'])->name('portals.admin.certifications.seed');
});

/*
|--------------------------------------------------------------------------
| Legacy /portals/* → clean-prefix redirects
|--------------------------------------------------------------------------
| The five operator dashboards moved to short, canonical prefixes
| (/admin, /pharmacy, /facility, /developer, /patient). These permanent
| redirects keep old bookmarks and any lingering hardcoded /portals/* link
| working. They are auth-free (a redirect needs no session) and are declared
| LAST so they can only ever match paths no real route claims — the real
| dashboards now live at the clean prefixes, and /portals/guardian/* and
| /portals/listing/* are deliberately NOT redirected (those prefixes were
| not renamed). POSTs keep working at the canonical URLs because every form
| links by route() name, whose names are unchanged.
|
| NOTE: portals/staff AND portals/facility both fold into /facility — the
| operations desk (was staff) and the facility-admin team page (was
| facility/team) now share one clean root with no path collision.
*/
foreach ([
    'portals/admin'     => 'admin',
    'portals/pharmacy'  => 'pharmacy',
    'portals/staff'     => 'facility',
    'portals/facility'  => 'facility',
    'portals/developer' => 'developer',
    'portals/patient'   => 'patient',
] as $legacyPrefix => $cleanPrefix) {
    // Bare prefix (e.g. /portals/admin → /admin)
    Route::permanentRedirect('/' . $legacyPrefix, '/' . $cleanPrefix);

    // Everything under it (e.g. /portals/admin/cc → /admin/cc), query string preserved.
    Route::get('/' . $legacyPrefix . '/{rest}', function (string $rest) use ($cleanPrefix) {
        $qs = request()->getQueryString();

        return redirect('/' . $cleanPrefix . '/' . $rest . ($qs ? '?' . $qs : ''), 301);
    })->where('rest', '.*');
}

/*
 * Bulk data import — the upload → map → validate → preview → approve wizard.
 *
 * Ported from the main platform, where it lived under /portals/staff. Here it
 * is admin-only: an approved import writes records (patients among them) into a
 * facility at scale, so it sits behind the same ['web','auth','platform.admin']
 * group as the MPI desk, and RequirePlatformAdmin treats everything under
 * /admin/ as platform tier. The facility is taken from the session context
 * only — never a header, body field or "the one facility we found".
 */
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/data-import',                        [\App\Http\Controllers\MedicalId\DataImportController::class, 'index'])->name('portals.admin.data_import.index');
    Route::get('/admin/data-import/new',                    [\App\Http\Controllers\MedicalId\DataImportController::class, 'create'])->name('portals.admin.data_import.create');
    Route::post('/admin/data-import',                       [\App\Http\Controllers\MedicalId\DataImportController::class, 'store'])->name('portals.admin.data_import.store');
    Route::get('/admin/data-import/{id}/mapping',           [\App\Http\Controllers\MedicalId\DataImportController::class, 'mapping'])->name('portals.admin.data_import.mapping');
    Route::post('/admin/data-import/{id}/mapping',          [\App\Http\Controllers\MedicalId\DataImportController::class, 'mappingStore'])->name('portals.admin.data_import.mapping.store');
    Route::post('/admin/data-import/{id}/validate',         [\App\Http\Controllers\MedicalId\DataImportController::class, 'validate'])->name('portals.admin.data_import.validate');
    Route::get('/admin/data-import/{id}/preview',           [\App\Http\Controllers\MedicalId\DataImportController::class, 'preview'])->name('portals.admin.data_import.preview');
    Route::post('/admin/data-import/{id}/approve',          [\App\Http\Controllers\MedicalId\DataImportController::class, 'approve'])->name('portals.admin.data_import.approve');
    Route::post('/admin/data-import/{id}/rollback',         [\App\Http\Controllers\MedicalId\DataImportController::class, 'rollback'])->name('portals.admin.data_import.rollback');
    Route::post('/admin/data-import/{id}/cancel',           [\App\Http\Controllers\MedicalId\DataImportController::class, 'cancel'])->name('portals.admin.data_import.cancel');
    Route::get('/admin/data-import/{id}/log',               [\App\Http\Controllers\MedicalId\DataImportController::class, 'auditLog'])->name('portals.admin.data_import.audit');
});

/*
 * Security oversight — the break-glass review desk.
 *
 * Ported from the main platform's /portals/admin/security and mounted on the
 * Finder's clean /admin/security prefix. Same group as the MPI and data-import
 * desks: ['web','auth','platform.admin']. RequirePlatformAdmin treats every
 * path under /admin/ as platform tier, so a facility-tier admin gets 403 here
 * rather than a 404 — the refusal names the reason instead of hiding the page.
 *
 * Oversight of emergency access is cross-facility by definition, so nothing on
 * this desk is facility-scoped and no facility id is ever read from a header,
 * body or query string. The only write against an override is a review case
 * recorded ALONGSIDE it; the access event and the audit trail are immutable.
 */
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/security',                                    [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'index'])->name('portals.admin.security.index');
    Route::get('/admin/security/emergency-access',                   [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'emergencyAccess'])->name('portals.admin.security.emergency_access');
    Route::post('/admin/security/emergency-access/{id}/review',      [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'emergencyReview'])->name('portals.admin.security.emergency_access.review');
    Route::get('/admin/security/incidents',                          [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'incidents'])->name('portals.admin.security.incidents');
    Route::post('/admin/security/incidents',                         [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'incidentStore'])->name('portals.admin.security.incidents.store');
    Route::get('/admin/security/incidents/{id}',                     [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'incidentShow'])->name('portals.admin.security.incidents.show');
    Route::post('/admin/security/incidents/{id}',                    [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'incidentUpdate'])->name('portals.admin.security.incidents.update');
    Route::get('/admin/security/audit',                              [\App\Http\Controllers\MedicalId\SecurityOpsController::class, 'auditExplorer'])->name('portals.admin.security.audit_explorer');
});

/*
|--------------------------------------------------------------------------
| Staff Portal — BLOOD REQUEST QUEUE (the facility side of a patient's ask)
|--------------------------------------------------------------------------
|
| The queue blood-bank staff answer in the browser. Until now the only
| receivers were the partner API (routes/blood_bank.php, behind
| VerifyIntegrationClient — zero credentials issued in production) and the
| provider app (routes/mobile_blood_desk.php). On the web there was no screen
| at all: BloodRequestService pinged every staff member at the facility the
| moment a request landed and sent them to /facility/inventory/blood, the
| STOCK page, which never shows a request. That notification now points here.
|
| MIDDLEWARE — deliberately identical to the group at line ~265 that carries
| the rest of the staff portal, plus the same capability gate as the blood
| stock screen it sits beside:
|
|   web, auth, mfa.verified, portal.access, platform.admin, facility.context,
|   patient.profile, guardian.profile, throttle:portal
|     + capability:blood_inventory
|
| `facility.context` is what makes the scoping in BloodRequestDeskController
| safe: the facility is resolved from the session by middleware and read back
| through PortalContextService, never from a header, body, query string or a
| fallback default. capability:blood_inventory is the SAME gate as
| portals.staff.inventory.blood — the authority that reads the blood shelf is
| the authority that answers a request against it, and no new capability is
| invented. Everyone else is refused 403 {error_code: role_forbidden} before
| the controller runs; a request belonging to ANOTHER blood bank is 404, never
| 403, because "forbidden" would already confirm a patient asked elsewhere.
|
| Appended as its own block rather than folded into the existing group so that
| nothing already routed changes. Verify with:
|   php artisan route:list --path=blood-requests
|
| ROUTE ORDER: every action ends in a literal segment, so nothing here can be
| swallowed by the {id} wildcard. whereUuid keeps a malformed id from reaching
| Postgres as a bad uuid cast (22P02) and surfacing as a 500 instead of the
| honest 404 — blood_requests.id is a uuid (App\Models\BloodRequest uses
| HasUuids).
|
| Nothing on this surface deletes a request, edits its history, moves it
| backwards, or touches `blood_availability`: confirming is the blood bank
| saying "yes, come", not a reservation.
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'platform.admin', 'facility.context', 'patient.profile', 'guardian.profile', 'throttle:portal'])->group(function () {
    Route::middleware('capability:blood_inventory')->group(function () {
        Route::get('/facility/blood-requests',                    [\App\Http\Controllers\MedicalId\BloodRequestDeskController::class, 'index'])->name('portals.staff.blood_requests.index');
        Route::get('/facility/blood-requests/new',                [\App\Http\Controllers\MedicalId\BloodRequestDeskController::class, 'create'])->name('portals.staff.blood_requests.create');
        Route::post('/facility/blood-requests',                   [\App\Http\Controllers\MedicalId\BloodRequestDeskController::class, 'store'])->name('portals.staff.blood_requests.store');
        Route::get('/facility/blood-requests/{id}',               [\App\Http\Controllers\MedicalId\BloodRequestDeskController::class, 'show'])->whereUuid('id')->name('portals.staff.blood_requests.show');
        Route::post('/facility/blood-requests/{id}/decide',       [\App\Http\Controllers\MedicalId\BloodRequestDeskController::class, 'decide'])->whereUuid('id')->name('portals.staff.blood_requests.decide');
        Route::post('/facility/blood-requests/{id}/ready',        [\App\Http\Controllers\MedicalId\BloodRequestDeskController::class, 'ready'])->whereUuid('id')->name('portals.staff.blood_requests.ready');
        Route::post('/facility/blood-requests/{id}/fulfil',       [\App\Http\Controllers\MedicalId\BloodRequestDeskController::class, 'fulfil'])->whereUuid('id')->name('portals.staff.blood_requests.fulfil');
    });
});

/*
 * Clinical terminology review desk — approving LOINC / ICD-10 mappings, and
 * putting an approved code onto the records it describes.
 *
 * `code_system_mappings` has carried twenty proposals since May with an
 * approve() method, a reject() and an `approved` scope, and no screen at all.
 * Twenty pending, zero approved, every `lab_results.loinc_code` null: the
 * interoperability layer was a table waiting for a person who had no door.
 *
 * Same group as the data-import and security desks — ['web','auth',
 * 'platform.admin'] — and RequirePlatformAdmin treats every path under /admin/
 * as platform tier, so a facility-tier admin gets a 403 that names the reason
 * rather than a 404 that hides the page.
 *
 * The mappings are platform-wide (facility_id null) and this desk is
 * cross-facility by definition, so no facility id is read from a header, body,
 * query string or "the one facility we found"; the controller takes it from
 * $request->attributes only, and only to stamp the audit trail.
 *
 * ROUTE ORDER: /log is declared before the {id} routes and every {id} route
 * carries whereUuid, so the literal segment cannot be swallowed by the
 * wildcard and a malformed id is an honest 404 rather than a Postgres 22P02
 * cast error surfacing as a 500.
 *
 * Appended as its own block so nothing already routed changes. Verify with:
 *   php artisan route:list --path=admin/terminology
 */
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/terminology',                    [\App\Http\Controllers\MedicalId\TerminologyController::class, 'index'])->name('portals.admin.terminology.index');
    Route::get('/admin/terminology/log',                [\App\Http\Controllers\MedicalId\TerminologyController::class, 'log'])->name('portals.admin.terminology.log');
    // A literal segment, so it stays ABOVE the {id} routes for the same reason
    // /log does: whereUuid would reject the word "dictionary" and the wildcard
    // would swallow the path before this line ever ran.
    Route::get('/admin/terminology/dictionary',         [\App\Http\Controllers\MedicalId\TerminologyController::class, 'dictionary'])->name('portals.admin.terminology.dictionary');
    // Also a literal segment, so it belongs ABOVE the {id} routes with /log and
    // /dictionary. GET only: the report itself reads. Its one POST is
    // /propose below, which raises a finding as a PENDING mapping for a
    // reviewer and writes to no clinical table, because nothing this report
    // finds may be fixed in bulk from a screen.
    Route::get('/admin/terminology/gaps',               [\App\Http\Controllers\MedicalId\TerminologyController::class, 'gaps'])->name('portals.admin.terminology.gaps');
    // A LITERAL segment, and therefore declared ABOVE the {id} routes for the
    // same reason /log, /dictionary and /gaps are: the wildcard would swallow
    // the word "propose" before this line ever ran.
    //
    // The gap report's one action. It creates a PENDING CodeSystemMapping from
    // a finding and writes an audit row, and that is the whole of it — no
    // clinical table is touched, here or anywhere downstream of here without a
    // human approval first. The proposal joins the queue at
    // /admin/terminology, where a reviewer approves or rejects it, and only
    // then may TerminologyApplier put the code onto records.
    Route::post('/admin/terminology/propose',           [\App\Http\Controllers\MedicalId\TerminologyController::class, 'propose'])->name('portals.admin.terminology.propose');
    Route::get('/admin/terminology/{id}',             [\App\Http\Controllers\MedicalId\TerminologyController::class, 'show'])->whereUuid('id')->name('portals.admin.terminology.show');
    Route::post('/admin/terminology/{id}/approve',      [\App\Http\Controllers\MedicalId\TerminologyController::class, 'approve'])->whereUuid('id')->name('portals.admin.terminology.approve');
    Route::post('/admin/terminology/{id}/reject',       [\App\Http\Controllers\MedicalId\TerminologyController::class, 'reject'])->whereUuid('id')->name('portals.admin.terminology.reject');
    Route::post('/admin/terminology/{id}/apply',        [\App\Http\Controllers\MedicalId\TerminologyController::class, 'apply'])->whereUuid('id')->name('portals.admin.terminology.apply');
});

/*
|--------------------------------------------------------------------------
| Partner onboarding — the desk that issues integration credentials
|--------------------------------------------------------------------------
| `integration_clients` is what every external system authenticates against,
| and until this block there was no route in the product that could create a
| row in it. The developer self-service portal above lets a DEVELOPER make a
| sandbox app for themselves; nothing let the PLATFORM onboard a partner
| organisation, choose its scopes, or revoke it. This is that desk.
|
| ROUTE ORDER: /create is a literal segment and is declared ABOVE the {id}
| routes, which all carry whereUuid — so the wildcard cannot swallow the word
| "create", and a malformed id is an honest 404 rather than a Postgres 22P02
| cast error surfacing as a 500.
|
| Appended as its own block so nothing already routed changes. Verify with:
|   php artisan route:list --path=admin/partners
*/
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/partners',                    [\App\Http\Controllers\MedicalId\PartnerOnboardingController::class, 'index'])->name('portals.admin.partners.index');
    Route::get('/admin/partners/create',             [\App\Http\Controllers\MedicalId\PartnerOnboardingController::class, 'create'])->name('portals.admin.partners.create');
    Route::post('/admin/partners',                   [\App\Http\Controllers\MedicalId\PartnerOnboardingController::class, 'store'])->name('portals.admin.partners.store');
    Route::get('/admin/partners/{id}',               [\App\Http\Controllers\MedicalId\PartnerOnboardingController::class, 'show'])->whereUuid('id')->name('portals.admin.partners.show');
    Route::post('/admin/partners/{id}/rotate',       [\App\Http\Controllers\MedicalId\PartnerOnboardingController::class, 'rotate'])->whereUuid('id')->name('portals.admin.partners.rotate');
    Route::post('/admin/partners/{id}/scopes',       [\App\Http\Controllers\MedicalId\PartnerOnboardingController::class, 'scopes'])->whereUuid('id')->name('portals.admin.partners.scopes');
    Route::post('/admin/partners/{id}/status',       [\App\Http\Controllers\MedicalId\PartnerOnboardingController::class, 'status'])->whereUuid('id')->name('portals.admin.partners.status');
});

/*
|--------------------------------------------------------------------------
| Patient rights desk — data export and record correction requests
|--------------------------------------------------------------------------
|
| THE DEFECT THIS CLOSES. Patients can file a statutory data-export request
| and a record-correction request from the shipped mobile app; both land in
| `data_export_requests` / `correction_requests` as `pending`. The ONLY code
| able to approve either was `Api\V1\Admin\AdminGovernanceController`, which
| has never had a single route registered anywhere in this application, and
| `DataExportService::downloadExport()` refuses to produce anything that is
| not approved. So a patient exercised a right under Cameroon Law No.
| 2010/012, the request was stored, and no human being on this platform could
| act on it. This block is the door.
|
| Platform tier: everything under /admin/ is platform-only by
| RequirePlatformAdmin::isPlatformOnlyPath(), so a facility-tier user is
| refused with a 403 before the controller runs.
|
| ROUTE ORDER: the literal segment /log is declared ABOVE the {id} routes,
| which all carry whereUuid — so the wildcard cannot swallow the word "log",
| and a malformed id is an honest 404 rather than a Postgres 22P02 cast error
| surfacing as a 500.
|
| Appended as its own block so nothing already routed changes. Verify with:
|   php artisan route:list --path=admin/governance
*/
Route::middleware(['web', 'auth', 'platform.admin'])->group(function () {
    Route::get('/admin/governance',      [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'index'])->name('portals.admin.governance.index');
    Route::get('/admin/governance/log',  [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'log'])->name('portals.admin.governance.log');

    Route::get('/admin/governance/exports/{id}',          [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'showExport'])->whereUuid('id')->name('portals.admin.governance.exports.show');
    Route::post('/admin/governance/exports/{id}/approve', [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'approveExport'])->whereUuid('id')->name('portals.admin.governance.exports.approve');
    Route::post('/admin/governance/exports/{id}/refuse',  [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'refuseExport'])->whereUuid('id')->name('portals.admin.governance.exports.refuse');

    Route::get('/admin/governance/corrections/{id}',          [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'showCorrection'])->whereUuid('id')->name('portals.admin.governance.corrections.show');
    Route::post('/admin/governance/corrections/{id}/approve', [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'approveCorrection'])->whereUuid('id')->name('portals.admin.governance.corrections.approve');
    Route::post('/admin/governance/corrections/{id}/refuse',  [\App\Http\Controllers\MedicalId\GovernanceRequestsController::class, 'refuseCorrection'])->whereUuid('id')->name('portals.admin.governance.corrections.refuse');
});

/*
|--------------------------------------------------------------------------
| Imaging report desk — the radiologist's report on a completed study
|--------------------------------------------------------------------------
|
| docs/superpowers/specs/2026-10-13-diagnostic-centre-dashboard-design.md.
| The intake/worklist/detail routes live in routes/imaging_desk.php under
| `capability:imaging`; this block is the WRITE side of a report and is gated
| on `capability:imaging_report` (radiologist, lab_manager). The queue page
| `portals.imaging.reports.index` is declared there too (same gate) so the
| sidebar link and the detail page's report panel resolve on either load
| order.
|
| Draft -> finalize -> amend (a NEW version, the old kept) -> distribute (the
| patient's documents list + bell). The PDF the patient downloads is rendered
| on demand and never stored; `attachment` streams the one uploaded file.
|
| Facility from PortalContextService only; another facility's order 404s.
| ROUTE ORDER: every path adds a segment after `orders/{id}`, so nothing in
| routes/imaging_desk.php can swallow it.
*/
Route::middleware(['web', 'auth', 'mfa.verified', 'portal.access', 'capability:imaging_report'])->group(function () {
    Route::get('/facility/imaging/orders/{id}/report', [\App\Http\Controllers\MedicalId\ImagingReportController::class, 'edit'])
        ->whereUuid('id')->name('portals.imaging.report.edit');
    Route::post('/facility/imaging/orders/{id}/report', [\App\Http\Controllers\MedicalId\ImagingReportController::class, 'save'])
        ->whereUuid('id')->name('portals.imaging.report.save');
    Route::post('/facility/imaging/orders/{id}/report/finalize', [\App\Http\Controllers\MedicalId\ImagingReportController::class, 'finalize'])
        ->whereUuid('id')->name('portals.imaging.report.finalize');
    Route::post('/facility/imaging/orders/{id}/report/amend', [\App\Http\Controllers\MedicalId\ImagingReportController::class, 'amend'])
        ->whereUuid('id')->name('portals.imaging.report.amend');
    Route::post('/facility/imaging/orders/{id}/report/distribute', [\App\Http\Controllers\MedicalId\ImagingReportController::class, 'distribute'])
        ->whereUuid('id')->name('portals.imaging.report.distribute');
    Route::get('/facility/imaging/orders/{id}/report/attachment', [\App\Http\Controllers\MedicalId\ImagingReportController::class, 'attachment'])
        ->whereUuid('id')->name('portals.imaging.report.attachment');
});
