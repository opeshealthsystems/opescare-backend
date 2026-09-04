<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Patient\MedicalRecordExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicalRecordExportController extends Controller
{
    public function __construct(private readonly MedicalRecordExportService $exportService)
    {
    }

    /**
     * Record the export in the patient's own access log.
     *
     * These two endpoints emit the entire longitudinal record in one response —
     * the highest-PHI egress in the mobile API — so an export has to be visible
     * to the patient afterwards in /mobile/access-logs alongside every clinician
     * read. Without this the largest disclosure of all was the only one leaving
     * no trace.
     *
     * Best-effort by design: access_logs.actor_id is NOT NULL with a FK to
     * users, so a patient with no linked user account cannot be logged, and an
     * audit failure must never turn a successful export into a 500. Both cases
     * degrade to a warning in the application log.
     */
    private function auditExport(Request $request, string $patientId, string $format): void
    {
        $actorId = $request->attributes->get('patient_user_id');

        if (! $actorId) {
            Log::warning('Medical record export not access-logged: no linked user account.', [
                'patient_id' => $patientId,
                'format'     => $format,
            ]);

            return;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $actorId,
                actorType: 'patient',
                organizationId: null,
                facilityId: null,
                purpose: 'patient_self_export',
                dataCategory: 'full_record',
                resourceType: 'MedicalRecordExport',
                resourceId: null,
                accessType: 'export',
            );
        } catch (\Throwable $e) {
            Log::warning('Medical record export access log failed.', [
                'patient_id' => $patientId,
                'format'     => $format,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /api/mobile/medical-records/export/pdf
     *
     * Returns the generated PDF inline as base64 (`file_base64`) rather than
     * the server-side storage path — the mobile client has no way to reach
     * the `local` disk directly, and returning an absolute filesystem path
     * to a client would leak server layout for no benefit. The file is also
     * left on disk (see MedicalRecordExportService::cleanupExports) purely
     * for audit/debugging; it is not re-served from there.
     */
    public function exportPdf(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        if (! $patientId) {
            return response()->json(['message' => __('api.no_patient_record')], 404);
        }

        $validated = $request->validate([
            'include_vitals'        => 'nullable|boolean',
            'include_diagnoses'     => 'nullable|boolean',
            'include_medications'   => 'nullable|boolean',
            'include_labs'          => 'nullable|boolean',
            'include_immunizations' => 'nullable|boolean',
        ]);

        $path = $this->exportService->generatePdf($patientId, $validated);

        $this->auditExport($request, $patientId, 'pdf');

        return response()->json([
            'message'     => __('api.pdf_generated'),
            'filename'    => basename($path),
            'mime_type'   => 'application/pdf',
            'file_base64' => base64_encode(file_get_contents($path)),
        ]);
    }

    /**
     * POST /api/mobile/medical-records/export/fhir
     * Returns a FHIR R4 Bundle JSON.
     */
    public function exportFhir(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        if (! $patientId) {
            return response()->json(['message' => __('api.no_patient_record')], 404);
        }

        $bundle = $this->exportService->generateFhirBundle($patientId);

        $this->auditExport($request, $patientId, 'fhir');

        return response()->json($bundle);
    }
}
