<?php

namespace App\Http\Middleware;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Services\Family\DependentAccessAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Act-as-dependent context for the mobile patient API.
 *
 * Runs right after AuthenticateMobilePatient on every `auth.mobile` route.
 * When the request carries `X-OpesCare-Dependent: <patient uuid>`, the caller
 * must hold an ACTIVE, non-age-expired FamilyLink from their own user
 * (`patient_user_id`) to that patient. If they do, the request's `patient_id`
 * attribute is swapped to the dependent, so every record endpoint — the card,
 * timeline, immunisations, appointments, prescriptions — answers for the
 * dependent without a single controller change. `patient_user_id` is left as
 * the GUARDIAN: whatever is written is attributed to the person acting.
 *
 *   - no header            → nothing happens
 *   - header, no such link → 403 dependent_forbidden (never 404: the answer
 *                            must not say whether the patient exists)
 *   - safe method (GET)    → allowed on any active link
 *   - write (POST/PATCH/…) → only when the link is `full`; `read_only` → 403
 *   - account-level paths  → 403 whatever the level: settings, push tokens,
 *                            account deletion and the family screen belong to
 *                            the signed-in account, not to the record.
 *
 * Every request served for a dependent is written to the dependent's access
 * log with the guardian as actor.
 */
class ActAsDependent
{
    public const HEADER = 'X-OpesCare-Dependent';

    public const ERROR_CODE = 'dependent_forbidden';

    /**
     * URI prefixes (after `api/mobile/`) that stay the caller's own even under
     * a full-access link.
     *
     * @var list<string>
     */
    public const OWN_ACCOUNT_PREFIXES = [
        'account',
        'auth',
        'family',
        'push-tokens',
        'settings',
        'offline',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $dependentId = trim((string) $request->header(self::HEADER, ''));

        if ($dependentId === '') {
            return $next($request);
        }

        $guardianUserId = $request->attributes->get('patient_user_id');

        if (! $guardianUserId || ! Str::isUuid($dependentId)) {
            return $this->forbidden();
        }

        $link = FamilyLink::query()
            ->active()
            ->where('guardian_user_id', $guardianUserId)
            ->where('dependent_patient_id', $dependentId)
            ->first();

        if (! $link || ! $link->grantsAccess()) {
            return $this->forbidden();
        }

        // The dependent must be visible under the current data partition; a
        // link across the demo boundary grants nothing.
        if (! Patient::query()->whereKey($dependentId)->exists()) {
            return $this->forbidden();
        }

        if ($this->isOwnAccountPath($request)) {
            return $this->forbidden();
        }

        if (! $request->isMethodSafe() && ! $link->isFullAccess()) {
            return $this->forbidden();
        }

        $request->attributes->set('patient_id', $link->dependent_patient_id);
        $request->attributes->set('acting_for_dependent', $link->id);
        $request->attributes->set('guardian_user_id', $guardianUserId);
        $request->attributes->set('family_link', $link);

        $response = $next($request);

        if ($response->isSuccessful()) {
            DependentAccessAudit::record(
                link: $link,
                guardianUserId: $guardianUserId,
                dataCategory: $this->dataCategory($request),
                resourceType: 'MobileRequest',
                resourceId: null,
                accessType: $request->isMethodSafe() ? 'read' : 'write',
            );
        }

        return $response;
    }

    private function isOwnAccountPath(Request $request): bool
    {
        $rest = $this->pathAfterMobilePrefix($request);

        foreach (self::OWN_ACCOUNT_PREFIXES as $prefix) {
            if ($rest === $prefix || str_starts_with($rest, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    /** First path segment after `api/mobile/` — `immunizations`, `appointments`, … */
    private function dataCategory(Request $request): string
    {
        $rest    = $this->pathAfterMobilePrefix($request);
        $segment = strtok($rest, '/') ?: 'record';

        return Str::limit(Str::slug($segment, '_'), 60, '');
    }

    private function pathAfterMobilePrefix(Request $request): string
    {
        $path = trim($request->path(), '/');

        return str_starts_with($path, 'api/mobile/') ? substr($path, strlen('api/mobile/')) : $path;
    }

    private function forbidden(): Response
    {
        return response()->json([
            'message'    => __('family_api.dependent_forbidden'),
            'error_code' => self::ERROR_CODE,
        ], 403);
    }
}
