<?php

namespace Tests\Feature\Security;

use App\Models\Patient;
use Tests\TestCase;

class PatientMassAssignmentTest extends TestCase
{
    public function test_is_demo_is_not_in_patient_fillable(): void
    {
        $fillable = (new Patient)->getFillable();
        $this->assertNotContains('is_demo', $fillable,
            'is_demo must not be in Patient.$fillable — it must only be set via forceFill or migrations/seeders');
    }

    public function test_patient_mass_assignment_cannot_set_is_demo(): void
    {
        // Attempting to mass-assign is_demo should be silently ignored (not throw)
        $patient = new Patient();
        $patient->fill(['is_demo' => true, 'health_id' => 'OC-TEST-001']);

        // is_demo should not be set via fill
        $this->assertFalse((bool) $patient->is_demo,
            'Mass assignment of is_demo must be silently ignored by the Patient model');
    }
}
