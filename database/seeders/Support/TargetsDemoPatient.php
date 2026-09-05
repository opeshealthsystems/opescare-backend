<?php

namespace Database\Seeders\Support;

/**
 * Lets a demo story seeder tell its story for any demo patient.
 *
 *     (new DemoClinicalCoreSeeder())->forPatient($patient->id)->run();
 *
 * Without `forPatient()` the seeder behaves exactly as before: the canonical
 * patient, the reserved ids. With it, `patientId()` is the target and `uid()`
 * / `seedKey()` derive that patient's own stable ids (see DemoStoryIds), so
 * two patients never share a row and each re-run is still idempotent.
 */
trait TargetsDemoPatient
{
    protected ?string $targetPatientId = null;

    public function forPatient(string $patientId): static
    {
        $this->targetPatientId = $patientId;

        return $this;
    }

    protected function patientId(): string
    {
        return $this->targetPatientId ?? DemoStoryIds::CANONICAL_PATIENT;
    }

    protected function uid(string $reservedId): string
    {
        return DemoStoryIds::uid($this->patientId(), $reservedId);
    }

    protected function seedKey(string $key): string
    {
        return DemoStoryIds::seedKey($this->patientId(), $key);
    }
}
