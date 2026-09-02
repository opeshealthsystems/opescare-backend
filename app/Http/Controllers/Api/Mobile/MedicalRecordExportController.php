<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Patient\MedicalRecordExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalRecordExportController extends Controller
{
    public function __construct(private readonly MedicalRecordExportService $exportService)
    {
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

        return response()->json($bundle);
    }
}
