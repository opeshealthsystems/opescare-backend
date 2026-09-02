<?php
namespace Tests\Feature\Pharmacy;

use App\Models\DrugFormulary;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrugFormularyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_drug_to_facility_formulary(): void
    {
        $facility = Facility::factory()->create();
        $creator  = User::factory()->create();

        $entry = DrugFormulary::create([
            'facility_id'      => $facility->id,
            'created_by'       => $creator->id,
            'generic_name'     => 'Metformin hydrochloride',
            'drug_code'        => 'MET-500',
            'drug_class'       => 'Biguanide antidiabetic',
            'form'             => 'tablet',
            'strength'         => '500mg',
            'unit'             => 'tablet',
            'is_available'     => true,
            'requires_prior_auth' => false,
        ]);

        $this->assertTrue($entry->is_available);
        $this->assertEquals('tablet', $entry->form);
    }

    public function test_can_filter_available_drugs_for_facility(): void
    {
        $facility = Facility::factory()->create();
        $creator  = User::factory()->create();

        DrugFormulary::create(['facility_id' => $facility->id, 'created_by' => $creator->id, 'generic_name' => 'Metformin', 'drug_code' => 'MET-500', 'drug_class' => 'Antidiabetic', 'form' => 'tablet', 'strength' => '500mg', 'unit' => 'tablet', 'is_available' => true, 'requires_prior_auth' => false]);
        DrugFormulary::create(['facility_id' => $facility->id, 'created_by' => $creator->id, 'generic_name' => 'Insulin glargine', 'drug_code' => 'INS-100', 'drug_class' => 'Insulin analogue', 'form' => 'injection', 'strength' => '100IU/mL', 'unit' => 'vial', 'is_available' => false, 'requires_prior_auth' => true]);

        $available = DrugFormulary::where('facility_id', $facility->id)
            ->where('is_available', true)->get();

        $this->assertCount(1, $available);
        $this->assertEquals('Metformin', $available->first()->generic_name);
    }

    public function test_formulary_entry_can_require_prior_auth(): void
    {
        $facility = Facility::factory()->create();
        $creator  = User::factory()->create();

        $entry = DrugFormulary::create([
            'facility_id'         => $facility->id,
            'created_by'          => $creator->id,
            'generic_name'        => 'Epoetin alfa',
            'drug_code'           => 'EPO-4000',
            'drug_class'          => 'Haematopoietic agent',
            'form'                => 'injection',
            'strength'            => '4000IU/mL',
            'unit'                => 'vial',
            'is_available'        => true,
            'requires_prior_auth' => true,
        ]);

        $this->assertTrue($entry->requires_prior_auth);
    }
}
