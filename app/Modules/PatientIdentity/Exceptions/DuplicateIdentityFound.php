<?php

namespace App\Modules\PatientIdentity\Exceptions;

use App\Models\Patient;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Thrown by PatientIdentityService::createPatientCandidate() when the person
 * described already holds a Health ID. Carries the matches so the caller can
 * send the patient to recovery, or a clinician to the existing record,
 * instead of minting a second identity.
 */
final class DuplicateIdentityFound extends RuntimeException
{
    /** @param Collection<int, Patient> $matches */
    public function __construct(public readonly Collection $matches)
    {
        parent::__construct('PATIENT_IDENTITY_DUPLICATE: ' . $matches->pluck('health_id')->implode(', '));
    }
}
