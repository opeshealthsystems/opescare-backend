<?php

namespace App\Http\Controllers\Api\V1\Connect\Concerns;

use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Shared plumbing for the Connect partner patient/consent endpoints:
 * the error envelope, the token-bound facility, and a PHI-free audit line.
 */
trait ConnectResponses
{
    protected function error(string $code, string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'status'     => $status === 404 ? 'not_found' : 'error',
            'error_code' => $code,
            'message'    => $message,
        ], $extra), $status);
    }

    /**
     * The facility the TOKEN is bound to — never a body, header or query value
     * (Finder invariant). Null for a facility-less (sandbox) app.
     */
    protected function tokenFacility(Request $request): ?string
    {
        $facility = $request->attributes->get('facility_id');

        return is_string($facility) && Str::isUuid($facility) ? $facility : null;
    }

    protected function facilityRequired(): JsonResponse
    {
        return $this->error('FACILITY_REQUIRED', __('connect_api.facility_required'), 403);
    }

    /**
     * audit_events row. Only identifiers, purpose, outcome — never names,
     * dates of birth or phone numbers. A malformed X-Consent-Grant-Id header
     * is dropped first (AuditLogger copies it into a uuid column). An audit
     * failure never fails the partner call; it is logged without payload.
     */
    protected function audit(Request $request, string $action, ?Patient $patient, array $context = []): void
    {
        $grantHeader = $request->header('X-Consent-Grant-Id');
        if ($grantHeader !== null && ! Str::isUuid((string) $grantHeader)) {
            $request->headers->remove('X-Consent-Grant-Id');
        }

        try {
            // Savepoint: a failed audit insert must not poison an enclosing PostgreSQL transaction.
            \Illuminate\Support\Facades\DB::transaction(fn () => AuditLogger::log(
                $request,
                $action,
                'patient',
                $patient?->getKey(),
                $patient?->getKey(),
                false,
                isset($context['purpose']) ? mb_substr((string) $context['purpose'], 0, 100) : null,
                [],
                $context + ['environment' => (string) $request->attributes->get('bearer_environment')],
            ));
        } catch (\Throwable $e) {
            Log::error('connect_audit_write_failed', ['action' => $action, 'exception' => class_basename($e)]);
        }
    }
}
