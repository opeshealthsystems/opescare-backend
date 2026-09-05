<?php

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * The four things every patient WRITE endpoint has to get right, in one place
 * so neither controller can get one of them right and the other wrong.
 *
 *   1. Whose record is this?      resolvePatientId()
 *   2. What language do we speak? resolveLocale()
 *   3. How do we refuse?          refuse()
 *   4. Who did what, when?        audit()
 */
trait InteractsWithPatientWrites
{
    /**
     * The authenticated patient, and only ever the authenticated patient.
     *
     * `patient_id` is read from `$request->attributes` — where
     * App\Http\Middleware\AuthenticateMobilePatient put it after verifying the
     * bearer token — and from NOWHERE else. Not the body, not the query string,
     * not a header, not the session, and with no fallback: a request that
     * somehow reaches here without an authenticated patient is refused, never
     * defaulted.
     *
     * A `patient_id` in the request body is therefore not "overridden" — it is
     * never read at all. The validators in these controllers do not declare the
     * key, so it is not even in `$validated`.
     */
    protected function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');

        abort_if(! is_string($patientId) || $patientId === '', 401, 'Unauthenticated.');

        return $patientId;
    }

    /**
     * The user account behind the patient's token, when there is one. Used for
     * attribution in the audit trail only — never for scoping, which is always
     * the patient id above.
     */
    protected function resolvePatientUserId(Request $request): ?string
    {
        $userId = $request->attributes->get('patient_user_id');

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    /**
     * EN or FR for this request's messages.
     *
     * App\Http\Middleware\SetLocale runs on the `web` stack and reads the
     * session; these are stateless API requests with no session, so the locale
     * is resolved here: an explicit `?lang=` first (the mobile client always
     * sends it), then `Accept-Language`, then the app default. A refusal a
     * patient cannot read is a refusal that teaches them nothing.
     */
    protected function resolveLocale(Request $request): string
    {
        $supported = ['en', 'fr'];

        $requested = $request->query('lang');
        if (is_string($requested) && in_array($requested, $supported, true)) {
            return $requested;
        }

        $header = (string) $request->header('Accept-Language', '');
        $primary = strtolower(substr(trim(explode(',', $header)[0] ?? ''), 0, 2));
        if (in_array($primary, $supported, true)) {
            return $primary;
        }

        return in_array(App::getLocale(), $supported, true) ? App::getLocale() : 'en';
    }

    /** Translate in the request's locale. */
    protected function say(string $key, array $replace = [], ?string $locale = null): string
    {
        return (string) __($key, $replace, $locale);
    }

    /**
     * A refusal that says what was rejected and why.
     *
     * `errors` keeps the Laravel-shaped map every client already understands.
     * `rejected` is the part that matters here: for each refused field, the
     * value the patient actually submitted, the bounds it fell outside, and a
     * sentence in their language. Nothing is clamped, nothing is rounded into
     * range, and nothing is stored — so the patient learns that their cuff read
     * 900, rather than finding a 300 in their record that they never measured.
     *
     * @param  array<int, array<string, mixed>>  $rejected
     */
    protected function refuse(string $message, string $errorCode, array $rejected, int $status = 422): JsonResponse
    {
        $errors = [];
        foreach ($rejected as $item) {
            $field = (string) ($item['field'] ?? 'value');
            $errors[$field][] = (string) ($item['reason'] ?? $message);
        }

        return response()->json([
            'message'    => $message,
            'error_code' => $errorCode,
            'errors'     => $errors,
            'rejected'   => array_values($rejected),
            // Said explicitly so no client is tempted to "fix" the value and
            // resubmit it on the patient's behalf.
            'stored'     => false,
        ], $status);
    }

    /**
     * Record the write.
     *
     * Every patient write lands in `audit_events` with the actor, the patient,
     * the resource, the source system and the resulting state. Auditing is not
     * best-effort in the sense of "skip it if inconvenient" — but a failure to
     * audit must not roll back a clinical record the patient successfully
     * filed, so a failure is logged loudly rather than thrown at the patient.
     *
     * @param  array<string, mixed>|null  $afterState
     */
    protected function audit(
        Request $request,
        string $actionType,
        string $resourceType,
        ?string $resourceId,
        ?array $afterState = null,
        ?string $reason = null,
    ): void {
        try {
            AuditEvent::create([
                'actor_id'      => $this->resolvePatientUserId($request),
                'actor_role'    => 'patient',
                'patient_id'    => $this->resolvePatientId($request),
                'action_type'   => $actionType,
                'resource_type' => $resourceType,
                'resource_id'   => $resourceId,
                'source_system' => 'patient_mobile_app',
                'ip_address'    => $request->ip(),
                'reason'        => $reason,
                'after_state'   => $afterState,
                'created_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('patient write audit failed', [
                'action_type'   => $actionType,
                'resource_type' => $resourceType,
                'resource_id'   => $resourceId,
                'exception'     => $e->getMessage(),
            ]);
        }
    }
}
