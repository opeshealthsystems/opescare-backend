<?php

namespace App\Http\Controllers\Api\Mobile\Lab;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Lab\LabDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * What the two lab desk controllers share: the token-derived actor, the
 * caller's language, and the access log.
 *
 * SCOPE COMES FROM THE TOKEN. `provider_facility_id` and `provider_user_id`
 * are stamped on the request attributes by AuthenticateMobileProvider. They
 * are never read from the body, the query string, a header or the session,
 * and no route here accepts a parameter that would let a caller name either.
 */
abstract class LabDeskBaseController extends Controller
{
    public function __construct(protected LabDeskService $desk)
    {
    }

    /**
     * Render this response in the caller's language. SetLocale is registered
     * on the `web` group only, so an API request would otherwise answer in
     * English however the phone is configured. Same approach as the results
     * inbox and the doctor chart.
     */
    protected function applyLocale(Request $request): void
    {
        $requested = $request->query('lang');

        if (! is_string($requested) || ! in_array($requested, ['en', 'fr'], true)) {
            $requested = str_contains(strtolower((string) $request->header('Accept-Language', '')), 'fr')
                ? 'fr'
                : null;
        }

        if ($requested !== null) {
            App::setLocale($requested);
        }
    }

    /**
     * The facility this session works for. The login refuses to mint a token
     * without one, so a null here is a session that should not exist.
     */
    protected function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return $id ? (string) $id : null;
    }

    protected function userId(Request $request): string
    {
        return (string) $request->attributes->get('provider_user_id');
    }

    /**
     * A provider token that names no facility. One refusal, one shape, on
     * every desk (front desk, lab, pharmacy, clinician): 422
     * `{error_code: facility_required, message}`.
     */
    protected function noFacility(): JsonResponse
    {
        return response()->json([
            'error_code' => 'facility_required',
            'message'    => __('lab_api.facility_required'),
        ], 422);
    }

    protected function notFound(string $key, string $errorCode): JsonResponse
    {
        return response()->json([
            'message'    => __($key),
            'error_code' => $errorCode,
        ], 404);
    }

    /**
     * ONE row for a worklist read. A list is not a chart: the bench sees a
     * name, a test and a status per line, and a pull-to-refresh that wrote
     * up to five hundred patient-scoped rows buried the trail it was meant to
     * keep. So a list read is logged once, at list level — purpose
     * `lab_worklist`, no patient, the facility on the row, the resource type
     * saying which list — and every single-patient read or write below keeps
     * its patient-scoped row through audit().
     *
     * Not persisted: the row count. `access_logs` has no details column; the
     * count is logged at debug so a silent host can still be diagnosed.
     */
    protected function auditList(Request $request, string $resourceType, int $count): void
    {
        $facilityId = $this->facilityId($request);

        try {
            AccessLogService::log(
                patientId: null,
                actorId: $this->userId($request),
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'lab_worklist',
                dataCategory: 'diagnostic_results',
                resourceType: $resourceType,
                resourceId: null,
                accessType: 'view',
            );

            Log::debug('lab desk: worklist read', [
                'facility_id'   => $facilityId,
                'resource_type' => $resourceType,
                'count'         => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('lab desk: worklist access log failed', [
                'facility_id'   => $facilityId,
                'resource_type' => $resourceType,
                'error'         => $e->getMessage(),
            ]);
        }
    }

    /**
     * Every patient whose record this request touched is logged once, the
     * same way the results inbox logs its reads. Best-effort: the access log
     * failing must not hide the result from the bench.
     *
     * @param  list<string|null>  $patientIds
     */
    protected function audit(
        Request $request,
        array $patientIds,
        string $purpose,
        string $resourceType,
        ?string $resourceId,
        string $accessType
    ): void {
        $actorId = $this->userId($request);

        foreach (array_unique(array_filter($patientIds)) as $patientId) {
            try {
                AccessLogService::log(
                    patientId: (string) $patientId,
                    actorId: $actorId,
                    actorType: 'provider',
                    organizationId: null,
                    facilityId: $this->facilityId($request),
                    purpose: $purpose,
                    dataCategory: 'diagnostic_results',
                    resourceType: $resourceType,
                    resourceId: $resourceId,
                    accessType: $accessType,
                );
            } catch (\Throwable $e) {
                Log::error('lab desk: access log failed', [
                    'patient_id' => $patientId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
