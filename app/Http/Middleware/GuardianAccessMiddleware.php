<?php
namespace App\Http\Middleware;

use App\Models\FamilyLink;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Turns session('guardian_viewing_patient_id') into two request attributes:
 *
 *   viewing_patient  — the dependent whose record the page must show
 *   guardian_link    — the active FamilyLink that authorises it
 *
 * The check is deliberately strict: an ACTIVE link owned by the signed-in
 * user, not past its age-transition expiry, whose dependent is visible under
 * the current demo scope. Anything else clears the session and sends the
 * guardian back to the dashboard — never a 500, never someone else's record.
 *
 * bind() is public and static so the two pages that live OUTSIDE the
 * `guardian.context` route group (the dashboard and the temporary-QR
 * endpoint, both declared in routes/web.php) can apply exactly the same rule
 * without duplicating it.
 */
class GuardianAccessMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! self::bind($request)) {
            return redirect()->route('portals.patient')
                ->with('error', __('family.switch_no_longer_active'));
        }

        return $next($request);
    }

    /**
     * Resolve the guardian session into request attributes.
     *
     * Returns true when there is nothing to bind (no session, anonymous) or
     * when the attributes were set; false when the session pointed at a link
     * that is no longer usable — the session key is cleared in that case so
     * the caller can redirect once without looping.
     */
    public static function bind(Request $request): bool
    {
        $patientId = session('guardian_viewing_patient_id');

        if (! $patientId || ! Auth::check()) {
            return true;
        }

        // Already bound earlier in the same request (middleware + controller).
        if ($request->attributes->has('viewing_patient')) {
            return true;
        }

        $link = FamilyLink::active()
            ->where('guardian_user_id', Auth::id())
            ->where('dependent_patient_id', $patientId)
            ->with('dependentPatient')
            ->first();

        // Reject if: no link found, age-expired, or dependent patient record missing
        if (! $link || $link->isExpiredByAge() || ! $link->dependentPatient) {
            session()->forget('guardian_viewing_patient_id');
            return false;
        }

        $request->attributes->set('guardian_link', $link);
        $request->attributes->set('viewing_patient', $link->dependentPatient);

        return true;
    }

    /**
     * Only `full` grants writes. `read_only` is the canonical read level, and
     * any legacy value that still sits in the column (`view_only`, `guardian`
     * from early mobile builds) is treated as read-only rather than as full —
     * the safe direction for a value the web never wrote.
     */
    public static function isReadOnly(?FamilyLink $link): bool
    {
        return $link !== null && $link->access_level !== 'full';
    }
}
