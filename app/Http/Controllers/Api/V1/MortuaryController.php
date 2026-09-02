<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AutopsyReportResource;
use App\Http\Resources\MortuaryRecordResource;
use App\Models\AutopsyReport;
use App\Models\MortuaryRecord;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MortuaryController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $documentIssuanceService)
    {
    }

    /**
     * POST /api/v1/mortuary/admit
     * Admit a body to the mortuary.
     */
    public function admit(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'full_name'           => 'required|string|max:200',
            'sex'                 => 'nullable|in:male,female',
            'approximate_age'     => 'nullable|integer|min:0|max:150',
            'cause_of_death'      => 'nullable|string',
            'death_date'          => 'nullable|date',
            'admission_date'      => 'required|date',
            'patient_id'          => 'nullable|uuid',
            'storage_location'    => 'nullable|string|max:100',
            'next_of_kin_name'    => 'nullable|string|max:200',
            'next_of_kin_contact' => 'nullable|string|max:100',
            'admitted_by'         => 'nullable|uuid',
            'notes'               => 'nullable|string',
        ]);

        $bodyNumber = 'MTY-' . strtoupper(substr(uniqid(), -6));

        $record = MortuaryRecord::create($validated + [
            'facility_id' => $facilityId,
            'body_number' => $bodyNumber,
            'status'      => 'admitted',
        ]);

        try {
            $this->documentIssuanceService->issueFromModel(
                'BRF',
                'Mortuary Admission Form — ' . $bodyNumber,
                [
                    'mortuary_record_id'  => $record->id,
                    'body_number'         => $bodyNumber,
                    'full_name'           => $record->full_name,
                    'sex'                 => $record->sex,
                    'approximate_age'     => $record->approximate_age,
                    'admission_date'      => $record->admission_date->toDateString(),
                    'cause_of_death'      => $record->cause_of_death,
                    'death_date'          => $record->death_date?->toDateString(),
                    'next_of_kin_name'    => $record->next_of_kin_name,
                    'next_of_kin_contact' => $record->next_of_kin_contact,
                ],
                $facilityId,
                $validated['patient_id'] ?? null,
                null,
                $validated['admitted_by'] ?? null,
            );
        } catch (\Throwable) {}

        try {
            $this->documentIssuanceService->issueFromModel(
                'MSL',
                'Mortuary Storage Log — ' . $bodyNumber,
                [
                    'mortuary_record_id' => $record->id,
                    'body_number'        => $bodyNumber,
                    'full_name'          => $record->full_name,
                    'admission_date'     => $record->admission_date->toDateString(),
                    'storage_location'   => $record->storage_location,
                    'status'             => $record->status,
                ],
                $facilityId,
                $validated['patient_id'] ?? null,
                null,
                $validated['admitted_by'] ?? null,
            );
        } catch (\Throwable) {}

        return response()->json(['data' => MortuaryRecordResource::make($record)], 201);
    }

    /**
     * POST /api/v1/mortuary/{record}/autopsy
     * Create an autopsy report for a mortuary record.
     */
    public function createAutopsy(Request $request, MortuaryRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'type'                     => 'required|in:clinical,forensic',
            'pathologist_id'           => 'required|uuid',
            'performed_at'             => 'required|date',
            'gross_findings'           => 'nullable|string',
            'microscopic_findings'     => 'nullable|string',
            'toxicology_results'       => 'nullable|string',
            'cause_of_death_confirmed' => 'required|string',
            'manner_of_death'          => 'nullable|string',
            'external_findings'        => 'nullable|string',
            'notes'                    => 'nullable|string',
        ]);

        $autopsy = AutopsyReport::create($validated + [
            'mortuary_record_id' => $record->id,
            'facility_id'        => $facilityId,
            'status'             => 'draft',
        ]);

        $docType  = $validated['type'] === 'forensic' ? 'FAR' : 'CAR';
        $docTitle = ($validated['type'] === 'forensic' ? 'Forensic' : 'Clinical') . ' Autopsy Report';

        $this->documentIssuanceService->issueFromModel(
            $docType,
            $docTitle,
            [
                'mortuary_record_id'       => $record->id,
                'body_number'              => $record->body_number,
                'full_name'                => $record->full_name,
                'type'                     => $validated['type'],
                'pathologist_id'           => $validated['pathologist_id'],
                'performed_at'             => $autopsy->performed_at->toISOString(),
                'cause_of_death_confirmed' => $validated['cause_of_death_confirmed'],
                'manner_of_death'          => $validated['manner_of_death'] ?? null,
            ],
            $facilityId,
            $record->patient_id,
            null,
            $validated['pathologist_id'],
        );

        return response()->json(['data' => AutopsyReportResource::make($autopsy)], 201);
    }

    /**
     * POST /api/v1/mortuary/{record}/burial-permit
     * Release a body and issue a burial permit.
     */
    public function releaseBurialPermit(Request $request, MortuaryRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'released_to' => 'required|string|max:200',
            'released_by' => 'required|uuid',
        ]);

        $record->update([
            'status'      => 'released',
            'released_at' => now(),
            'released_to' => $validated['released_to'],
            'released_by' => $validated['released_by'],
        ]);

        $this->documentIssuanceService->issueFromModel(
            'BPN',
            'Burial Permit',
            [
                'body_number'  => $record->body_number,
                'full_name'    => $record->full_name,
                'released_to'  => $validated['released_to'],
                'released_at'  => now()->toDateString(),
            ],
            $facilityId,
            $record->patient_id,
            null,
            $validated['released_by'],
        );

        try {
            $this->documentIssuanceService->issueFromModel(
                'BRL',
                'Body Release Form',
                [
                    'body_number'  => $record->body_number,
                    'full_name'    => $record->full_name,
                    'released_to'  => $validated['released_to'],
                    'released_by'  => $validated['released_by'],
                    'released_at'  => now()->toISOString(),
                ],
                $facilityId,
                $record->patient_id,
                null,
                $validated['released_by'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => MortuaryRecordResource::make($record)]);
    }

    /**
     * POST /api/v1/mortuary/{record}/autopsy-consent
     * Record that autopsy consent has been obtained.
     */
    public function recordAutopsyConsent(Request $request, MortuaryRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'consenting_party'       => 'required|string|max:200',
            'relationship_to_deceased' => 'required|string|max:100',
            'consent_date'           => 'required|date',
            'witnessed_by'           => 'nullable|uuid',
            'consented_by_staff'     => 'required|uuid',
            'notes'                  => 'nullable|string',
        ]);

        $record->update(['autopsy_consent' => true, 'autopsy_consent_at' => now()]);

        try {
            $this->documentIssuanceService->issueFromModel(
                'PMC',
                'Autopsy Consent Form',
                [
                    'mortuary_record_id'       => $record->id,
                    'body_number'              => $record->body_number,
                    'full_name'                => $record->full_name,
                    'consenting_party'         => $validated['consenting_party'],
                    'relationship_to_deceased' => $validated['relationship_to_deceased'],
                    'consent_date'             => $validated['consent_date'],
                    'consented_by_staff'       => $validated['consented_by_staff'],
                ],
                $facilityId,
                $record->patient_id,
                null,
                $validated['consented_by_staff'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => MortuaryRecordResource::make($record)]);
    }

    /**
     * POST /api/v1/mortuary/{record}/embalm
     * Record an embalming procedure.
     */
    public function recordEmbalming(Request $request, MortuaryRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'performed_by'      => 'required|uuid',
            'performed_at'      => 'required|date',
            'chemicals_used'    => 'nullable|string|max:500',
            'technique'         => 'nullable|string|max:255',
            'duration_minutes'  => 'nullable|integer|min:1',
            'notes'             => 'nullable|string',
        ]);

        $record->update(['status' => 'embalmed', 'embalmed_at' => now()]);

        try {
            $this->documentIssuanceService->issueFromModel(
                'EMB',
                'Embalming Record',
                [
                    'mortuary_record_id' => $record->id,
                    'body_number'        => $record->body_number,
                    'full_name'          => $record->full_name,
                    'performed_by'       => $validated['performed_by'],
                    'performed_at'       => $validated['performed_at'],
                    'chemicals_used'     => $validated['chemicals_used'] ?? null,
                    'technique'          => $validated['technique'] ?? null,
                ],
                $facilityId,
                $record->patient_id,
                null,
                $validated['performed_by'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => MortuaryRecordResource::make($record)]);
    }

    /**
     * POST /api/v1/mortuary/{record}/identify
     * Mark a body as identified and issue a body identification certificate.
     */
    public function identifyBody(Request $request, MortuaryRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'identified_by' => 'required|uuid',
        ]);

        $record->update([
            'status'        => 'identified',
            'identified_by' => $validated['identified_by'],
            'identified_at' => now(),
        ]);

        $this->documentIssuanceService->issueFromModel(
            'BIR',
            'Body Identification Certificate',
            [
                'body_number'   => $record->body_number,
                'full_name'     => $record->full_name,
                'identified_by' => $validated['identified_by'],
                'identified_at' => now()->toISOString(),
            ],
            $facilityId ?? $record->facility_id,
            $record->patient_id,
            null,
            $validated['identified_by'],
        );

        return response()->json(['data' => MortuaryRecordResource::make($record)]);
    }

    /**
     * GET /api/v1/mortuary
     * List all mortuary records for the facility.
     */
    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $records = MortuaryRecord::where('facility_id', $facilityId)
            ->orderByDesc('admission_date')
            ->get();

        return response()->json(['data' => MortuaryRecordResource::collection($records)]);
    }

    /**
     * GET /api/v1/mortuary/{record}
     * Show a single mortuary record with relations.
     */
    public function show(Request $request, MortuaryRecord $record): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $record->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => $record->load(['autopsyReports', 'admittedBy', 'facility'])]);
    }
}
