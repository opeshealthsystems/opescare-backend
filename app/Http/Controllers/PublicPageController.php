<?php

namespace App\Http\Controllers;

use App\Mail\OpesCareNotificationMail;
use App\Models\Facility;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Auth\Services\TwoFactorService;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PasswordResetLinkNotification;
use App\Services\Dashboard\DashboardProfileService;
use App\Services\Identity\HealthIdGeneratorService;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PublicPageController extends Controller
{
    public function index()
    {
        return view('public.landing');
    }

    /*
     * Network services — the destination pages for the medicine and blood
     * availability detail that used to occupy two full sections of the
     * homepage. They are network capabilities, not the definition of OpesCare,
     * so the homepage carries a card each and the substance lives here.
     */
    public function networkMedicineFinder()
    {
        return view('public.network.medicine_finder');
    }

    public function networkBloodFinder()
    {
        return view('public.network.blood_finder');
    }

    /**
     * Shown to a signed-in user whose portal is frozen out of this release.
     * Reached only by redirect from EnsurePortalAccess — the frozen URL
     * itself still 404s, unchanged.
     */
    public function showPortalUnavailable()
    {
        return view('auth.portal_unavailable');
    }

    public function about()
    {
        return view('public.about');
    }

    public function howItWorks()
    {
        return view('public.how_it_works');
    }

    public function solutionsPatients()
    {
        return view('public.solutions.patients');
    }

    public function solutionsHospitals()
    {
        return view('public.solutions.hospitals');
    }

    public function solutionsPharmacies()
    {
        return view('public.solutions.pharmacies');
    }

    public function solutionsLaboratories()
    {
        return view('public.solutions.laboratories');
    }

    public function solutionsInsurers()
    {
        return view('public.solutions.insurers');
    }

    public function solutionsPublicHealth()
    {
        return view('public.solutions.public_health');
    }

    public function interoperability()
    {
        return view('public.interoperability');
    }

    public function developers()
    {
        return view('public.developers');
    }

    public function security()
    {
        return view('public.security');
    }

    public function privacy()
    {
        return view('public.privacy');
    }

    public function terms()
    {
        return view('public.terms');
    }

    public function consent()
    {
        return view('public.consent');
    }

    public function faq()
    {
        return view('public.faq');
    }

    public function help()
    {
        return view('public.help');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function contactSubmit(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:120',
            'email'             => 'required|email|max:180',
            'subject'           => 'nullable|string|max:60',
            'message'           => 'required|string|max:5000',
            // Partner inquiry extras (from landing page form)
            'organisation'      => 'nullable|string|max:160',
            'organization'      => 'nullable|string|max:160',
            'role'              => 'nullable|string|max:100',
            'phone'             => 'nullable|string|max:30',
            'organization_type' => 'nullable|string|max:60',
            'country'           => 'nullable|string|max:80',
        ]);

        $supportEmail = config('mail.support_address', config('mail.from.address'));
        $name    = $request->input('name');
        $email   = $request->input('email');
        $subject = $request->input('subject', 'Contact enquiry');
        $org     = $request->input('organisation') ?? $request->input('organization', '');
        $body    = "From: {$name} <{$email}>" . ($org ? "\nOrganisation: {$org}" : '') . "\n\n" . $request->input('message');

        /*
         * Record the enquiry BEFORE sending anything.
         *
         * This used to queue an email and nothing else, to an address on a
         * domain that accepts no inbound mail — so every message a visitor
         * sent through the contact form was discarded while the page thanked
         * them for it. The lead is the record; the email is a notification.
         */
        Lead::create([
            'name'              => $name,
            'email'             => $email,
            'phone'             => $request->input('phone'),
            'organization_name' => $org ?: null,
            'organization_type' => in_array($request->input('organization_type'), Lead::ORGANIZATION_TYPES, true)
                ? $request->input('organization_type')
                : 'other',
            'message'           => trim(($subject ? "Subject: {$subject}
" : '') . $body),
            'source'            => 'contact',
            'status'            => 'new',
        ]);

        // A mail transport failure must not lose an enquiry already recorded.
        try {
            Mail::to($supportEmail)->queue(new OpesCareNotificationMail(
                mailSubject: "OpesCare Contact: {$subject}",
                bodyText: $body,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('contact_enquiry_mail_failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Redirect back to the originating page with success flag
        $back = url()->previous();
        return redirect($back)->with('contact_success', true)->with('success', __('flash.contact_message_received'));
    }

    public function status(\App\Modules\Admin\Services\SystemHealthService $health)
    {
        // Active or upcoming maintenance windows are the public incident feed —
        // never expose internal SecurityIncident records here.
        $maintenance = \App\Models\MaintenanceWindow::query()
            ->where('is_active', true)
            ->orWhere('starts_at', '>=', now())
            ->orderByDesc('starts_at')
            ->limit(5)
            ->get();

        return view('public.status', [
            'health'      => $health->currentHealth(),
            'maintenance' => $maintenance,
        ]);
    }

    public function sla()
    {
        return view('public.sla');
    }

    public function pricing()
    {
        return view('public.api-pricing', ['plans' => \App\Models\ApiPlan::public()]);
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function submitLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        // Attempt real authentication
        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('onboarding.login.errors.invalid_credentials', [], app()->getLocale())
                    ?: 'The email or password you entered is incorrect.']);
        }

        $user = Auth::user();

        // Suspended account
        if ($user->status === 'suspended') {
            Auth::logout();
            return redirect()->route('account.suspended');
        }

        // Pending account
        if ($user->status === 'pending') {
            Auth::logout();
            return redirect()->route('account.pending');
        }

        if ($user->requiresTwoFactor()) {
            Auth::logout();
            $request->session()->regenerate();
            $request->session()->put('mfa.user_id', $user->id);
            $request->session()->put('mfa.remember', $remember);
            $request->session()->put('mfa.setup_required', ! $user->hasTwoFactorEnabled());

            return redirect()->route('mfa.challenge');
        }

        $request->session()->regenerate();
        $request->session()->put('mfa.verified', true);

        // Route to correct portal based on role
        $landingUrl = app(DashboardProfileService::class)->landingUrlForUser($user);

        return redirect()->intended($landingUrl);
    }

    public function showMfaChallenge(Request $request)
    {
        if (! $request->session()->has('mfa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.mfa_challenge', [
            'setupRequired' => (bool) $request->session()->get('mfa.setup_required', false),
        ]);
    }

    public function submitMfaChallenge(Request $request, TwoFactorService $twoFactor)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $user = User::find($request->session()->get('mfa.user_id'));

        if (! $user) {
            $request->session()->forget(['mfa.user_id', 'mfa.remember', 'mfa.setup_required']);

            return redirect()->route('login')->with('error', __('flash.secure_session_expired'));
        }

        if (! $user->hasTwoFactorEnabled()) {
            return back()->with('error', __('flash.mfa_setup_required'));
        }

        if (! $twoFactor->verify($user->two_factor_secret, $validated['code'])) {
            return back()->withErrors(['code' => __('flash.auth_code_invalid')]);
        }

        Auth::login($user, (bool) $request->session()->get('mfa.remember', false));
        $request->session()->regenerate();
        $request->session()->put('mfa.verified', true);
        $request->session()->forget(['mfa.user_id', 'mfa.remember', 'mfa.setup_required']);

        $landingUrl = app(DashboardProfileService::class)->landingUrlForUser($user);

        return redirect()->intended($landingUrl);
    }

    public function showRegisterSelector()
    {
        return view('auth.register');
    }

    public function showPatientRegister()
    {
        return view('auth.register.patient');
    }

    /**
     * Patient sign-up: email and password, nothing else.
     *
     * It used to demand nine fields — names, date of birth, sex, phone and a
     * full emergency contact — before anyone could create an account. Everything
     * except the credentials now moves to profile completion, after login.
     *
     * No Patient row and no Health ID are created here, deliberately. A Health
     * ID is a *verified identity*; minting one against a blank record would put
     * unidentifiable rows into the Master Patient Index that later have to be
     * reconciled, which is exactly what the no-auto-merge invariant exists to
     * avoid. Identity is created once there is an identity to bind it to.
     *
     * The account is also created ACTIVE. It used to be 'pending', while
     * submitLogin() rejects 'pending' and redirects to the approval screen — so
     * every self-registered patient was locked out of the account they had just
     * made. Patients are self-service; there is nobody to approve them.
     */
    public function submitPatientRegister(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email|max:180|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => __('onboarding.patient.email_taken'),
        ]);

        $user = DB::transaction(function () use ($data) {
            $role = Role::where('name', 'patient')->first();

            $user = User::create([
                // A display name is NOT NULL on users; the local-part is a
                // placeholder replaced by the real name at profile completion.
                'name'     => Str::before($data['email'], '@'),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'status'   => 'active',
            ]);

            if ($role) {
                $user->role_id = $role->id;
                $user->save();
            }

            return $user;
        });

        // Referral capture must never break sign-up.
        $refCode = trim((string) $request->input('ref', ''));
        if ($refCode !== '') {
            $request->session()->put('pending_referral_code', $refCode);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('mfa.verified', true);

        return redirect()->route('portals.patient.complete-profile')
            ->with('success', __('onboarding.patient.account_created'));
    }

    public function showGuardianRegister()
    {
        return view('auth.register.guardian');
    }

    /**
     * Caregiver sign-up — an email and a password, like every other account.
     *
     * What used to be one 16-field form is now two steps. Guardian access to
     * somebody else's record still requires institutional verification before
     * it becomes active, which is what the success message has always said;
     * the identity and dependant details that a reviewer needs are collected
     * after login, at /portals/guardian/complete-profile.
     */
    public function submitGuardianRegister(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email|max:180|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => __('onboarding.guardian.email_taken'),
        ]);

        $user = DB::transaction(function () use ($data) {
            $role = Role::where('name', 'guardian')->first();

            $user = User::create([
                'name'     => Str::before($data['email'], '@'),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'status'   => 'active',
            ]);

            if ($role) {
                $user->role_id = $role->id;
                $user->save();
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('mfa.verified', true);

        return redirect()->route('portals.guardian.complete-profile')
            ->with('success', __('onboarding.guardian.account_created'));
    }


    public function showOrganizationRegister()
    {
        return view('auth.register.organization');
    }

    public function submitOrganizationRegister(Request $request)
    {
        $data = $request->validate([
            'org_type'      => 'required|string|max:60',
            'legal_name'    => 'required|string|max:200',
            'trade_name'    => 'nullable|string|max:200',
            'reg_number'    => 'required|string|max:80',
            'license_number'=> 'required|string|max:80',
            'address'       => 'required|string|max:300',
            'main_phone'    => 'required|string|max:30',
            'main_email'    => 'required|email|max:180',
            'contact_name'  => 'required|string|max:120',
            'contact_role'  => 'required|string|max:100',
            'contact_email' => 'required|email|max:180',
            'contact_phone' => 'required|string|max:30',
        ]);

        $refCode  = 'OPC-' . strtoupper(bin2hex(random_bytes(4)));
        $adminEmail = config('mail.support_address', config('mail.from.address'));

        $body = "New Organisation Application\n\n"
            . "Ref: {$refCode}\n"
            . "Type: {$data['org_type']}\n"
            . "Legal Name: {$data['legal_name']}\n"
            . "Reg#: {$data['reg_number']} | License#: {$data['license_number']}\n"
            . "Address: {$data['address']}\n"
            . "Main: {$data['main_email']} / {$data['main_phone']}\n"
            . "Contact: {$data['contact_name']} ({$data['contact_role']}) — {$data['contact_email']} / {$data['contact_phone']}\n";

        /*
         * Record the application BEFORE sending anything.
         *
         * The email used to be its only trace. Production has no SMTP host
         * configured, so a queued mail that never leaves took the whole
         * application with it — and the reference code below was handed to the
         * applicant for a record that existed nowhere. The lead IS the record;
         * the email is only a notification about it.
         */
        Lead::create([
            'name'              => $data['contact_name'],
            'email'             => $data['contact_email'],
            'phone'             => $data['contact_phone'],
            'organization_name' => $data['legal_name'],
            'organization_type' => in_array($data['org_type'], Lead::ORGANIZATION_TYPES, true)
                ? $data['org_type']
                : 'other',
            'message'           => $body,
            'source'            => 'organization_application',
            'status'            => 'new',
        ]);

        // A mail transport failure must not lose an application that is
        // already safely recorded.
        try {
            Mail::to($adminEmail)->queue(new OpesCareNotificationMail(
                mailSubject: "OpesCare Organisation Application: {$data['legal_name']} [{$refCode}]",
                bodyText: $body,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('organisation_application_mail_failed', [
                'ref'   => $refCode,
                'error' => $e->getMessage(),
            ]);
        }

        return view('auth.register.organization', [
            'success_application' => true,
            'ref_code'   => $refCode,
            'legal_name' => $data['legal_name'],
        ]);
    }

    public function showDeveloperRegister()
    {
        return view('auth.register.developer');
    }

    /**
     * Connect API access request — reviewed by the interoperability panel,
     * exactly as the success message says. It used to say so and record
     * nothing, so no request ever reached the panel.
     */
    public function submitDeveloperRegister(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:120',
            'email'               => 'required|email|max:180',
            'phone'               => 'nullable|string|max:30',
            'organization'        => 'required|string|max:200',
            'role'                => 'nullable|string|max:100',
            'country'             => 'nullable|string|max:80',
            'system_type'         => 'nullable|string|max:120',
            'integration_purpose' => 'nullable|string|max:2000',
            'data_flow'           => 'nullable|string|max:60',
            'sandbox'             => 'nullable',
            'production'          => 'nullable',
        ]);

        $lines = [];
        foreach ([
            'Role'         => $data['role'] ?? null,
            'Country'      => $data['country'] ?? null,
            'System type'  => $data['system_type'] ?? null,
            'Data flow'    => $data['data_flow'] ?? null,
            'Sandbox'      => $request->boolean('sandbox') ? 'yes' : null,
            'Production'   => $request->boolean('production') ? 'yes' : null,
        ] as $label => $value) {
            if (! empty($value)) {
                $lines[] = $label . ': ' . $value;
            }
        }
        if (! empty($data['integration_purpose'])) {
            $lines[] = '';
            $lines[] = 'Integration purpose: ' . $data['integration_purpose'];
        }

        Lead::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'organization_name' => $data['organization'],
            'organization_type' => 'developer',
            'message'           => implode("
", $lines),
            'source'            => 'developer_signup',
            'status'            => 'new',
        ]);

        return redirect()->route('register.developer')->with('success', __('flash.developer_request_submitted'));
    }

    /**
     * Staff invitation — landing page.
     *
     * This used to render five hardcoded strings ("Metro Clinical Diagnostics
     * Lab", "Dr. Elizabeth Blackwell") for ANY token, including nonsense ones,
     * and its POST twin wrote nothing at all. It now resolves a real
     * FacilityStaffInvite by the sha256 of the token in the URL.
     *
     * An unknown token renders the same 'expired' page as a genuinely expired
     * one — deliberately, so the page cannot be used to test whether a token
     * exists.
     */
    public function showStaffInvite($token)
    {
        $invite = \App\Models\FacilityStaffInvite::findByToken((string) $token);

        if (! $invite) {
            return view('auth.invite', ['error' => 'expired']);
        }

        if ($reason = $invite->failureReason()) {
            return view('auth.invite', ['error' => $reason]);
        }

        $invite->loadMissing(['facility', 'role', 'inviter']);

        return view('auth.invite', [
            'token'         => $token,
            'email'         => $invite->email,
            'facility_name' => $invite->facility?->name ?? '—',
            'role_name'     => $this->inviteRoleLabel($invite->role?->name),
            'invited_by'    => $invite->inviter?->name ?? '—',
            'expiry'        => $invite->expires_at?->isoFormat('LLL') ?? '—',
        ]);
    }

    /**
     * Staff invitation — acceptance.
     *
     * Creates the account the invite describes and links it to the INVITING
     * facility. Everything that decides privilege — the facility and the role —
     * is read from the invite row, never from the submitted form, so a crafted
     * POST cannot redirect the account at another facility or escalate it to a
     * platform role.
     *
     * Single-use is enforced by re-reading the invite under a row lock inside
     * the transaction: two simultaneous submissions of the same link serialise,
     * and the second one finds accepted_at already set.
     */
    public function submitStaffInvite(Request $request, $token)
    {
        $invite = \App\Models\FacilityStaffInvite::findByToken((string) $token);

        if (! $invite || $invite->failureReason() !== null) {
            return view('auth.invite', ['error' => $invite?->failureReason() ?? 'expired']);
        }

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'password'         => ['required', 'string', 'min:8', 'same:confirm_password'],
            'confirm_password' => ['required', 'string'],
            'accept_terms'     => ['accepted'],
        ]);

        try {
            $user = DB::transaction(function () use ($invite, $validated) {
                /** @var \App\Models\FacilityStaffInvite $locked */
                $locked = \App\Models\FacilityStaffInvite::whereKey($invite->id)->lockForUpdate()->first();

                if (! $locked || ! $locked->isUsable()) {
                    throw new \RuntimeException('INVITE_NOT_USABLE');
                }

                $role = Role::find($locked->role_id);

                // Belt and braces: the allow-list is enforced when the invite is
                // issued, and again here. A role that was renamed out of the
                // allow-list between issue and acceptance must not be granted.
                if (! $role || ! in_array($role->name, \App\Models\FacilityStaffInvite::INVITABLE_ROLES, true)) {
                    throw new \RuntimeException('INVITE_ROLE_NOT_ALLOWED');
                }

                if (User::where('email', $locked->email)->exists()) {
                    throw new \RuntimeException('INVITE_EMAIL_TAKEN');
                }

                $user = User::create([
                    'name'                => $validated['name'],
                    'email'               => $locked->email,
                    'password'            => Hash::make($validated['password']),
                    // The whole point of the invite: the new account is bound to
                    // the inviting facility, so RequireFacilityContext resolves
                    // and the user never lands on the empty /select-facility page.
                    'primary_facility_id' => $locked->facility_id,
                    'status'              => 'active',
                ]);

                $user->role_id = $role->id;
                $user->email_verified_at = now();
                $user->save();

                \App\Models\FacilityRoleAssignment::create([
                    'user_id'     => $user->id,
                    'facility_id' => $locked->facility_id,
                    'role_id'     => $role->id,
                    'is_active'   => true,
                    'assigned_by' => $locked->invited_by,
                    'assigned_at' => now(),
                ]);

                $locked->forceFill([
                    'accepted_at'      => now(),
                    'accepted_user_id' => $user->id,
                ])->save();

                return $user;
            });
        } catch (\Throwable $e) {
            $reason = match ($e->getMessage()) {
                'INVITE_NOT_USABLE'       => 'used',
                'INVITE_ROLE_NOT_ALLOWED' => 'revoked',
                default                   => null,
            };

            if ($reason !== null) {
                return view('auth.invite', ['error' => $reason]);
            }

            \Illuminate\Support\Facades\Log::error('staff_invite_acceptance_failed', [
                'invite_id' => $invite->id,
                'error'     => $e->getMessage(),
            ]);

            return redirect()
                ->route('invite.accept', $token)
                ->with('error', __('team.accept_failed'));
        }

        try {
            \App\Models\AuditEvent::create([
                'actor_id'           => $user->id,
                'actor_role'         => $user->role?->name,
                'facility_id'        => $invite->facility_id,
                'action_type'        => 'facility_staff_invite_accepted',
                'resource_type'      => 'FacilityStaffInvite',
                'resource_id'        => $invite->id,
                'source_system'      => 'portal',
                'ip_address'         => $request->ip(),
                'emergency_override' => false,
                'created_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('audit_event_failed', [
                'action' => 'facility_staff_invite_accepted',
                'error'  => $e->getMessage(),
            ]);
        }

        return redirect()->route('login')->with('success', __('flash.staff_account_activated'));
    }

    /** Human label for an invited role, bilingual, with the role name as last resort. */
    private function inviteRoleLabel(?string $roleName): string
    {
        if (! $roleName) {
            return '—';
        }

        $label = __('team.roles.' . $roleName);

        return is_string($label) && $label !== 'team.roles.' . $roleName ? $label : $roleName;
    }

    /*
     * ── Password recovery — the web half ────────────────────────────────────
     *
     * All four of these methods used to be decorative. submitForgotPassword()
     * took a Request it never read, issued no token, sent no mail, and flashed
     * "instructions have been sent". submitResetPassword() took a token and a
     * password it never read, wrote nothing, and redirected to /login with
     * "your password has been securely updated". showResetPassword() rendered
     * a working form for any string in the URL. A user who forgot their
     * password was told twice that it had worked and stayed locked out for
     * good.
     *
     * The mechanism here is Laravel's own password broker, not a new one.
     * Checked 2026-09-02: password_reset_tokens is migrated (in
     * 2026_05_14_215752_alter_users_table_for_foundation), config/auth.php
     * defines the 'users' broker over it with a 60-minute expiry, and
     * App\Models\User extends Illuminate\Foundation\Auth\User, so it already
     * satisfies CanResetPassword. The broker gives all four properties this
     * flow needs without a line of custom crypto: the token is
     * hash_hmac('sha256', Str::random(40), appKey) — cryptographically
     * random; it is persisted as Hash::make($token) — never in the clear; it
     * is deleted the moment a reset succeeds — single use; and it is checked
     * against created_at + expire — time-limited. It also wraps both legs in a
     * fixed 200 ms Timebox, which is exactly the enumeration defence
     * requirement 1 asks for.
     *
     * The mobile flow (MobileAuthController + PasswordResetCode) is a
     * DIFFERENT mechanism on purpose and stays as it is: it mails a 6-digit
     * code because an in-app screen has no browser to land a link in. A link
     * is the right primitive on the web, and duplicating the mobile table here
     * would mean maintaining two hand-rolled token stores where the framework
     * already ships one.
     */

    public function showForgotPassword()
    {
        return view('auth.forgot_password');
    }

    /**
     * Step 1 — issue and mail a reset link.
     *
     * The response is deliberately constant. Whether the address is registered,
     * unregistered, or registered but inside the broker's own 60-second
     * re-request window, the caller gets the same redirect, the same status and
     * the same flash string. Reporting "no such account" here — or reporting
     * "you already asked" — hands an attacker a membership oracle for a
     * national health platform, where knowing that someone holds an account is
     * itself disclosure.
     */
    public function submitForgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:180'],
        ]);

        /*
         * Trimmed, but deliberately NOT lower-cased.
         *
         * users.email is stored exactly as it was typed at registration (see
         * submitPatientRegister) and both Auth::attempt() and the unique index
         * match it case-sensitively on PostgreSQL. Folding the case here would
         * make recovery MISS an account that login can reach — the worst
         * possible direction for this particular endpoint to be wrong in.
         */
        $email = trim($validated['email']);

        $status = Password::broker()->sendResetLink(
            ['email' => $email],
            function (User $user, string $token): string {
                try {
                    $user->notify(
                        (new PasswordResetLinkNotification($token, $this->passwordResetTtlMinutes()))
                            ->locale(app()->getLocale())
                    );
                } catch (\Throwable $e) {
                    /*
                     * A dead queue or a dead mail transport must not change the
                     * SHAPE of this response. Letting it bubble would answer
                     * 500 for a registered address and 302 for an unknown one —
                     * a cleaner enumeration oracle than any message body. The
                     * token stays valid until it expires; the user sees the
                     * same line either way and can ask again. Same posture as
                     * MobileAuthController::forgotPassword.
                     */
                    \Illuminate\Support\Facades\Log::warning('password_reset_link_dispatch_failed', [
                        'user_id' => $user->id,
                        'error'   => $e->getMessage(),
                    ]);
                }

                return Password::RESET_LINK_SENT;
            }
        );

        if ($status !== Password::RESET_LINK_SENT) {
            /*
             * Equalise the timing, not just the body.
             *
             * The registered branch pays for one bcrypt hash of the token
             * (BCRYPT_ROUNDS=12 in production, ~250-400 ms) — more than the
             * broker's 200 ms timebox can absorb — while an unregistered
             * address returns after a single indexed SELECT and gets padded to
             * 200 ms flat. That gap is a clean enumeration signal to anyone
             * with a stopwatch. Burning an equivalent hash here costs the
             * attacker the same wall-clock either way.
             */
            Hash::make(Str::random(40));
        }

        return redirect()
            ->route('password.request')
            ->with('success', __('passwords.request.sent', ['minutes' => $this->passwordResetTtlMinutes()]));
    }

    /**
     * Step 2a — render the form, but only for a link that is actually live.
     *
     * A dead token must never reach a form. Rendering one invites the user to
     * type a new password into a page that cannot save it, and then tells them
     * on submit that something went wrong — after the fact. It is also the
     * cheaper half of a guessing attack: a form for a live token and an error
     * card for a dead one is a yes/no answer served at 200 OK.
     */
    public function showResetPassword(Request $request, $token)
    {
        // Not lower-cased, for the reason given in submitForgotPassword(): the
        // address in the link is the stored one, and the match is exact.
        $email = trim((string) $request->query('email', ''));

        if (! $this->passwordResetTokenIsLive($email, (string) $token)) {
            return $this->deadResetLink();
        }

        return view('auth.reset_password', [
            'token'   => $token,
            'email'   => $email,
            'invalid' => false,
        ]);
    }

    /**
     * Step 2b — actually set the password.
     *
     * On success the account is treated as compromised until proven otherwise,
     * because that is the usual reason someone resets: every other browser
     * session is destroyed, the remember-me token is rotated so a stolen cookie
     * stops working, any mobile access tokens for the linked patient are
     * revoked (the same thing MobileAuthController::resetPassword does), the
     * change is written to audit_events, and the owner is emailed that it
     * happened.
     */
    public function submitResetPassword(Request $request, $token)
    {
        $request->validate([
            'email'    => ['required', 'string', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var \App\Models\User|null $reset */
        $reset = null;

        $status = Password::broker()->reset(
            [
                'email'                 => trim((string) $request->input('email')),
                'password'              => (string) $request->input('password'),
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token'                 => (string) $token,
            ],
            function (User $user, string $password) use ($request, &$reset): void {
                $user->forceFill([
                    // 'password' carries the 'hashed' cast on User, so this is
                    // hashed on save — never assign an already-hashed value.
                    'password'       => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Everything else that was signed in as this account is signed
                // out. SESSION_DRIVER is 'database', so the session rows ARE
                // the live sessions.
                DB::table('sessions')->where('user_id', $user->id)->delete();

                if ($user->patient_id) {
                    \App\Models\PatientAccessToken::where('patient_id', $user->patient_id)->delete();
                }

                event(new PasswordResetEvent($user));

                $reset = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET || ! $reset instanceof User) {
            // One answer for a forged token, an expired token, a spent token
            // and an address that does not exist. Anything more specific is an
            // oracle, and none of the four is recoverable from this page.
            return $this->deadResetLink();
        }

        try {
            \App\Models\AuditEvent::create([
                'actor_id'           => $reset->id,
                'actor_role'         => $reset->role?->name,
                'facility_id'        => $reset->primary_facility_id,
                'action_type'        => 'password_reset_completed',
                'resource_type'      => 'User',
                'resource_id'        => $reset->id,
                'source_system'      => 'portal',
                'ip_address'         => $request->ip(),
                'emergency_override' => false,
                'created_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('audit_event_failed', [
                'action' => 'password_reset_completed',
                'error'  => $e->getMessage(),
            ]);
        }

        try {
            $reset->notify(
                (new PasswordChangedNotification($request->ip()))
                    ->locale(app()->getLocale())
            );
        } catch (\Throwable $e) {
            // The password IS changed at this point. A failed confirmation
            // email must not turn a successful reset into an error page.
            \Illuminate\Support\Facades\Log::warning('password_changed_notification_failed', [
                'user_id' => $reset->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return redirect()->route('login')->with('success', __('passwords.reset'));
    }

    /** How long a reset link stays live, straight from the broker's own config. */
    private function passwordResetTtlMinutes(): int
    {
        $broker = (string) config('auth.defaults.passwords', 'users');

        return (int) config("auth.passwords.{$broker}.expire", 60);
    }

    /**
     * True only if this exact token is live for this exact address.
     *
     * Both halves matter: the token is stored hashed and keyed by email, so a
     * token issued for one account cannot be replayed against another simply by
     * editing the address in the link.
     */
    private function passwordResetTokenIsLive(string $email, string $token): bool
    {
        if ($email === '' || $token === '') {
            return false;
        }

        $broker = Password::broker();
        $user   = $broker->getUser(['email' => $email]);

        return $user instanceof CanResetPassword && $broker->tokenExists($user, $token);
    }

    /**
     * The one visible failure for every dead reset link.
     *
     * 410 Gone rather than 200: the page is honest to a human reader and to a
     * crawler, and it carries no form, so nothing on it can report a reset that
     * did not happen.
     */
    private function deadResetLink(): \Illuminate\Http\Response
    {
        return response()->view('auth.reset_password', [
            'token'   => null,
            'email'   => null,
            'invalid' => true,
            'ttl'     => $this->passwordResetTtlMinutes(),
        ], 410);
    }

    /*
     * ── /verify/otp — a verification screen with nothing behind it ──────────
     *
     * This trio used to be theatre. submitVerifyOtp() compared the six digits
     * against two hardcoded literals — '000000' returned "incorrect", '111111'
     * returned "expired" — and EVERY other value, including 123456 and 999999,
     * fell through to a redirect carrying flash.authentication_complete. It
     * verified nothing, consumed nothing, and told the user they were verified.
     * resendOtp() sent no code and claimed one had been sent.
     *
     * It is not wired to anything and cannot honestly be wired here. Checked
     * 2026-09-02:
     *
     *   - Nothing in the application redirects to otp.verify. The only
     *     references to the route names are the three route lines, this
     *     controller, and the view's own form action.
     *   - There is no session state naming a subject. The real second factor,
     *     /mfa/challenge, carries 'mfa.user_id' through the session and refuses
     *     to render without it; /verify/otp has no equivalent, so the page
     *     cannot even say WHO is being verified, let alone at which address.
     *   - The OTP tables that do exist — patient_otp_codes, provider_otp_codes —
     *     are keyed by phone_number and belong to the mobile API's phone login
     *     (MobileAuthController, ProviderMobileAuthController). Adopting one of
     *     them here would mean inventing an entry point, a channel, a subject
     *     and a success effect that no surrounding code specifies.
     *
     * So it fails closed. Wiring it would require guessing four decisions that
     * govern authentication, and a screen that guesses is worse than one that
     * says the channel is not available. The strings live in auth.otp_unavailable
     * so both halves of the site say the same thing.
     */

    /** The page states plainly that nothing here verifies anything. */
    public function showVerifyOtp()
    {
        return view('auth.verify_otp');
    }

    /**
     * Fail closed. No code is checked, so no success is ever reported and no
     * redirect leads anywhere privileged.
     */
    public function submitVerifyOtp(Request $request)
    {
        return $this->otpUnavailable($request);
    }

    /** Nothing is sent, so nothing may claim to have been sent. */
    public function resendOtp(Request $request)
    {
        return $this->otpUnavailable($request);
    }

    /**
     * The single refusal both OTP writes return.
     *
     * 503 for a machine (the view's resend button uses fetch() and checks
     * res.ok, so a non-2xx keeps it from announcing a code that was never
     * sent); a redirect carrying the error flash for a browser form post.
     */
    private function otpUnavailable(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'otp_channel_unavailable',
                'message' => __('auth.otp_unavailable.error'),
            ], 503);
        }

        return redirect()
            ->route('otp.verify')
            ->with('error', __('auth.otp_unavailable.error'));
    }

    public function showPendingApproval()
    {
        return view('auth.pending_approval', [
            'ref_code' => 'OPC-' . strtoupper(bin2hex(random_bytes(4))),
            'org_name' => 'St. Mary Pediatric Care Clinic',
            'submitted_date' => now()->subDays(2)->format('Y-m-d H:i')
        ]);
    }

    public function showAccountSuspended()
    {
        return view('auth.account_suspended');
    }

    /**
     * The facilities this user is actually entitled to act in.
     *
     * Returns null for the platform tier, meaning "no restriction" — platform
     * admins belong to no facility and legitimately scope themselves into any
     * of them. For everyone else it is their primary facility plus any active
     * FacilityRoleAssignment, and nothing else.
     *
     * This is the authority behind session('active_facility_id'), which more
     * than twenty controllers trust as their facility scope. Without it, the
     * selector's POST would accept any facility id the browser sent, and every
     * one of those controllers would then happily read and write another
     * hospital's data.
     */
    private function selectableFacilityIds(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        if (\App\Http\Middleware\RequirePlatformAdmin::isPlatformTier($user)) {
            return null; // unrestricted
        }

        $ids = [];

        if ($user->primary_facility_id) {
            $ids[] = $user->primary_facility_id;
        }

        if (method_exists($user, 'facilityRoleAssignments')) {
            $ids = array_merge(
                $ids,
                $user->facilityRoleAssignments()->active()->pluck('facility_id')->all()
            );
        }

        return array_values(array_unique($ids));
    }

    public function showSelectFacility()
    {
        $user     = Auth::user();
        $roleName = $user?->role?->description ?? $user?->role?->name ?? 'User';

        // Build a list of selectable facilities: exactly the ones the user is
        // entitled to (see selectableFacilityIds). Platform-tier users get the
        // full list; everyone else gets their own assignments only, so the page
        // never offers a choice the POST would refuse.
        $query = Facility::withoutGlobalScope('isolate_demo')
            ->orderBy('name');

        $allowed = $this->selectableFacilityIds($user);

        if ($allowed !== null) {
            $query->whereIn('id', $allowed);
        }

        $facilities = $query->get()->map(fn(Facility $f) => [
            'id'     => $f->id,
            'name'   => $f->name,
            'branch' => ucfirst($f->type ?? ''),
            'role'   => $roleName,
            'status' => ($f->status === 'suspended') ? 'suspended' : 'active',
        ])->values()->all();

        return view('auth.select_facility', compact('facilities'));
    }

    public function submitSelectFacility(Request $request)
    {
        $facilityId = $request->input('facility');

        if ($facilityId === 'suspended') {
            return redirect()->route('select-facility')
                ->with('error', __('onboarding.login.errors.facility_suspended'));
        }

        if (!$facilityId) {
            return redirect()->route('select-facility')
                ->with('error', __('flash.facility_select_required'));
        }

        // The chosen id has to be one the user is entitled to. Without this
        // check the selector was an open door: POST any facility uuid and
        // session('active_facility_id') became that facility, which is the
        // scope every portal controller then trusts — including staff
        // invitations, patient queues, billing and messaging.
        $allowed = $this->selectableFacilityIds(Auth::user());

        if ($allowed !== null && ! in_array($facilityId, $allowed, true)) {
            return redirect()->route('select-facility')
                ->with('error', __('flash.facility_select_not_allowed'));
        }

        // ✅ Save the chosen facility to session so RequireFacilityContext passes
        session(['active_facility_id' => $facilityId]);

        $url = Auth::check()
            ? app(DashboardProfileService::class)->landingUrlForCurrent()
            : route('login');

        return redirect($url)->with('success', __('flash.clinical_session_established'));
    }
}
