<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Identity\HealthIdGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The second half of patient sign-up, moved to after login.
 *
 * Registration now takes an email and a password and nothing else. Everything
 * that identifies a person — name, date of birth, sex, phone, emergency
 * contact — is collected here, once they are inside their own account.
 *
 * The Health ID is minted at the END of this step, not at sign-up. A Health ID
 * asserts that a specific human exists; issuing one against a blank record
 * would seed the Master Patient Index with rows nothing can be matched
 * against, which the no-probabilistic-auto-merge invariant exists to prevent.
 */
class PatientProfileCompletionController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();

        // Already has an identity — nothing to complete.
        if ($user->patient_id) {
            return redirect()->route('portals.patient');
        }

        return view('portals.patient.complete_profile');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->patient_id) {
            return redirect()->route('portals.patient');
        }

        $data = $request->validate([
            'first_name'             => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'middle_name'            => 'nullable|string|max:100',
            'dob'                    => 'required|date|before:today',
            // Two sexes, matching the CHECK constraint on patients.sex.
            'sex'                    => 'required|in:male,female',
            'phone'                  => 'required|string|max:30',
            'city'                   => 'nullable|string|max:80',
            'national_id'            => 'nullable|string|max:60',
            'emergency_name'         => 'nullable|string|max:120',
            'emergency_relationship' => 'nullable|string|max:80',
            'emergency_phone'        => 'nullable|string|max:30',
        ]);

        $patient = DB::transaction(function () use ($data, $user) {
            // The country code is the Health ID prefix. It is a deployment
            // property of the platform, not something a patient types into a
            // box — a free-text "Cameroon" here used to mint a CA-HID-… ID.
            $countryCode = strtoupper((string) config('health_id.default_country', 'CM'));
            $healthId    = app(HealthIdGeneratorService::class)->generate($countryCode);

            $patient = Patient::create([
                'health_id'          => $healthId,
                'first_name'         => $data['first_name'],
                'last_name'          => $data['last_name'],
                'middle_name'        => $data['middle_name'] ?? null,
                'date_of_birth'      => $data['dob'],
                'sex'                => $data['sex'],
                'phone_number'       => $data['phone'],
                'email'              => $user->email,
                'national_id_number' => $data['national_id'] ?? null,
                'country_code'       => $countryCode,
                'address'            => trim((string) ($data['city'] ?? '')) ?: null,
                'emergency_contact'  => ($data['emergency_name'] ?? null)
                    ? json_encode([
                        'name'         => $data['emergency_name'],
                        'relationship' => $data['emergency_relationship'] ?? null,
                        'phone'        => $data['emergency_phone'] ?? null,
                    ])
                    : null,
                // Provisional until a facility verifies them in person; that is
                // what the verification workflow is for.
                'identity_status'     => 'provisional',
                'verification_status' => 'unverified',
            ]);

            $user->forceFill([
                'patient_id' => $patient->id,
                'name'       => $data['first_name'] . ' ' . $data['last_name'],
            ])->save();

            return $patient;
        });

        // A referral code captured at sign-up is applied here, where the patient
        // finally exists. Never allowed to break the flow.
        $refCode = $request->session()->pull('pending_referral_code');
        if ($refCode) {
            try {
                $rewards = app(\App\Modules\Subscription\Services\ReferralRewardService::class);
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

        return redirect()->route('portals.patient')
            ->with('success', __('onboarding.patient.profile_complete', ['health_id' => $patient->health_id]));
    }
}
