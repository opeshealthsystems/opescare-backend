<?php
namespace Tests\Feature\Clinical;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\ProblemList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProblemListTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_active_problem(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();

        $problem = ProblemList::create([
            'patient_id'  => $patient->id,
            'provider_id' => $provider->id,
            'icd_code'    => 'E11.9',
            'icd_version' => '10',
            'description' => 'Type 2 diabetes mellitus without complications',
            'onset_date'  => '2020-01-15',
            'status'      => 'active',
            'priority'    => 'high',
        ]);

        $this->assertEquals('E11.9', $problem->icd_code);
        $this->assertEquals('active', $problem->status);
    }

    public function test_can_resolve_problem(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();

        $problem = ProblemList::create([
            'patient_id'  => $patient->id,
            'provider_id' => $provider->id,
            'icd_code'    => 'J06.9',
            'icd_version' => '10',
            'description' => 'Acute upper respiratory infection',
            'status'      => 'active',
            'priority'    => 'low',
        ]);

        $problem->update(['status' => 'resolved', 'resolved_date' => now()->toDateString()]);
        $this->assertEquals('resolved', $problem->fresh()->status);
    }

    public function test_problem_list_scoped_to_patient(): void
    {
        $p1       = Patient::factory()->create();
        $p2       = Patient::factory()->create();
        $provider = User::factory()->create();

        ProblemList::create(['patient_id' => $p1->id, 'provider_id' => $provider->id, 'icd_code' => 'E11.9', 'icd_version' => '10', 'description' => 'Diabetes', 'status' => 'active', 'priority' => 'high']);
        ProblemList::create(['patient_id' => $p2->id, 'provider_id' => $provider->id, 'icd_code' => 'I10',   'icd_version' => '10', 'description' => 'Hypertension', 'status' => 'active', 'priority' => 'high']);

        $this->assertCount(1, ProblemList::where('patient_id', $p1->id)->get());
    }
}
