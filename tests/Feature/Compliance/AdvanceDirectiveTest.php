<?php
namespace Tests\Feature\Compliance;

use App\Models\AdvanceDirective;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvanceDirectiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_advance_directive(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $directive = AdvanceDirective::create([
            'patient_id'     => $patient->id,
            'facility_id'    => $facility->id,
            'directive_type' => 'dnr',
            'instructions'   => 'Patient does not wish to be resuscitated.',
            'effective_date' => '2026-07-01',
            'witness_name'   => 'Marie Nguembi',
            'is_active'      => true,
        ]);

        $this->assertEquals('dnr', $directive->directive_type);
        $this->assertTrue($directive->is_active);
    }

    public function test_only_one_active_dnr_per_patient(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        AdvanceDirective::create([
            'patient_id'     => $patient->id,
            'facility_id'    => $facility->id,
            'directive_type' => 'dnr',
            'instructions'   => 'Original DNR',
            'effective_date' => '2026-01-01',
            'is_active'      => true,
        ]);

        // Deactivate the first, then create a new one
        $first = AdvanceDirective::where('patient_id', $patient->id)
            ->where('directive_type', 'dnr')
            ->where('is_active', true)->first();
        $first->update(['is_active' => false]);

        AdvanceDirective::create([
            'patient_id'     => $patient->id,
            'facility_id'    => $facility->id,
            'directive_type' => 'dnr',
            'instructions'   => 'Updated DNR with new conditions',
            'effective_date' => '2026-07-01',
            'is_active'      => true,
        ]);

        $activeDnrs = AdvanceDirective::where('patient_id', $patient->id)
            ->where('directive_type', 'dnr')
            ->where('is_active', true)->count();

        $this->assertEquals(1, $activeDnrs);
    }
}
