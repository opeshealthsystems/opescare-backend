<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\ProviderCredential;
use App\Models\StaffProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Self-service profile + qualifications for facility professionals (doctors,
 * nurses, receptionists, pharmacists, …). Lives at /professional/* so it is
 * reachable WITHOUT a facility context — a self-registered professional who is
 * still pending verification, and so not yet attached to a facility, must still
 * be able to complete their profile and add the qualifications an admin will
 * review. Verification state is read from ProviderCredential::verified_at.
 */
class ProfessionalProfileController extends Controller
{
    /** Credential types offered on the qualifications form. */
    public const CREDENTIAL_TYPES = [
        'medical_license',
        'specialist_cert',
        'board_certification',
        'cpr_cert',
        'hospital_privilege',
        'other',
    ];

    public function profile(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        // Patients and non-staff have their own areas; keep this one to staff.
        if (! $user->isProfessional()) {
            return redirect('/');
        }

        $profile     = $this->resolveProfile($user);
        $credentials = $user->providerCredentials()
            ->orderByRaw('verified_at IS NULL DESC')
            ->orderBy('expiry_date')
            ->get();

        return view('portals.professional.profile', [
            'user'            => $user,
            'profile'         => $profile,
            'credentials'     => $credentials,
            'credentialTypes' => self::CREDENTIAL_TYPES,
            'verifiedCount'   => $credentials->whereNotNull('verified_at')->count(),
            'pendingCount'    => $credentials->whereNull('verified_at')->count(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfessional(), 403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'nullable|string|max:30',
            'job_title'  => 'nullable|string|max:120',
            'department' => 'nullable|string|max:120',
        ]);

        $this->resolveProfile($user)->update($validated);

        return redirect()->route('professional.profile')
            ->with('success', __('professional.profile_saved'));
    }

    public function storeQualification(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfessional(), 403);

        $validated = $request->validate([
            'credential_type'   => 'required|in:' . implode(',', self::CREDENTIAL_TYPES),
            'issuing_body'      => 'required|string|max:255',
            'credential_number' => 'required|string|max:100',
            'issued_date'       => 'required|date|before_or_equal:today',
            'expiry_date'       => 'nullable|date|after:issued_date',
        ]);

        // A newly added credential is 'active' but UNVERIFIED — verified_at stays
        // null until an admin reviews it, which is what drives the badge.
        ProviderCredential::create(array_merge($validated, [
            'provider_id' => $user->id,
            'status'      => 'active',
        ]));

        return redirect()->route('professional.profile')
            ->with('success', __('professional.qualification_added'));
    }

    public function destroyQualification(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isProfessional(), 403);

        $credential = ProviderCredential::where('id', $id)
            ->where('provider_id', $user->id)
            ->firstOrFail();

        // Once verified, a credential is an official record — a professional may
        // not silently delete it; that is an admin action (suspend/revoke).
        if ($credential->verified_at !== null) {
            return redirect()->route('professional.profile')
                ->with('error', __('professional.qualification_locked'));
        }

        $credential->delete();

        return redirect()->route('professional.profile')
            ->with('success', __('professional.qualification_removed'));
    }

    /**
     * Every professional has exactly one staff record. Create it lazily on first
     * visit, seeded from the account so the form is never empty.
     *
     * `facility_id` is deliberately allowed to be null here. A clinician who
     * registered themselves has no facility until one admits them, and that is
     * precisely the person this page exists for — seeding the row with an
     * arbitrary facility to satisfy a constraint would publish them as that
     * facility's staff. The column was NOT NULL until
     * 2026_09_05_120000_make_staff_profiles_facility_id_nullable, which is why
     * this used to raise SQLSTATE 23502 and 500 the page.
     */
    private function resolveProfile($user): StaffProfile
    {
        [$first, $last] = $this->splitName($user->name);

        return StaffProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                // May be null — see above.
                'facility_id'  => $user->primary_facility_id,
                'first_name'   => $first,
                'last_name'    => $last,
                'email'        => $user->email,
                'job_title'    => $user->role?->description,
                'staff_category' => $this->staffCategory($user),
                'status'       => 'active',
            ]
        );
    }

    /** staff_profiles.staff_category is NOT NULL — map the role tier onto it. */
    private function staffCategory($user): string
    {
        return match ($user->role?->dashboard_profile_key) {
            'receptionist', 'front_desk'                          => 'administrative',
            'facility_admin', 'facility_executive', 'facility_ceo'=> 'management',
            default                                               => 'clinical',
        };
    }

    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
