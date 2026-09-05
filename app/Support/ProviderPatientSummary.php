<?php

namespace App\Support;

use App\Models\Patient;

/**
 * The one shape a clinician's app receives when it identifies a patient —
 * by exact Health ID lookup or by scanning their Health ID QR. Kept in one
 * place so the two paths can never drift apart.
 */
final class ProviderPatientSummary
{
    /** @return array<string, mixed> */
    public static function from(Patient $patient): array
    {
        return [
            'id'            => $patient->id,
            'health_id'     => $patient->health_id,
            'first_name'    => $patient->first_name,
            'last_name'     => $patient->last_name,
            'full_name'     => trim($patient->first_name . ' ' . $patient->last_name),
            'date_of_birth' => $patient->date_of_birth?->toDateString(),
            'sex'           => $patient->sex,
            'blood_group'   => $patient->blood_group,
        ];
    }
}
