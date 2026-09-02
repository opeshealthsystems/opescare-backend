<?php

namespace App\Modules\Governance\Services;

use App\Models\EmergencyAccessEvent;
use App\Models\EmergencyReviewCase;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\User;
use App\Models\AllergyRecord;
use App\Models\Diagnosis;
use App\Models\SecurityIncident;
use Carbon\Carbon;

class EmergencyAccessService
{
    public function requestEmergencyAccess(
        string $patientId,
        string $facilityId,
        string $actorId,
        string $reason
    ): EmergencyAccessEvent {
        // Verify facility status
        $facility = Facility::findOrFail($facilityId);

        $event = new EmergencyAccessEvent();
        $event->patient_id = $patientId;
        $event->facility_id = $facilityId;
        $event->provider_id = $actorId;
        $event->reason = $reason;
        $event->save();

        // Spawn a companion review case
        $reviewCase = new EmergencyReviewCase();
        $reviewCase->emergency_access_event_id = $event->id;
        $reviewCase->status = 'pending';
        $reviewCase->save();

        // Audit the emergency override via append-only AccessLog
        AccessLogService::log(
            $patientId,
            $actorId,
            'User',
            null,
            $facilityId,
            'emergency',
            'clinical_summary',
            'Patient',
            $patientId,
            'override',
            true
        );

        return $event;
    }

    public function buildEmergencyProfile(string $patientId): array
    {
        $patient = Patient::findOrFail($patientId);

        // Fetch active allergies
        $allergies = AllergyRecord::where('patient_id', $patientId)
            ->where('status', 'active')
            ->get(['substance', 'severity', 'status'])
            ->toArray();

        // Fetch chronic or active diagnoses
        $diagnoses = Diagnosis::where('patient_id', $patientId)
            ->where('status', 'active')
            ->get(['code', 'code_system', 'display_name'])
            ->toArray();

        // Strip clinical notes, prescriptions, and billing to strictly follow minimization
        return [
            'identity' => [
                'health_id' => $patient->health_id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'sex' => $patient->sex,
                'date_of_birth' => $patient->date_of_birth,
            ],
            'emergency_contact' => $patient->emergency_contact ?? null,
            'allergies' => $allergies,
            'chronic_conditions' => $diagnoses,
        ];
    }

    public function reviewEmergencyAccess(
        string $eventId,
        string $reviewerId,
        string $reviewStatus,
        ?string $comment = null
    ): EmergencyReviewCase {
        // Find review case linked to this event
        $reviewCase = EmergencyReviewCase::where('emergency_access_event_id', $eventId)->firstOrFail();
        $reviewCase->status = $reviewStatus;
        $reviewCase->reviewed_by = $reviewerId;
        $reviewCase->reviewed_at = Carbon::now();
        $reviewCase->reviewer_notes = $comment;
        $reviewCase->save();

        // Log audit event for review action
        AccessLogService::log(
            null,
            $reviewerId,
            'User',
            null,
            null,
            'system_security',
            'audit_log',
            'EmergencyReviewCase',
            $reviewCase->id,
            'review'
        );

        // If abuse is suspected or confirmed, create a high-severity security incident case
        if (in_array($reviewStatus, ['suspected_abuse', 'confirmed_abuse'])) {
            $incident = new SecurityIncident();
            $incident->incident_type = 'data_export_abuse';
            $incident->severity = 'high';
            $incident->status = 'new';
            $incident->summary = "Suspected override abuse detected on EmergencyReviewCase: {$reviewCase->id}. Reviewer Comment: {$comment}";
            $incident->detected_at = Carbon::now();
            $incident->created_by = $reviewerId;
            $incident->save();
        }

        return $reviewCase;
    }
}
