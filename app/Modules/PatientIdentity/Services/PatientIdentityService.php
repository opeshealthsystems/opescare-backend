<?php

namespace App\Modules\PatientIdentity\Services;

use App\Models\AuditEvent;
use App\Models\Patient;
use App\Models\PatientIdentifier;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;
use App\Services\Identity\HealthIdGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PatientIdentityService — the front door for a new Health ID.
 *
 * The root brief says patients are created only through this service. In
 * practice the four creation paths in this fork guard themselves with
 * Patient::findExistingIdentity() and call Patient::create() — this service
 * had no callers, threw a bare Exception, generated the Health ID outside
 * the insert it protected, and wrote an audit row missing its NOT NULL
 * `is_demo`. It is now correct and consistent with those paths: the same
 * search (MasterPatientIndexService::searchCandidates, which itself uses
 * findExistingIdentity), a typed exception carrying the matches, and the
 * atomic generate-and-insert every other path uses.
 */
class PatientIdentityService
{
    public function __construct(protected MasterPatientIndexService $mpiService)
    {
    }

    /**
     * @param  array{first_name: string, last_name: string, middle_name?: string|null, date_of_birth?: string|null, is_dob_estimated?: bool, sex?: string|null, phone_number?: string|null, national_id_number?: string|null, address?: string|null, identity_status?: string, country_code?: string, identifiers?: list<array{type: string, value: string, issuer?: string|null}>}  $data
     * @throws DuplicateIdentityFound
     */
    public function createPatientCandidate(array $data, ?string $actorId = null, ?string $facilityId = null): Patient
    {
        $matches = $this->mpiService->searchCandidates($data);

        if ($matches->isNotEmpty()) {
            throw new DuplicateIdentityFound($matches);
        }

        $countryCode = strtoupper(substr($data['country_code'] ?? 'CM', 0, 2));

        return DB::transaction(function () use ($data, $actorId, $facilityId, $countryCode) {
            // Atomic: the Health ID is generated inside the insert's retry loop,
            // so a collision gets a fresh candidate rather than a 23505.
            $patient = app(HealthIdGeneratorService::class)->generate($countryCode, function (string $healthId) use ($data, $countryCode) {
                return Patient::create([
                    'health_id'          => $healthId,
                    'first_name'         => $data['first_name'],
                    'last_name'          => $data['last_name'],
                    'middle_name'        => $data['middle_name'] ?? null,
                    'date_of_birth'      => $data['date_of_birth'] ?? null,
                    'is_dob_estimated'   => (bool) ($data['is_dob_estimated'] ?? false),
                    'sex'                => $data['sex'] ?? null,
                    'phone_number'       => $data['phone_number'] ?? null,
                    'national_id_number' => $data['national_id_number'] ?? null,
                    'address'            => $data['address'] ?? null,
                    'country_code'       => $countryCode,
                    'identity_status'    => $data['identity_status'] ?? 'provisional',
                ]);
            });

            foreach ($data['identifiers'] ?? [] as $identifier) {
                if (empty($identifier['type']) || empty($identifier['value'])) {
                    continue;
                }
                PatientIdentifier::create([
                    'patient_id'       => $patient->getKey(),
                    'identifier_type'  => $identifier['type'],
                    'identifier_value' => $identifier['value'],
                    'issuer'           => $identifier['issuer'] ?? null,
                ]);
            }

            (new AuditEvent())->forceFill([
                'id'                 => (string) Str::uuid(),
                'actor_id'           => $actorId,
                'facility_id'        => $facilityId,
                'patient_id'         => $patient->getKey(),
                'action_type'        => 'create',
                'resource_type'      => 'patient',
                'resource_id'        => $patient->getKey(),
                'emergency_override' => false,
                'source_system'      => 'identity',
                'reason'             => 'Initial patient creation',
                'is_demo'            => (bool) config('demo.enabled', false),
                'created_at'         => now(),
            ])->save();

            return $patient;
        });
    }
}
