<?php

namespace App\Services\Clinical;

use App\Models\AdvanceDirective;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdvanceDirectiveService
{
    public function register(array $data): AdvanceDirective
    {
        return AdvanceDirective::create(array_merge(['is_active' => true], $data));
    }

    public function revoke(string $directiveId, string $revokedBy): AdvanceDirective
    {
        $directive = AdvanceDirective::findOrFail($directiveId);

        $directive->update([
            'is_active'    => false,
            'verified_by'  => $revokedBy,
            'verified_at'  => Carbon::now(),
            'instructions' => ($directive->instructions ?? '') . "\n[Revoked by {$revokedBy} at " . Carbon::now()->toDateTimeString() . ']',
        ]);

        return $directive->fresh();
    }

    public function getActiveForPatient(string $patientId): Collection
    {
        return AdvanceDirective::where('patient_id', $patientId)
            ->where('is_active', true)
            ->orderBy('directive_type')
            ->get();
    }

    public function hasActiveDnr(string $patientId): bool
    {
        return AdvanceDirective::where('patient_id', $patientId)
            ->where('directive_type', 'dnr')
            ->where('is_active', true)
            ->exists();
    }

    public function getHealthcareProxy(string $patientId): ?AdvanceDirective
    {
        return AdvanceDirective::where('patient_id', $patientId)
            ->where('directive_type', 'healthcare_proxy')
            ->where('is_active', true)
            ->latest()
            ->first();
    }
}
