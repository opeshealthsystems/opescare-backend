<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The second half of caregiver sign-up.
 *
 * Sign-up stops at an email and a password. This is where a guardian says who
 * they are and who they are asking to act for — the details a reviewer needs.
 *
 * It deliberately does NOT grant anything. Guardian access to another person's
 * record is a consent-bearing decision under Cameroon Law No. 2010/012, so the
 * submission lands in the leads queue for institutional verification, exactly
 * as the sign-up page has always promised. A guardian_relationships row is
 * written by the reviewer, once the dependant has been identified — that table
 * requires a patient_id, which does not exist yet at this point.
 */
class GuardianProfileCompletionController extends Controller
{
    public function show(Request $request)
    {
        if (Auth::user()->profile_completed_at) {
            return redirect()->route('portals.guardian.pending');
        }

        return view('portals.guardian.complete_profile');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->profile_completed_at) {
            return redirect()->route('portals.guardian.pending');
        }

        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'phone'              => 'required|string|max:30',
            'dob'                => 'nullable|date|before:today',
            'preferred_language' => 'nullable|string|max:10',
            'dep_name'           => 'required|string|max:200',
            'dep_relationship'   => 'required|string|max:80',
            'dep_dob'            => 'nullable|date|before:today',
            // Two sexes, matching the CHECK constraint on patients.sex.
            'dep_sex'            => 'nullable|in:male,female',
            'dep_health_id'      => 'nullable|string|max:40',
            'access_reason'      => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($data, $user) {
            Lead::create([
                'name'              => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'             => $user->email,
                'phone'             => $data['phone'],
                'organization_name' => null,
                'organization_type' => 'other',
                'message'           => $this->summaryForReviewer($data),
                'source'            => 'guardian_signup',
                'status'            => 'new',
            ]);

            $user->forceFill([
                'name'                 => trim($data['first_name'] . ' ' . $data['last_name']),
                'profile_completed_at' => now(),
            ])->save();
        });

        return redirect()->route('portals.guardian.pending')
            ->with('success', __('onboarding.guardian.success'));
    }

    /** Where a guardian waits until a facility verifies the relationship. */
    public function pending(Request $request)
    {
        if (! Auth::user()->profile_completed_at) {
            return redirect()->route('portals.guardian.complete-profile');
        }

        return view('portals.guardian.pending');
    }

    /**
     * Everything a reviewer needs, as text.
     *
     * Never includes the password — that belongs to the account, which already
     * exists by this point, and has no business being restated here.
     */
    private function summaryForReviewer(array $data): string
    {
        $lines = [
            'Dependant: ' . $data['dep_name'],
            'Relationship: ' . $data['dep_relationship'],
        ];

        foreach ([
            'Dependant DOB'       => $data['dep_dob'] ?? null,
            'Dependant sex'       => $data['dep_sex'] ?? null,
            'Dependant Health ID' => $data['dep_health_id'] ?? null,
            'Guardian DOB'        => $data['dob'] ?? null,
            'Preferred language'  => $data['preferred_language'] ?? null,
        ] as $label => $value) {
            if (! empty($value)) {
                $lines[] = $label . ': ' . $value;
            }
        }

        if (! empty($data['access_reason'])) {
            $lines[] = '';
            $lines[] = 'Reason given: ' . $data['access_reason'];
        }

        return implode("\n", $lines);
    }
}
