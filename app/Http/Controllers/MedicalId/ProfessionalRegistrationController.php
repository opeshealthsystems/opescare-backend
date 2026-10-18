<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\ProviderCredential;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Public self-service registration for a health professional. Creates the
 * account, a staff record, and (optionally) a first qualification — all
 * UNVERIFIED. The professional can sign in immediately to complete their
 * profile, but has no facility context until an admin verifies them and a
 * facility attaches them, so login lands them on /professional/profile.
 *
 * The other way in — a facility admin inviting staff — already exists at
 * /facility/team (FacilityStaffController); this is the complementary
 * self-initiated path.
 */
class ProfessionalRegistrationController extends Controller
{
    /** Professions offered on the form → the role name each maps to. */
    public const PROFESSIONS = [
        'doctor'       => 'doctor',
        'nurse'        => 'nurse',
        'midwife'      => 'midwife',
        'pharmacist'   => 'pharmacist',
        'labtech'      => 'labtech',
        'receptionist' => 'receptionist',
    ];

    public function create(): View
    {
        return view('auth.register-professional', [
            'professions' => array_keys(self::PROFESSIONS),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:150',
            'email'             => 'required|email|max:255|unique:users,email',
            'phone'             => 'required|string|max:30',
            'profession'        => 'required|in:' . implode(',', array_keys(self::PROFESSIONS)),
            'password'          => 'required|string|min:8|confirmed',
            // Optional first qualification — most will have one to hand.
            'issuing_body'      => 'nullable|required_with:credential_number|string|max:255',
            'credential_number' => 'nullable|string|max:100',
        ]);

        $role = Role::where('name', self::PROFESSIONS[$validated['profession']])->first();
        abort_if($role === null, 422, 'That profession is not available.');

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role_id'           => $role->id,
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        [$first, $last] = $this->splitName($validated['name']);
        StaffProfile::create([
            'user_id'    => $user->id,
            // No facility: this person registered themselves and no facility has
            // admitted them yet. They ask to join one through
            // /professional/facilities, and the approving facility writes this
            // column. Omitting it used to violate a NOT NULL constraint and 500
            // the whole signup.
            'facility_id' => null,
            'first_name' => $first,
            'last_name'  => $last,
            'email'      => $validated['email'],
            'phone'      => $validated['phone'],
            'job_title'  => $role->description,
            'staff_category' => $validated['profession'] === 'receptionist' ? 'administrative' : 'clinical',
            'status'     => 'active',
        ]);

        if (! empty($validated['credential_number'])) {
            ProviderCredential::create([
                'provider_id'       => $user->id,
                'credential_type'   => 'medical_license',
                'issuing_body'      => $validated['issuing_body'],
                'credential_number' => $validated['credential_number'],
                'issued_date'       => now()->toDateString(),
                'status'            => 'active',
            ]);
        }

        Auth::login($user);
        // ASVS V3.2.1: new session id on authentication (session fixation).
        $request->session()->regenerate();

        return redirect()->route('professional.profile')
            ->with('success', __('professional.registered'));
    }

    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
