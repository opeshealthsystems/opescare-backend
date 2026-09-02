<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A patient account with no identity yet is sent to finish signing up.
 *
 * Registration deliberately stops at email + password, so between sign-up and
 * profile completion a user exists with patient_id = null. Almost every patient
 * page assumes a Patient behind it, so without this they would meet a series of
 * empty screens and null errors rather than one clear next step.
 *
 * Only patient-role users are affected. Staff, admins and partners have no
 * patient record by design and must pass straight through.
 */
class RequireCompletePatientProfile
{
    /** Reachable while the profile is still incomplete. */
    private const ALLOWED = [
        'portals/patient/complete-profile',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || $user->patient_id) {
            return $next($request);
        }

        // Only gate accounts that are supposed to have a patient identity.
        if (($user->role?->name) !== 'patient') {
            return $next($request);
        }

        foreach (self::ALLOWED as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return $next($request);
            }
        }

        return redirect()->route('portals.patient.complete-profile');
    }
}
