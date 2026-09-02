<?php

namespace App\Modules\Governance\Services;

use App\Models\CorrectionRequest;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\AllergyRecord;
use Carbon\Carbon;

class CorrectionRequestService
{
    public function createRequest(
        string $patientId,
        string $userId,
        string $resourceType,
        string $resourceId,
        string $reason,
        ?string $supportingDocumentId = null
    ): CorrectionRequest {
        $request = new CorrectionRequest();
        $request->patient_id = $patientId;
        $request->requested_by_user_id = $userId;
        $request->resource_type = $resourceType;
        $request->resource_id = $resourceId;
        $request->reason = $reason;
        $request->supporting_document_id = $supportingDocumentId;
        $request->status = 'pending';
        $request->save();

        // Audit log correction request creation
        AccessLogService::log(
            $patientId,
            $userId,
            'User',
            null,
            null,
            'patient_request',
            'correction_filing',
            'CorrectionRequest',
            $request->id,
            'create'
        );

        return $request;
    }

    public function approveRequest(string $requestId, string $reviewerId): CorrectionRequest
    {
        $request = CorrectionRequest::findOrFail($requestId);
        $request->status = 'approved';
        $request->reviewed_by = $reviewerId;
        $request->reviewed_at = Carbon::now();
        $request->save();

        // Dynamically find and amend the target record without overwriting original data
        $modelClass = $this->resolveModelClass($request->resource_type);
        if ($modelClass) {
            $record = $modelClass::find($request->resource_id);
            if ($record) {
                // If it's a signed clinical note, we mark it as amended/entered_in_error to retain lineage
                if (isset($record->status)) {
                    $record->status = 'entered_in_error';
                    $record->save();
                }
            }
        }

        // Audit log approval
        AccessLogService::log(
            $request->patient_id,
            $reviewerId,
            'User',
            null,
            null,
            'facility_operations',
            'clinical_summary',
            'CorrectionRequest',
            $request->id,
            'approve'
        );

        return $request;
    }

    public function rejectRequest(string $requestId, string $reviewerId): CorrectionRequest
    {
        $request = CorrectionRequest::findOrFail($requestId);
        $request->status = 'rejected';
        $request->reviewed_by = $reviewerId;
        $request->reviewed_at = Carbon::now();
        $request->save();

        // Audit log rejection
        AccessLogService::log(
            $request->patient_id,
            $reviewerId,
            'User',
            null,
            null,
            'facility_operations',
            'clinical_summary',
            'CorrectionRequest',
            $request->id,
            'reject'
        );

        return $request;
    }

    private function resolveModelClass(string $type): ?string
    {
        return match ($type) {
            'clinical_note' => ClinicalNote::class,
            'diagnosis' => Diagnosis::class,
            'allergy' => AllergyRecord::class,
            default => null,
        };
    }
}
