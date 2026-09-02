<?php
namespace Tests\Feature;

use App\Models\DrugFormulary;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrugFormularyTest extends TestCase {
    use RefreshDatabase;

    private array $headers = ['X-Client-ID' => 'test_client_id', 'X-Client-Secret' => 'test_client_secret'];
    private User $user;
    private Facility $facility;

    protected function setUp(): void {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->facility = Facility::factory()->create();
    }

    private function baseEntry(): array {
        return [
            'generic_name' => 'Metformin',
            'brand_names'  => ['Glucophage','Fortamet'],
            'drug_code'    => 'MET500',
            'drug_class'   => 'Biguanides',
            'form'         => 'tablet',
            'strength'     => '500mg',
            'unit'         => 'mg',
            'created_by'   => $this->user->id,
        ];
    }

    public function test_add_drug_to_formulary(): void {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/v1/pharmacy/formulary', $this->baseEntry());
        $response->assertCreated();
        $this->assertDatabaseHas('drug_formularies', ['drug_code' => 'MET500', 'is_available' => true]);
    }

    public function test_search_by_generic_name(): void {
        DrugFormulary::factory()->create(array_merge($this->baseEntry(), ['created_by' => $this->user->id]));
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/pharmacy/formulary/search?q=Metformin');
        $response->assertOk();
        $this->assertGreaterThan(0, count($response->json('data')));
        $this->assertEquals('Metformin', $response->json('data.0.generic_name'));
    }

    public function test_search_by_brand_name(): void {
        DrugFormulary::factory()->create(array_merge($this->baseEntry(), ['created_by' => $this->user->id]));
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/pharmacy/formulary/search?q=Glucophage');
        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_toggle_availability_to_false(): void {
        $entry = DrugFormulary::factory()->create(
            array_merge($this->baseEntry(), ['is_available' => true, 'created_by' => $this->user->id])
        );
        $response = $this->withHeaders($this->headers)
            ->patchJson("/api/v1/pharmacy/formulary/{$entry->id}/availability", [
                'is_available' => false,
            ]);
        $response->assertOk();
        $response->assertJsonPath('data.is_available', false);
        $this->assertDatabaseHas('drug_formularies', ['id' => $entry->id, 'is_available' => false]);
    }

    public function test_controlled_substances_endpoint(): void {
        DrugFormulary::factory()->create(array_merge($this->baseEntry(), [
            'is_controlled' => true,
            'drug_code'     => 'OXY10',
            'generic_name'  => 'Oxycodone',
            'created_by'    => $this->user->id,
        ]));
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/v1/pharmacy/formulary/controlled');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertTrue(collect($data)->every(fn ($d) => $d['is_controlled'] === true));
    }
}
