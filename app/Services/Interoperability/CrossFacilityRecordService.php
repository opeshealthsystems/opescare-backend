<?php
namespace App\Services\Interoperability;

use App\Models\CrossFacilityRecordRequest;

class CrossFacilityRecordService
{
    public function requestRecords(
        string $patientId,
        string $requestingFacility,
        string $sourceFacility,
        string $requestedBy,
        string $purpose,
        array  $recordTypes,
        bool   $requireConsent = false,
        bool   $hasConsent     = true,
    ): CrossFacilityRecordRequest {
        if ($requireConsent && !$hasConsent) {
            throw new \Exception('PATIENT_CONSENT_REQUIRED');
        }

        return CrossFacilityRecordRequest::create([
            'patient_id'             => $patientId,
            'requesting_facility_id' => $requestingFacility,
            'source_facility_id'     => $sourceFacility,
            'requested_by'           => $requestedBy,
            'purpose'                => $purpose,
            'record_types'           => $recordTypes,
            'status'                 => 'pending',
            'consent_obtained'       => $hasConsent,
            'expires_at'             => now()->addDays(30),
        ]);
    }

    public function approveRequest(string $requestId, string $approverId): CrossFacilityRecordRequest
    {
        $request = CrossFacilityRecordRequest::findOrFail($requestId);
        $request->update([
            'status'      => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
        return $request;
    }

    public function rejectRequest(string $requestId, string $reason): CrossFacilityRecordRequest
    {
        $request = CrossFacilityRecordRequest::findOrFail($requestId);
        $request->update(['status' => 'rejected', 'rejection_reason' => $reason]);
        return $request;
    }
}
