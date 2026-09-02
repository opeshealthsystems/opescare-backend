<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Mail\OpesCareNotificationMail;
use App\Mail\PasswordResetCodeMail;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\PatientOtpCode;
use App\Models\PasswordResetCode;
use App\Models\Role;
use App\Models\User;
use App\Modules\Notifications\Services\SmsNotificationService;
use App\Modules\Subscription\Services\ReferralRewardService;
use App\Services\Identity\HealthIdGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function __construct(private SmsNotificationService $sms) {}

    /**
     * Step 1: Patient provides phone + PIN.
     * If PIN matches, we send an OTP.
     * If patient has no PIN yet, we set it on first login (bootstrap flow).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number'  => 'required|string',
            'pin'           => 'required|string|min:4|max:8',
            'date_of_birth' => 'sometimes|date_format:Y-m-d',
        ]);

        // phone_number is stored encrypted; use the keyed hash for DB lookup
        $patient = Patient::findByPhone($request->phone_number);

        if (!$patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        if (is_null($patient->pin_hash)) {
            // Bootstrap: require date_of_birth to verify identity before setting PIN
            if (!$request->has('date_of_birth') || $request->date_of_birth !== $patient->date_of_birth?->format('Y-m-d')) {
                return response()->json(['message' => __('api.identity_verification_required')], 422);
            }
            $patient->update(['pin_hash' => Hash::make($request->pin)]);
        } elseif (!Hash::check($request->pin, $patient->pin_hash)) {
            return response()->json(['message' => __('api.invalid_credentials')], 401);
        }

        $this->sendOtp($patient);

        return response()->json(['message' => __('api.otp_sent')], 200);
    }

    /**
     * Resend an OTP during the phone + PIN login flow.
     *
     * POST /mobile/auth/otp/resend
     * Body: { phone_number }
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $patient = Patient::findByPhone($request->phone_number);

        if (! $patient || is_null($patient->pin_hash)) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        PatientOtpCode::where('phone_number', $patient->phone_number)
            ->whereNull('used_at')
            ->update(['used_at' => Carbon::now()]);

        $this->sendOtp($patient);

        return response()->json(['message' => __('api.otp_resent')], 200);
    }

    /**
     * Step 2: Patient provides phone + OTP.
     * Issues a 24-hour access token on success.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp'          => 'required|string|size:6',
        ]);

        // phone_number is stored encrypted; use the keyed hash for DB lookup
        $patient = Patient::findByPhone($request->phone_number);

        if (!$patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        // Find the most recent unused, unexpired OTP for this phone
        $otpRecord = PatientOtpCode::where('phone_number', $request->phone_number)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($request->otp, $otpRecord->code_hash)) {
            return response()->json(['message' => __('api.otp_invalid')], 401);
        }

        // Mark OTP as used
        $otpRecord->update(['used_at' => Carbon::now()]);

        // Issue a 30-day access token
        $rawToken = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id'   => $patient->id,
            'token_hash'   => Hash::make($rawToken),
            'token_prefix' => substr($rawToken, 0, 12),
            'expires_at'   => Carbon::now()->addDays(30),
        ]);

        return response()->json([
            'status'       => 'authenticated',
            'access_token' => $rawToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 2592000,
            'patient_id'   => $patient->id,
        ], 200);
    }

    /**
     * Email + password login — uses the same credentials as the patient portal.
     * Finds the patient record by email match, verifies the portal password,
     * then issues a 24-hour mobile access token directly (no OTP step).
     *
     * POST /mobile/auth/login-email
     * Body: { email, password }
     */
    public function loginWithCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email|max:180',
            'password' => 'required|string|min:4',
        ]);

        // Step 1: Verify credentials against the users table (same as web portal)
        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json(['message' => __('api.invalid_email_or_password')], 401);
        }

        $user = Auth::user();
        Auth::logout(); // We only used Auth to verify — mobile uses its own token system

        // Step 2: Find the matching patient record by email
        $patient = Patient::where('email', $request->email)->first();

        if (! $patient) {
            return response()->json([
                'message' => __('api.no_patient_record_contact'),
            ], 404);
        }

        // Step 3: Issue a 30-day mobile access token
        $rawToken = 'pat_' . Str::random(40);

        PatientAccessToken::create([
            'patient_id'   => $patient->id,
            'token_hash'   => Hash::make($rawToken),
            'token_prefix' => substr($rawToken, 0, 12),
            'expires_at'   => Carbon::now()->addDays(30),
        ]);

        return response()->json([
            'status'       => 'authenticated',
            'access_token' => $rawToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 2592000,
            'patient_id'   => $patient->id,
        ], 200);
    }

    /**
     * Patient self-registration — native equivalent of the web
     * `/signup/patient` form (PublicPageController::submitPatientRegister),
     * exposed as JSON for the mobile app. Creates the Patient (provisional /
     * unverified — identity is confirmed in person at a facility, same as
     * web signup) and a linked portal User, then issues a mobile access
     * token directly so the app can sign the patient in without a second
     * login round-trip.
     *
     * POST /mobile/auth/register
     * Body: { first_name, last_name, dob, sex, phone, email?, password,
     *         password_confirmation, emergency_name, emergency_relationship,
     *         emergency_phone, country?, ref? }
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'dob'                    => 'required|date|before:today',
            'sex'                    => 'required|in:male,female',
            'phone'                  => 'required|string|max:30',
            'email'                  => 'nullable|email|max:180|unique:users,email',
            'country'                => 'nullable|string|max:80',
            'emergency_name'         => 'required|string|max:120',
            'emergency_relationship' => 'required|string|max:80',
            'emergency_phone'        => 'required|string|max:30',
            'password'               => 'required|string|min:8|confirmed',
        ]);

        // phone_number is stored encrypted (see Patient::setPhoneNumberAttribute), so
        // uniqueness can't be checked via a `unique:patients,phone_number` validation
        // rule — it has to go through the keyed-hash lookup like every other phone match.
        if (Patient::findByPhone($data['phone'])) {
            return response()->json(['message' => __('api.phone_already_registered')], 409);
        }

        // Duplicate name+dob check (not a hard block on the web form either — surfaces
        // as a conflict so the app can point the patient at existing-Health-ID recovery
        // instead of silently creating a second identity for the same person).
        //
        // date_of_birth is stored via Laravel's encrypter (random IV per value, see
        // Patient::getDateOfBirthAttribute), so a raw `where('date_of_birth', ...)`
        // comparison against ciphertext can never match — narrow by name in SQL, then
        // decrypt each same-name candidate's DOB in PHP to compare.
        $duplicate = Patient::where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->get()
            ->contains(fn (Patient $candidate) => $candidate->date_of_birth?->toDateString() === $data['dob']);

        if ($duplicate) {
            return response()->json(['message' => __('flash.patient_identity_duplicate')], 409);
        }

        $countryCode = strtoupper(substr($data['country'] ?? 'CM', 0, 2));

        [$patient, $rawToken] = DB::transaction(function () use ($data, $countryCode) {
            $healthIdSvc = app(HealthIdGeneratorService::class);

            // Atomic generate+insert: the callback runs inside the retry loop, so a
            // Health ID collision (UniqueConstraintViolationException) is retried with
            // a fresh candidate rather than racing a separate exists()-then-create().
            $patient = $healthIdSvc->generate($countryCode, function (string $healthId) use ($data, $countryCode) {
                return Patient::create([
                    'health_id'           => $healthId,
                    'first_name'          => $data['first_name'],
                    'last_name'           => $data['last_name'],
                    'date_of_birth'       => $data['dob'],
                    'sex'                 => $data['sex'],
                    'phone_number'        => $data['phone'],
                    'email'               => $data['email'] ?? null,
                    'country_code'        => $countryCode,
                    'emergency_contact'   => json_encode([
                        'name'         => $data['emergency_name'],
                        'relationship' => $data['emergency_relationship'],
                        'phone'        => $data['emergency_phone'],
                    ]),
                    'identity_status'     => 'provisional',
                    'verification_status' => 'unverified',
                ]);
            });

            // Create the linked portal user (same credentials work in the web portal).
            $role  = Role::where('name', 'patient')->first();
            $email = $data['email'] ?? ($data['phone'] . '@patients.opescare.local');

            $user = User::create([
                'name'       => $data['first_name'] . ' ' . $data['last_name'],
                'email'      => $email,
                'password'   => Hash::make($data['password']),
                'patient_id' => $patient->id,
                'status'     => 'pending',
            ]);

            if ($role) {
                $user->role_id = $role->id;
                $user->save();
            }

            // Issue a 30-day mobile access token so the app can sign the patient
            // straight in — same token shape as loginWithCredentials()/verifyOtp().
            $rawToken = 'pat_' . Str::random(40);
            PatientAccessToken::create([
                'patient_id'   => $patient->id,
                'token_hash'   => Hash::make($rawToken),
                'token_prefix' => substr($rawToken, 0, 12),
                'expires_at'   => Carbon::now()->addDays(30),
            ]);

            return [$patient, $rawToken];
        });

        // "Refer & Earn" capture — MUST NEVER break signup. Wrapped so any failure
        // (bad code, missing plan, DB error) is logged and swallowed; the new
        // patient still completes registration successfully. Mirrors
        // PublicPageController::submitPatientRegister.
        $refCode = trim((string) $request->input('ref', ''));
        if ($refCode !== '') {
            try {
                $rewards = app(ReferralRewardService::class);
                $invite  = $rewards->recordSignup($patient, $refCode);
                if ($invite !== null) {
                    $rewards->grantRewards($invite);
                }
            } catch (\Throwable $e) {
                Log::error('referral_signup_capture_failed', [
                    'patient_id' => $patient->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // Welcome email — best-effort, never blocks the response the app is waiting on.
        if ($patient->email) {
            try {
                Mail::to($patient->email)->queue(new OpesCareNotificationMail(
                    mailSubject: 'Welcome to OpesCare — Your Health ID is ready',
                    bodyText: "Hello {$patient->first_name},\n\nYour OpesCare account has been created.\nYour Health ID: {$patient->health_id}\n\nPlease visit a registered facility to complete identity verification.\n\nOpesCare Team",
                ));
            } catch (\Throwable $e) {
                Log::warning('registration_welcome_email_failed', [
                    'patient_id' => $patient->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status'       => 'authenticated',
            'access_token' => $rawToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 2592000,
            'patient_id'   => $patient->id,
            'health_id'    => $patient->health_id,
        ], 201);
    }

    /**
     * Refresh an existing token, issuing a new 30-day token.
     * Accepts both valid and recently-expired tokens (up to 7 days grace).
     *
     * POST /mobile/auth/refresh
     * Body: { token } or Authorization: Bearer <token>
     */
    public function refresh(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken() ?? $request->input('token');

        if (!$bearer) {
            return response()->json(['message' => __('api.no_token_provided')], 401);
        }

        $prefix = substr($bearer, 0, 12);

        // Allow tokens expired within the last 7 days (grace period for refresh)
        $token = PatientAccessToken::where('token_prefix', $prefix)
            ->where('expires_at', '>', Carbon::now()->subDays(7))
            ->first();

        if (!$token || !Hash::check($bearer, $token->token_hash)) {
            return response()->json(['message' => __('api.token_too_old')], 401);
        }

        // Revoke old token
        $token->delete();

        // Issue a fresh 30-day token
        $rawToken = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id'   => $token->patient_id,
            'token_hash'   => Hash::make($rawToken),
            'token_prefix' => substr($rawToken, 0, 12),
            'expires_at'   => Carbon::now()->addDays(30),
        ]);

        return response()->json([
            'status'       => 'refreshed',
            'access_token' => $rawToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 2592000,
            'patient_id'   => $token->patient_id,
        ], 200);
    }

    /**
     * Step 1 of the forgot-password flow: patient requests a reset code by
     * email. Matches the same `users` table credentials that login-email
     * checks. Always responds 200 with a generic message regardless of
     * whether the email is registered, to avoid account enumeration.
     *
     * POST /mobile/auth/forgot-password
     * Body: { email }
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:180',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Invalidate any still-live codes for this email before issuing a new one.
            PasswordResetCode::where('email', $user->email)
                ->whereNull('used_at')
                ->update(['used_at' => Carbon::now()]);

            PasswordResetCode::create([
                'email'      => $user->email,
                'code_hash'  => Hash::make($code),
                'expires_at' => Carbon::now()->addMinutes(15),
            ]);

            try {
                Mail::to($user->email)->send(new PasswordResetCodeMail($code, $user->email));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('OpesCare password reset email delivery failed', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['message' => __('api.password_reset_code_sent')], 200);
    }

    /**
     * Step 2: patient submits the emailed code + a new password. On success,
     * every existing mobile access token for the linked patient is revoked
     * so a stolen device session cannot outlive the password change.
     *
     * POST /mobile/auth/reset-password
     * Body: { email, code, password, password_confirmation }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email|max:180',
            'code'     => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        $codeRecord = PasswordResetCode::where('email', $request->email)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$user || !$codeRecord || !Hash::check($request->code, $codeRecord->code_hash)) {
            return response()->json(['message' => __('api.password_reset_code_invalid')], 401);
        }

        $codeRecord->update(['used_at' => Carbon::now()]);

        $user->password = $request->password; // 'hashed' cast on User::$casts
        $user->save();

        if ($user->patient_id) {
            PatientAccessToken::where('patient_id', $user->patient_id)->delete();
        }

        return response()->json(['message' => __('api.password_reset_success')], 200);
    }

    private function sendOtp(Patient $patient): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PatientOtpCode::create([
            'phone_number' => $patient->phone_number,
            'code_hash'    => Hash::make($otp),
            'expires_at'   => Carbon::now()->addMinutes(10),
        ]);

        try {
            $this->sms->send(
                $patient->phone_number,
                "Your OpesCare verification code is: {$otp}. Valid for 10 minutes."
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('OpesCare SMS delivery failed', [
                'phone' => $patient->phone_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
