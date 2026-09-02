<?php

namespace App\Modules\PatientIdentity\Services;

use App\Models\Patient;
use App\Models\PatientIdentifier;
use App\Models\AuditEvent;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientIdentityService
{
    protected MasterPatientIndexService $mpiService;

    public function __construct(MasterPatientIndexService $mpiService)
    {
        $this->mpiService = $mpiService;
    }

    public function createPatientCandidate(array $data, ?string $actorId = null, ?string $facilityId = null): Patient
    {
        $candidates = $this->mpiService->searchCandidates($data);

        if ($candidates->count() > 0) {
            throw new Exception("Duplicate candidate found. Please review existing patients before creating.");
        }

        DB::beginTransaction();

        try {
            $healthId = $this->generateHealthId();

            $patient = Patient::create([
                'health_id' => $healthId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'is_dob_estimated' => $data['is_dob_estimated'] ?? false,
                'sex' => $data['sex'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'address' => $data['address'] ?? null,
                'identity_status' => $data['identity_status'] ?? 'provisional',
            ]);

            if (!empty($data['identifiers'])) {
                foreach ($data['identifiers'] as $identifier) {
                    PatientIdentifier::create([
                        'patient_id' => $patient->id,
                        'identifier_type' => $identifier['type'],
                        'identifier_value' => $identifier['value'],
                        'issuer' => $identifier['issuer'] ?? null,
                    ]);
                }
            }

            AuditEvent::create([
                'actor_id' => $actorId,
                'facility_id' => $facilityId,
                'patient_id' => $patient->id,
                'action_type' => 'create',
                'resource_type' => 'patient',
                'resource_id' => $patient->id,
                'reason' => 'Initial patient creation',
            ]);

            DB::commit();

            return $patient;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function generateHealthId(): string
    {
        // Delegate to the canonical generator so every patient (including
        // imports) gets the real Cameroon format CM-HID-XXXX-XXXX-XXXX with a
        // checksum block and DB-backed uniqueness — not the legacy OC-MVP stub.
        return app(\App\Services\Identity\HealthIdGeneratorService::class)->generate();
    }
}
